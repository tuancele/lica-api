<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Modules\Product\Models\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_can_list_products_via_api()
    {
        // Arrange
        Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/products');

        // Assert
        $response->assertStatus(200);
        // Note: This assumes you have API routes set up
        // You may need to create API routes first
    }

    /** @test */
    public function it_can_show_product_via_api()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/products/{$product->id}");

        // Assert
        $response->assertStatus(200);
        // Note: This assumes you have API routes set up
    }
}
