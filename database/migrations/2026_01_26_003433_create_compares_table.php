<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compares')) {
            Schema::create('compares', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('store_id')->nullable();
                $table->string('name', 250);
                $table->string('brand', 250)->nullable();
                $table->decimal('price', 15, 2)->nullable();
                $table->string('link', 500)->nullable();
                $table->smallInteger('is_link')->default(0);
                $table->smallInteger('status')->default(1);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('store_id');
                $table->index('status');
            });
        }
        // Table already exists, no changes needed
    }

    public function down(): void
    {
        Schema::dropIfExists('compares');
    }
};
