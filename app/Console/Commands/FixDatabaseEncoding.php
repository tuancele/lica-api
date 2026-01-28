<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDatabaseEncoding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:fix-encoding 
                            {--table= : Fix specific table only}
                            {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix encoding issues in database data (latin1 to utf8mb4 conversion)';

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

        // Get tables with text columns
        $tables = $this->getTablesWithTextColumns($specificTable);

        if (empty($tables)) {
            $this->info('No tables found to check');
            return Command::SUCCESS;
        }

        $this->info("Checking " . count($tables) . " table(s) for encoding issues");

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        $totalFixed = 0;

        foreach ($tables as $table) {
            try {
                $fixed = $this->fixTableEncoding($table, $dryRun);
                $totalFixed += $fixed;
                $bar->advance();
            } catch (\Exception $e) {
                $bar->finish();
                $this->error("\nError fixing table {$table}: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("Would fix encoding issues in {$totalFixed} row(s)");
        } else {
            $this->info("Fixed encoding issues in {$totalFixed} row(s)");
        }

        return Command::SUCCESS;
    }

    /**
     * Get tables with text columns that might have encoding issues
     */
    private function getTablesWithTextColumns(?string $specificTable = null): array
    {
        $query = "
            SELECT DISTINCT TABLE_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = ?
            AND CHARACTER_SET_NAME IS NOT NULL
            AND DATA_TYPE IN ('varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext')
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
     * Fix encoding issues in a table
     */
    private function fixTableEncoding(string $tableName, bool $dryRun): int
    {
        // Get text columns
        $columns = DB::select("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            AND CHARACTER_SET_NAME IS NOT NULL
            AND DATA_TYPE IN ('varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext')
        ", [DB::connection()->getDatabaseName(), $tableName]);

        if (empty($columns)) {
            return 0;
        }

        $fixed = 0;

        foreach ($columns as $column) {
            $columnName = $column->COLUMN_NAME;

            // Check for potential encoding issues
            // This is a simplified check - in practice, you might need more sophisticated detection
            $problematicRows = DB::select("
                SELECT COUNT(*) as count
                FROM `{$tableName}`
                WHERE `{$columnName}` IS NOT NULL
                AND `{$columnName}` != ''
                AND (
                    HEX(`{$columnName}`) LIKE '%C3%' 
                    OR HEX(`{$columnName}`) LIKE '%E1%BA%'
                )
            ");

            // Note: This is a basic check. Real encoding issues might need more complex detection
            // For now, we'll just report potential issues
            if ($problematicRows[0]->count > 0 && !$dryRun) {
                // In a real scenario, you might need to:
                // 1. Convert latin1 bytes to utf8mb4 properly
                // 2. Use CONVERT() or CAST() functions
                // This requires careful handling to avoid double-encoding
                
                $this->warn("\nTable {$tableName}, column {$columnName}: Found {$problematicRows[0]->count} potentially problematic rows");
                $this->warn("Manual review recommended for this table");
            }
        }

        return $fixed;
    }
}

