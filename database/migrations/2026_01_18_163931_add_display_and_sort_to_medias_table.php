<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add display and sort columns to medias table if they don't exist
     */
    public function up(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            // Add display column if it doesn't exist
            if (!Schema::hasColumn('medias', 'display')) {
                $table->string('display')->nullable()->after('image')->comment('Thiết bị hiển thị: desktop hoặc mobile');
            }
            
            // Add sort column if it doesn't exist
            if (!Schema::hasColumn('medias', 'sort')) {
                $table->integer('sort')->default(0)->after('display')->comment('Thứ tự sắp xếp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medias', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('medias', 'display')) {
                $table->dropColumn('display');
            }
            
            if (Schema::hasColumn('medias', 'sort')) {
                $table->dropColumn('sort');
            }
        });
    }
};
