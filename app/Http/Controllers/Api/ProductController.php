<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Brand\Models\Brand;
use App\Enums\ProductType;
use App\Enums\ProductStatus;
use App\Services\PriceCalculationService;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Product API Controller for Frontend
 * 
 * Handles product data API endpoints for homepage and public pages
 */
class ProductController extends Controller
{
    protected PriceCalculationService $priceService;
    protected WarehouseServiceInterface $warehouseService;
    
    public function __construct(
        PriceCalculationService $priceService,
        WarehouseServiceInterface $warehouseService
    ) {
        // Inject warehouse service into price service for effective stock calculation
        $priceService->setWarehouseService($warehouseService);
        $this->priceService = $priceService;
        $this->warehouseService = $warehouseService;
    }
    
    /**
     * Format image URL for API response - Always use R2 storage
     * 
     * @param string|null $image
     * @return string
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (!empty($r2Domain)) {
                return rtrim($r2Domain, '/') . '/public/image/no_image.png';
            }
            return asset('/public/image/no_image.png');
        }
        
        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = !empty($r2Domain) ? rtrim($r2Domain, '/') : '';
        
        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }
        
        // Clean input
        $image = trim($image);
        
        // Remove all occurrences of the R2 domain (http/https agnostic) to clean up duplication
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        
        // Remove protocols from image for cleaning
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        
        // 1. Fix "uploadscdn.lica.vn" concatenation bug
        // Replaces "uploads" + "cdn.lica.vn" -> "uploads/"
        $cleanPath = str_replace('uploads' . $checkR2, 'uploads/', $cleanPath);
        
        // 2. Remove domain from path globally (handles recursive/nested domain strings)
        // First try removing with trailing slash to keep path clean
        $cleanPath = str_replace($checkR2 . '/', '', $cleanPath);
        
        // Then remove domain string itself if it still exists (e.g. at end or without slash)
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        
        // Also clean local domains
        $appUrl = config('app.url', '');
        $appDomain = parse_url($appUrl, PHP_URL_HOST);
        if ($appDomain) {
            $cleanPath = str_replace($appDomain . '/', '', $cleanPath);
            $cleanPath = str_replace($appDomain, '', $cleanPath);
        }
        $cleanPath = str_replace('localhost/', '', $cleanPath);
        
        // 3. Normalize slashes (remove double slashes)
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        
        // 4. Deduplicate repeating folders (Fix for recursive "uploads/uploads/..." issue)
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = preg_replace('#(images/)+#', 'images/', $cleanPath);
        $cleanPath = preg_replace('#(upload/)+#', 'upload/', $cleanPath);
        
        $cleanPath = ltrim($cleanPath, '/');
        
        // Check if the original image was an external URL to somewhere else
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $host = parse_url($image, PHP_URL_HOST);
            $r2Host = parse_url($r2DomainClean, PHP_URL_HOST);
            $appHost = parse_url($appUrl, PHP_URL_HOST);
            
            // If host exists and is NOT our R2 and NOT our App and NOT localhost
            if ($host && $host !== $r2Host && $host !== $appHost && $host !== 'localhost' && $host !== '127.0.0.1') {
                // It's a valid external image (e.g. google image)
                return $image;
            }
        }
        
        // Build R2 URL
        return $r2DomainClean . '/' . $cleanPath;
    }
    
    /**
     * Format product for API response with brand information
     * 
     * @param mixed $product Product model or object with brand relationship
     * @param float $variantPrice
     * @param array $additionalData Additional data to include
     * @return array
     */
    private function formatProductForResponse($product, float $variantPrice, array $additionalData = []): array
    {
        // Get brand info from relationship (Eager Loading) or fallback
        $brandName = null;
        $brandSlug = null;
        
        // Check if $product is Eloquent Model (has relationLoaded method) or stdClass
        $isEloquentModel = method_exists($product, 'relationLoaded');
        
        if ($isEloquentModel && $product->relationLoaded('brand') && $product->brand) {
            // Use eager loaded brand relationship
            $brandName = $product->brand->name;
            $brandSlug = $product->brand->slug;
        } elseif (isset($product->brand_name) && !empty($product->brand_name) && $product->brand_name !== 'null') {
            // Fallback to brand_name from join (backward compatibility)
            $brandName = $product->brand_name;
            $brandSlug = $product->brand_slug ?? null;
        } elseif (!empty($product->brand_id)) {
            // Last resort: query brand if we have brand_id but no relationship
            try {
                $brand = Brand::find($product->brand_id);
                if ($brand) {
                    $brandName = $brand->name;
                    $brandSlug = $brand->slug;
                }
            } catch (\Exception $e) {
                Log::warning('获取品牌信息失败', [
                    'product_id' => $product->id,
                    'brand_id' => $product->brand_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Calculate price using PriceCalculationService (Flash Sale > Marketing Campaign > Normal)
        $priceInfo = null;
        
        try {
            // Try to get variant if we have size_id and color_id
            if (isset($product->size_id) && isset($product->color_id)) {
                $variant = Variant::where('product_id', $product->id)
                    ->where('size_id', $product->size_id)
                    ->where('color_id', $product->color_id)
                    ->first();
                
                if ($variant) {
                    $priceInfo = $this->priceService->calculateVariantPrice($variant, $product->id);
                }
            }
            
            // Fallback: use product-level calculation
            if (!$priceInfo) {
                $productModel = $isEloquentModel ? $product : Product::find($product->id);
                if ($productModel) {
                    $priceInfo = $this->priceService->calculateProductPrice($productModel);
                }
            }
        } catch (\Exception $e) {
            Log::warning('计算价格失败', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // If price calculation failed, use fallback
        if (!$priceInfo) {
            $priceInfo = (object) [
                'price' => $variantPrice,
                'original_price' => $variantPrice,
                'type' => 'normal',
                'label' => '',
                'discount_percent' => 0,
            ];
        }
        
        // Build result with price_info
        $result = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $this->formatImageUrl($product->image ?? null),
            'brand_id' => $product->brand_id ?? null,
            'brand_name' => $brandName,
            'brand_slug' => $brandSlug,
            'price' => $priceInfo->original_price ?? $variantPrice, // Original price
            'price_info' => [
                'price' => $priceInfo->price ?? $variantPrice, // Final price (after all discounts)
                'original_price' => $priceInfo->original_price ?? $variantPrice,
                'type' => $priceInfo->type ?? 'normal',
                'label' => $priceInfo->label ?? '',
                'discount_percent' => $priceInfo->discount_percent ?? 0,
            ],
            'stock' => (int) ($product->stock ?? 0),
            'best' => (int) ($product->best ?? 0),
            'is_new' => (int) ($product->is_new ?? 0),
            'size_id' => $product->size_id ?? null,
            'color_id' => $product->color_id ?? null,
        ];
        
        // Add flash_sale_info if available
        if (isset($priceInfo->flash_sale_info)) {
            $result['flash_sale'] = [
                'number' => $priceInfo->flash_sale_info->number ?? 0,
                'buy' => $priceInfo->flash_sale_info->buy ?? 0,
                'remaining' => $priceInfo->flash_sale_info->remaining ?? 0,
            ];
            $result['price_sale'] = $priceInfo->price; // Flash Sale price for backward compatibility
        }
        
        // Get warehouse stock for variant
        $warehouseStock = 0;
        $variantId = null;
        if (isset($product->size_id) && isset($product->color_id)) {
            $variant = Variant::where('product_id', $product->id)
                ->where('size_id', $product->size_id)
                ->where('color_id', $product->color_id)
                ->first();
            if ($variant) {
                $variantId = $variant->id;
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    Log::warning('Failed to get warehouse stock for variant: ' . $variant->id, [
                        'error' => $e->getMessage()
                    ]);
                    // Fallback to variant stock
                    $warehouseStock = (int) ($variant->stock ?? 0);
                }
            }
        } else {
            // No variant info - try to get first variant's stock
            try {
                $firstVariant = Variant::where('product_id', $product->id)
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();
                if ($firstVariant) {
                    $variantId = $firstVariant->id;
                    $stockData = $this->warehouseService->getVariantStock($firstVariant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } else {
                    // Fallback to product stock
                    $warehouseStock = (int) ($product->stock ?? 0);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get warehouse stock for product: ' . $product->id, [
                    'error' => $e->getMessage()
                ]);
                $warehouseStock = (int) ($product->stock ?? 0);
            }
        }
        
        // Add warehouse_stock to result
        $result['warehouse_stock'] = $warehouseStock;
        $result['is_out_of_stock'] = $warehouseStock <= 0;
        
        // Add Deal information if available
        // Frontend will handle excluding Deal voucher in Flash Sale block
        $dealInfo = $this->getActiveDeal($product->id, $variantPrice, $variantId);
        if ($dealInfo) {
            $result['deal'] = $dealInfo;
        }
        
        // Merge additional data (for flash sale, etc.) - additionalData takes precedence
        return array_merge($result, $additionalData);
    }

    /**
     * Get active deal information for a product
     * 
     * @param int $productId
     * @param float $variantPrice
     * @param int|null $variantId Optional variant ID for variant-specific deals
     * @return array|null
     */
    private function getActiveDeal(int $productId, float $variantPrice, ?int $variantId = null): ?array
    {
        try {
            $now = strtotime(date('Y-m-d H:i:s'));
            
            // Get product deal IDs - support variant_id
            $productDealQuery = ProductDeal::where('product_id', $productId)
                ->where('status', 1);
            
            // If variant_id is provided, check for variant-specific deals first
            if ($variantId) {
                $productDealQuery->where(function($q) use ($variantId) {
                    $q->where('variant_id', $variantId)
                      ->orWhereNull('variant_id');
                });
            } else {
                $productDealQuery->whereNull('variant_id');
            }
            
            $dealIds = $productDealQuery->pluck('deal_id')->toArray();
            
            if (empty($dealIds)) {
                return null;
            }
            
            // Get active deal
            $activeDeal = Deal::whereIn('id', $dealIds)
                ->where('status', 1)
                ->where('start', '<=', $now)
                ->where('end', '>=', $now)
                ->first();
            
            if (!$activeDeal) {
                return null;
            }
            
            // Get sale deal price - support variant_id
            $saleDealQuery = SaleDeal::where('deal_id', $activeDeal->id)
                ->where('product_id', $productId)
                ->where('status', 1);
            
            if ($variantId) {
                $saleDealQuery->where(function($q) use ($variantId) {
                    $q->where('variant_id', $variantId)
                      ->orWhereNull('variant_id');
                });
            } else {
                $saleDealQuery->whereNull('variant_id');
            }
            
            $saleDeal = $saleDealQuery->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 0 ELSE 1 END')
                ->first();
            
            $discountPercent = 0;
            if ($saleDeal && $saleDeal->price && $variantPrice > 0) {
                $discountPercent = round(($variantPrice - $saleDeal->price) / ($variantPrice / 100));
            }
            
            return [
                'id' => $activeDeal->id,
                'name' => $activeDeal->name ?? 'Deal sốc',
                'discount_percent' => $discountPercent,
            ];
        } catch (\Exception $e) {
            Log::error('获取 Deal 信息失败: ' . $e->getMessage(), [
                'product_id' => $productId,
                'variant_id' => $variantId
            ]);
            return null;
        }
    }
    /**
     * Get top selling products (Top sản phẩm bán chạy)
     * 
     * GET /api/products/top-selling
     * 
     * Tính toán dựa trên tổng số lượng đã bán từ tất cả đơn hàng (trừ đơn hàng đã hủy)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopSelling(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);
            
            // Bypass cache for real-time data integrity
            $result = (function () use ($limit) {
                // Base product columns (avoid duplicate rows when joining variants)
                $baseProductColumns = [
                    'posts.id',
                    'posts.stock',
                    'posts.name',
                    'posts.slug',
                    'posts.image',
                    'posts.brand_id',
                    'posts.best',
                    'posts.is_new'
                ];
                // Aggregate variant info to a single row per product
                $variantAggregateColumns = [
                    DB::raw('MIN(variants.price) as price'),
                    DB::raw('MIN(variants.size_id) as size_id'),
                    DB::raw('MIN(variants.color_id) as color_id'),
                ];
                $productSelect = array_merge($baseProductColumns, $variantAggregateColumns);

                // Get top selling products from all orders (except cancelled)
                // Tính tổng số lượng đã bán từ tất cả đơn hàng (trừ đơn hàng đã hủy - status = 4)
                $topProducts = DB::table('orderdetail')
                    ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                    ->where('orders.status', '!=', '4') // Exclude cancelled orders
                    ->whereNotNull('orderdetail.product_id')
                    ->select('orderdetail.product_id', DB::raw('SUM(orderdetail.qty) as total_sold'))
                    ->groupBy('orderdetail.product_id')
                    ->orderBy('total_sold', 'desc')
                    ->limit(100) // Lấy nhiều hơn để đảm bảo có đủ sau khi lọc
                    ->get();
                
                $topProductIds = [];
                $totalSoldMap = []; // Map product_id => total_sold
                $productsFromOrders = collect();
                
                if (!$topProducts->isEmpty()) {
                    // Create map of product_id => total_sold
                    foreach ($topProducts as $item) {
                        $topProductIds[] = $item->product_id;
                        $totalSoldMap[$item->product_id] = (int) $item->total_sold;
                    }
                    
                    // Get product details with Eager Loading for brand
                    $productsFromOrders = Product::with(['brand:id,name,slug'])
                        ->join('variants', 'variants.product_id', '=', 'posts.id')
                        ->select($productSelect)
                        ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value]])
                        ->whereIn('posts.id', $topProductIds)
                        ->groupBy($baseProductColumns)
                        ->get();
                    
                    // Add total_sold to each product and sort by original order
                    $productsFromOrders = $productsFromOrders->map(function($product) use ($totalSoldMap) {
                        $product->total_sold = $totalSoldMap[$product->id] ?? 0;
                        return $product;
                    })->sortBy(function($product) use ($topProductIds) {
                        $index = array_search($product->id, $topProductIds);
                        return $index !== false ? $index : 999;
                    })->values();
                }
                
                // Fill remaining slots if needed
                $neededCount = $limit - $productsFromOrders->count();
                if ($neededCount > 0) {
                    $bestProducts = Product::with(['brand:id,name,slug'])
                        ->join('variants', 'variants.product_id', '=', 'posts.id')
                        ->select($productSelect)
                        ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value], ['posts.best', '1']]);
                    
                    if (!empty($topProductIds)) {
                        $bestProducts->whereNotIn('posts.id', $topProductIds);
                    }
                    
                    $bestProducts = $bestProducts->groupBy($baseProductColumns)
                        ->limit($neededCount)
                        ->orderBy('posts.created_at', 'desc')
                        ->get();
                    
                    // Add total_sold = 0 for best products
                    $bestProducts = $bestProducts->map(function($product) {
                        $product->total_sold = 0;
                        return $product;
                    });
                    
                    $productsFromOrders = $productsFromOrders->merge($bestProducts);
                }
                
                // Fallback if still empty
                if ($productsFromOrders->isEmpty()) {
                    $productsFromOrders = Product::with(['brand:id,name,slug'])
                        ->join('variants', 'variants.product_id', '=', 'posts.id')
                        ->select($productSelect)
                        ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value]])
                        ->groupBy($baseProductColumns)
                        ->limit($limit)
                        ->orderBy('posts.best', 'desc')
                        ->orderBy('posts.created_at', 'desc')
                        ->get();
                    
                    // Add total_sold = 0 for fallback products
                    $productsFromOrders = $productsFromOrders->map(function($product) {
                        $product->total_sold = 0;
                        return $product;
                    });
                }
                
                return [
                    'products' => $productsFromOrders->take($limit),
                    'totalSoldMap' => $totalSoldMap
                ];
            })();
            
            $products = $result['products'];
            $totalSoldMap = $result['totalSoldMap'] ?? [];
            
            // Format products for response using optimized helper method (with PriceCalculationService)
            $formattedProducts = $products->map(function($product) use ($totalSoldMap) {
                $variantPrice = (float) $product->price;
                $formatted = $this->formatProductForResponse($product, $variantPrice);
                
                // Add total_sold information
                $formatted['total_sold'] = $product->total_sold ?? ($totalSoldMap[$product->id] ?? 0);
                $formatted['total_sold_month'] = $this->getTotalSoldThisMonth($product->id);
                
                return $formatted;
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'count' => $formattedProducts->count(),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取热销产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取热销产品失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get total sold this month for a product
     * 
     * @param int $productId
     * @return int
     */
    private function getTotalSoldThisMonth(int $productId): int
    {
        try {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            
            return (int) DB::table('orderdetail')
                ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                ->where('orderdetail.product_id', $productId)
                ->where('orders.status', '!=', '4') // Exclude cancelled
                ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
                ->sum('orderdetail.qty');
        } catch (\Exception $e) {
            Log::error('Get total sold this month failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get products by category ID
     * 
     * GET /api/products/by-category/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getByCategory(Request $request, int $id): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 20);
            
            // Bypass cache for real-time data integrity
            $products = Product::with(['brand:id,name,slug'])
                    ->join('variants', 'variants.product_id', '=', 'posts.id')
                    ->select('posts.id', 'posts.stock', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id',
                         'variants.price as price', 'variants.size_id as size_id',
                             'variants.color_id as color_id', 'posts.best', 'posts.is_new')
                    ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value], ['posts.stock', '1']])
                    ->where('posts.cat_id', 'like', '%"' . $id . '"%')
                    ->orderBy('posts.created_at', 'desc')
                    ->groupBy('posts.id', 'posts.stock', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id',
                          'variants.price', 'variants.size_id', 'variants.color_id', 
                              'posts.best', 'posts.is_new')
                    ->limit($limit)
                    ->get();
            
            // Format products for response using optimized helper method
            $formattedProducts = $products->map(function($product) {
                $variantPrice = (float) $product->price;
                return $this->formatProductForResponse($product, $variantPrice);
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'count' => $formattedProducts->count(),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取分类产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'category_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取分类产品失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get Flash Sale products
     * 
     * GET /api/products/flash-sale
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFlashSale(Request $request): JsonResponse
    {
        try {
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
            
            \Log::info('Flash Sale API Check', [
                'current_time' => $date,
                'current_date' => date('Y-m-d H:i:s'),
                'flash_found' => $flash ? $flash->id : null,
                'flash_start' => $flash ? date('Y-m-d H:i:s', $flash->start) : null,
                'flash_end' => $flash ? date('Y-m-d H:i:s', $flash->end) : null,
            ]);
            
            if (!$flash) {
                \Log::info('No active Flash Sale found');
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'flash_sale' => null,
                    'count' => 0,
                ], 200);
            }
            
            // Bypass cache for real-time data integrity
            $products = (function () use ($flash) {
                // Get ProductSales with variants support - only available ones
                $productSales = ProductSale::where('flashsale_id', $flash->id)
                    ->whereRaw('buy < number')
                    ->get();
                
                if ($productSales->isEmpty()) {
                    return collect();
                }
                
                // Get unique product IDs
                $productIds = $productSales->pluck('product_id')->unique()->toArray();
                
                // Load products with eager loading
                $products = Product::with(['brand:id,name,slug'])
                    ->where([['status', '1'], ['type', ProductType::PRODUCT->value], ['stock', '1']])
                    ->whereIn('id', $productIds)
                    ->get();
                
                $result = collect();
                
                foreach ($products as $product) {
                    // Get ProductSales for this product
                    $productSalesForProduct = $productSales->where('product_id', $product->id);
                    
                    if ($productSalesForProduct->isEmpty()) {
                        continue;
                    }
                    
                    // Check if product has variants
                    $hasVariants = $product->has_variants == 1;
                    
                    // Get brand info from product relationship
                    $brandName = null;
                    $brandSlug = null;
                    if ($product->relationLoaded('brand') && $product->brand) {
                        $brandName = $product->brand->name;
                        $brandSlug = $product->brand->slug;
                    }
                    
                    if ($hasVariants) {
                        // Product has variants - get the BEST Flash Sale variant (lowest price_sale)
                        $bestProductSale = $productSalesForProduct
                            ->where('variant_id', '!=', null)
                            ->sortBy('price_sale')
                            ->first();
                        
                        if ($bestProductSale && $bestProductSale->variant_id) {
                            $variant = Variant::where([['id', $bestProductSale->variant_id], ['product_id', $product->id]])->first();
                            if ($variant) {
                                $productData = (object) [
                                    'id' => $product->id,
                                    'stock' => $product->stock,
                                    'name' => $product->name,
                                    'slug' => $product->slug,
                                    'image' => $product->image,
                                    'brand_id' => $product->brand_id,
                                    'brand_name' => $brandName,
                                    'brand_slug' => $brandSlug,
                                    'price' => $variant->price,
                                    'size_id' => $variant->size_id,
                                    'color_id' => $variant->color_id,
                                    'price_sale' => $bestProductSale->price_sale,
                                    'number' => $bestProductSale->number,
                                    'buy' => $bestProductSale->buy,
                                    'best' => $product->best ?? 0,
                                    'is_new' => $product->is_new ?? 0,
                                ];
                                $result->push($productData);
                            }
                        }
                    } else {
                        // Product without variants - use first variant and product-level Flash Sale
                        $productSale = $productSalesForProduct->whereNull('variant_id')->first();
                        if (!$productSale) {
                            $productSale = $productSalesForProduct->first();
                        }
                        
                        if ($productSale) {
                            $variant = Variant::where('product_id', $product->id)->first();
                            if ($variant) {
                                $productData = (object) [
                                    'id' => $product->id,
                                    'stock' => $product->stock,
                                    'name' => $product->name,
                                    'slug' => $product->slug,
                                    'image' => $product->image,
                                    'brand_id' => $product->brand_id,
                                    'brand_name' => $brandName,
                                    'brand_slug' => $brandSlug,
                                    'price' => $variant->price,
                                    'size_id' => $variant->size_id,
                                    'color_id' => $variant->color_id,
                                    'price_sale' => $productSale->price_sale,
                                    'number' => $productSale->number,
                                    'buy' => $productSale->buy,
                                    'best' => $product->best ?? 0,
                                    'is_new' => $product->is_new ?? 0,
                                ];
                                $result->push($productData);
                            }
                        }
                    }
                }
                
                // Limit to 20 products for homepage
                return $result->sortByDesc(function($item) {
                    return $item->id;
                })->take(20)->values();
            })();
            
            // Format products for response using optimized helper method
            $formattedProducts = $products->map(function($product) {
                $variantPrice = (float) $product->price;
                $additionalData = [
                    'price_sale' => (float) ($product->price_sale ?? 0),
                    'flash_sale' => [
                        'number' => (int) ($product->number ?? 0),
                        'buy' => (int) ($product->buy ?? 0),
                        'remaining' => (int) (($product->number ?? 0) - ($product->buy ?? 0)),
                    ],
                ];
                return $this->formatProductForResponse($product, $variantPrice, $additionalData);
            });
            
            // Calculate total products in Flash Sale (unique product IDs)
            $totalProductsInFlashSale = ProductSale::where('flashsale_id', $flash->id)
                ->distinct('product_id')
                ->count('product_id');
            
            $response = [
                'success' => true,
                'data' => $formattedProducts,
                'flash_sale' => [
                    'id' => $flash->id,
                    'name' => $flash->name ?? "Flash Sale #{$flash->id}",
                    'start' => $flash->start,
                    'end' => $flash->end,
                    'end_date' => date('Y-m-d H:i:s', $flash->end), // ISO format for JavaScript Date parsing
                    'end_timestamp' => $flash->end,
                    'total_products' => $totalProductsInFlashSale, // Total unique products in Flash Sale
                ],
                'count' => $formattedProducts->count(), // Number of products returned in this response
            ];
            
            \Log::info('Flash Sale API Response', [
                'flash_id' => $flash->id,
                'products_count' => $formattedProducts->count(),
                'has_data' => $formattedProducts->count() > 0,
            ]);
            
            return response()->json($response, 200);
            
        } catch (\Exception $e) {
            Log::error('获取Flash Sale产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取Flash Sale产品失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get product price info (for frontend rendering)
     * 
     * GET /api/products/{id}/price-info
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getPriceInfo(int $id): JsonResponse
    {
        try {
            $product = Product::find($id);
            
            if (!$product || $product->type !== ProductType::PRODUCT->value) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Get price info using Product model's accessor
            $priceInfo = $product->price_info;
            
            // Get variant for base price
            $variant = Variant::where('product_id', $id)->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'price' => $variant ? (float) $variant->price : 0,
                    'price_info' => $priceInfo ? [
                        'price' => $priceInfo->price ?? 0,
                        'original_price' => $priceInfo->original_price ?? 0,
                        'type' => $priceInfo->type ?? 'normal',
                        'label' => $priceInfo->label ?? ''
                    ] : null,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取产品价格信息失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取产品价格信息失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get product detail by slug
     * 
     * GET /api/products/{slug}/detail
     * 
     * @param string $slug
     * @return JsonResponse
     */
    public function getDetailBySlug(string $slug): JsonResponse
    {
        try {
            // Bypass cache for real-time data integrity
            $product = Product::with(['brand', 'origin', 'variants.color', 'variants.size'])
                    ->where([['slug', $slug], ['status', '1'], ['type', ProductType::PRODUCT->value]])
                    ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Get first variant
            $firstVariant = $product->variants->first();
            
            // Get category
            $arrCate = json_decode($product->cat_id);
            $catId = ($arrCate && !empty($arrCate)) ? $arrCate[0] : null;
            $category = null;
            if ($catId) {
                $category = Product::select('id', 'name', 'slug', 'cat_id')
                    ->where([['type', 'taxonomy'], ['id', $catId]])
                    ->first();
            }
            
            // Get rates
            $rates = \App\Modules\Rate\Models\Rate::where([['status', '1'], ['product_id', $product->id]])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $tRates = \App\Modules\Rate\Models\Rate::select('id', 'rate')
                ->where([['status', '1'], ['product_id', $product->id]])
                ->get();
            
            // Calculate rating
            $rateCount = $tRates->count();
            $rateSum = $tRates->sum('rate');
            $averageRate = $rateCount > 0 ? round($rateSum / $rateCount, 1) : 0;
            
            // Get total sold
            $totalSold = DB::table('orderdetail')
                ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                ->where('orderdetail.product_id', $product->id)
                ->where('orders.ship', 2)
                ->where('orders.status', '!=', 2)
                ->sum('orderdetail.qty') ?? 0;
            
            // Get gallery
            $gallery = json_decode($product->gallery) ?? [];
            $galleryImages = [];
            if (!empty($gallery)) {
                foreach ($gallery as $img) {
                    $galleryImages[] = $this->formatImageUrl($img);
                }
            }
            
            // Get variants with price info and warehouse stock
            $variants = $product->variants->map(function($variant) use ($product) {
                $variantPriceInfo = $this->getVariantPriceInfo($variant->id, $product->id);
                
                // Get warehouse stock
                $warehouseStock = 0;
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    Log::warning('Failed to get warehouse stock for variant: ' . $variant->id, [
                        'error' => $e->getMessage()
                    ]);
                    // Fallback to variant stock
                    $warehouseStock = (int) ($variant->stock ?? 0);
                }
                
                $optLabel = $variant->option1_value;
                if (!$optLabel) {
                    $color = $variant->color ? $variant->color->name : '';
                    $size = $variant->size ? $variant->size->name : '';
                    $optLabel = trim(($color ?: '') . (($color && $size) ? ' / ' : '') . ($size ?: ''));
                }
                if (!$optLabel) {
                    $optLabel = 'Mặc định';
                }
                
                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'option1_value' => $variant->option1_value,
                    'image' => $this->formatImageUrl($variant->image ?? null),
                    'price' => (float) $variant->price,
                    'stock' => (int) ($variant->stock ?? 0), // Original stock from variants table
                    'warehouse_stock' => $warehouseStock, // Current stock from warehouse
                    'is_out_of_stock' => $warehouseStock <= 0,
                    'weight' => (float) $variant->weight,
                    'size_id' => $variant->size_id,
                    'color_id' => $variant->color_id,
                    'color' => $variant->color ? [
                        'id' => $variant->color->id,
                        'name' => $variant->color->name,
                    ] : null,
                    'size' => $variant->size ? [
                        'id' => $variant->size->id,
                        'name' => $variant->size->name,
                        'unit' => $variant->size->unit ?? '',
                    ] : null,
                    'price_info' => $variantPriceInfo,
                    'option_label' => $optLabel,
                ];
            });
            
            // Get Flash Sale info
            $flashSale = null;
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
            if ($flash) {
                $productSale = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $product->id]])->first();
                if ($productSale && $productSale->buy < $productSale->number) {
                    $flashSale = [
                        'id' => $flash->id,
                        'name' => $flash->name,
                        'start' => $flash->start,
                        'end' => $flash->end,
                        'end_date' => date('Y/m/d H:i:s', $flash->end),
                        'price_sale' => (float) $productSale->price_sale,
                        'number' => (int) $productSale->number,
                        'buy' => (int) $productSale->buy,
                        'remaining' => (int) ($productSale->number - $productSale->buy),
                    ];
                }
            }
            
            // Get Deal info
            $deal = null;
            $saleDeals = [];
            $now = strtotime(date('Y-m-d H:i:s'));
            $dealIds = ProductDeal::where('product_id', $product->id)->where('status', 1)->pluck('deal_id')->toArray();
            if (!empty($dealIds)) {
                $activeDeal = Deal::whereIn('id', $dealIds)
                    ->where('status', 1)
                    ->where('start', '<=', $now)
                    ->where('end', '>=', $now)
                    ->first();
                
                if ($activeDeal) {
                    $saleDealsData = SaleDeal::where([['deal_id', $activeDeal->id], ['status', '1']])->get();
                    $saleDeals = $saleDealsData->map(function($saleDeal) {
                        $dealProduct = Product::find($saleDeal->product_id);
                        $dealVariant = Variant::where('product_id', $saleDeal->product_id)->first();

                        // Tính remaining_quota và tồn kho vật lý cho deal item
                        $remaining = max(0, ((int)$saleDeal->qty) - ((int)($saleDeal->buy ?? 0)));
                        $stock = 0;
                        try {
                            if ($dealVariant) {
                                $stockData = $this->warehouseService->getVariantStock($dealVariant->id);
                                $stock = (int)($stockData['current_stock'] ?? 0);
                            } elseif ($dealProduct) {
                                $stock = (int)($dealProduct->stock ?? 0);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Failed to get warehouse stock for deal sale item', [
                                'sale_deal_id' => $saleDeal->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        $available = $remaining > 0 && $stock > 0;
                        
                        return [
                            'id' => $saleDeal->id,
                            'product_id' => $saleDeal->product_id,
                            'product_name' => $dealProduct ? $dealProduct->name : '',
                            'product_image' => $dealProduct ? $this->formatImageUrl($dealProduct->image ?? null) : '',
                            'variant_id' => $dealVariant ? $dealVariant->id : null,
                            'price' => (float) $saleDeal->price,
                            'original_price' => $dealVariant ? (float) $dealVariant->price : 0,
                            'remaining_quota' => $remaining,
                            'physical_stock' => $stock,
                            'available' => $available,
                        ];
                    })->toArray();
                    
                    $deal = [
                        'id' => $activeDeal->id,
                        'name' => $activeDeal->name,
                        'limited' => (int) $activeDeal->limited,
                        'sale_deals' => $saleDeals,
                    ];
                }
            }
            
            // Get related products
            $relatedProducts = [];
            if ($catId) {
                $relatedProductsData = Product::join('variants', 'variants.product_id', '=', 'posts.id')
                    ->leftJoin('brands', 'brands.id', '=', 'posts.brand_id')
                    ->select('posts.id', 'posts.stock', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id',
                             'variants.price as price', 'variants.size_id as size_id',
                             'variants.color_id as color_id', 'posts.best', 'posts.is_new',
                             'brands.name as brand_name', 'brands.slug as brand_slug')
                    ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value], ['posts.id', '!=', $product->id]])
                    ->where('posts.cat_id', 'like', '%"' . $catId . '"%')
                    ->groupBy('posts.id', 'posts.stock', 'posts.name', 'posts.slug', 'posts.image', 'posts.brand_id',
                              'variants.price', 'variants.size_id', 'variants.color_id', 
                              'posts.best', 'posts.is_new', 'brands.name', 'brands.slug')
                    ->limit(9)
                    ->orderBy('posts.created_at', 'desc')
                    ->get();
                
                $relatedProducts = $relatedProductsData->map(function($p) {
                    $variantPrice = (float) $p->price;
                    $activeDeal = $this->getActiveDeal($p->id, $variantPrice);
                    
                    $brandName = $p->brand_name ?? null;
                    $brandSlug = $p->brand_slug ?? null;
                    
                    if ((empty($brandName) || $brandName === 'null' || trim($brandName) === '') && !empty($p->brand_id)) {
                        try {
                            $brand = Brand::find($p->brand_id);
                            if ($brand) {
                                $brandName = $brand->name;
                                $brandSlug = $brand->slug;
                            }
                        } catch (\Exception $e) {
                            // Silent fail
                        }
                    }
                    
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'image' => $this->formatImageUrl($p->image ?? null),
                        'brand_id' => $p->brand_id,
                        'brand_name' => $brandName,
                        'brand_slug' => $brandSlug,
                        'price' => $variantPrice,
                        'stock' => (int) $p->stock,
                        'best' => (int) $p->best,
                        'is_new' => (int) ($p->is_new ?? 0),
                        'deal' => $activeDeal,
                    ];
                })->toArray();
            }
            
            // Format response
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => $this->formatImageUrl($product->image ?? null),
                    'video' => $product->video ? $this->formatImageUrl($product->video) : null,
                    'gallery' => $galleryImages,
                    'description' => $product->description,
                    'content' => $product->content,
                    'seo_title' => $product->seo_title,
                    'seo_description' => $product->seo_description,
                    'stock' => (int) $product->stock,
                    'warehouse_stock' => $firstVariant ? (function() use ($firstVariant) {
                        try {
                            $stockData = $this->warehouseService->getVariantStock($firstVariant->id);
                            return (int) ($stockData['current_stock'] ?? 0);
                        } catch (\Exception $e) {
                            return (int) ($firstVariant->stock ?? 0);
                        }
                    })() : (int) $product->stock,
                    'is_out_of_stock' => $firstVariant ? (function() use ($firstVariant) {
                        try {
                            $stockData = $this->warehouseService->getVariantStock($firstVariant->id);
                            return (int) ($stockData['current_stock'] ?? 0) <= 0;
                        } catch (\Exception $e) {
                            return (int) ($firstVariant->stock ?? 0) <= 0;
                        }
                    })() : ((int) $product->stock <= 0),
                    'best' => (int) $product->best,
                    'is_new' => (int) ($product->is_new ?? 0),
                    'cbmp' => $product->cbmp,
                    'option1_name' => $product->option1_name,
                    'has_variants' => (int) $product->has_variants,
                    'brand' => $product->brand ? [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'slug' => $product->brand->slug,
                    ] : null,
                    'origin' => $product->origin ? [
                        'id' => $product->origin->id,
                        'name' => $product->origin->name,
                    ] : null,
                    'category' => $category ? [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ] : null,
                    'first_variant' => $firstVariant ? (function() use ($firstVariant) {
                        $warehouseStock = 0;
                        try {
                            $stockData = $this->warehouseService->getVariantStock($firstVariant->id);
                            $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                        } catch (\Exception $e) {
                            Log::warning('Failed to get warehouse stock for first variant: ' . $firstVariant->id);
                            $warehouseStock = (int) ($firstVariant->stock ?? 0);
                        }
                        return [
                            'id' => $firstVariant->id,
                            'sku' => $firstVariant->sku,
                            'price' => (float) $firstVariant->price,
                            'sale' => (float) $firstVariant->sale,
                            'stock' => (int) ($firstVariant->stock ?? 0),
                            'warehouse_stock' => $warehouseStock,
                            'is_out_of_stock' => $warehouseStock <= 0,
                        ];
                    })() : null,
                    'variants' => $variants->toArray(),
                    'variants_count' => $variants->count(),
                    'rating' => [
                        'average' => $averageRate,
                        'count' => $rateCount,
                        'sum' => $rateSum,
                    ],
                    'total_sold' => (int) $totalSold,
                    'rates' => $rates->map(function($rate) {
                        return [
                            'id' => $rate->id,
                            'rate' => (int) $rate->rate,
                            'comment' => $rate->comment,
                            'user_name' => $rate->user_name,
                            'created_at' => $rate->created_at?->toISOString(),
                        ];
                    })->toArray(),
                    'flash_sale' => $flashSale,
                    'deal' => $deal,
                    'related_products' => $relatedProducts,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取产品详情失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'slug' => $slug,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取产品详情失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }
    
    /**
     * Get variant price info
     * 
     * @param int $variantId
     * @param int $productId
     * @return array
     */
    private function getVariantPriceInfo(int $variantId, int $productId): array
    {
        try {
            $variant = Variant::find($variantId);
            if (!$variant) {
                return ['final_price' => 0, 'original_price' => 0, 'html' => '<p>Liên hệ</p>'];
            }
            
            $originalPrice = (float) $variant->price;
            $finalPrice = $originalPrice;
            $percent = 0;
            
            // 1. Check Flash Sale
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();
            if ($flash) {
                $productSale = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $productId]])->first();
                if ($productSale && $productSale->buy < $productSale->number) {
                    $finalPrice = (float) $productSale->price_sale;
                    $percent = round(($originalPrice - $finalPrice) / ($originalPrice / 100));
                    return [
                        'final_price' => $finalPrice,
                        'original_price' => $originalPrice,
                        'html' => '<p>' . number_format($finalPrice) . 'đ</p><del>' . number_format($originalPrice) . 'đ</del><div class="tag"><span>-' . $percent . '%</span></div>'
                    ];
                }
            }
            
            // 2. Check Marketing Campaign
            $nowDate = \Carbon\Carbon::now();
            $campaignProduct = \App\Modules\Marketing\Models\MarketingCampaignProduct::where('product_id', $productId)
                ->whereHas('campaign', function ($q) use ($nowDate) {
                    $q->where('status', 1)
                      ->where('start_at', '<=', $nowDate)
                      ->where('end_at', '>=', $nowDate);
                })->first();
            
            if ($campaignProduct) {
                $finalPrice = (float) $campaignProduct->price;
                $percent = round(($originalPrice - $finalPrice) / ($originalPrice / 100));
                return [
                    'final_price' => $finalPrice,
                    'original_price' => $originalPrice,
                    'html' => '<p>' . number_format($finalPrice) . 'đ</p><del>' . number_format($originalPrice) . 'đ</del><div class="tag"><span>-' . $percent . '%</span></div>'
                ];
            }
            
            // 3. Use sale price
            if ($variant->sale > 0 && $variant->sale < $originalPrice) {
                $finalPrice = (float) $variant->sale;
                $percent = round(($originalPrice - $finalPrice) / ($originalPrice / 100));
                return [
                    'final_price' => $finalPrice,
                    'original_price' => $originalPrice,
                    'html' => '<p>' . number_format($finalPrice) . 'đ</p><del>' . number_format($originalPrice) . 'đ</del><div class="tag"><span>-' . $percent . '%</span></div>'
                ];
            }
            
            // 4. Return original price
            return [
                'final_price' => $originalPrice,
                'original_price' => $originalPrice,
                'html' => '<p>' . number_format($originalPrice) . 'đ</p>'
            ];
            
        } catch (\Exception $e) {
            Log::error('获取变体价格信息失败: ' . $e->getMessage(), [
                'variant_id' => $variantId,
                'product_id' => $productId
            ]);
            
            return ['final_price' => 0, 'original_price' => 0, 'html' => '<p>Liên hệ</p>'];
        }
    }
}
