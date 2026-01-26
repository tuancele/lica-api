<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->string('slug', 250)->unique()->nullable();
                $table->text('content')->nullable();
                $table->string('image')->nullable();
                $table->string('banner')->nullable();
                $table->string('logo')->nullable();
                $table->text('gallery')->nullable()->comment('JSON array of image URLs');
                $table->string('seo_title', 255)->nullable();
                $table->string('seo_description', 500)->nullable();
                $table->smallInteger('status')->default(1);
                $table->integer('sort')->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('sort');
            });
        }
        // Table already exists with all required columns - no changes needed
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
