<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Migration to update old products to new Shopee-style variant model
 *
 * This migration:
 * 1. Sets option1_value for variants based on color/size combination
 * 2. Sets stock for variants based on product.stock
 * 3. Sets position for variants (ordered by id)
 * 4. Updates products.has_variants and option1_name
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('variants') || ! Schema::hasTable('posts')) {
            return;
        }

        // Step 1: Update variants with option1_value, stock, and position
        // First, update stock based on product.stock
        DB::statement('
            UPDATE variants v
            INNER JOIN posts p ON v.product_id = p.id
            SET v.stock = CASE
                WHEN v.stock IS NULL OR v.stock = 0 THEN
                    CASE WHEN p.stock = 1 THEN 999 ELSE 0 END
                ELSE v.stock
            END
            WHERE v.stock IS NULL OR v.stock = 0
        ');

        // Step 2: Update option1_value based on color/size
        // Try to get color and size names if tables exist
        $hasColors = Schema::hasTable('colors');
        $hasSizes = Schema::hasTable('sizes');

        if ($hasColors && $hasSizes) {
            // Both tables exist - use names
            DB::statement("
                UPDATE variants v
                LEFT JOIN colors c ON v.color_id = c.id AND v.color_id > 0
                LEFT JOIN sizes s ON v.size_id = s.id AND v.size_id > 0
                SET v.option1_value = CASE
                    WHEN v.color_id > 0 AND v.size_id > 0 AND c.name IS NOT NULL AND s.name IS NOT NULL 
                        THEN CONCAT(c.name, ' / ', s.name)
                    WHEN v.color_id > 0 AND c.name IS NOT NULL 
                        THEN c.name
                    WHEN v.size_id > 0 AND s.name IS NOT NULL 
                        THEN s.name
                    ELSE 'Mặc định'
                END
                WHERE v.option1_value IS NULL OR v.option1_value = ''
            ");
        } else {
            // Tables don't exist - use IDs or default
            DB::statement("
                UPDATE variants v
                SET v.option1_value = CASE
                    WHEN v.color_id > 0 AND v.size_id > 0 
                        THEN CONCAT('Màu ', v.color_id, ' / Size ', v.size_id)
                    WHEN v.color_id > 0 
                        THEN CONCAT('Màu ', v.color_id)
                    WHEN v.size_id > 0 
                        THEN CONCAT('Size ', v.size_id)
                    ELSE 'Mặc định'
                END
                WHERE v.option1_value IS NULL OR v.option1_value = ''
            ");
        }

        // Step 3: Update position (ordered by id within each product)
        // Use a temporary table approach to avoid MySQL error 1093
        DB::statement('
            CREATE TEMPORARY TABLE IF NOT EXISTS temp_variant_positions AS
            SELECT 
                v.id,
                (SELECT COUNT(*) 
                 FROM variants v2 
                 WHERE v2.product_id = v.product_id 
                 AND v2.id <= v.id) - 1 as new_position
            FROM variants v
        ');

        DB::statement('
            UPDATE variants v
            INNER JOIN temp_variant_positions t ON v.id = t.id
            SET v.position = t.new_position
            WHERE v.position IS NULL
        ');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS temp_variant_positions');

        // Step 4: Update products: set has_variants = 1 if product has variants
        DB::statement("
            UPDATE posts p
            SET 
                p.has_variants = CASE
                    WHEN EXISTS (
                        SELECT 1 FROM variants v 
                        WHERE v.product_id = p.id
                    ) THEN 1
                    ELSE 0
                END,
                p.option1_name = CASE
                    WHEN EXISTS (
                        SELECT 1 FROM variants v 
                        WHERE v.product_id = p.id
                    ) THEN 'Phân loại'
                    ELSE NULL
                END
            WHERE p.type = 'product'
            AND (p.has_variants IS NULL OR p.has_variants = 0)
        ");

        // Step 5: For products without variants, create a default variant
        // Only create if product doesn't have any variant yet
        DB::statement("
            INSERT INTO variants (sku, product_id, option1_value, image, size_id, color_id, weight, price, sale, stock, position, user_id, created_at, updated_at)
            SELECT 
                CONCAT('SKU-', p.id, '-', UNIX_TIMESTAMP()) as sku,
                p.id as product_id,
                'Mặc định' as option1_value,
                p.image as image,
                0 as size_id,
                0 as color_id,
                0 as weight,
                0 as price,
                0 as sale,
                CASE WHEN p.stock = 1 THEN 999 ELSE 0 END as stock,
                0 as position,
                p.user_id as user_id,
                NOW() as created_at,
                NOW() as updated_at
            FROM posts p
            WHERE p.type = 'product'
            AND NOT EXISTS (
                SELECT 1 FROM variants v WHERE v.product_id = p.id
            )
        ");
    }

    public function down(): void
    {
        // This migration is data migration, cannot be fully reversed
        // But we can reset some fields if needed
        if (Schema::hasTable('variants')) {
            DB::statement('
                UPDATE variants 
                SET option1_value = NULL, stock = 0, position = 0
                WHERE option1_value IS NOT NULL
            ');
        }

        if (Schema::hasTable('posts')) {
            DB::statement("
                UPDATE posts 
                SET has_variants = 0, option1_name = NULL
                WHERE type = 'product'
            ");
        }
    }
};
