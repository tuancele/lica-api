<?php

namespace App\Modules\Config\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Config\Models\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    public function index(Request $request){
        if($request->get('group') == "r2"){
            active('config','r2');
        } else {
            active('config','index');
        }
        
        if($request->get('group') != ""){
            return view('Config::'.$request->get('group'));
        }else{
            return view('Config::index');
        }
    }
    
    public function update(Request $request){
        updateConfig($request->data);
        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => ''
        ]);
    }

    public function toolSyncR2(Request $request)
    {
        active('config', 'r2');
        return view('Config::tool_sync_r2');
    }

    public function startSyncBackground(Request $request) {
        $batch = $request->input('batch', 5);
        $skip = $request->input('skip', false);
        
        $cmd = "php artisan sync:media-r2 --batch=$batch";
        if ($skip) {
            $cmd .= " --skip";
        }
        
        // Run in background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null 2>&1 &");
        }

        return response()->json(['status' => 'started']);
    }

    public function stopSyncBackground() {
        $path = storage_path('app/sync_r2_status.json');
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            
            // Mark as stopping
            $data['status'] = 'stopping';
            File::put($path, json_encode($data));
            
            // Kill Process
            if (isset($data['pid']) && $data['pid']) {
                $pid = $data['pid'];
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec("taskkill /F /PID $pid");
                } else {
                    exec("kill -9 $pid");
                }
            }
            
            // Finalize status
            $data['status'] = 'error';
            $data['message'] = 'Đã dừng bởi người dùng.';
            File::put($path, json_encode($data));
            
            return response()->json(['status' => 'stopped']);
        }
        return response()->json(['status' => 'error', 'message' => 'Không tìm thấy tiến trình.']);
    }

    public function getSyncStatus() {
        $path = storage_path('app/sync_r2_status.json');
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            return response()->json($data);
        }
        return response()->json(['status' => 'waiting', 'message' => 'Chưa bắt đầu...']);
    }

    public function syncR2Process(Request $request)
    {
        // Redirect GET requests to the tool page to avoid MethodNotAllowed
        if ($request->isMethod('get')) {
            return redirect('/admin/config/tool-sync-r2');
        }

        // Increase time limit
        set_time_limit(300);
        
        $page = $request->get('page', 1);
        Log::info("Sync R2 Process Started - Page $page");

        $perPage = 5; // Upload 5 files at a time to avoid timeout
        
        // Explicitly use base_path('uploads') because it contains the actual files
        $targetDir = base_path('uploads');
        $baseFolder = 'uploads';
        Log::info("Target Dir: $targetDir, Base Folder: $baseFolder");

        // Cache file list to avoid rescanning directory on every request
        $cacheKey = 'sync_r2_files_list';
        Log::info("Cache key: $cacheKey, Page: $page");
        if ($page == 1) {
            $files = File::allFiles($targetDir);
            // Convert to array of paths to store in cache
            $filePaths = array_map(function($file) {
                return $file->getRelativePathname();
            }, $files);
            \Cache::put($cacheKey, $filePaths, 600); // 10 minutes
            Log::info("Found " . count($filePaths) . " files to sync.");
        } else {
            $filePaths = \Cache::get($cacheKey);
            if (!$filePaths) {
                // If cache expired, rescan
                $files = File::allFiles($targetDir);
                $filePaths = array_map(function($file) {
                    return $file->getRelativePathname();
                }, $files);
                \Cache::put($cacheKey, $filePaths, 600);
            }
        }
        
        $totalFiles = count($filePaths);
        $totalPages = ceil($totalFiles / $perPage);
        
        if ($page > $totalPages) {
             \Cache::forget($cacheKey);
             Log::info("Sync R2 Process Completed.");
             return response()->json([
                'status' => 'done',
                'message' => 'Hoàn tất đồng bộ.'
            ]);
        }
        
        $filesToProcess = array_slice($filePaths, ($page - 1) * $perPage, $perPage);
        $messages = [];
        
        foreach($filesToProcess as $relativePath) {
            $filePath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;
            
            try {
                if (File::exists($filePath)) {
                    // Standardize path separators for R2
                    $cleanRelativePath = str_replace('\\', '/', $relativePath);
                    
                    // If the path already starts with the folder name, don't double it
                    if (strpos($cleanRelativePath, $baseFolder . '/') === 0) {
                        $r2Path = $cleanRelativePath;
                    } else {
                        $r2Path = $baseFolder . '/' . $cleanRelativePath;
                    }

                    // Log for debugging
                    Log::info("Syncing file: $filePath to R2 path: $r2Path");
                    
                    // Upload to R2
                    $content = File::get($filePath);
                    $success = Storage::disk('r2')->put($r2Path, $content);
                    
                    if ($success) {
                        // Get R2 URL
                        $r2Url = Storage::disk('r2')->url($r2Path);
                        
                        // DB Update
                        $this->updateDatabasePaths($r2Path, $r2Url);
                        
                        $messages[] = "Đã upload: " . $relativePath;
                        Log::info("Successfully uploaded: $r2Path");
                    } else {
                        $messages[] = "Upload thất bại: " . $relativePath;
                        Log::error("Failed to upload to R2: $r2Path");
                    }
                } else {
                    $messages[] = "Không tìm thấy file: " . $relativePath;
                }
            } catch (\Exception $e) {
                $errorMsg = "Lỗi " . $relativePath . ": " . $e->getMessage();
                $messages[] = $errorMsg;
                Log::error($errorMsg);
            }
        }
        
        $percent = $totalFiles > 0 ? round(($page / $totalPages) * 100) : 100;
        
        return response()->json([
            'status' => 'processing',
            'percent' => $percent,
            'message' => implode('<br>', $messages),
            'next_url' => '/admin/config/sync-r2-process?page=' . ($page + 1),
            'next_page' => $page + 1
        ]);
    }

    private function updateDatabasePaths($oldPath, $newPath) {
        $tables = [
            'posts' => ['image', 'content', 'images'], 
            'sliders' => ['image', 'mobile'],
            'banners' => ['image'],
            'users' => ['image'],
            'configs' => ['value'],
            'brands' => ['image'],
        ];
        
        // Also handle the case with leading slash
        $oldPathWithSlash = '/' . $oldPath;

        foreach($tables as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            foreach($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    // Update with leading slash first (more specific)
                    DB::table($table)->where($column, 'LIKE', '%'.$oldPathWithSlash.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldPathWithSlash', '$newPath')")
                        ]);

                    // Update without leading slash
                    DB::table($table)->where($column, 'LIKE', '%'.$oldPath.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldPath', '$newPath')")
                        ]);
                }
            }
        }
    }
}
