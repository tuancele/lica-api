<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('variants', 'position')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->integer('position')->default(0)->after('stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('variants', 'position')) {
            Schema::table('variants', function (Blueprint $table) {
                $table->dropColumn('position');
            });
        }
    }
};
