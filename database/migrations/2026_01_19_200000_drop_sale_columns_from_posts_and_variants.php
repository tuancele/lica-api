<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safety: normalize legacy data before dropping columns
        if (Schema::hasColumn('variants', 'sale')) {
            DB::table('variants')->update(['sale' => 0]);
        }
        if (Schema::hasColumn('posts', 'sale')) {
            DB::table('posts')->update(['sale' => 0]);
        }

        // Drop legacy sale columns
        if (Schema::hasColumn('variants', 'sale')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->dropColumn('sale');
            });
        }

        if (Schema::hasColumn('posts', 'sale')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('sale');
            });
        }
    }

    public function down(): void
    {
        // Re-add columns for rollback in local env (no data restoration)
        if (!Schema::hasColumn('variants', 'sale')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->unsignedInteger('sale')->default(0)->after('price');
            });
        }

        if (!Schema::hasColumn('posts', 'sale')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->unsignedInteger('sale')->default(0);
            });
        }
    }
};

