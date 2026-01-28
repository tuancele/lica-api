<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get database name
        $databaseName = DB::connection()->getDatabaseName();

        // List of important tables that should use utf8mb4
        $importantTables = [
            'posts',
            'variants',
            'categories',
            'brands',
            'medias',
            'orders',
            'orderdetail',
            'members',
            'rates',
            'tags',
            'ingredients',
            'origins',
            'promotions',
            'deals',
            'productsales',
            'saledeals',
            'flashsales',
            'marketing_campaigns',
            'marketing_campaign_products',
        ];

        foreach ($importantTables as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    // Convert table charset and collation
                    DB::statement("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                    // Convert all text/varchar columns to utf8mb4
                    $columns = DB::select("SHOW FULL COLUMNS FROM `{$tableName}`");
                    foreach ($columns as $column) {
                        $columnName = $column->Field;
                        $columnType = $column->Type;
                        $columnCollation = $column->Collation;

                        // Only convert text/varchar/char columns
                        if (in_array(strtolower($columnType), ['text', 'varchar', 'char', 'tinytext', 'mediumtext', 'longtext']) && 
                            $columnCollation && 
                            strpos($columnCollation, 'utf8mb4') === false) {
                            DB::statement("ALTER TABLE `{$tableName}` MODIFY `{$columnName}` {$columnType} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other tables
                    \Log::warning("Failed to convert table {$tableName} to utf8mb4: ".$e->getMessage());
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
        // as it would require knowing the original charset
    }
};

