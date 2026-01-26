<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Inventory\InventoryService;
use App\Services\Pricing\PriceEngineService;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

/**
 * Unit Test cho Flash Sale Mixed Pricing.
 *
 * Test Scenario 2: Mua vượt hạn mức Flash Sale
 * - Sản phẩm A có 100 tồn kho
 * - Flash Sale 5 sản phẩm giá 100k, giá thường 150k
 * - User đặt mua 15 sản phẩm
 *
 * Kỳ vọng:
 * 1. Tổng tiền = (5 × 100k) + (10 × 150k) = 2.000k
 * 2. Tồn kho Flash Sale (buy) = 5
 * 3. Tồn kho thực tế (S_phy) = 85
 * 4. Có trả về warning trong kết quả
 */
class FlashSaleMixedPriceTest extends TestCase
{
    use DatabaseTransactions;

    protected PriceEngineService $priceEngine;
    protected InventoryService $inventoryService;
    protected $warehouseServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();

        // Mock WarehouseService
        $this->warehouseServiceMock = Mockery::mock(WarehouseServiceInterface::class);
        $this->app->instance(WarehouseServiceInterface::class, $this->warehouseServiceMock);

        // Khởi tạo services
        $this->priceEngine = app(PriceEngineService::class);
        $this->inventoryService = app(InventoryService::class);
    }

    private function ensureTables(): void
    {
        if (! Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('slug')->nullable();
                $table->string('type')->nullable();
                $table->string('status')->nullable();
                $table->unsignedTinyInteger('has_variants')->default(0);
                $table->text('cat_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('variants')) {
            Schema::create('variants', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('product_id');
                $table->string('sku')->nullable();
                $table->string('option1_value')->nullable();
                $table->string('image')->nullable();
                $table->unsignedInteger('size_id')->default(0);
                $table->unsignedInteger('color_id')->default(0);
                $table->decimal('weight', 10, 2)->default(0);
                $table->unsignedInteger('price')->default(0);
                $table->unsignedInteger('stock')->default(0);
                $table->integer('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('flashsales')) {
            Schema::create('flashsales', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('status')->default('1');
                $table->unsignedInteger('start')->default(0);
                $table->unsignedInteger('end')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('productsales')) {
            Schema::create('productsales', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('flashsale_id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('variant_id')->nullable();
                $table->unsignedInteger('price_sale')->default(0);
                $table->unsignedInteger('number')->default(0);
                $table->unsignedInteger('buy')->default(0);
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test Scenario 2: Mua vượt hạn mức Flash Sale.
     */
    public function test_mixed_pricing_when_exceeding_flash_sale_limit(): void
    {
        // Arrange: Tạo dữ liệu test
        try {
            $now = time();

            // Make test deterministic: disable any existing active flash sales
            DB::table('flashsales')->update(['status' => '0']);

            $productId = (int) DB::table('posts')->insertGetId([
                'name' => 'Product A',
                'slug' => 'product-a-'.$now,
                'type' => 'product',
                'status' => '1',
                'has_variants' => 0,
                'cat_id' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $variantId = (int) DB::table('variants')->insertGetId([
                'product_id' => $productId,
                'sku' => 'SKU-A-'.$now,
                'price' => 150000, // Normal 150k
                'stock' => 100,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $flashSaleRow = [
                'status' => '1',
                'start' => $now - 3600,
                'end' => $now + 3600,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('flashsales', 'name')) {
                $flashSaleRow['name'] = 'Flash Sale A';
            }
            $flashSaleId = (int) DB::table('flashsales')->insertGetId($flashSaleRow);

            $productSaleId = (int) DB::table('productsales')->insertGetId([
                'flashsale_id' => $flashSaleId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'price_sale' => 100000,
                'number' => 5,
                'buy' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Sanity: ensure Flash Sale data is visible for PriceEngine
            $activeFlashId = DB::table('flashsales')
                ->where('status', '1')
                ->where('start', '<=', time())
                ->where('end', '>=', time())
                ->value('id');
            $this->assertNotNull($activeFlashId, 'FlashSale must be active in DB');

            $psExists = DB::table('productsales')
                ->where('flashsale_id', $activeFlashId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->exists();
            $this->assertTrue($psExists, 'ProductSale must exist for active FlashSale');

            // Mock WarehouseService để trả về tồn kho 100
            $this->warehouseServiceMock
                ->shouldReceive('getVariantStock')
                ->with($variantId)
                ->andReturn([
                    'current_stock' => 100,
                    'import_total' => 100,
                    'export_total' => 0,
                ]);

            $this->warehouseServiceMock
                ->shouldReceive('deductStock')
                ->with($variantId, 15, 'flashsale_order')
                ->once();

            // Act: Tính giá với số lượng 15
            $priceResult = $this->priceEngine->calculatePriceWithQuantity(
                $productId,
                $variantId,
                15
            );

            // Assert 1: Kiểm tra tổng tiền
            $expectedTotal = (5 * 100000) + (10 * 150000); // 500k + 1.500k = 2.000k
            $this->assertEquals($expectedTotal, $priceResult['total_price'], 'Tổng tiền phải là 2.000k');

            // Assert: Kiểm tra price_breakdown
            $this->assertCount(2, $priceResult['price_breakdown'], 'Phải có 2 loại giá trong breakdown');

            $flashBreakdown = $priceResult['price_breakdown'][0];
            $this->assertEquals('flashsale', $flashBreakdown['type']);
            $this->assertEquals(5, $flashBreakdown['quantity']);
            $this->assertEquals(100000, $flashBreakdown['unit_price']);
            $this->assertEquals(500000, $flashBreakdown['subtotal']);

            $normalBreakdown = $priceResult['price_breakdown'][1];
            $this->assertEquals('normal', $normalBreakdown['type']);
            $this->assertEquals(10, $normalBreakdown['quantity']);
            $this->assertEquals(150000, $normalBreakdown['unit_price']);
            $this->assertEquals(1500000, $normalBreakdown['subtotal']);

            // Assert: Kiểm tra warning
            $this->assertNotNull($priceResult['warning'], 'Phải có warning khi mua vượt mức');
            $this->assertStringContainsString('5 sản phẩm giá Flash Sale', $priceResult['warning']);
            $this->assertStringContainsString('10 sản phẩm', $priceResult['warning']);

            // Act: Xử lý đơn hàng
            $orderItems = [
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => 15,
                    'order_type' => 'flashsale',
                ],
            ];

            $processResult = $this->inventoryService->processOrder($orderItems);

            // Assert: Kiểm tra kết quả xử lý đơn hàng
            $this->assertTrue($processResult['success'], 'Xử lý đơn hàng phải thành công');

            // Assert 2: Kiểm tra tồn kho Flash Sale (buy phải = 5)
            $buy = (int) DB::table('productsales')->where('id', $productSaleId)->value('buy');
            $this->assertEquals(5, $buy, 'Tồn kho Flash Sale (buy) phải bằng 5');

            // Assert 3: Kiểm tra tồn kho thực tế (S_phy phải còn 85)
            // WarehouseService đã được mock để deductStock, nên ta kiểm tra qua mock
            $this->warehouseServiceMock
                ->shouldHaveReceived('deductStock')
                ->with($variantId, 15, 'flashsale_order')
                ->once();

            // Assert 4: Kiểm tra warning trong kết quả
            $this->assertArrayHasKey('warnings', $processResult, 'Kết quả phải có warnings');
            $this->assertNotEmpty($processResult['warnings'], 'Phải có ít nhất 1 warning');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Test: Mua trong hạn mức Flash Sale (không vượt mức).
     */
    public function test_normal_pricing_when_within_flash_sale_limit(): void
    {
        // Arrange
        try {
            $now = time();

            // Make test deterministic: disable any existing active flash sales
            DB::table('flashsales')->update(['status' => '0']);

            $productId = (int) DB::table('posts')->insertGetId([
                'name' => 'Product B',
                'slug' => 'product-b-'.$now,
                'type' => 'product',
                'status' => '1',
                'has_variants' => 0,
                'cat_id' => '[]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $variantId = (int) DB::table('variants')->insertGetId([
                'product_id' => $productId,
                'sku' => 'SKU-B-'.$now,
                'price' => 150000,
                'stock' => 100,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $flashSaleRow = [
                'status' => '1',
                'start' => $now - 3600,
                'end' => $now + 3600,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('flashsales', 'name')) {
                $flashSaleRow['name'] = 'Flash Sale B';
            }
            $flashSaleId = (int) DB::table('flashsales')->insertGetId($flashSaleRow);

            DB::table('productsales')->insert([
                'flashsale_id' => $flashSaleId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'price_sale' => 100000,
                'number' => 10,
                'buy' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->warehouseServiceMock
                ->shouldReceive('getVariantStock')
                ->with($variantId)
                ->andReturn([
                    'current_stock' => 100,
                ]);

            $this->warehouseServiceMock
                ->shouldReceive('deductStock')
                ->with($variantId, 5, 'flashsale_order')
                ->once();

            // Act: Tính giá với số lượng 5 (trong hạn mức)
            $priceResult = $this->priceEngine->calculatePriceWithQuantity(
                $productId,
                $variantId,
                5
            );

            // Assert: Tất cả tính theo giá Flash Sale
            $this->assertEquals(500000, $priceResult['total_price']); // 5 × 100k
            $this->assertCount(1, $priceResult['price_breakdown']);
            $this->assertEquals('flashsale', $priceResult['price_breakdown'][0]['type']);
            $this->assertNull($priceResult['warning'], 'Không có warning khi mua trong hạn mức');

            // Act: Xử lý đơn hàng
            $orderItems = [
                [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => 5,
                    'order_type' => 'flashsale',
                ],
            ];

            $processResult = $this->inventoryService->processOrder($orderItems);

            // Assert
            $this->assertTrue($processResult['success']);
            $buy = (int) DB::table('productsales')
                ->where('flashsale_id', $flashSaleId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->value('buy');
            $this->assertEquals(5, $buy);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
