<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Add indexes to posts table for better query performance
 *
 * This migration adds indexes to commonly queried columns
 * to improve database performance
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add index for type and status (commonly used together)
            if (! $this->hasIndex('posts', 'posts_type_status_index') && $this->hasColumn('posts', 'type') && $this->hasColumn('posts', 'status')) {
                $table->index(['type', 'status'], 'posts_type_status_index');
            }

            // Add index for slug (used in unique checks and lookups) - slug already has unique index
            // Skip if already unique

            // Add index for cat_id (used in category filtering)
            // cat_id hiện đang là TEXT/JSON nên không thể tạo index thông thường trên MySQL cũ
            // để tránh lỗi "BLOB/TEXT column used in key specification without a key length"
            // chúng ta bỏ qua index này (nếu cần tối ưu sau sẽ xử lý lại bằng FULLTEXT hoặc length cụ thể).

            // Add index for brand_id (used in brand filtering) - only if column exists
            if (! $this->hasIndex('posts', 'posts_brand_id_index') && $this->hasColumn('posts', 'brand_id')) {
                $table->index('brand_id', 'posts_brand_id_index');
            }

            // Add index for sort (used in ordering) - only if column exists
            if (! $this->hasIndex('posts', 'posts_sort_index') && $this->hasColumn('posts', 'sort')) {
                $table->index('sort', 'posts_sort_index');
            }

            // Add index for user_id (used in user filtering)
            if (! $this->hasIndex('posts', 'posts_user_id_index') && $this->hasColumn('posts', 'user_id')) {
                $table->index('user_id', 'posts_user_id_index');
            }
        });

        // Add indexes to variants table (only if table exists)
        if (Schema::hasTable('variants')) {
            Schema::table('variants', function (Blueprint $table) {
                // Add index for product_id (foreign key, used in joins)
                if (! $this->hasIndex('variants', 'variants_product_id_index') && $this->hasColumn('variants', 'product_id')) {
                    $table->index('product_id', 'variants_product_id_index');
                }

                // Add unique index for sku (only if column exists and index doesn't)
                if (! $this->hasIndex('variants', 'variants_sku_unique') && $this->hasColumn('variants', 'sku')) {
                    // Check if unique constraint already exists
                    try {
                        $table->unique('sku', 'variants_sku_unique');
                    } catch (\Exception $e) {
                        // Index might already exist, skip
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            if ($this->hasIndex('posts', 'posts_type_status_index')) {
                $table->dropIndex('posts_type_status_index');
            }
            if ($this->hasIndex('posts', 'posts_slug_index')) {
                $table->dropIndex('posts_slug_index');
            }
            if ($this->hasIndex('posts', 'posts_cat_id_index')) {
                $table->dropIndex('posts_cat_id_index');
            }
            if ($this->hasIndex('posts', 'posts_brand_id_index')) {
                $table->dropIndex('posts_brand_id_index');
            }
            if ($this->hasIndex('posts', 'posts_sort_index')) {
                $table->dropIndex('posts_sort_index');
            }
            if ($this->hasIndex('posts', 'posts_user_id_index')) {
                $table->dropIndex('posts_user_id_index');
            }
        });

        Schema::table('variants', function (Blueprint $table) {
            if ($this->hasIndex('variants', 'variants_product_id_index')) {
                $table->dropIndex('variants_product_id_index');
            }
            if ($this->hasIndex('variants', 'variants_sku_unique')) {
                $table->dropUnique('variants_sku_unique');
            }
        });
    }

    /**
     * Check if index exists.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?',
            [$databaseName, $table, $indexName]
        );

        return $result[0]->count > 0;
    }

    /**
     * Check if column exists.
     */
    private function hasColumn(string $table, string $columnName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) as count 
             FROM information_schema.columns 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND column_name = ?',
            [$databaseName, $table, $columnName]
        );

        return $result[0]->count > 0;
    }
};
