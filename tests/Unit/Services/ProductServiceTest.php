<?php

declare(strict_types=1);
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Product\ProductService;
use App\Services\Product\ProductServiceInterface;
use App\Repositories\Product\ProductRepositoryInterface;
use App\Services\Image\ImageServiceInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ProductCreationException;
use App\Exceptions\ProductDeletionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductServiceInterface $service;
    private $repositoryMock;
    private $imageServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repositoryMock = Mockery::mock(ProductRepositoryInterface::class);
        $this->imageServiceMock = Mockery::mock(ImageServiceInterface::class);
        
        $this->service = new ProductService(
            $this->repositoryMock,
            $this->imageServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_a_product()
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'content' => 'Test content',
            'status' => ProductStatus::ACTIVE->value,
            'type' => ProductType::PRODUCT->value,
            'user_id' => 1,
        ];

        $gallery = ['image1.jpg', 'image2.jpg'];
        $product = new Product($data);
        $product->id = 1;

        $this->imageServiceMock
            ->shouldReceive('processGallery')
            ->once()
            ->andReturn($gallery);

        $this->imageServiceMock
            ->shouldReceive('getMainImage')
            ->once()
            ->andReturn('image1.jpg');

        $this->imageServiceMock
            ->shouldReceive('clearSessionUrls')
            ->once();

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($product);

        // Act
        $result = $this->service->createProduct($data);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Test Product', $result->name);
    }

    /** @test */
    public function it_throws_exception_when_creating_product_fails()
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'slug' => 'test-product',
        ];

        $this->imageServiceMock
            ->shouldReceive('processGallery')
            ->once()
            ->andReturn([]);

        $this->imageServiceMock
            ->shouldReceive('getMainImage')
            ->once()
            ->andReturn(null);

        $this->repositoryMock
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        // Act & Assert
        $this->expectException(ProductCreationException::class);
        $this->service->createProduct($data);
    }

    /** @test */
    public function it_can_update_a_product()
    {
        // Arrange
        $id = 1;
        $data = [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
        ];

        $existingProduct = new Product([
            'id' => $id,
            'name' => 'Old Product',
            'slug' => 'old-product',
        ]);

        $updatedProduct = new Product($data);
        $updatedProduct->id = $id;

        $this->repositoryMock
            ->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($existingProduct);

        $this->imageServiceMock
            ->shouldReceive('processGallery')
            ->once()
            ->andReturn([]);

        $this->imageServiceMock
            ->shouldReceive('getMainImage')
            ->once()
            ->andReturn(null);

        $this->imageServiceMock
            ->shouldReceive('clearSessionUrls')
            ->once();

        $this->repositoryMock
            ->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $this->repositoryMock
            ->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($updatedProduct);

        // Act
        $result = $this->service->updateProduct($id, $data);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Updated Product', $result->name);
    }

    /** @test */
    public function it_throws_exception_when_updating_nonexistent_product()
    {
        // Arrange
        $id = 999;
        $data = ['name' => 'Test'];

        $this->repositoryMock
            ->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(ProductNotFoundException::class);
        $this->service->updateProduct($id, $data);
    }

    /** @test */
    public function it_can_delete_a_product()
    {
        // Arrange
        $id = 1;
        $product = new Product(['id' => $id]);

        $this->repositoryMock
            ->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($product);

        $this->repositoryMock
            ->shouldReceive('delete')
            ->once()
            ->with($id)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteProduct($id);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_deleting_nonexistent_product()
    {
        // Arrange
        $id = 999;

        $this->repositoryMock
            ->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(ProductNotFoundException::class);
        $this->service->deleteProduct($id);
    }

    /** @test */
    public function it_can_get_product_with_relations()
    {
        // Arrange
        $id = 1;
        $product = new Product(['id' => $id, 'name' => 'Test Product']);

        $this->repositoryMock
            ->shouldReceive('findWithRelations')
            ->once()
            ->with($id)
            ->andReturn($product);

        // Act
        $result = $this->service->getProductWithRelations($id);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Test Product', $result->name);
    }

    /** @test */
    public function it_throws_exception_when_product_not_found()
    {
        // Arrange
        $id = 999;

        $this->repositoryMock
            ->shouldReceive('findWithRelations')
            ->once()
            ->with($id)
            ->andReturn(null);

        // Act & Assert
        $this->expectException(ProductNotFoundException::class);
        $this->service->getProductWithRelations($id);
    }

    /** @test */
    public function it_can_get_paginated_products()
    {
        // Arrange
        $filters = ['status' => ProductStatus::ACTIVE->value];
        $perPage = 10;

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            [new Product(['id' => 1])],
            1,
            $perPage,
            1,
            ['path' => '/']
        );

        $this->repositoryMock
            ->shouldReceive('paginate')
            ->once()
            ->with($filters, $perPage)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getProducts($filters, $perPage);

        // Assert
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }
}
