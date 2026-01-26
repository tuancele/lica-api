<?php

declare(strict_types=1);

namespace App\Services\Product;

use App\Modules\Product\Models\Product;

/**
 * Interface for Product Service.
 *
 * Defines the contract for product business logic operations
 */
interface ProductServiceInterface
{
    /**
     * Create a new product.
     *
     * @param  array  $data  Product data
     */
    public function createProduct(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param  int  $id  Product ID
     * @param  array  $data  Updated product data
     */
    public function updateProduct(int $id, array $data): Product;

    /**
     * Delete a product.
     *
     * @param  int  $id  Product ID
     */
    public function deleteProduct(int $id): bool;

    /**
     * Get product with all relations.
     *
     * @param  int  $id  Product ID
     */
    public function getProductWithRelations(int $id): Product;

    /**
     * Get paginated products with filters.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProducts(array $filters = [], int $perPage = 10);
}
