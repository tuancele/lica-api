<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup 
                            {--path= : Custom backup path}
                            {--compress : Compress backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database before charset conversion';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = DB::connection()->getDatabaseName();
        $host = Config::get('database.connections.mysql.host', '127.0.0.1');
        $port = Config::get('database.connections.mysql.port', '3306');
        $username = Config::get('database.connections.mysql.username');
        $password = Config::get('database.connections.mysql.password');

        $backupPath = $this->option('path') ?: storage_path('backups');
        
        // Create backup directory if not exists
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "{$database}_backup_{$timestamp}.sql";
        $filepath = "{$backupPath}/{$filename}";

        $this->info("Starting database backup...");
        $this->info("Database: {$database}");
        $this->info("Backup path: {$filepath}");

        // Build mysqldump command
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            $password ? '-p' . escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        // Execute backup
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error("Backup failed!");
            $this->error(implode("\n", $output));
            
            // Try alternative method using Laravel DB
            $this->warn("Trying alternative backup method...");
            return $this->alternativeBackup($filepath, $database);
        }

        // Check if file was created and has content
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            $this->error("Backup file is empty or not created!");
            return Command::FAILURE;
        }

        $fileSize = $this->formatBytes(filesize($filepath));
        $this->info("Backup completed successfully!");
        $this->info("File size: {$fileSize}");
        $this->info("Backup saved to: {$filepath}");

        // Compress if requested
        if ($this->option('compress')) {
            $this->info("Compressing backup...");
            $compressedPath = $filepath . '.gz';
            if (function_exists('gzencode')) {
                $data = file_get_contents($filepath);
                file_put_contents($compressedPath, gzencode($data, 9));
                unlink($filepath);
                $this->info("Compressed backup saved to: {$compressedPath}");
            } else {
                $this->warn("gzencode not available, skipping compression");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Alternative backup method using Laravel DB
     */
    private function alternativeBackup(string $filepath, string $database): int
    {
        $this->warn("Note: Alternative method may not backup all data types correctly.");
        $this->warn("Please use mysqldump if possible.");
        
        // Get all tables
        $tables = DB::select("SHOW TABLES");
        $tableKey = "Tables_in_{$database}";
        
        $backupContent = "-- Database Backup: {$database}\n";
        $backupContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $backupContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $this->line("Backing up table: {$tableName}");

            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $backupContent .= "\n-- Table structure for `{$tableName}`\n";
            $backupContent .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $backupContent .= $createTable[0]->{'Create Table'} . ";\n\n";

            // Get table data
            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                $backupContent .= "-- Data for table `{$tableName}`\n";
                foreach ($rows as $row) {
                    $values = [];
                    foreach ((array)$row as $value) {
                        $values[] = is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    $backupContent .= "INSERT INTO `{$tableName}` VALUES (" . implode(',', $values) . ");\n";
                }
                $backupContent .= "\n";
            }
        }

        $backupContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

        file_put_contents($filepath, $backupContent);
        
        if (file_exists($filepath) && filesize($filepath) > 0) {
            $fileSize = $this->formatBytes(filesize($filepath));
            $this->info("Alternative backup completed!");
            $this->info("File size: {$fileSize}");
            $this->info("Backup saved to: {$filepath}");
            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

