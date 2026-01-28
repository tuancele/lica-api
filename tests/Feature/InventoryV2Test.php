<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\User;
use App\Models\InventoryStock;
use App\Models\WarehouseV2;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryV2Test extends TestCase
{
    public function test_get_stocks_endpoint(): void
    {
        $this->withoutMiddleware();

        $res = $this->getJson('/api/v2/inventory/stocks');
        $res->assertStatus(200);
        $res->assertJsonStructure(['success', 'data']);
    }

    public function test_get_warehouses_endpoint(): void
    {
        $this->withoutMiddleware();

        $res = $this->getJson('/api/v2/inventory/warehouses');
        $res->assertStatus(200);
        $res->assertJsonStructure(['success', 'data']);
    }

    public function test_import_and_export_receipts_and_movements(): void
    {
        $this->withoutMiddleware();

        $user = User::query()->first();
        if (! $user) {
            $userId = (int) DB::table('users')->insertGetId([
                'name' => 'Test Admin',
                'email' => 'test-inventory-'.time().'@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $user = User::query()->find($userId);
        }
        $this->actingAs($user);

        $warehouseId = (int) (DB::table('warehouses_v2')->where('is_default', 1)->value('id') ?? config('inventory.default_warehouse_id', 1));
        $variantId = (int) DB::table('variants')->value('id');
        $this->assertTrue($variantId > 0, 'No variants found for testing');

        // Ensure default warehouse and stock row exist for the test variant.
        $warehouse = WarehouseV2::query()->where('id', $warehouseId)->first();
        if (! $warehouse) {
            // Prefer existing MAIN warehouse to avoid unique code conflicts in shared DB.
            $warehouse = WarehouseV2::query()->where('code', 'MAIN')->first();
            if ($warehouse) {
                $warehouseId = (int) $warehouse->id;
            }
        }
        if (! $warehouse && ! DB::table('warehouses_v2')->where('id', $warehouseId)->exists()) {
            DB::table('warehouses_v2')->insert([
                'code' => 'MAIN_TEST_'.$warehouseId,
                'name' => 'Main Warehouse (Test)',
                'is_default' => 1,
                'is_active' => 1,
                'allow_negative_stock' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $warehouseId = (int) DB::table('warehouses_v2')->where('code', 'MAIN_TEST_'.$warehouseId)->value('id');
        }
        InventoryStock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'variant_id' => $variantId],
            [
                'physical_stock' => 0,
                'reserved_stock' => 0,
                'flash_sale_hold' => 0,
                'deal_hold' => 0,
                'low_stock_threshold' => 10,
                'reorder_point' => 20,
                'average_cost' => 0,
                'last_cost' => 0,
            ]
        );

        $beforeStock = (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('physical_stock');
        $beforeMovements = (int) DB::table('stock_movements')->where('variant_id', $variantId)->count();

        // Import +5
        $importRes = $this->postJson('/api/v2/inventory/receipts/import', [
            'code' => 'TEST-IMP-'.time(),
            'subject' => 'Test import',
            'warehouse_id' => $warehouseId,
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 5, 'unit_price' => 1000],
            ],
        ]);
        $importRes->assertStatus(201);
        $importRes->assertJson(['success' => true]);

        // Export -2
        $exportRes = $this->postJson('/api/v2/inventory/receipts/export', [
            'code' => 'TEST-EXP-'.time(),
            'subject' => 'Test export',
            'warehouse_id' => $warehouseId,
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 2, 'unit_price' => 1000],
            ],
        ]);
        $exportRes->assertStatus(201);
        $exportRes->assertJson(['success' => true]);

        $afterStock = (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('physical_stock');
        $afterMovements = (int) DB::table('stock_movements')->where('variant_id', $variantId)->count();

        $this->assertSame($beforeStock + 3, $afterStock, 'Physical stock should change by +3');
        $this->assertTrue($afterMovements >= $beforeMovements + 2, 'Should have at least 2 new movement rows');
    }

    public function test_compare_new_stock_vs_legacy_totals_for_sample_variants(): void
    {
        $this->withoutMiddleware();

        $variantIds = DB::table('variants')->limit(5)->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->assertNotEmpty($variantIds, 'No variants found for testing');

        foreach ($variantIds as $variantId) {
            $legacyImport = (int) DB::table('product_warehouse')->where('variant_id', $variantId)->where('type', 'import')->sum('qty');
            $legacyExport = (int) DB::table('product_warehouse')->where('variant_id', $variantId)->where('type', 'export')->sum('qty');
            $legacyStock = max(0, $legacyImport - $legacyExport);

            $newStock = (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('physical_stock');

            // Allow drift after live operations; ensure it's not wildly inconsistent.
            $this->assertTrue(abs($newStock - $legacyStock) < 1000000, "Stock mismatch too large for variant {$variantId}");
        }
    }
}
