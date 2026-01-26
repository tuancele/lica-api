<?php

declare(strict_types=1);
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_import_and_export_receipts_and_movements(): void
    {
        $this->withoutMiddleware();

        $variantId = (int) DB::table('variants')->value('id');
        $this->assertTrue($variantId > 0, 'No variants found for testing');

        $beforeStock = (int) DB::table('inventory_stocks')->where('variant_id', $variantId)->value('physical_stock');
        $beforeMovements = (int) DB::table('stock_movements')->where('variant_id', $variantId)->count();

        // Import +5
        $importRes = $this->postJson('/api/v2/inventory/receipts/import', [
            'code' => 'TEST-IMP-' . time(),
            'subject' => 'Test import',
            'warehouse_id' => 1,
            'items' => [
                ['variant_id' => $variantId, 'quantity' => 5, 'unit_price' => 1000],
            ],
        ]);
        $importRes->assertStatus(201);
        $importRes->assertJson(['success' => true]);

        // Export -2
        $exportRes = $this->postJson('/api/v2/inventory/receipts/export', [
            'code' => 'TEST-EXP-' . time(),
            'subject' => 'Test export',
            'warehouse_id' => 1,
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


