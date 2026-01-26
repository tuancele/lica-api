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
        if (! Schema::hasTable('productsales')) {
            return;
        }

        Schema::table('productsales', function (Blueprint $table) {
            if (! Schema::hasColumn('productsales', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('buy');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('productsales')) {
            return;
        }

        Schema::table('productsales', function (Blueprint $table) {
            if (Schema::hasColumn('productsales', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
