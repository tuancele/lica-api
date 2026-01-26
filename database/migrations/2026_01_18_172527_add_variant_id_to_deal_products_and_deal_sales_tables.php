<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add variant_id column to deal_products and deal_sales tables to support variants.
     */
    public function up(): void
    {
        // Add variant_id to deal_products table
        if (Schema::hasTable('deal_products')) {
            Schema::table('deal_products', function (Blueprint $table) {
                if (! Schema::hasColumn('deal_products', 'variant_id')) {
                    $table->integer('variant_id')->unsigned()->nullable()->after('product_id');

                    // Add index for better query performance
                    $table->index(['deal_id', 'variant_id'], 'deal_products_deal_variant_index');
                }
            });

            // Add foreign key separately using raw SQL
            if (Schema::hasTable('variants') && Schema::hasColumn('deal_products', 'variant_id')) {
                try {
                    DB::statement('ALTER TABLE deal_products ADD CONSTRAINT deal_products_variant_id_foreign 
                        FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE SET NULL');
                } catch (\Exception $e) {
                    // Foreign key might already exist - continue without it
                }
            }
        }

        // Add variant_id to deal_sales table
        if (Schema::hasTable('deal_sales')) {
            Schema::table('deal_sales', function (Blueprint $table) {
                if (! Schema::hasColumn('deal_sales', 'variant_id')) {
                    $table->integer('variant_id')->unsigned()->nullable()->after('product_id');

                    // Add index for better query performance
                    $table->index(['deal_id', 'variant_id'], 'deal_sales_deal_variant_index');
                }
            });

            // Add foreign key separately using raw SQL
            if (Schema::hasTable('variants') && Schema::hasColumn('deal_sales', 'variant_id')) {
                try {
                    DB::statement('ALTER TABLE deal_sales ADD CONSTRAINT deal_sales_variant_id_foreign 
                        FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE SET NULL');
                } catch (\Exception $e) {
                    // Foreign key might already exist - continue without it
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove variant_id from deal_sales table
        if (Schema::hasTable('deal_sales')) {
            Schema::table('deal_sales', function (Blueprint $table) {
                if (Schema::hasColumn('deal_sales', 'variant_id')) {
                    // Drop index first
                    $table->dropIndex('deal_sales_deal_variant_index');

                    // Drop foreign key if exists
                    try {
                        $table->dropForeign(['variant_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }

                    // Drop column
                    $table->dropColumn('variant_id');
                }
            });
        }

        // Remove variant_id from deal_products table
        if (Schema::hasTable('deal_products')) {
            Schema::table('deal_products', function (Blueprint $table) {
                if (Schema::hasColumn('deal_products', 'variant_id')) {
                    // Drop index first
                    $table->dropIndex('deal_products_deal_variant_index');

                    // Drop foreign key if exists
                    try {
                        $table->dropForeign(['variant_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist
                    }

                    // Drop column
                    $table->dropColumn('variant_id');
                }
            });
        }
    }
};
