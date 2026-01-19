<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Pricing\PriceEngineService;
use App\Services\Inventory\InventoryService;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;

/**
 * Unit Test cho Flash Sale Mixed Pricing
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
    use RefreshDatabase;

    protected PriceEngineService $priceEngine;
    protected InventoryService $inventoryService;
    protected $warehouseServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock WarehouseService
        $this->warehouseServiceMock = Mockery::mock(WarehouseServiceInterface::class);
        $this->app->instance(WarehouseServiceInterface::class, $this->warehouseServiceMock);

        // Khởi tạo services
        $this->priceEngine = app(PriceEngineService::class);
        $this->inventoryService = app(InventoryService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test Scenario 2: Mua vượt hạn mức Flash Sale
     */
    public function test_mixed_pricing_when_exceeding_flash_sale_limit(): void
    {
        // Arrange: Tạo dữ liệu test
        DB::beginTransaction();

        try {
            // Tạo Product
            $product = Product::factory()->create([
                'name' => 'Sản phẩm A',
                'has_variants' => 0,
            ]);

            // Tạo Variant
            $variant = Variant::factory()->create([
                'product_id' => $product->id,
                'price' => 150000, // Giá thường 150k
                'stock' => 100, // Tồn kho 100
            ]);

            // Tạo Flash Sale
            $flashSale = FlashSale::factory()->create([
                'status' => '1',
                'start' => now()->subHour()->timestamp,
                'end' => now()->addHour()->timestamp,
            ]);

            // Tạo ProductSale với 5 sản phẩm Flash Sale
            $productSale = ProductSale::factory()->create([
                'flashsale_id' => $flashSale->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'price_sale' => 100000, // Giá Flash Sale 100k
                'number' => 5, // Flash stock limit = 5
                'buy' => 0, // Chưa bán gì
            ]);

            // Mock WarehouseService để trả về tồn kho 100
            $this->warehouseServiceMock
                ->shouldReceive('getVariantStock')
                ->with($variant->id)
                ->andReturn([
                    'current_stock' => 100,
                    'import_total' => 100,
                    'export_total' => 0,
                ]);

            $this->warehouseServiceMock
                ->shouldReceive('deductStock')
                ->with($variant->id, 15, 'flashsale_order')
                ->once();

            // Act: Tính giá với số lượng 15
            $priceResult = $this->priceEngine->calculatePriceWithQuantity(
                $product->id,
                $variant->id,
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
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 15,
                    'order_type' => 'flashsale',
                ]
            ];

            $processResult = $this->inventoryService->processOrder($orderItems);

            // Assert: Kiểm tra kết quả xử lý đơn hàng
            $this->assertTrue($processResult['success'], 'Xử lý đơn hàng phải thành công');

            // Assert 2: Kiểm tra tồn kho Flash Sale (buy phải = 5)
            $productSale->refresh();
            $this->assertEquals(5, $productSale->buy, 'Tồn kho Flash Sale (buy) phải bằng 5');

            // Assert 3: Kiểm tra tồn kho thực tế (S_phy phải còn 85)
            // WarehouseService đã được mock để deductStock, nên ta kiểm tra qua mock
            $this->warehouseServiceMock
                ->shouldHaveReceived('deductStock')
                ->with($variant->id, 15, 'flashsale_order')
                ->once();

            // Assert 4: Kiểm tra warning trong kết quả
            $this->assertArrayHasKey('warnings', $processResult, 'Kết quả phải có warnings');
            $this->assertNotEmpty($processResult['warnings'], 'Phải có ít nhất 1 warning');

            DB::rollBack();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Test: Mua trong hạn mức Flash Sale (không vượt mức)
     */
    public function test_normal_pricing_when_within_flash_sale_limit(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $product = Product::factory()->create([
                'name' => 'Sản phẩm B',
                'has_variants' => 0,
            ]);

            $variant = Variant::factory()->create([
                'product_id' => $product->id,
                'price' => 150000,
                'stock' => 100,
            ]);

            $flashSale = FlashSale::factory()->create([
                'status' => '1',
                'start' => now()->subHour()->timestamp,
                'end' => now()->addHour()->timestamp,
            ]);

            $productSale = ProductSale::factory()->create([
                'flashsale_id' => $flashSale->id,
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'price_sale' => 100000,
                'number' => 10,
                'buy' => 0,
            ]);

            $this->warehouseServiceMock
                ->shouldReceive('getVariantStock')
                ->with($variant->id)
                ->andReturn([
                    'current_stock' => 100,
                ]);

            $this->warehouseServiceMock
                ->shouldReceive('deductStock')
                ->with($variant->id, 5, 'flashsale_order')
                ->once();

            // Act: Tính giá với số lượng 5 (trong hạn mức)
            $priceResult = $this->priceEngine->calculatePriceWithQuantity(
                $product->id,
                $variant->id,
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
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 5,
                    'order_type' => 'flashsale',
                ]
            ];

            $processResult = $this->inventoryService->processOrder($orderItems);

            // Assert
            $this->assertTrue($processResult['success']);
            $productSale->refresh();
            $this->assertEquals(5, $productSale->buy);

            DB::rollBack();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
