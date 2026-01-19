<?php

namespace Tests\Feature;

use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Warehouse\Models\Warehouse;
use App\Services\Inventory\InventoryServiceInterface;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DealRaceConditionTest extends TestCase
{
    /** @var int[] */
    private array $createdDealIds = [];
    /** @var int[] */
    private array $createdSaleDealIds = [];
    /** @var int[] */
    private array $createdProductIds = [];
    /** @var int[] */
    private array $createdVariantIds = [];

    private function getOrCreateUserId(): int
    {
        if (!Schema::hasTable('users')) {
            // Fallback for minimal environments
            Schema::create('users', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->timestamps();
            });
        }

        $existing = DB::table('users')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $data = [
            'name' => 'Test User',
            'email' => 'test-' . time() . '@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Some schemas may require extra fields; keep insert minimal and tolerant
        try {
            return (int) DB::table('users')->insertGetId($data);
        } catch (\Throwable $e) {
            // Try without timestamps if schema differs
            unset($data['created_at'], $data['updated_at']);
            return (int) DB::table('users')->insertGetId($data);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTables();

        // Fake Inventory service to always succeed
        $this->app->instance(InventoryServiceInterface::class, new class implements InventoryServiceInterface {
            public function processOrder(array $orderItems): array
            {
                return ['success' => true];
            }
            public function getAvailableStock(int $productId, ?int $variantId = null): int
            {
                return 10;
            }
            public function validateFlashSaleStock(int $productId, ?int $variantId, int $flashStockLimit): array
            {
                return [];
            }
        });

        // Fake PriceEngine to simplify price calculation
        $this->app->instance(PriceEngineServiceInterface::class, new class implements PriceEngineServiceInterface {
            public function calculateDisplayPrice(int $productId, ?int $variantId = null): array
            {
                return [
                    'price' => 100,
                    'original_price' => 100,
                    'type' => 'normal',
                    'label' => '',
                    'discount_percent' => 0,
                ];
            }
            public function calculatePriceWithQuantity(int $productId, ?int $variantId = null, int $quantity = 1): array
            {
                return [
                    'total_price' => 100 * $quantity,
                    'price_breakdown' => [],
                    'flash_sale_remaining' => 0,
                    'warning' => null,
                ];
            }
            public function setWarehouseService(WarehouseServiceInterface $warehouseService): void
            {
                // no-op
            }
        });

        // Fake Warehouse service (đầy đủ chữ ký)
        $this->app->instance(WarehouseServiceInterface::class, new class implements WarehouseServiceInterface {
            private function paginator(): LengthAwarePaginator
            {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
            }
            public function getInventory(array $filters = [], int $perPage = 10): LengthAwarePaginator
            {
                return $this->paginator();
            }
            public function getVariantInventory(int $variantId): array
            {
                return [];
            }
            public function getImportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator
            {
                return $this->paginator();
            }
            public function getImportReceipt(int $id): Warehouse
            {
                return new Warehouse();
            }
            public function createImportReceipt(array $data): Warehouse
            {
                return new Warehouse();
            }
            public function updateImportReceipt(int $id, array $data): Warehouse
            {
                return new Warehouse();
            }
            public function deleteImportReceipt(int $id): bool
            {
                return true;
            }
            public function getExportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator
            {
                return $this->paginator();
            }
            public function getExportReceipt(int $id): Warehouse
            {
                return new Warehouse();
            }
            public function createExportReceipt(array $data): Warehouse
            {
                return new Warehouse();
            }
            public function updateExportReceipt(int $id, array $data): Warehouse
            {
                return new Warehouse();
            }
            public function deleteExportReceipt(int $id): bool
            {
                return true;
            }
            public function searchProducts(string $keyword, int $limit = 50): array
            {
                return [];
            }
            public function getProductVariants(int $productId): array
            {
                return [];
            }
            public function getVariantStock(int $variantId): array
            {
                return ['current_stock' => 5];
            }
            public function getVariantPrice(int $variantId, string $type = 'export'): array
            {
                return ['price' => 100];
            }
            public function getQuantityStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator
            {
                return $this->paginator();
            }
            public function getRevenueStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator
            {
                return $this->paginator();
            }
            public function getSummaryStatistics(array $filters = []): array
            {
                return [];
            }
            public function deductStock(int $variantId, int $quantity, string $reason = 'order'): bool
            {
                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdSaleDealIds)) {
            DB::table('deal_sales')->whereIn('id', $this->createdSaleDealIds)->delete();
        }
        if (!empty($this->createdDealIds)) {
            DB::table('deals')->whereIn('id', $this->createdDealIds)->delete();
        }
        if (!empty($this->createdVariantIds)) {
            DB::table('variants')->whereIn('id', $this->createdVariantIds)->delete();
        }
        if (!empty($this->createdProductIds)) {
            DB::table('posts')->whereIn('id', $this->createdProductIds)->delete();
        }
        parent::tearDown();
    }

    /**
     * Tạo nhanh các bảng tối thiểu phục vụ test khi môi trường không có migrations đầy đủ.
     */
    private function ensureTables(): void
    {
        if (!Schema::hasTable('deals')) {
            Schema::create('deals', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->integer('start');
                $table->integer('end');
                $table->string('status')->default('1');
                $table->integer('limited')->default(1);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('deal_sales')) {
            Schema::create('deal_sales', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('deal_id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('variant_id')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->integer('qty')->default(0);
                $table->integer('buy')->default(0);
                $table->string('status')->default('1');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->string('slug')->unique();
                $table->tinyInteger('status')->default(1);
                $table->string('type')->default('product');
                $table->tinyInteger('has_variants')->default(0);
                $table->integer('stock')->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('variants')) {
            Schema::create('variants', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('product_id');
                $table->string('sku')->unique();
                $table->string('option1_value')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->timestamps();
            });
        }
    }

    public function test_only_one_user_can_take_last_deal_gift(): void
    {
        $now = time();
        $userId = $this->getOrCreateUserId();

        $product = Product::create([
            'name' => 'Deal Product',
            'slug' => 'deal-product-' . uniqid(),
            'status' => 1,
            'type' => 'product',
            'has_variants' => 1,
            'stock' => 10,
        ]);
        $this->createdProductIds[] = $product->id;

        $variant = Variant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-TEST',
            'option1_value' => 'Default',
            'price' => 100,
            'stock' => 5,
        ]);
        $this->createdVariantIds[] = $variant->id;

        $deal = Deal::create([
            'name' => 'Deal Test',
            'start' => $now - 10,
            'end' => $now + 3600,
            'status' => '1',
            'limited' => 1,
            'user_id' => $userId,
        ]);
        $this->createdDealIds[] = $deal->id;

        SaleDeal::create([
            'deal_id' => $deal->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'price' => 0,
            'qty' => 1,
            'buy' => 0,
            'status' => '1',
        ]);
        $this->createdSaleDealIds[] = SaleDeal::where('deal_id', $deal->id)->value('id');

        // First user succeeds
        $response1 = $this->postJson('/api/orders/process', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'order_type' => 'deal',
                ],
            ],
        ]);
        $response1->assertStatus(200)->assertJson(['success' => true]);

        // Second user fails because deal gift is exhausted
        $response2 = $this->postJson('/api/orders/process', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'order_type' => 'deal',
                ],
            ],
        ]);
        $this->assertTrue($response2->getStatusCode() >= 400);
        $this->assertEquals(1, SaleDeal::first()->buy);
    }
}

