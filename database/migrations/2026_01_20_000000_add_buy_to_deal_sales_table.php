<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('deal_sales')) {
            return;
        }

        if (!Schema::hasColumn('deal_sales', 'buy')) {
            Schema::table('deal_sales', function (Blueprint $table) {
                $table->unsignedInteger('buy')->default(0)->after('qty');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('deal_sales')) {
            return;
        }

        if (Schema::hasColumn('deal_sales', 'buy')) {
            Schema::table('deal_sales', function (Blueprint $table) {
                $table->dropColumn('buy');
            });
        }
    }
};

