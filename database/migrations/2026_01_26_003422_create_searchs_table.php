<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('searchs')) {
            Schema::create('searchs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->smallInteger('status')->default(1);
                $table->integer('sort')->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('status');
                $table->index('sort');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('searchs', function (Blueprint $table) {
                if (!Schema::hasColumn('searchs', 'sort')) {
                    $table->integer('sort')->default(0)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('searchs');
    }
};
