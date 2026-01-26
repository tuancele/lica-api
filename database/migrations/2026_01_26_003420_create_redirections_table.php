<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('redirections')) {
            Schema::create('redirections', function (Blueprint $table) {
                $table->increments('id');
                $table->string('link_from', 500);
                $table->string('link_to', 500);
                $table->string('type', 10)->default('301')->comment('301 or 302');
                $table->smallInteger('status')->default(1);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('link_from');
                $table->index('status');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('redirections', function (Blueprint $table) {
                if (!Schema::hasColumn('redirections', 'type')) {
                    $table->string('type', 10)->default('301')->after('link_to');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('redirections');
    }
};
