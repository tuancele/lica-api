<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add variant_id column to productsales table to support Flash Sale for individual variants
     */
    public function up(): void
    {
        if (Schema::hasTable('productsales')) {
            Schema::table('productsales', function (Blueprint $table) {
                // Add variant_id column if it doesn't exist
                if (!Schema::hasColumn('productsales', 'variant_id')) {
                    // Use integer to match variants.id type (int)
                    $table->integer('variant_id')->unsigned()->nullable()->after('product_id');
                    
                    // Add index for better query performance
                    $table->index(['flashsale_id', 'variant_id'], 'productsales_flashsale_variant_index');
                }
            });
            
            // Add foreign key separately using raw SQL to avoid type mismatch
            if (Schema::hasTable('variants') && Schema::hasTable('productsales') && Schema::hasColumn('productsales', 'variant_id')) {
                try {
                    DB::statement('ALTER TABLE productsales ADD CONSTRAINT productsales_variant_id_foreign 
                        FOREIGN KEY (variant_id) REFERENCES variants(id) ON DELETE CASCADE');
                } catch (\Exception $e) {
                    // Foreign key might already exist or type mismatch - continue without it
                    // Index is more important for performance
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('productsales')) {
            Schema::table('productsales', function (Blueprint $table) {
                if (Schema::hasColumn('productsales', 'variant_id')) {
                    // Drop index first
                    $table->dropIndex('productsales_flashsale_variant_index');
                    
                    // Drop foreign key if exists
                    if (Schema::hasTable('variants')) {
                        $table->dropForeign(['variant_id']);
                    }
                    
                    // Drop column
                    $table->dropColumn('variant_id');
                }
            });
        }
    }
};
