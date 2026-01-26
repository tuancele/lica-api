<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (! Schema::hasColumn('posts', 'weight')) {
                    $table->decimal('weight', 10, 2)->default(0)->after('stock');
                }
                if (! Schema::hasColumn('posts', 'length')) {
                    $table->decimal('length', 10, 2)->default(0)->after('weight');
                }
                if (! Schema::hasColumn('posts', 'width')) {
                    $table->decimal('width', 10, 2)->default(0)->after('length');
                }
                if (! Schema::hasColumn('posts', 'height')) {
                    $table->decimal('height', 10, 2)->default(0)->after('width');
                }
            });
        }

        if (Schema::hasTable('variants')) {
            Schema::table('variants', function (Blueprint $table) {
                if (! Schema::hasColumn('variants', 'length')) {
                    $table->decimal('length', 10, 2)->default(0)->after('weight');
                }
                if (! Schema::hasColumn('variants', 'width')) {
                    $table->decimal('width', 10, 2)->default(0)->after('length');
                }
                if (! Schema::hasColumn('variants', 'height')) {
                    $table->decimal('height', 10, 2)->default(0)->after('width');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                if (Schema::hasColumn('posts', 'height')) {
                    $table->dropColumn('height');
                }
                if (Schema::hasColumn('posts', 'width')) {
                    $table->dropColumn('width');
                }
                if (Schema::hasColumn('posts', 'length')) {
                    $table->dropColumn('length');
                }
                if (Schema::hasColumn('posts', 'weight')) {
                    $table->dropColumn('weight');
                }
            });
        }

        if (Schema::hasTable('variants')) {
            Schema::table('variants', function (Blueprint $table) {
                if (Schema::hasColumn('variants', 'height')) {
                    $table->dropColumn('height');
                }
                if (Schema::hasColumn('variants', 'width')) {
                    $table->dropColumn('width');
                }
                if (Schema::hasColumn('variants', 'length')) {
                    $table->dropColumn('length');
                }
            });
        }
    }
};
