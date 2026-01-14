<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // posts table (products)
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (!Schema::hasColumn('posts', 'has_variants')) {
                    $table->boolean('has_variants')->default(false)->after('type');
                }
                if (!Schema::hasColumn('posts', 'option1_name')) {
                    $table->string('option1_name', 50)->nullable()->after('has_variants');
                }
            });
        }

        // variants table (product variants)
        if (Schema::hasTable('variants')) {
            Schema::table('variants', function (Blueprint $table) {
                if (!Schema::hasColumn('variants', 'option1_value')) {
                    $table->string('option1_value', 50)->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('variants', 'stock')) {
                    $table->integer('stock')->default(0)->after('sale');
                }
                if (!Schema::hasColumn('variants', 'position')) {
                    $table->integer('position')->default(0)->after('stock');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('variants')) {
            Schema::table('variants', function (Blueprint $table) {
                if (Schema::hasColumn('variants', 'position')) {
                    $table->dropColumn('position');
                }
                if (Schema::hasColumn('variants', 'stock')) {
                    $table->dropColumn('stock');
                }
                if (Schema::hasColumn('variants', 'option1_value')) {
                    $table->dropColumn('option1_value');
                }
            });
        }

        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (Schema::hasColumn('posts', 'option1_name')) {
                    $table->dropColumn('option1_name');
                }
                if (Schema::hasColumn('posts', 'has_variants')) {
                    $table->dropColumn('has_variants');
                }
            });
        }
    }
};

