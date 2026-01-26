<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing columns to posts table
 * 
 * This migration adds columns that are used in the refactored code
 * but may not exist in the original migration
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add gallery column if not exists
            if (!Schema::hasColumn('posts', 'gallery')) {
                $table->text('gallery')->nullable()->after('image');
            }
            
            // Add brand_id column if not exists
            if (!Schema::hasColumn('posts', 'brand_id')) {
                $table->integer('brand_id')->nullable()->after('cat_id');
            }
            
            // Add origin_id column if not exists
            if (!Schema::hasColumn('posts', 'origin_id')) {
                $table->integer('origin_id')->nullable()->after('brand_id');
            }
            
            // Add feature column if not exists
            if (!Schema::hasColumn('posts', 'feature')) {
                $table->smallInteger('feature')->default(0)->after('status');
            }
            
            // Add best column if not exists
            if (!Schema::hasColumn('posts', 'best')) {
                $table->smallInteger('best')->default(0)->after('feature');
            }
            
            // Add stock column if not exists
            if (!Schema::hasColumn('posts', 'stock')) {
                $table->smallInteger('stock')->default(1)->after('best');
            }
            
            // Add ingredient column if not exists
            if (!Schema::hasColumn('posts', 'ingredient')) {
                $table->text('ingredient')->nullable()->after('content');
            }
            
            // Add verified column if not exists
            if (!Schema::hasColumn('posts', 'verified')) {
                $table->smallInteger('verified')->default(0)->after('stock');
            }
            
            // Add cbmp column if not exists
            if (!Schema::hasColumn('posts', 'cbmp')) {
                $table->string('cbmp')->nullable()->after('content');
            }
            
            // Add sort column if not exists
            if (!Schema::hasColumn('posts', 'sort')) {
                $table->integer('sort')->default(0)->after('view');
            }
            
            // Add temp column if not exists (used for page templates)
            if (!Schema::hasColumn('posts', 'temp')) {
                $table->string('temp')->nullable()->after('type');
            }
            
            // Add is_home column if not exists
            if (!Schema::hasColumn('posts', 'is_home')) {
                $table->smallInteger('is_home')->default(0)->after('feature');
            }
            
            // Add is_new column if not exists
            if (!Schema::hasColumn('posts', 'is_new')) {
                $table->smallInteger('is_new')->default(0)->after('is_home');
            }
            
            // Add tracking column if not exists
            if (!Schema::hasColumn('posts', 'tracking')) {
                $table->smallInteger('tracking')->default(0)->after('is_new');
            }
            
            // Add tags column if not exists
            if (!Schema::hasColumn('posts', 'tags')) {
                $table->text('tags')->nullable()->after('content');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'gallery')) {
                $table->dropColumn('gallery');
            }
            if (Schema::hasColumn('posts', 'brand_id')) {
                $table->dropColumn('brand_id');
            }
            if (Schema::hasColumn('posts', 'origin_id')) {
                $table->dropColumn('origin_id');
            }
            if (Schema::hasColumn('posts', 'feature')) {
                $table->dropColumn('feature');
            }
            if (Schema::hasColumn('posts', 'best')) {
                $table->dropColumn('best');
            }
            if (Schema::hasColumn('posts', 'stock')) {
                $table->dropColumn('stock');
            }
            if (Schema::hasColumn('posts', 'ingredient')) {
                $table->dropColumn('ingredient');
            }
            if (Schema::hasColumn('posts', 'verified')) {
                $table->dropColumn('verified');
            }
            if (Schema::hasColumn('posts', 'cbmp')) {
                $table->dropColumn('cbmp');
            }
            if (Schema::hasColumn('posts', 'sort')) {
                $table->dropColumn('sort');
            }
            if (Schema::hasColumn('posts', 'temp')) {
                $table->dropColumn('temp');
            }
            if (Schema::hasColumn('posts', 'is_home')) {
                $table->dropColumn('is_home');
            }
            if (Schema::hasColumn('posts', 'is_new')) {
                $table->dropColumn('is_new');
            }
            if (Schema::hasColumn('posts', 'tracking')) {
                $table->dropColumn('tracking');
            }
            if (Schema::hasColumn('posts', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
