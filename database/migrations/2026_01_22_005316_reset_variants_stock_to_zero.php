<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Reset all variants.stock to 0 to remove legacy Warehouse V1 data.
     * Warehouse V2 (product_warehouse) is now the single source of truth.
     */
    public function up(): void
    {
        DB::table('variants')->update(['stock' => 0]);
    }

    /**
     * Reverse the migrations.
     * 
     * Note: Cannot restore original values, so this is a no-op.
     */
    public function down(): void
    {
        // Cannot restore original stock values
        // This migration is irreversible by design
    }
};
