<?php

declare(strict_types=1);
namespace App\Repositories\Product;

use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for Product Repository
 * 
 * Defines the contract for product data access operations
 */
interface ProductRepositoryInterface
{
    /**
     * Find product by ID
     * 
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product;

    /**
     * Find product with relations
     * 
     * @param int $id
     * @return Product|null
     */
    public function findWithRelations(int $id): ?Product;

    /**
     * Create new product
     * 
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update product
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete product
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Get paginated products with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all active products
     * 
     * @return Collection
     */
    public function getActiveProducts(): Collection;

    /**
     * Get products by category
     * 
     * @param int $categoryId
     * @param int $limit
     * @return Collection
     */
    public function getByCategory(int $categoryId, int $limit = 10): Collection;

    /**
     * Get featured products
     * 
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedProducts(int $limit = 10): Collection;

    /**
     * Search products
     * 
     * @param string $keyword
     * @param int $limit
     * @return Collection
     */
    public function search(string $keyword, int $limit = 20): Collection;

    /**
     * Check if slug exists
     * 
     * @param string $slug
     * @param int|null $exceptId
     * @return bool
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool;
}
