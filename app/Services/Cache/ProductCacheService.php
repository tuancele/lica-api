<?php

namespace App\Services\Cache;

use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Service for Product caching
 * 
 * Handles caching strategy for products to improve performance
 */
class ProductCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const LIST_CACHE_TTL = 1800; // 30 minutes
    
    /**
     * Get product from cache or database
     * 
     * @param int $id
     * @return Product|null
     */
    public function getProduct(int $id): ?Product
    {
        return Cache::remember(
            "product:{$id}",
            self::CACHE_TTL,
            fn() => Product::with(['brand', 'origin', 'variants', 'category'])
                ->where('id', $id)
                ->first()
        );
    }
    
    /**
     * Get product with relations from cache
     * 
     * @param int $id
     * @return Product|null
     */
    public function getProductWithRelations(int $id): ?Product
    {
        return Cache::remember(
            "product:{$id}:relations",
            self::CACHE_TTL,
            fn() => Product::with(['brand', 'origin', 'variants', 'category'])
                ->where('id', $id)
                ->first()
        );
    }
    
    /**
     * Forget product cache
     * 
     * @param int $id
     * @return void
     */
    public function forgetProduct(int $id): void
    {
        Cache::forget("product:{$id}");
        Cache::forget("product:{$id}:relations");
        $this->clearListCache();
    }
    
    /**
     * Clear all product list caches
     * 
     * @return void
     */
    public function clearListCache(): void
    {
        // Clear paginated list caches
        // Only use tags if cache driver supports it
        try {
            Cache::tags(['products:list'])->flush();
        } catch (\Exception $e) {
            // Cache driver doesn't support tags, use pattern-based clearing
            // Note: This is a fallback, may not clear all list caches
            Cache::forget("products:list:*");
        }
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
        $cacheKey = 'products:list:' . md5(serialize($filters) . $perPage);
        
        // Use tags if supported, otherwise use regular cache
        try {
            return Cache::tags(['products:list'])->remember(
                $cacheKey,
                self::LIST_CACHE_TTL,
                fn() => Product::with(['brand', 'variants'])
                    ->where('type', 'product')
                    ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
                    ->when(isset($filters['cat_id']), fn($q) => $q->where('cat_id', 'like', '%' . $filters['cat_id'] . '%'))
                    ->when(isset($filters['keyword']), fn($q) => $q->where('name', 'like', '%' . $filters['keyword'] . '%'))
                    ->orderBy($filters['sort_by'] ?? 'sort', $filters['sort_order'] ?? 'desc')
                    ->paginate($perPage)
            );
        } catch (\Exception $e) {
            // Cache driver doesn't support tags, use regular cache
            return Cache::remember(
                $cacheKey,
                self::LIST_CACHE_TTL,
                fn() => Product::with(['brand', 'variants'])
                    ->where('type', 'product')
                    ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
                    ->when(isset($filters['cat_id']), fn($q) => $q->where('cat_id', 'like', '%' . $filters['cat_id'] . '%'))
                    ->when(isset($filters['keyword']), fn($q) => $q->where('name', 'like', '%' . $filters['keyword'] . '%'))
                    ->orderBy($filters['sort_by'] ?? 'sort', $filters['sort_order'] ?? 'desc')
                    ->paginate($perPage)
            );
        }
    }
}
