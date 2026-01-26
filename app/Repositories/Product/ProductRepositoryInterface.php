<?php

declare(strict_types=1);

namespace App\Repositories\Product;

use App\Modules\Product\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for Product Repository.
 *
 * Defines the contract for product data access operations
 */
interface ProductRepositoryInterface
{
    /**
     * Find product by ID.
     */
    public function find(int $id): ?Product;

    /**
     * Find product with relations.
     */
    public function findWithRelations(int $id): ?Product;

    /**
     * Create new product.
     */
    public function create(array $data): Product;

    /**
     * Update product.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete product.
     */
    public function delete(int $id): bool;

    /**
     * Get paginated products with filters.
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all active products.
     */
    public function getActiveProducts(): Collection;

    /**
     * Get products by category.
     */
    public function getByCategory(int $categoryId, int $limit = 10): Collection;

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(int $limit = 10): Collection;

    /**
     * Search products.
     */
    public function search(string $keyword, int $limit = 20): Collection;

    /**
     * Check if slug exists.
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool;
}
