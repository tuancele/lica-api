<?php

namespace Tests\Feature\ApiAdmin;

use Tests\TestCase;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Enums\ProductType;
use App\Enums\ProductStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

/**
 * Product API Test Suite
 * 
 * Tests all Product API endpoints
 */
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user and get API token if needed
        // For now, we'll test without authentication by temporarily removing auth:api middleware
    }

    /**
     * Test GET /admin/api/products - List products
     */
    public function test_can_get_products_list(): void
    {
        // Create test products
        Product::factory()->count(5)->create([
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/admin/api/products?limit=5');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data',
                     'pagination' => [
                         'current_page',
                         'per_page',
                         'total',
                         'last_page'
                     ]
                 ])
                 ->assertJson(['success' => true]);
    }

    /**
     * Test GET /admin/api/products with filters
     */
    public function test_can_filter_products(): void
    {
        Product::factory()->create([
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
            'name' => 'Test Product Filter'
        ]);

        $response = $this->getJson('/admin/api/products?keyword=Test&status=1');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    /**
     * Test GET /admin/api/products/{id} - Get single product
     */
    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value
        ]);

        $response = $this->getJson("/admin/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'id',
                         'name',
                         'slug',
                         'status'
                     ]
                 ])
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id' => $product->id
                     ]
                 ]);
    }

    /**
     * Test GET /admin/api/products/{id} - Product not found
     */
    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/admin/api/products/99999');

        $response->assertStatus(404)
                 ->assertJson(['success' => false]);
    }

    /**
     * Test POST /admin/api/products - Create product
     */
    public function test_can_create_product(): void
    {
        $productData = [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'description' => 'Test description',
            'status' => '1',
            'price' => 100000,
            'sale' => 80000,
            'sku' => 'TEST-SKU-' . time(),
            'has_variants' => 0,
            'stock_qty' => 100,
            'weight' => 500
        ];

        $response = $this->postJson('/admin/api/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data'
                 ])
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'type' => ProductType::PRODUCT->value
        ]);
    }

    /**
     * Test POST /admin/api/products - Validation error
     */
    public function test_validation_error_on_create_product(): void
    {
        $response = $this->postJson('/admin/api/products', []);

        $response->assertStatus(422); // Validation error
    }

    /**
     * Test PUT /admin/api/products/{id} - Update product
     */
    public function test_can_update_product(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'id' => $product->id,
            'name' => 'Updated Name',
            'slug' => $product->slug,
            'status' => '1'
        ];

        $response = $this->putJson("/admin/api/products/{$product->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'id' => $product->id,
            'name' => 'Updated Name'
        ]);
    }

    /**
     * Test DELETE /admin/api/products/{id} - Delete product
     */
    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        $response = $this->deleteJson("/admin/api/products/{$product->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('posts', [
            'id' => $product->id
        ]);
    }

    /**
     * Test PATCH /admin/api/products/{id}/status - Update status
     */
    public function test_can_update_product_status(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value
        ]);

        $response = $this->patchJson("/admin/api/products/{$product->id}/status", [
            'status' => '0'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'id' => $product->id,
            'status' => '0'
        ]);
    }

    /**
     * Test POST /admin/api/products/bulk-action - Bulk action
     */
    public function test_can_perform_bulk_action(): void
    {
        $products = Product::factory()->count(3)->create([
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value
        ]);

        $productIds = $products->pluck('id')->toArray();

        $response = $this->postJson('/admin/api/products/bulk-action', [
            'checklist' => $productIds,
            'action' => 0 // Hide
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        foreach ($productIds as $id) {
            $this->assertDatabaseHas('posts', [
                'id' => $id,
                'status' => '0'
            ]);
        }
    }

    /**
     * Test PATCH /admin/api/products/sort - Update sort
     */
    public function test_can_update_product_sort(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        $response = $this->patchJson('/admin/api/products/sort', [
            'sort' => [
                (string)$product->id => 10
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('posts', [
            'id' => $product->id,
            'sort' => 10
        ]);
    }

    /**
     * Test GET /admin/api/products/{id}/variants - Get variants
     */
    public function test_can_get_product_variants(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        Variant::factory()->count(3)->create([
            'product_id' => $product->id
        ]);

        $response = $this->getJson("/admin/api/products/{$product->id}/variants");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id',
                             'sku',
                             'product_id',
                             'price'
                         ]
                     ]
                 ])
                 ->assertJson(['success' => true]);
    }

    /**
     * Test POST /admin/api/products/{id}/variants - Create variant
     */
    public function test_can_create_variant(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        $variantData = [
            'sku' => 'VARIANT-SKU-' . time(),
            'product_id' => $product->id,
            'price' => 120000,
            'sale' => 100000,
            'stock' => 50,
            'weight' => 600
        ];

        $response = $this->postJson("/admin/api/products/{$product->id}/variants", $variantData);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('variants', [
            'product_id' => $product->id,
            'sku' => $variantData['sku']
        ]);
    }

    /**
     * Test PUT /admin/api/products/{id}/variants/{code} - Update variant
     */
    public function test_can_update_variant(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        $variant = Variant::factory()->create([
            'product_id' => $product->id,
            'price' => 100000
        ]);

        $response = $this->putJson("/admin/api/products/{$product->id}/variants/{$variant->id}", [
            'price' => 130000
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('variants', [
            'id' => $variant->id,
            'price' => 130000
        ]);
    }

    /**
     * Test DELETE /admin/api/products/{id}/variants/{code} - Delete variant
     */
    public function test_can_delete_variant(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::PRODUCT->value
        ]);

        $variant = Variant::factory()->create([
            'product_id' => $product->id
        ]);

        $response = $this->deleteJson("/admin/api/products/{$product->id}/variants/{$variant->id}");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('variants', [
            'id' => $variant->id
        ]);
    }
}
