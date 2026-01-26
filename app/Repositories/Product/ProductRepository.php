<?php

declare(strict_types=1);
namespace App\Repositories\Product;

use App\Modules\Product\Models\Product;
use App\Enums\ProductType;
use App\Enums\ProductStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository for Product data access
 * 
 * This repository handles all database operations for products,
 * separating data access logic from business logic.
 */
class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private Product $model
    ) {}

    /**
     * Find product by ID
     * 
     * @param int $id
     * @return Product|null
     */
    public function find(int $id): ?Product
    {
        return $this->model->find($id);
    }

    /**
     * Find product with relations
     * 
     * @param int $id
     * @return Product|null
     */
    public function findWithRelations(int $id): ?Product
    {
        return $this->model
            ->with(['brand', 'origin', 'variants', 'category'])
            ->where('type', ProductType::PRODUCT->value)
            ->find($id);
    }

    /**
     * Create new product
     * 
     * @param array $data
     * @return Product
     */
    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    /**
     * Update product
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Delete product
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete();
    }

    /**
     * Get paginated products with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model
            ->where('type', ProductType::PRODUCT->value)
            ->with(['brand', 'variants']); // Eager load to avoid N+1 queries

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['cat_id'])) {
            $query->where('cat_id', 'like', '%' . $filters['cat_id'] . '%');
        }

        if (isset($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'sort';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage)
            ->appends($filters);
    }

    /**
     * Get all active products
     * 
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return $this->model
            ->where('type', ProductType::PRODUCT->value)
            ->where('status', ProductStatus::ACTIVE->value)
            ->with(['brand', 'variants'])
            ->orderBy('sort', 'desc')
            ->get();
    }

    /**
     * Get products by category
     * 
     * @param int $categoryId
     * @param int $limit
     * @return Collection
     */
    public function getByCategory(int $categoryId, int $limit = 10): Collection
    {
        return $this->model
            ->where('type', ProductType::PRODUCT->value)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('cat_id', 'like', '%' . $categoryId . '%')
            ->with(['brand', 'variants'])
            ->orderBy('sort', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured products
     * 
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedProducts(int $limit = 10): Collection
    {
        return $this->model
            ->where('type', ProductType::PRODUCT->value)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('feature', '1')
            ->with(['brand', 'variants'])
            ->orderBy('sort', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Search products
     * 
     * @param string $keyword
     * @param int $limit
     * @return Collection
     */
    public function search(string $keyword, int $limit = 20): Collection
    {
        return $this->model
            ->where('type', ProductType::PRODUCT->value)
            ->where('status', ProductStatus::ACTIVE->value)
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->with(['brand', 'variants'])
            ->orderBy('sort', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug
     * @param int|null $exceptId
     * @return bool
     */
    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = $this->model->where('slug', $slug);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        
        return $query->exists();
    }
}
