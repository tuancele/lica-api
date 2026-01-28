<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConvertDatabaseCharset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:convert-charset 
                            {--dry-run : Show what would be converted without making changes}
                            {--table= : Convert specific table only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert database tables from latin1 to utf8mb4 charset';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificTable = $this->option('table');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $database = DB::connection()->getDatabaseName();
        $this->info("Database: {$database}");

        // Get all tables that need conversion
        $tables = $this->getTablesToConvert($specificTable);

        if (empty($tables)) {
            $this->info('No tables need conversion. All tables are already utf8mb4_unicode_ci');
            return Command::SUCCESS;
        }

        $this->info("Found " . count($tables) . " table(s) to convert");

        if (!$dryRun && !$this->confirm('Do you want to proceed with conversion?', true)) {
            $this->info('Conversion cancelled');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($tables));
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($tables as $index => $table) {
            $bar->setMessage("Converting: {$table}");
            try {
                if (!$dryRun) {
                    $this->convertTable($table);
                    $successCount++;
                } else {
                    $this->line("\nWould convert: {$table}");
                }
                $bar->advance();
            } catch (\Exception $e) {
                $errorCount++;
                $errorMsg = "Error converting table {$table}: " . $e->getMessage();
                $errors[] = $errorMsg;
                $this->error("\n" . $errorMsg);
                $bar->advance();
                // Continue with other tables instead of failing completely
            }
        }

        $bar->setMessage('Completed');
        $bar->finish();
        $this->newLine();
        
        if ($dryRun) {
            $this->info('Dry run completed successfully!');
        } else {
            $this->info("Conversion completed! Success: {$successCount}, Errors: {$errorCount}");
            
            if ($errorCount > 0) {
                $this->warn("\nErrors encountered:");
                foreach ($errors as $error) {
                    $this->warn("  - {$error}");
                }
                $this->warn("\nSome tables may need manual conversion. Check the errors above.");
            } else {
                $this->info('All tables converted successfully!');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get list of tables that need charset conversion
     */
    private function getTablesToConvert(?string $specificTable = null): array
    {
        $query = "
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_COLLATION != 'utf8mb4_unicode_ci'
            AND TABLE_TYPE = 'BASE TABLE'
        ";

        $params = [DB::connection()->getDatabaseName()];

        if ($specificTable) {
            $query .= " AND TABLE_NAME = ?";
            $params[] = $specificTable;
        }

        $results = DB::select($query, $params);
        return array_column($results, 'TABLE_NAME');
    }

    /**
     * Convert a single table to utf8mb4
     */
    private function convertTable(string $tableName): void
    {
        // Set longer timeout for large tables
        DB::statement("SET SESSION wait_timeout = 600");
        DB::statement("SET SESSION interactive_timeout = 600");

        try {
            // Convert table charset and collation in one command
            // This is more efficient than converting columns separately
            DB::statement("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (\Exception $e) {
            // If CONVERT TO fails, try column-by-column approach
            $this->warn("\nTable-level conversion failed for {$tableName}, trying column-by-column...");
            
            // Get all columns that need conversion
            $columns = DB::select("
                SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND CHARACTER_SET_NAME IS NOT NULL
                AND CHARACTER_SET_NAME != 'utf8mb4'
            ", [DB::connection()->getDatabaseName(), $tableName]);

            foreach ($columns as $column) {
                $null = $column->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
                $default = '';
                if ($column->COLUMN_DEFAULT !== null) {
                    if ($column->COLUMN_DEFAULT === 'CURRENT_TIMESTAMP') {
                        $default = "DEFAULT CURRENT_TIMESTAMP";
                    } else {
                        $default = "DEFAULT '" . addslashes($column->COLUMN_DEFAULT) . "'";
                    }
                }
                $extra = $column->EXTRA ?: '';

                // Determine column type
                $type = $this->getColumnTypeForConversion($column->COLUMN_TYPE);

                try {
                    DB::statement("
                        ALTER TABLE `{$tableName}` 
                        MODIFY COLUMN `{$column->COLUMN_NAME}` {$type} 
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci 
                        {$null} {$default} {$extra}
                    ");
                } catch (\Exception $colException) {
                    $this->warn("\nWarning: Could not convert column {$tableName}.{$column->COLUMN_NAME}: " . $colException->getMessage());
                }
            }
        }
    }

    /**
     * Get column type for conversion (preserve original type)
     */
    private function getColumnTypeForConversion(string $columnType): string
    {
        // Remove charset/collation info if present, keep the base type
        $type = preg_replace('/\s+(CHARACTER SET|COLLATE)\s+\w+/i', '', $columnType);
        return trim($type);
    }
}
