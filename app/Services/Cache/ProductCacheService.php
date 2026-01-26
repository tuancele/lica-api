<?php

declare(strict_types=1);
namespace App\Services\Cache;

use App\Modules\Product\Models\Product;

/**
 * Service for Product caching
 * 
 * Handles caching strategy for products to improve performance
 */
class ProductCacheService
{
    // Bypass cache for real-time data integrity
    
    /**
     * Get product from cache or database
     * 
     * @param int $id
     * @return Product|null
     */
    public function getProduct(int $id): ?Product
    {
        return Product::with(['brand', 'origin', 'variants', 'category'])
            ->where('id', $id)
            ->first();
    }
    
    /**
     * Get product with relations from cache
     * 
     * @param int $id
     * @return Product|null
     */
    public function getProductWithRelations(int $id): ?Product
    {
        return Product::with(['brand', 'origin', 'variants', 'category'])
            ->where('id', $id)
            ->first();
    }
    
    /**
     * Forget product cache
     * 
     * @param int $id
     * @return void
     */
    public function forgetProduct(int $id): void
    {
        // no-op (cache disabled)
    }
    
    /**
     * Clear all product list caches
     * 
     * @return void
     */
    public function clearListCache(): void
    {
        // no-op (cache disabled)
    }
    
    /**
     * Get cached products list
     * 
     * @param array $filters
     * @param int $perPage
     * @return mixed
     */
    public function getCachedProducts(array $filters = [], int $perPage = 10)
    {
        return Product::with(['brand', 'variants'])
            ->where('type', 'product')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['cat_id']), fn($q) => $q->where('cat_id', 'like', '%' . $filters['cat_id'] . '%'))
            ->when(isset($filters['keyword']), fn($q) => $q->where('name', 'like', '%' . $filters['keyword'] . '%'))
            ->orderBy($filters['sort_by'] ?? 'sort', $filters['sort_order'] ?? 'desc')
            ->paginate($perPage);
    }
}
