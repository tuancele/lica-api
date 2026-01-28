<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration attempts to fix data that was stored with wrong encoding.
     * It converts data from latin1/utf8 to utf8mb4 if possible.
     */
    public function up(): void
    {
        // Important text columns that might have encoding issues
        $tablesToFix = [
            'posts' => ['name', 'slug', 'content', 'description', 'title', 'meta_description', 'meta_keyword'],
            'variants' => ['sku'],
            'categories' => ['name', 'slug', 'description'],
            'brands' => ['name', 'slug', 'description'],
            'medias' => ['name', 'content'],
            'tags' => ['name', 'slug'],
        ];

        foreach ($tablesToFix as $table => $columns) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    continue;
                }

                try {
                    // Try to fix encoding by converting from latin1 to utf8mb4
                    // This works if data was stored as latin1 but should be utf8
                    DB::statement("
                        UPDATE `{$table}` 
                        SET `{$column}` = CONVERT(CAST(CONVERT(`{$column}` USING latin1) AS BINARY) USING utf8mb4)
                        WHERE `{$column}` IS NOT NULL 
                        AND `{$column}` != ''
                        AND HEX(`{$column}`) REGEXP '^([0-9A-F]{2})*$'
                    ");
                } catch (\Exception $e) {
                    // If conversion fails, data might already be in correct format
                    // or might need manual fixing
                    \Log::info("Skipped encoding fix for {$table}.{$column}: ".$e->getMessage());
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed
    }
};

