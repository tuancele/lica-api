<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Emollient category if not exists
        $exists = DB::table('ingredient_category')
            ->where('name', 'Emollient')
            ->exists();

        if (! $exists) {
            $maxSort = DB::table('ingredient_category')->max('sort') ?? 0;
            DB::table('ingredient_category')->insert([
                'name' => 'Emollient',
                'status' => '1',
                'sort' => $maxSort + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Emollient category
        DB::table('ingredient_category')
            ->where('name', 'Emollient')
            ->delete();
    }
};
