<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Create website table
 *
 * This migration creates the website table for storing header/footer blocks
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (! Schema::hasTable('website')) {
            Schema::create('website', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code')->unique();
                $table->text('block_0')->nullable();
                $table->text('block_1')->nullable();
                $table->text('block_2')->nullable();
                $table->text('block_3')->nullable();
                $table->text('block_4')->nullable();
                $table->text('block_5')->nullable();
                $table->text('block_6')->nullable();
                $table->text('block_7')->nullable();
                $table->text('block_8')->nullable();
                $table->text('block_9')->nullable();
                $table->integer('user_id')->nullable();
                $table->timestamps();

                $table->index('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('website');
    }
};
