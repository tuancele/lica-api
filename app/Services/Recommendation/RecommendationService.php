<?php

declare(strict_types=1);
namespace App\Services\Recommendation;

use App\Modules\Recommendation\Models\UserBehavior;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Analytics\UserAnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class RecommendationService
{
    protected $analyticsService;

    public function __construct(UserAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * 跟踪用户行为
     */
    public function trackBehavior(int $productId, string $behaviorType, array $additionalData = [])
    {
        try {
            $sessionId = Session::getId();
            $userId = Auth::id();
            
            if ($behaviorType === UserBehavior::TYPE_VIEW) {
                $recentView = UserBehavior::where('session_id', $sessionId)
                    ->where('product_id', $productId)
                    ->where('behavior_type', UserBehavior::TYPE_VIEW)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->exists();
                
                if ($recentView) {
                    return;
                }
            }

            $product = Product::with(['brand', 'rates'])->find($productId);
            
            $categories = [];
            if ($product && $product->cat_id) {
                $catIds = json_decode($product->cat_id, true);
                if (is_array($catIds)) {
                    $categories = $catIds;
                }
            }

            $ingredients = [];
            if ($product && $product->ingredient) {
                preg_match_all('/<a[^>]*class=["\']item_ingredient["\'][^>]*>([^<]+)<\/a>/i', $product->ingredient, $matches);
                if (!empty($matches[1])) {
                    $ingredients = array_map('trim', $matches[1]);
                } else {
                    $text = strip_tags($product->ingredient);
                    $ingredients = array_map('trim', explode(',', $text));
                }
            }

            $features = [];

            $ipAddress = request()->ip();
            $ipInfo = $this->analyticsService->getIpInfo($ipAddress);
            $userAgent = request()->userAgent();
            $deviceInfo = $this->analyticsService->parseUserAgent($userAgent);
            $sessionStats = $this->analyticsService->getSessionStats($sessionId);

            UserBehavior::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'product_id' => $productId,
                'behavior_type' => $behaviorType,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'referrer' => request()->header('referer'),
                'duration' => $additionalData['duration'] ?? 0,
                'country' => $ipInfo['country'] ?? null,
                'region' => $ipInfo['region'] ?? null,
                'city' => $ipInfo['city'] ?? null,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'page_url' => request()->fullUrl(),
                'page_title' => $additionalData['page_title'] ?? null,
                'product_categories' => $categories,
                'product_brand_id' => $product->brand_id ?? null,
                'product_ingredients' => $ingredients,
                'product_features' => $features,
                'scroll_depth' => $additionalData['scroll_depth'] ?? 0,
                'clicked_product' => $additionalData['clicked_product'] ?? false,
                'viewed_gallery' => $additionalData['viewed_gallery'] ?? false,
                'read_description' => $additionalData['read_description'] ?? false,
                'session_page_views' => $sessionStats['page_views'],
                'session_start_time' => $sessionStats['start_time'],
            ]);

            $this->clearRecommendationCache($sessionId, $userId);
            Cache::forget("session_stats:{$sessionId}");
        } catch (\Exception $e) {
            \Log::error('Failed to track user behavior: ' . $e->getMessage());
        }
    }

    /**
     * 获取推荐产品
     */
    public function getRecommendedProducts(int $limit = 12, array $excludeProductIds = [], int $offset = 0)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();
        
        $cacheKey = "recommendations:{$sessionId}:" . ($userId ?? 'guest') . ":" . md5(implode(',', $excludeProductIds));
        
        // 获取所有推荐产品（带缓存）
        $allRecommendations = Cache::remember($cacheKey, 1800, function () use ($excludeProductIds, $sessionId, $userId) {
            $recommendations = collect();
            $totalLimit = 100; // 获取足够多的产品以支持分页
            
            $collaborativeProducts = $this->getCollaborativeFilteringProducts($totalLimit, $excludeProductIds, $sessionId, $userId);
            $recommendations = $recommendations->merge($collaborativeProducts);
            
            if ($recommendations->count() < $totalLimit) {
                $contentBasedProducts = $this->getContentBasedProducts($totalLimit - $recommendations->count(), $excludeProductIds, $sessionId, $userId);
                $recommendations = $recommendations->merge($contentBasedProducts);
            }
            
            if ($recommendations->count() < $totalLimit) {
                $popularProducts = $this->getPopularProducts($totalLimit - $recommendations->count(), $excludeProductIds);
                $recommendations = $recommendations->merge($popularProducts);
            }
            
            if ($recommendations->count() < $totalLimit) {
                $newProducts = $this->getNewProducts($totalLimit - $recommendations->count(), $excludeProductIds);
                $recommendations = $recommendations->merge($newProducts);
            }
            
            return $recommendations->unique('id');
        });
        
        // 应用offset和limit
        return $allRecommendations->slice($offset)->take($limit);
    }

    /**
     * 协同过滤推荐
     */
    private function getCollaborativeFilteringProducts(int $limit, array $excludeProductIds, string $sessionId, ?int $userId)
    {
        $userBehaviors = UserBehavior::where(function($query) use ($sessionId, $userId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->where('behavior_type', UserBehavior::TYPE_VIEW)
        ->with('product')
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();

        if ($userBehaviors->isEmpty()) {
            return collect();
        }

        $userViewedProducts = $userBehaviors->pluck('product_id')->unique()->toArray();
        $userPreferences = $this->analyzeUserPreferences($userBehaviors);

        $similarUsers = UserBehavior::whereIn('product_id', $userViewedProducts)
            ->where(function($query) use ($sessionId, $userId) {
                if ($userId) {
                    $query->where('user_id', '!=', $userId);
                } else {
                    $query->where('session_id', '!=', $sessionId);
                }
            })
            ->select('session_id', 'user_id', 'product_id', 'product_brand_id', 'product_categories')
            ->get()
            ->groupBy(function($item) {
                return $item->user_id ? "user_{$item->user_id}" : "session_{$item->session_id}";
            })
            ->map(function($group) use ($userViewedProducts) {
                $viewedProducts = $group->pluck('product_id')->unique()->toArray();
                $commonProducts = count(array_intersect($viewedProducts, $userViewedProducts));
                $similarity = $commonProducts / max(count($userViewedProducts), count($viewedProducts));
                
                return [
                    'user_id' => $group->first()->user_id,
                    'session_id' => $group->first()->session_id,
                    'similarity' => $similarity,
                    'viewed_products' => $viewedProducts,
                    'brands' => $group->pluck('product_brand_id')->filter()->unique()->toArray(),
                    'categories' => $group->pluck('product_categories')->flatten()->unique()->toArray(),
                ];
            })
            ->filter(function($user) {
                return $user['similarity'] >= 0.3;
            })
            ->sortByDesc('similarity')
            ->take(50);

        if ($similarUsers->isEmpty()) {
            return collect();
        }

        $similarUserIds = $similarUsers->pluck('user_id')->filter();
        $similarSessionIds = $similarUsers->pluck('session_id')->filter();

        $recommendedProductIds = UserBehavior::where(function($query) use ($similarUserIds, $similarSessionIds) {
            if ($similarUserIds->isNotEmpty()) {
                $query->whereIn('user_id', $similarUserIds);
            }
            if ($similarSessionIds->isNotEmpty()) {
                $query->orWhereIn('session_id', $similarSessionIds);
            }
        })
        ->whereNotIn('product_id', $userViewedProducts)
        ->whereNotIn('product_id', $excludeProductIds)
        ->where('behavior_type', UserBehavior::TYPE_VIEW)
        ->select('product_id', 'product_brand_id', 'product_categories', DB::raw('COUNT(*) as view_count'))
        ->groupBy('product_id', 'product_brand_id', 'product_categories')
        ->orderBy('view_count', 'desc')
        ->limit($limit * 2)
        ->get();

        $scoredProducts = $recommendedProductIds->map(function($item) use ($userPreferences) {
            $score = $item->view_count;
            
            if ($item->product_brand_id && in_array($item->product_brand_id, $userPreferences['brands'])) {
                $score *= 1.5;
            }
            
            $categories = is_array($item->product_categories) ? $item->product_categories : json_decode($item->product_categories, true) ?? [];
            $commonCategories = count(array_intersect($categories, $userPreferences['categories']));
            if ($commonCategories > 0) {
                $score *= (1 + $commonCategories * 0.2);
            }
            
            return [
                'product_id' => $item->product_id,
                'score' => $score,
            ];
        })
        ->sortByDesc('score')
        ->take($limit)
        ->pluck('product_id')
        ->toArray();

        if (empty($scoredProducts)) {
            return collect();
        }

        return $this->getProductsByIds($scoredProducts);
    }

    /**
     * 分析用户偏好
     */
    private function analyzeUserPreferences($behaviors): array
    {
        $brands = [];
        $categories = [];
        $ingredients = [];
        
        foreach ($behaviors as $behavior) {
            if ($behavior->product_brand_id) {
                $brands[] = $behavior->product_brand_id;
            }
            
            if ($behavior->product_categories) {
                $cats = is_array($behavior->product_categories) 
                    ? $behavior->product_categories 
                    : json_decode($behavior->product_categories, true) ?? [];
                $categories = array_merge($categories, $cats);
            }
        }
        
        return [
            'brands' => array_unique($brands),
            'categories' => array_unique($categories),
            'ingredients' => array_unique($ingredients),
        ];
    }

    /**
     * 基于内容的推荐
     */
    private function getContentBasedProducts(int $limit, array $excludeProductIds, string $sessionId, ?int $userId)
    {
        $recentProducts = UserBehavior::where(function($query) use ($sessionId, $userId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->where('behavior_type', UserBehavior::TYPE_VIEW)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->pluck('product_id')
        ->unique()
        ->toArray();

        if (empty($recentProducts)) {
            return collect();
        }

        $products = Product::whereIn('id', $recentProducts)
            ->where('status', '1')
            ->where('type', 'product')
            ->get();

        $categoryIds = [];
        $brandIds = [];

        foreach ($products as $product) {
            $catIds = json_decode($product->cat_id, true);
            if (is_array($catIds)) {
                $categoryIds = array_merge($categoryIds, $catIds);
            }
            if ($product->brand_id) {
                $brandIds[] = $product->brand_id;
            }
        }

        $categoryIds = array_unique($categoryIds);
        $brandIds = array_unique($brandIds);

        if (empty($categoryIds) && empty($brandIds)) {
            return collect();
        }

        $query = Product::with(['brand', 'rates'])
            ->join('variants', 'variants.product_id', '=', 'posts.id')
            ->select('posts.*', 'variants.price')
            ->where('posts.status', '1')
            ->where('posts.type', 'product')
            ->whereNotIn('posts.id', $recentProducts)
            ->whereNotIn('posts.id', $excludeProductIds)
            ->groupBy('posts.id');

        $query->where(function($q) use ($categoryIds, $brandIds) {
            if (!empty($categoryIds)) {
                foreach ($categoryIds as $catId) {
                    $q->orWhere('posts.cat_id', 'like', '%"' . $catId . '"%');
                }
            }
            if (!empty($brandIds)) {
                $q->orWhereIn('posts.brand_id', $brandIds);
            }
        });

        return $query->orderBy('posts.view', 'desc')
            ->orderBy('posts.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取热门产品
     */
    private function getPopularProducts(int $limit, array $excludeProductIds)
    {
        return Product::with(['brand', 'rates'])
            ->join('variants', 'variants.product_id', '=', 'posts.id')
            ->select('posts.*', 'variants.price')
            ->where('posts.status', '1')
            ->where('posts.type', 'product')
            ->whereNotIn('posts.id', $excludeProductIds)
            ->groupBy('posts.id')
            ->orderBy('posts.view', 'desc')
            ->orderBy('posts.feature', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取最新产品
     */
    private function getNewProducts(int $limit, array $excludeProductIds)
    {
        return Product::with(['brand', 'rates'])
            ->join('variants', 'variants.product_id', '=', 'posts.id')
            ->select('posts.*', 'variants.price')
            ->where('posts.status', '1')
            ->where('posts.type', 'product')
            ->whereNotIn('posts.id', $excludeProductIds)
            ->groupBy('posts.id')
            ->orderBy('posts.created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 根据ID获取产品
     */
    private function getProductsByIds(array $productIds)
    {
        return Product::with(['brand', 'rates'])
            ->join('variants', 'variants.product_id', '=', 'posts.id')
            ->select('posts.*', 'variants.price')
            ->whereIn('posts.id', $productIds)
            ->where('posts.status', '1')
            ->where('posts.type', 'product')
            ->groupBy('posts.id')
            ->get()
            ->sortBy(function($product) use ($productIds) {
                return array_search($product->id, $productIds);
            })
            ->values();
    }

    /**
     * 清除推荐缓存
     */
    private function clearRecommendationCache(string $sessionId, ?int $userId)
    {
        try {
            Cache::forget("recommendations:{$sessionId}:" . ($userId ?? 'guest') . ":*");
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取用户浏览历史
     */
    public function getUserViewHistory(int $limit = 20)
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        return UserBehavior::where(function($query) use ($sessionId, $userId) {
            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('session_id', $sessionId);
            }
        })
        ->where('behavior_type', UserBehavior::TYPE_VIEW)
        ->with('product')
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get()
        ->pluck('product')
        ->filter()
        ->unique('id');
    }
}
