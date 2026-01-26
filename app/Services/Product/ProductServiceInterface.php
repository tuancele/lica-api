<?php

declare(strict_types=1);
namespace App\Services\Product;

use App\Modules\Product\Models\Product;

/**
 * Interface for Product Service
 * 
 * Defines the contract for product business logic operations
 */
interface ProductServiceInterface
{
    /**
     * Create a new product
     * 
     * @param array $data Product data
     * @return Product
     */
    public function createProduct(array $data): Product;

    /**
     * Update an existing product
     * 
     * @param int $id Product ID
     * @param array $data Updated product data
     * @return Product
     */
    public function updateProduct(int $id, array $data): Product;

    /**
     * Delete a product
     * 
     * @param int $id Product ID
     * @return bool
     */
    public function deleteProduct(int $id): bool;

    /**
     * Get product with all relations
     * 
     * @param int $id Product ID
     * @return Product
     */
    public function getProductWithRelations(int $id): Product;

    /**
     * Get paginated products with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProducts(array $filters = [], int $perPage = 10);
}
