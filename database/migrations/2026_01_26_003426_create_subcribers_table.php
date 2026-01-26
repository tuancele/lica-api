<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subcribers')) {
            Schema::create('subcribers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('email', 200)->unique();
                $table->timestamps();
                
                $table->index('email');
            });
        }
        // Table already exists, no changes needed
    }

    public function down(): void
    {
        Schema::dropIfExists('subcribers');
    }
};
