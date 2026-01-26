<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('showrooms')) {
            Schema::create('showrooms', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->string('image')->nullable();
                $table->string('address', 500)->nullable();
                $table->string('phone', 50)->nullable();
                $table->integer('cat_id')->nullable()->comment('Group Showroom ID');
                $table->smallInteger('status')->default(1);
                $table->integer('sort')->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('status');
                $table->index('sort');
                $table->index('cat_id');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('showrooms', function (Blueprint $table) {
                if (!Schema::hasColumn('showrooms', 'sort')) {
                    $table->integer('sort')->default(0)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('showrooms');
    }
};
