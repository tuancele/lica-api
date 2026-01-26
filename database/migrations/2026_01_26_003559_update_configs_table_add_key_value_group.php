<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('configs')) {
            Schema::table('configs', function (Blueprint $table) {
                // Add code column if it doesn't exist (configs table uses 'name' as key)
                if (!Schema::hasColumn('configs', 'code')) {
                    $table->string('code', 255)->unique()->nullable()->after('name');
                }
                // Add key column (alias for code/name)
                if (!Schema::hasColumn('configs', 'key')) {
                    $table->string('key', 255)->unique()->nullable()->after('code');
                }
                // value column already exists, but ensure it's text type
                if (!Schema::hasColumn('configs', 'content')) {
                    $table->text('content')->nullable()->after('value');
                }
                // Add group column
                if (!Schema::hasColumn('configs', 'group')) {
                    $table->string('group', 100)->default('general')->after('value');
                }
                // Add status column if missing
                if (!Schema::hasColumn('configs', 'status')) {
                    $table->smallInteger('status')->default(1)->after('group');
                }
            });
            
            // Migrate existing data: name -> code -> key, value -> value
            try {
                DB::statement("UPDATE configs SET `code` = `name` WHERE `code` IS NULL");
                DB::statement("UPDATE configs SET `key` = COALESCE(`code`, `name`) WHERE `key` IS NULL");
                DB::statement("UPDATE configs SET `group` = 'general' WHERE `group` IS NULL");
            } catch (\Exception $e) {
                // Ignore errors if columns don't exist yet
            }
        }
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            if (Schema::hasColumn('configs', 'key')) {
                $table->dropColumn('key');
            }
            if (Schema::hasColumn('configs', 'value')) {
                $table->dropColumn('value');
            }
            if (Schema::hasColumn('configs', 'group')) {
                $table->dropColumn('group');
            }
        });
    }
};
