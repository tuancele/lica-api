<?php

declare(strict_types=1);
namespace App\Modules\R2\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Modules\R2\Services\ImageConverter;
use Aws\S3\Exception\S3Exception;

class R2Controller extends Controller
{
    public function toolSyncR2(Request $request)
    {
        active('config', 'r2');
        return view('R2::tool_sync');
    }

    public function startSyncBackground(Request $request) {
        $batch = $request->input('batch', 5);
        $skip = $request->input('skip', false);
        
        $cmd = "php artisan sync:media-r2 --batch=$batch";
        if ($skip) {
            $cmd .= " --skip";
        }
        
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
            
            $data['status'] = 'stopping';
            File::put($path, json_encode($data));
            
            if (isset($data['pid']) && $data['pid']) {
                $pid = $data['pid'];
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec("taskkill /F /PID $pid");
                } else {
                    exec("kill -9 $pid");
                }
            }
            
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
        if ($request->isMethod('get')) {
            return redirect('/admin/r2/tool-sync');
        }

        set_time_limit(300);
        
        $page = $request->get('page', 1);
        Log::info("Sync R2 Process Started - Page $page");

        $perPage = 5;
        $targetDir = base_path('uploads');
        $baseFolder = 'uploads';

        $cacheKey = 'sync_r2_files_list';
        if ($page == 1) {
            $files = File::allFiles($targetDir);
            $filePaths = array_map(function($file) {
                return $file->getRelativePathname();
            }, $files);
            \Cache::put($cacheKey, $filePaths, 600);
            Log::info("Found " . count($filePaths) . " files to sync.");
        } else {
            $filePaths = \Cache::get($cacheKey);
            if (!$filePaths) {
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
                    $cleanRelativePath = str_replace('\\', '/', $relativePath);
                    
                    if (strpos($cleanRelativePath, $baseFolder . '/') === 0) {
                        $r2Path = $cleanRelativePath;
                    } else {
                        $r2Path = $baseFolder . '/' . $cleanRelativePath;
                    }

                    Log::info("Syncing file: $filePath to R2 path: $r2Path");
                    
                    $content = File::get($filePath);
                    $success = Storage::disk('r2')->put($r2Path, $content);
                    
                    if ($success) {
                        $r2Url = Storage::disk('r2')->url($r2Path);
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
            'next_url' => '/admin/r2/sync-process?page=' . ($page + 1),
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
        
        $oldPathWithSlash = '/' . $oldPath;

        foreach($tables as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            foreach($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    DB::table($table)->where($column, 'LIKE', '%'.$oldPathWithSlash.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldPathWithSlash', '$newPath')")
                        ]);

                    DB::table($table)->where($column, 'LIKE', '%'.$oldPath.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldPath', '$newPath')")
                        ]);
                }
            }
        }
    }

    public function upload(Request $request)
    {
        $logId = uniqid('R2-', true);
        
        // Helper function to add logId to all log messages
        $log = function($level, $message, $context = []) use ($logId) {
            $prefix = "R2 Upload [{$logId}]";
            $fullMessage = "{$prefix}: {$message}";
            if (!empty($context)) {
                $fullMessage .= " | Context: " . json_encode($context);
            }
            Log::$level($fullMessage);
        };
        
        $log('info', "=== Request Start ===");
        $log('info', "Request received", [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'has_files' => $request->hasFile('files'),
            'total_files_param' => $request->input('TotalFiles', 'not set'),
            'all_keys' => array_keys($request->all())
        ]);
        
        // Check which file keys exist
        $fileKeys = [];
        for ($i = 0; $i < 10; $i++) {
            $key = 'files' . $i;
            if ($request->hasFile($key)) {
                $fileKeys[] = $key;
                $file = $request->file($key);
                Log::info("R2 Upload: File key '$key' exists - Name: " . ($file ? $file->getClientOriginalName() : 'null') . ", Valid: " . ($file && $file->isValid() ? 'yes' : 'no'));
            }
        }
        if ($request->hasFile('files')) {
            $fileKeys[] = 'files';
            $file = $request->file('files');
            $log('info', "File key 'files' exists", [
                'name' => $file ? (is_array($file) ? 'array' : $file->getClientOriginalName()) : 'null'
            ]);
        }
        $log('info', "File keys found", ['keys' => $fileKeys]);
        
        try {
            // Skip validation if no files in 'files' array (we check files0, files1 separately)
            // Only validate if 'files' array is present
            if ($request->hasFile('files')) {
                try {
        $request->validate([
            'files' => 'required',
                        'files.*' => 'mimes:jpeg,png,jpg,gif,webp|max:10240' // 10MB max
                    ], [
                        'files.required' => 'Không có file nào được upload.',
                        'files.*.mimes' => 'File phải là ảnh (jpeg, png, jpg, gif, webp).',
                        'files.*.max' => 'File không được vượt quá 10MB.'
                    ]);
                } catch (\Illuminate\Validation\ValidationException $ve) {
                    Log::error("R2 Upload Validation Error: " . json_encode($ve->errors()));
                    throw $ve;
                }
            }
            
            $folder = $request->input('folder', 'image'); 
            $convertWebP = filter_var($request->input('convert_webp', true), FILTER_VALIDATE_BOOLEAN);
            $quality = (int) $request->input('quality', 85);
            
            Log::info("R2 Upload: Folder=$folder, ConvertWebP=" . ($convertWebP ? 'yes' : 'no') . ", Quality=$quality");
        
        $converter = new ImageConverter();
        $insert = [];
        $tempFiles = [];
        
        // Retrieve files from different potential input keys
        $allFiles = [];
        
        // Check for indexed files0, files1... (used by r2-upload-preview.js)
        $totalFilesInput = (int) ($request->input('TotalFiles', 0));
        Log::info("R2 Upload: TotalFiles input = $totalFilesInput");
        
        // Check all possible file keys
        for ($i = 0; $i < max($totalFilesInput, 10); $i++) {
            $fileKey = 'files' . $i;
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                if ($file && $file->isValid()) {
                    $allFiles[] = $file;
                    Log::info("R2 Upload: Found file at key: $fileKey, Name: " . $file->getClientOriginalName() . ", Size: " . $file->getSize());
                } else {
                    Log::warning("R2 Upload: File at key $fileKey exists but is invalid");
                }
            }
        }
        
        // Also check for 'files' or 'files[]' array (fallback)
        if ($request->hasFile('files')) {
            $filesArray = $request->file('files');
            if (is_array($filesArray)) {
                foreach ($filesArray as $f) {
                    if ($f && $f->isValid() && !in_array($f, $allFiles, true)) {
                        $allFiles[] = $f;
                    }
                }
            } elseif ($filesArray && $filesArray->isValid()) {
                // Check if not already added
                $alreadyAdded = false;
                foreach ($allFiles as $existingFile) {
                    if ($existingFile->getRealPath() === $filesArray->getRealPath()) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                if (!$alreadyAdded) {
                    $allFiles[] = $filesArray;
                }
            }
        }

        if (empty($allFiles)) {
            Log::warning("R2 Upload: No files provided. Request data: " . json_encode($request->all()));
            return response()->json(["message" => "Không có file nào được gửi lên. Vui lòng thử lại."], 400);
        }
        
        Log::info("R2 Upload: Found " . count($allFiles) . " file(s) to process");

        foreach ($allFiles as $index => $file) {
            try {
                if (!$file || !$file->isValid()) {
                    Log::warning("R2 Upload: Invalid or null file at index $index");
                    continue;
                }

                $originalName = $file->getClientOriginalName();
                $name = $originalName;
            
            // Get file content - try multiple methods
            $content = null;
            $filePath = $file->getRealPath();
            
            if ($filePath && file_exists($filePath)) {
                $content = @file_get_contents($filePath);
            }
            
            // Fallback: read from uploaded file stream
            if ($content === false || $content === null) {
                try {
                    $content = $file->get();
                } catch (\Exception $e) {
                    Log::error("Failed to read uploaded file stream: " . $e->getMessage());
                }
            }
            
            // Last resort: read from pathname
            if (($content === false || $content === null) && $filePath) {
                $content = @file_get_contents($filePath);
            }
            
            if ($content === false || $content === null || strlen($content) === 0) {
                Log::error("Failed to read uploaded file: $originalName (Path: " . ($filePath ?? 'N/A') . ")");
                continue;
            }
            
            Log::info("R2 Upload: Successfully read file content. Size: " . strlen($content) . " bytes");

            // Generate new filename components: hash + date + random
            // Format: {hash}_{YYYYMMDD}_{random8-10chars}.webp
            $contentHash = substr(md5($content), 0, 8); // First 8 chars of MD5 hash
            $dateStr = date('Ymd'); // YYYYMMDD format
            $randomStr = Str::random(rand(8, 10)); // 8-10 random characters
            
            $webpResult = null;
            $isWebP = false; // Track if file is WebP (converted or original)
            $conversionFailed = false;
            
            // Check if file is already WebP by MIME type
            $mimeType = $file->getMimeType();
            $isAlreadyWebP = ($mimeType === 'image/webp');
            
            Log::info("R2 Upload: File MIME type check", [
                'originalName' => $originalName,
                'mimeType' => $mimeType,
                'isAlreadyWebP' => $isAlreadyWebP
            ]);
            
            // FORCE WebP conversion - no fallback to original format
            if ($convertWebP) {
                try {
                    if ($isAlreadyWebP) {
                        // File is already WebP, no conversion needed
                        $isWebP = true;
                        Log::info("R2 Upload: File is already WebP format. No conversion needed. Using original content.");
                        // Content is already WebP, use it as-is
                    } else {
                        // File needs conversion
                        Log::info("R2 Upload: Attempting WebP conversion for: $originalName (REQUIRED)", [
                            'mimeType' => $mimeType,
                            'fileSize' => strlen($content),
                            'quality' => $quality
                        ]);
                        $webpResult = $converter->convertToWebP($file, $quality);
                        
                        Log::info("R2 Upload: ImageConverter result", [
                            'webpResult' => $webpResult ? 'exists' : 'null',
                            'is_converted' => $webpResult && isset($webpResult['is_converted']) ? $webpResult['is_converted'] : 'N/A',
                            'path' => $webpResult && isset($webpResult['path']) ? $webpResult['path'] : 'N/A'
                        ]);
                        
                        if ($webpResult) {
                            if (isset($webpResult['is_converted']) && $webpResult['is_converted']) {
                                // File was converted to WebP
                                $webpPath = $webpResult['path'];
                                $isWebP = true; // Mark as WebP
                                
                                Log::info("R2 Upload: WebP file path check", [
                                    'webpPath' => $webpPath,
                                    'fileExists' => file_exists($webpPath),
                                    'fileSize' => file_exists($webpPath) ? filesize($webpPath) : 0
                                ]);
                                
                                if (file_exists($webpPath)) {
                                    $newContent = @file_get_contents($webpPath);
                                    if ($newContent !== false && strlen($newContent) > 0) {
                                        $content = $newContent;
                                        $tempFiles[] = $webpPath;
                                        Log::info("R2 Upload: WebP conversion successful. New size: " . strlen($content) . " bytes");
                                    } else {
                                        Log::error("R2 Upload: WebP file exists but content is empty. Conversion FAILED.", [
                                            'webpPath' => $webpPath,
                                            'fileExists' => file_exists($webpPath),
                                            'contentLength' => $newContent !== false ? strlen($newContent) : 0,
                                            'lastError' => error_get_last()
                                        ]);
                                        $conversionFailed = true;
                                    }
                                } else {
                                    Log::error("R2 Upload: WebP file not found at: $webpPath. Conversion FAILED.", [
                                        'webpPath' => $webpPath,
                                        'fileExists' => file_exists($webpPath),
                                        'directory' => dirname($webpPath),
                                        'dirExists' => file_exists(dirname($webpPath))
                                    ]);
                                    $conversionFailed = true;
                                }
                            } else {
                                // File is already WebP (ImageConverter detected it)
                                $isWebP = true;
                                Log::info("R2 Upload: ImageConverter says file is already WebP. Using original content.", [
                                    'originalName' => $originalName,
                                    'mimeType' => $mimeType,
                                    'is_converted' => $webpResult['is_converted'] ?? false
                                ]);
                                // Content is already WebP, use it as-is (no conversion needed)
                            }
                        } else {
                            $errorMsg = "R2 Upload: WebP conversion returned null. Conversion FAILED.";
                            $log('error', $errorMsg, [
                                'originalName' => $originalName,
                                'mimeType' => $mimeType,
                                'fileSize' => strlen($content),
                                'convertWebP' => $convertWebP,
                                'quality' => $quality,
                                'filePath' => $file->getRealPath(),
                                'fileExists' => $file->getRealPath() ? file_exists($file->getRealPath()) : false,
                                'fileIsValid' => $file->isValid(),
                                'fileError' => $file->getError(),
                                'fileMimeType' => $file->getMimeType(),
                                'fileClientOriginalName' => $file->getClientOriginalName()
                            ]);
                            
                            // Also log to Laravel log and debug file
                            Log::error($errorMsg, [
                                'originalName' => $originalName,
                                'mimeType' => $mimeType,
                                'fileSize' => strlen($content),
                                'logId' => $logId
                            ]);
                            
                            $debugLog = storage_path('logs/r2_debug.log');
                            $debugMsg = date('Y-m-d H:i:s') . " - R2 Upload: WebP conversion returned null\n";
                            $debugMsg .= "File: $originalName\n";
                            $debugMsg .= "MIME: $mimeType\n";
                            $debugMsg .= "Size: " . strlen($content) . " bytes\n";
                            $debugMsg .= "LogId: $logId\n\n";
                            @file_put_contents($debugLog, $debugMsg, FILE_APPEND);
                            
                            $conversionFailed = true;
                        }
                    }
                } catch (\Exception $e) {
                    $log('error', "WebP conversion failed for {$originalName}: " . $e->getMessage(), [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'mimeType' => $mimeType,
                        'isAlreadyWebP' => $isAlreadyWebP,
                        'fileSize' => strlen($content)
                    ]);
                    $conversionFailed = true;
                }
            } else {
                // If convertWebP is false, but we still want to ensure WebP
                Log::warning("R2 Upload: convertWebP is false, but WebP is required. Attempting conversion anyway.");
                try {
                    if ($isAlreadyWebP) {
                        $isWebP = true;
                        Log::info("R2 Upload: File is already WebP format (forced check).");
                    } else {
                        $webpResult = $converter->convertToWebP($file, $quality);
                        if ($webpResult && isset($webpResult['is_converted']) && $webpResult['is_converted']) {
                            $webpPath = $webpResult['path'];
                            if (file_exists($webpPath)) {
                                $newContent = @file_get_contents($webpPath);
                                if ($newContent !== false && strlen($newContent) > 0) {
                                    $content = $newContent;
                                    $tempFiles[] = $webpPath;
                                    $isWebP = true;
                                    Log::info("R2 Upload: WebP conversion successful (forced).");
                                } else {
                                    $conversionFailed = true;
                                }
                            } else {
                                $conversionFailed = true;
                            }
                        } else {
                            $conversionFailed = true;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Forced WebP conversion failed: " . $e->getMessage());
                    $conversionFailed = true;
                }
            }
            
                // If conversion failed, log error but don't throw - let outer catch handle it
                if ($conversionFailed && $convertWebP && !$isAlreadyWebP) {
                    $log('error', "R2 Upload: WebP conversion is REQUIRED but failed. Rejecting upload.", [
                        'originalName' => $originalName,
                        'mimeType' => $mimeType,
                        'isAlreadyWebP' => $isAlreadyWebP,
                        'convertWebP' => $convertWebP,
                        'webpResult' => $webpResult ? 'exists' : 'null',
                        'fileSize' => strlen($content),
                        'filePath' => $file->getRealPath(),
                        'fileExists' => $file->getRealPath() ? file_exists($file->getRealPath()) : false,
                        'fileIsValid' => $file->isValid(),
                        'fileError' => $file->getError()
                    ]);
                    // Throw exception to be caught by outer catch
                    throw new \Exception("Không thể chuyển đổi ảnh sang định dạng WebP. Vui lòng thử lại với ảnh khác.");
                }
                
                // Final check: ensure we have WebP content
                if (!$isWebP) {
                    $log('error', "R2 Upload: File is not WebP and conversion failed. Rejecting upload.", [
                        'originalName' => $originalName,
                        'mimeType' => $mimeType,
                        'isAlreadyWebP' => $isAlreadyWebP,
                        'convertWebP' => $convertWebP,
                        'isWebP' => $isWebP,
                        'conversionFailed' => $conversionFailed,
                        'webpResult' => $webpResult ? 'exists' : 'null'
                    ]);
                    // Throw exception to be caught by outer catch
                    throw new \Exception("Không thể chuyển đổi ảnh sang định dạng WebP. Vui lòng thử lại với ảnh khác.");
                }
            
            Log::info("R2 Upload: WebP validation passed", [
                'originalName' => $originalName,
                'mimeType' => $mimeType,
                'isWebP' => $isWebP,
                'isAlreadyWebP' => $isAlreadyWebP,
                'contentSize' => strlen($content)
            ]);
            
            // Ensure we always use WebP extension
            $extension = '.webp';
            $isWebP = true; // Force WebP

            try {
                // Generate new filename with format: {hash}_{date}_{random}.webp
                $uniqueName = $contentHash . '_' . $dateStr . '_' . $randomStr . $extension;
                
                Log::info("R2 Upload: Generated new filename", [
                    'original' => $originalName,
                    'new' => $uniqueName,
                    'format' => 'hash_date_random.webp',
                    'hash' => $contentHash,
                    'date' => $dateStr,
                    'random' => $randomStr
                ]);
                
                // Ensure folder is safe
                $safeFolder = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($folder, '/'));
                if (empty($safeFolder)) {
                    $safeFolder = 'image';
                }
                
                $filePath = 'images/' . $safeFolder . '/' . $uniqueName;
                
                // Validate filePath is not empty
                $filePath = trim($filePath);
                if (empty($filePath)) {
                    throw new \Exception("Generated file path is empty after processing");
                }
                
                // Validate content is not empty
                if (empty($content) || strlen($content) === 0) {
                    throw new \Exception("File content is empty for: $originalName");
                }
                
                Log::info("R2 Upload: Processing file - Original: $originalName, Final name: $uniqueName");
                Log::info("R2 Upload: Attempting to upload file to path: $filePath (size: " . strlen($content) . " bytes)");
                
                // Check if R2 disk is configured
                try {
                    $r2Config = config('filesystems.disks.r2');
                    if (empty($r2Config) || empty($r2Config['key']) || empty($r2Config['bucket'])) {
                        throw new \Exception("R2 storage is not properly configured. Please check your R2 configuration.");
                    }
                    Log::info("R2 Config OK: Bucket=" . $r2Config['bucket'] . ", Endpoint=" . ($r2Config['endpoint'] ?? 'N/A'));
                } catch (\Exception $configError) {
                    Log::error("R2 Config Error: " . $configError->getMessage());
                    throw $configError;
                }
                
                // Ensure filePath is not empty and properly formatted
                $filePath = trim($filePath);
                if (empty($filePath)) {
                    throw new \Exception("File path is empty after trimming");
                }
                
                // Remove leading slash if present (S3/R2 doesn't like it)
                $filePath = ltrim($filePath, '/');
                
                Log::info("R2 Upload: Final file path: $filePath");
                
                try {
                        $result = Storage::disk('r2')->put($filePath, $content);
                } catch (S3Exception $s3e) {
                    Log::error("S3/R2 Exception: " . $s3e->getMessage());
                    Log::error("S3 Error Code: " . $s3e->getAwsErrorCode());
                    Log::error("S3 Request ID: " . $s3e->getAwsRequestId());
                    throw new \Exception("R2 Storage Error: " . $s3e->getMessage());
                } catch (\Exception $storageEx) {
                    Log::error("Storage Exception: " . $storageEx->getMessage());
                    Log::error("Storage Exception Class: " . get_class($storageEx));
                    Log::error("Storage Exception Trace: " . $storageEx->getTraceAsString());
                    throw $storageEx;
                        }

                        if ($result) {
                            $url = Storage::disk('r2')->url($filePath);
                    if (empty($url)) {
                        // Fallback: construct URL manually if Storage::url() fails
                        $r2Url = config('filesystems.disks.r2.url');
                        if (!empty($r2Url)) {
                            $url = rtrim($r2Url, '/') . '/' . $filePath;
                        } else {
                            throw new \Exception("Failed to generate URL for uploaded file - R2 URL not configured");
                        }
                    }
                    Log::info("R2 Upload: Successfully uploaded file. URL: $url");
                    $insert[$index] = $url;
                        } else {
                            throw new \Exception("Storage::put returned false for $name");
                        }
                    } catch (\Exception $e) {
                        $log('error', "R2 Upload Error for file {$originalName}: " . $e->getMessage(), [
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'trace' => $e->getTraceAsString(),
                            'filePath' => $filePath ?? 'N/A'
                        ]);
                        // Don't return here - continue with other files
                        // Just log the error and skip this file
                        continue;
                    }
            } catch (\Exception $outerException) {
                // Catch any exception thrown outside the inner try-catch (e.g., conversion failures)
                $log('error', "R2 Upload Outer Exception for file: " . ($originalName ?? 'unknown'), [
                    'message' => $outerException->getMessage(),
                    'file' => $outerException->getFile(),
                    'line' => $outerException->getLine(),
                    'trace' => $outerException->getTraceAsString(),
                    'exceptionClass' => get_class($outerException)
                ]);
                
                // Also log to Laravel log for debugging
                Log::error("R2 Upload Outer Exception", [
                    'fileName' => $originalName ?? 'unknown',
                    'message' => $outerException->getMessage(),
                    'file' => $outerException->getFile(),
                    'line' => $outerException->getLine(),
                    'logId' => $logId
                ]);
                
                // Continue with next file
                continue;
            }
        }
        
        // Clean up temp files
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
        
        // Check if we have any successful uploads
        if (empty($insert)) {
            $log('error', "No files were successfully uploaded", [
                'totalFiles' => count($allFiles),
                'insertCount' => count($insert),
                'tempFilesCount' => count($tempFiles)
            ]);
            
            // Log to Laravel log as well for debugging
            Log::error("R2 Upload: No files were successfully uploaded", [
                'totalFiles' => count($allFiles),
                'insertCount' => count($insert),
                'logId' => $logId
            ]);
            
            return response()->json([
                "message" => "Không có file nào được upload thành công. Vui lòng kiểm tra log để biết chi tiết.",
                "debug" => [
                    "totalFiles" => count($allFiles),
                    "successfulUploads" => count($insert),
                    "logId" => $logId
                ]
            ], 500);
        }
        
        $uploadedUrls = array_values($insert);
        
        // IMPORTANT: Verify that we have the same number of URLs as successful uploads
        $totalFiles = count($allFiles);
        $successfulUploads = count($uploadedUrls);
        
        $log('info', "Upload summary - Files vs URLs", [
            'total_files_received' => $totalFiles,
            'successful_uploads' => $successfulUploads,
            'uploaded_urls_count' => count($uploadedUrls),
            'match' => ($totalFiles === $successfulUploads) ? 'OK' : 'MISMATCH',
            'urls' => $uploadedUrls
        ]);
        
        // Store URLs in session for later retrieval
        // Use a unique session key for this upload batch (scoped to this upload only)
        $sessionKey = 'r2_uploaded_urls_' . $logId;
        // Store ONLY the URLs from this upload batch (not merged with any shared bucket)
        Session::put($sessionKey, $uploadedUrls);
        
        $log('info', "Upload completed successfully - Session storage", [
            'urls_count' => count($uploadedUrls),
            'urls' => $uploadedUrls,
            'session_key' => $sessionKey,
            'session_stored_count' => count(Session::get($sessionKey, [])),
        ]);
        
        return response()->json([
            'urls' => $uploadedUrls,
            'urls_count' => count($uploadedUrls), // Add count for verification
            'session_key' => $sessionKey,
            'log_id' => $logId,
            'total_files' => $totalFiles,
            'successful_uploads' => $successfulUploads
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("R2 Upload Validation Error: " . $e->getMessage());
            return response()->json([
                "message" => "Lỗi validation: " . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            $errorMessage = "R2Controller@upload critical error: " . $e->getMessage();
            Log::error($errorMessage);
            Log::error("Stack trace: " . $e->getTraceAsString());
            Log::error("File: " . $e->getFile() . " Line: " . $e->getLine());
            Log::error("Exception class: " . get_class($e));
            
            // Also write to file directly for debugging
            \File::append(storage_path('logs/r2_debug.log'), 
                date('Y-m-d H:i:s') . " - " . $errorMessage . "\n" . 
                "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n" .
                "Trace: " . $e->getTraceAsString() . "\n\n"
            );
            
            // Clean up temp files
            if (isset($tempFiles)) {
                foreach ($tempFiles as $tempFile) {
                    @unlink($tempFile);
                }
            }
            
            return response()->json([
                "message" => "Lỗi hệ thống: " . $e->getMessage(),
                "error" => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }
    }

    /**
     * Simple upload handler for video files (no WebP conversion)
     */
    public function uploadVideo(Request $request)
    {
        $logId = uniqid('R2-VIDEO-', true);

        Log::info("R2 Video Upload [$logId]: Request received", [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
        ]);

        try {
            $request->validate([
                // max is in kilobytes → 30720 = 30 MB
                'file' => 'required|file|mimes:mp4,webm,mov,avi,quicktime|max:30720',
                'folder' => 'nullable|string|max:100',
            ]);

            $file = $request->file('file');
            $folder = $request->input('folder', 'videos/products');

            if (!$file || !$file->isValid()) {
                Log::error("R2 Video Upload [$logId]: Invalid file upload");
                return response()->json([
                    'message' => 'File video không hợp lệ, vui lòng thử lại.'
                ], 400);
            }

            $extension = $file->getClientOriginalExtension() ?: 'mp4';
            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBase = Str::slug($baseName) ?: 'video';

            // Lưu trực tiếp qua Storage disk r2 bằng nội dung file
            $relativeDir = trim($folder, '/');
            $relativePath = $relativeDir . '/' . date('Y/m/d');
            $fileName = $safeBase . '-' . uniqid() . '.' . $extension;
            $fullPath = $relativePath . '/' . $fileName;

            Log::info("R2 Video Upload [$logId]: Storing file", [
                'path' => $fullPath,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            // Dùng get() thay vì rely vào getRealPath() để tránh lỗi "Path cannot be empty"
            $content = $file->get();
            if ($content === false || $content === null || $content === '') {
                Log::error("R2 Video Upload [$logId]: Empty file content when reading upload");
                return response()->json([
                    'message' => 'Không đọc được nội dung video, vui lòng thử lại.'
                ], 500);
            }

            $stored = Storage::disk('r2')->put($fullPath, $content);

            if (!$stored) {
                Log::error("R2 Video Upload [$logId]: Storage::put returned false", [
                    'path' => $fullPath,
                ]);

                return response()->json([
                    'message' => 'Upload video thất bại, vui lòng thử lại.'
                ], 500);
            }

            $url = Storage::disk('r2')->url($fullPath);
            if (empty($url)) {
                $r2Url = config('filesystems.disks.r2.url');
                if (!empty($r2Url)) {
                    $url = rtrim($r2Url, '/') . '/' . $fullPath;
                }
            }

            Log::info("R2 Video Upload [$logId]: Success", [
                'url' => $url,
                'path' => $fullPath,
            ]);

            return response()->json([
                'url' => $url,
                'path' => $fullPath,
                'log_id' => $logId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("R2 Video Upload [$logId] Validation Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Dữ liệu không hợp lệ: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error("R2 Video Upload [$logId] Critical error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ], 500);
        }
    }
}
