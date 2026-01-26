<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rates')) {
            Schema::create('rates', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->nullable();
                $table->integer('user_id')->nullable();
                $table->integer('rate')->default(0)->comment('Rating 1-5');
                $table->text('comment')->nullable();
                $table->smallInteger('status')->default(1);
                $table->timestamps();
                
                $table->index('product_id');
                $table->index('user_id');
                $table->index('status');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('rates', function (Blueprint $table) {
                if (!Schema::hasColumn('rates', 'status')) {
                    $table->smallInteger('status')->default(1)->after('comment');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
    }
};
