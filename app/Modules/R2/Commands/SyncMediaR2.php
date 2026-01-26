<?php

declare(strict_types=1);
namespace App\Modules\R2\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Each;

class SyncMediaR2 extends Command
{
    protected $signature = 'sync:media-r2 {--batch=20 : Concurrency limit} {--skip : Skip existing files on R2}';

    protected $description = 'Sync local media files to R2 bucket with concurrency';

    protected $statusFile;
    protected $processedCount = 0;
    protected $skippedCount = 0;
    protected $errorCount = 0;
    protected $totalFiles = 0;
    protected $pid;

    public function __construct()
    {
        parent::__construct();
        $this->statusFile = storage_path('app/sync_r2_status.json');
    }

    public function handle()
    {
        $concurrency = (int) $this->option('batch');
        $skipExisting = $this->option('skip');
        $this->pid = getmypid();
        
        $this->updateStatus('scanning', 0, 0, 'Đang khởi tạo...', 0, 0, 0, 'Initializing');

        $targetDir = base_path('uploads');
        $baseFolder = 'uploads';

        if (!File::exists($targetDir)) {
            $this->error("Directory not found: $targetDir");
            $this->updateStatus('error', 0, 0, "Thư mục không tồn tại: $targetDir");
            return 1;
        }

        $files = File::allFiles($targetDir);
        $this->totalFiles = count($files);
        
        $this->info("Found {$this->totalFiles} files. PID: {$this->pid}. Concurrency: $concurrency");
        $this->updateStatus('processing', 0, $this->totalFiles, "Tìm thấy {$this->totalFiles} file. Đang chuẩn bị...", 0, 0, 0, 'Preparing');

        // Get S3 Client
        $client = $this->getS3Client();
        if (!$client) return 1;

        $bucket = config('filesystems.disks.r2.bucket');

        // Generator that yields promises
        $promises = (function () use ($files, $client, $bucket, $baseFolder, $skipExisting) {
            foreach ($files as $file) {
                if ($this->shouldStop()) break;

                $relativePath = $file->getRelativePathname();
                $cleanRelativePath = str_replace('\\', '/', $relativePath);
                $r2Path = $baseFolder . '/' . $cleanRelativePath;
                $filePath = $file->getPathname();

                // Skip Logic
                // Note: Using synchronous check inside generator might block the loop slightly but ensures accuracy.
                // For better perf, we could make this async too (headObjectAsync), but that complicates flow.
                if ($skipExisting) {
                    if ($client->doesObjectExist($bucket, $r2Path)) {
                        $this->skippedCount++;
                        $this->processedCount++;
                        
                        // Update DB for skipped files too
                        $r2Url = Storage::disk('r2')->url($r2Path);
                        $this->updateDatabasePaths($relativePath, $r2Url);
                        
                        if ($this->skippedCount % 10 == 0) {
                            $this->updateProgress("Skipping existing files...", "Skip: $relativePath");
                        }
                        continue;
                    }
                }

                // Create Async Upload Promise
                $promise = $client->putObjectAsync([
                    'Bucket' => $bucket,
                    'Key'    => $r2Path,
                    'Body'   => fopen($filePath, 'r'),
                    'ACL'    => 'public-read',
                ]);

                // Attach callbacks
                yield $promise->then(
                    function ($result) use ($relativePath, $r2Path) {
                        $this->processedCount++;
                        $r2Url = Storage::disk('r2')->url($r2Path);
                        $this->updateDatabasePaths($relativePath, $r2Url);
                        $this->updateProgress("Đang upload...", "Uploaded: $relativePath");
                    },
                    function ($reason) use ($relativePath) {
                        $this->processedCount++;
                        $this->errorCount++;
                        Log::error("Upload failed [$relativePath]: " . $reason->getMessage());
                        $this->updateProgress("Lỗi upload", "Error: $relativePath");
                    }
                );
            }
        })();

        // Execute Pool
        // Each::ofLimit ensures only $concurrency promises are pending at once.
        // As one resolves, the generator yields the next one.
        $each = Each::ofLimit($promises, $concurrency);
        
        // Block until all complete
        $each->promise()->wait();

        $this->updateStatus('done', 100, $this->totalFiles, "Hoàn tất!", $this->processedCount, $this->skippedCount, $this->errorCount, 'Finished');
        return 0;
    }

    private function getS3Client() {
        try {
            $disk = Storage::disk('r2');
            $adapter = $disk->getAdapter();
            
            if (method_exists($adapter, 'getClient')) {
                return $adapter->getClient();
            }
            
            // Fallback config
            $config = config('filesystems.disks.r2');
            return new S3Client([
                'region' => $config['region'] ?? 'auto',
                'version' => 'latest',
                'endpoint' => $config['endpoint'],
                'credentials' => [
                    'key' => $config['key'],
                    'secret' => $config['secret'],
                ],
            ]);
        } catch (\Exception $e) {
            $this->error("Init S3 Client Error: " . $e->getMessage());
            $this->updateStatus('error', 0, 0, "Lỗi kết nối R2: " . $e->getMessage());
            return null;
        }
    }

    private function shouldStop() {
        if (!File::exists($this->statusFile)) return false;
        $data = json_decode(File::get($this->statusFile), true);
        return ($data['status'] ?? '') === 'stopping';
    }

    private function updateProgress($msg, $action) {
        // Debounce updates to avoid file IO overhead
        static $lastUpdate = 0;
        if (time() - $lastUpdate < 1 && $this->processedCount < $this->totalFiles) return;
        $lastUpdate = time();

        $percent = $this->totalFiles > 0 ? round(($this->processedCount / $this->totalFiles) * 100) : 0;
        $this->updateStatus('processing', $percent, $this->totalFiles, $msg, $this->processedCount, $this->skippedCount, $this->errorCount, $action);
    }

    private function updateStatus($status, $percent, $total, $message, $processed = 0, $skipped = 0, $errors = 0, $lastAction = '') {
        $data = [
            'status' => $status,
            'percent' => $percent,
            'total' => $total,
            'message' => $message,
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors,
            'last_action' => $lastAction,
            'pid' => $this->pid,
            'updated_at' => now()->toDateTimeString()
        ];
        
        File::put($this->statusFile, json_encode($data));
    }

    private function updateDatabasePaths($relativePath, $newPath) {
        $tables = [
            'posts' => ['image', 'content', 'images'], 
            'sliders' => ['image', 'mobile'],
            'banners' => ['image'],
            'users' => ['image'],
            'configs' => ['value'],
            'brands' => ['image'],
        ];
        
        $oldPath = str_replace('\\', '/', $relativePath);
        $oldPathWithSlash = '/' . $oldPath;
        $searchPathWithFolder = 'uploads/' . $oldPath;

        foreach($tables as $table => $columns) {
            if (!Schema::hasTable($table)) continue;

            foreach($columns as $column) {
                if (!Schema::hasColumn($table, $column)) continue;

                try {
                    DB::table($table)->where($column, 'LIKE', '%'.$searchPathWithFolder.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$searchPathWithFolder', '$newPath')")
                        ]);

                    DB::table($table)->where($column, 'LIKE', '%'.$oldPathWithSlash.'%')
                        ->update([
                            $column => DB::raw("REPLACE($column, '$oldPathWithSlash', '$newPath')")
                        ]);
                } catch (\Exception $e) {
                     // Silent fail for DB locks
                }
            }
        }
    }
}
