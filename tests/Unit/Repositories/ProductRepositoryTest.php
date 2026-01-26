<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Modules\Product\Models\Product;
use App\Repositories\Product\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product);
    }

    /** @test */
    public function it_can_find_product_by_id()
    {
        // Arrange
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $result = $this->repository->find($product->id);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Test Product', $result->name);
    }

    /** @test */
    public function it_returns_null_when_product_not_found()
    {
        // Act
        $result = $this->repository->find(999);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_create_a_product()
    {
        // Arrange
        $data = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('New Product', $result->name);
        $this->assertDatabaseHas('posts', [
            'name' => 'New Product',
            'slug' => 'new-product',
        ]);
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
            'name' => 'Updated Product',
        ];

        // Act
        $result = $this->repository->update($product->id, $data);

        // Assert
        $this->assertTrue($result);
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
        $result = $this->repository->delete($product->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('posts', [
            'id' => $product->id,
        ]);
    }

    /** @test */
    public function it_can_paginate_products_with_filters()
    {
        // Arrange
        Product::create([
            'name' => 'Active Product',
            'slug' => 'active-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        Product::create([
            'name' => 'Inactive Product',
            'slug' => 'inactive-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::INACTIVE->value,
        ]);

        $filters = ['status' => ProductStatus::ACTIVE->value];

        // Act
        $result = $this->repository->paginate($filters, 10);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Active Product', $result->first()->name);
    }

    /** @test */
    public function it_can_search_products_by_keyword()
    {
        // Arrange
        Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
            'description' => 'This is a test product',
        ]);

        Product::create([
            'name' => 'Another Product',
            'slug' => 'another-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act
        $result = $this->repository->search('Test', 10);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Test Product', $result->first()->name);
    }

    /** @test */
    public function it_can_check_if_slug_exists()
    {
        // Arrange
        Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->slugExists('test-product'));
        $this->assertFalse($this->repository->slugExists('non-existent-slug'));
    }

    /** @test */
    public function it_can_get_active_products()
    {
        // Arrange
        Product::create([
            'name' => 'Active Product',
            'slug' => 'active-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        Product::create([
            'name' => 'Inactive Product',
            'slug' => 'inactive-product',
            'type' => ProductType::PRODUCT->value,
            'status' => ProductStatus::INACTIVE->value,
        ]);

        // Act
        $result = $this->repository->getActiveProducts();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Active Product', $result->first()->name);
    }
}
