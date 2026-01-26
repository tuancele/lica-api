<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create variants table
 * 
 * This migration creates the variants table if it doesn't exist
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('variants')) {
            Schema::create('variants', function (Blueprint $table) {
                $table->increments('id');
                $table->string('sku')->unique();
                $table->integer('product_id');
                $table->string('image')->nullable();
                $table->integer('size_id')->default(0);
                $table->integer('color_id')->default(0);
                $table->decimal('weight', 10, 2)->default(0);
                $table->decimal('price', 15, 2)->default(0);
                $table->decimal('sale', 15, 2)->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                // Add indexes
                $table->index('product_id');
                $table->index('sku');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variants');
    }
};
