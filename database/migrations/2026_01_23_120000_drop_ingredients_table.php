<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop legacy ingredients table if it still exists.
        // All logic has been migrated to IngredientPaulas/dictionary.
        if (Schema::hasTable('ingredients')) {
            Schema::drop('ingredients');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No automatic rollback – legacy structure intentionally removed.
    }
};
