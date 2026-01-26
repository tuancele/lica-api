<?php

declare(strict_types=1);
namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_can_display_product_list()
    {
        // Arrange
        Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get('/admin/product');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('Product::index');
        $response->assertViewHas('products');
    }

    /** @test */
    public function it_can_show_create_product_form()
    {
        // Act
        $response = $this->actingAs($this->user)
            ->get('/admin/product/create');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('Product::create');
    }

    /** @test */
    public function it_can_store_a_new_product()
    {
        // Arrange
        $data = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'content' => 'Product content',
            'status' => ProductStatus::ACTIVE->value,
            'price' => 100000,
            'sale' => 80000,
            'imageOther' => [],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/create', $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        
        $this->assertDatabaseHas('posts', [
            'name' => 'New Product',
            'slug' => 'new-product',
            'type' => ProductType::PRODUCT->value,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_product()
    {
        // Arrange
        $data = [];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/create', $data);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
        ]);
    }

    /** @test */
    public function it_can_show_edit_product_form()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get("/admin/product/edit/{$product->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('Product::edit');
        $response->assertViewHas('detail');
    }

    /** @test */
    public function it_can_update_a_product()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Old Product',
            'slug' => 'old-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        $data = [
            'id' => $product->id,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'content' => 'Updated content',
            'status' => ProductStatus::ACTIVE->value,
            'imageOther' => [],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/edit', $data);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        
        $this->assertDatabaseHas('posts', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/delete', ['id' => $product->id]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
        ]);
        
        $this->assertDatabaseMissing('posts', [
            'id' => $product->id,
        ]);
    }

    /** @test */
    public function it_can_update_product_status()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/status', [
                'id' => $product->id,
                'status' => ProductStatus::INACTIVE->value,
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $product->id,
            'status' => ProductStatus::INACTIVE->value,
        ]);
    }

    /** @test */
    public function it_can_perform_bulk_actions()
    {
        // Arrange
        $product1 = Product::create([
            'name' => 'Product 1',
            'slug' => 'product-1',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        $product2 = Product::create([
            'name' => 'Product 2',
            'slug' => 'product-2',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act - Hide products
        $response = $this->actingAs($this->user)
            ->postJson('/admin/product/action', [
                'checklist' => [$product1->id, $product2->id],
                'action' => 0, // Hide
            ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('posts', [
            'id' => $product1->id,
            'status' => ProductStatus::INACTIVE->value,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $product2->id,
            'status' => ProductStatus::INACTIVE->value,
        ]);
    }
}
