<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->smallInteger('status')->default(1);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('status');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('name');
                }
                if (!Schema::hasColumn('roles', 'status')) {
                    $table->smallInteger('status')->default(1)->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
