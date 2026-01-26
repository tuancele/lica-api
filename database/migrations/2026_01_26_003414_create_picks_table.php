<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('picks')) {
            Schema::create('picks', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->string('address', 500)->nullable();
                $table->string('tel', 50)->nullable();
                $table->integer('province_id')->nullable();
                $table->integer('district_id')->nullable();
                $table->integer('ward_id')->nullable();
                $table->integer('cat_id')->nullable()->comment('Category/Group ID');
                $table->smallInteger('status')->default(1);
                $table->integer('sort')->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('status');
                $table->index('sort');
                $table->index('province_id');
                $table->index('district_id');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('picks', function (Blueprint $table) {
                if (!Schema::hasColumn('picks', 'cat_id')) {
                    $table->integer('cat_id')->nullable()->after('ward_id');
                }
                if (!Schema::hasColumn('picks', 'sort')) {
                    $table->integer('sort')->default(0)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('picks');
    }
};
