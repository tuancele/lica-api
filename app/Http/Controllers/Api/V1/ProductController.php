<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductDetailResource;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Rate\Models\Rate;
use App\Services\PriceCalculationService;
use App\Services\Product\IngredientService;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Product API Controller V1.
 *
 * RESTful API endpoints for product detail
 * Base URL: /api/v1/products
 */
class ProductController extends Controller
{
    protected IngredientService $ingredientService;
    protected PriceCalculationService $priceService;
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(
        IngredientService $ingredientService,
        PriceCalculationService $priceService,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->ingredientService = $ingredientService;
        $this->priceService = $priceService;
        $this->warehouseService = $warehouseService;
    }

    /**
     * Format image URL for API response - Always use R2 storage.
     */
    private function formatImageUrl(?string $image): string
    {
        if (empty($image)) {
            $r2Domain = config('filesystems.disks.r2.url', '');
            if (! empty($r2Domain)) {
                return rtrim($r2Domain, '/').'/public/image/no_image.png';
            }

            return asset('/public/image/no_image.png');
        }

        $r2Domain = config('filesystems.disks.r2.url', '');
        $r2DomainClean = ! empty($r2Domain) ? rtrim($r2Domain, '/') : '';

        if (empty($r2DomainClean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }

        // Clean input
        $image = trim($image);

        // Remove all occurrences of the R2 domain (http/https agnostic) to clean up duplication
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);

        // Remove protocols from image for cleaning
        $cleanPath = str_replace(['http://', 'https://'], '', $image);

        // Fix "uploadscdn.lica.vn" concatenation bug
        $cleanPath = str_replace('uploads'.$checkR2, 'uploads/', $cleanPath);

        // Remove domain from path globally
        $cleanPath = str_replace($checkR2.'/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);

        // Also clean local domains
        $appUrl = config('app.url', '');
        $appDomain = parse_url($appUrl, PHP_URL_HOST);
        if ($appDomain) {
            $cleanPath = str_replace($appDomain.'/', '', $cleanPath);
            $cleanPath = str_replace($appDomain, '', $cleanPath);
        }
        $cleanPath = str_replace('localhost/', '', $cleanPath);

        // Normalize slashes
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);

        // Deduplicate repeating folders
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath);
        $cleanPath = preg_replace('#(images/)+#', 'images/', $cleanPath);
        $cleanPath = preg_replace('#(upload/)+#', 'upload/', $cleanPath);

        $cleanPath = ltrim($cleanPath, '/');

        // Check if the original image was an external URL
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $host = parse_url($image, PHP_URL_HOST);
            $r2Host = parse_url($r2DomainClean, PHP_URL_HOST);
            $appHost = parse_url($appUrl, PHP_URL_HOST);

            if ($host && $host !== $r2Host && $host !== $appHost && $host !== 'localhost' && $host !== '127.0.0.1') {
                return $image;
            }
        }

        // Build R2 URL
        return $r2DomainClean.'/'.$cleanPath;
    }

    /**
     * Get product detail by slug.
     *
     * GET /api/v1/products/{slug}
     *
     * @param  string  $slug  Product slug
     */
    public function show(string $slug): JsonResponse
    {
        try {
            // Bypass cache for real-time data integrity
            $product = Product::with([
                'brand:id,name,slug,image,logo',
                'origin:id,name',
                'variants' => function ($query) {
                    $query->orderBy('position', 'asc')
                        ->orderBy('id', 'asc')
                        ->with(['color:id,name,color', 'size:id,name,unit']);
                },
                'rates' => function ($query) {
                    $query->where('status', '1')
                        ->orderBy('created_at', 'desc')
                        ->limit(5);
                },
            ])
                ->where([['slug', $slug], ['status', '1'], ['type', ProductType::PRODUCT->value]])
                ->first();

            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại',
                ], 404);
            }

            // Get first variant
            $firstVariant = $product->variants->first();

            // Get category (primary category from cat_id JSON)
            $arrCate = json_decode($product->cat_id ?? '[]', true);
            $catId = (is_array($arrCate) && ! empty($arrCate)) ? $arrCate[0] : null;
            $category = null;
            if ($catId) {
                $category = Product::select('id', 'name', 'slug', 'cat_id')
                    ->where([['type', 'taxonomy'], ['id', $catId]])
                    ->first();
            }

            // Get all rates for rating calculation
            $tRates = Rate::select('id', 'rate')
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

            // Process gallery images
            $gallery = json_decode($product->gallery ?? '[]', true) ?? [];
            $galleryImages = [];
            if (! empty($gallery)) {
                foreach ($gallery as $img) {
                    $galleryImages[] = $this->formatImageUrl($img);
                }
            }

            // Process ingredients
            $ingredientData = $this->ingredientService->processIngredient($product->ingredient);

            // Get variants with price info and warehouse stock
            $variants = $product->variants->map(function ($variant) use ($product) {
                $variantPriceInfo = $this->getVariantPriceInfo($variant->id, $product->id);

                // Get warehouse stock (prefer available_stock from new warehouse ledger)
                $warehouseStock = 0;
                $physicalStock = 0;
                $flashStock = 0;
                $dealStock = 0;
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['available_stock'] ?? $stockData['current_stock'] ?? 0);
                    $physicalStock = (int) ($stockData['physical_stock'] ?? 0);
                    $flashStock = (int) ($stockData['flash_sale_stock'] ?? 0);
                    $dealStock = (int) ($stockData['deal_stock'] ?? 0);
                } catch (\Exception $e) {
                    Log::warning('Failed to get warehouse stock for variant: '.$variant->id, [
                        'error' => $e->getMessage(),
                    ]);
                    // Fallback to variant stock if warehouse service fails
                    $warehouseStock = (int) ($variant->stock ?? 0);
                    $physicalStock = $warehouseStock;
                }

                // Build option label
                $optLabel = $variant->option1_value;
                if (! $optLabel) {
                    $color = $variant->color ? $variant->color->name : '';
                    $size = $variant->size ? $variant->size->name : '';
                    $optLabel = trim(($color ?: '').(($color && $size) ? ' / ' : '').($size ?: ''));
                }
                if (! $optLabel) {
                    $optLabel = 'Mặc định';
                }

                return [
                    'id' => $variant->id,
                    'sku' => $variant->sku,
                    'option1_value' => $variant->option1_value,
                    'image' => $this->formatImageUrl($variant->image ?? null),
                    // Keep single source of truth: always expose final price from PriceEngine
                    'price' => (float) ($variantPriceInfo['final_price'] ?? (float) $variant->price),
                    'final_price' => (float) ($variantPriceInfo['final_price'] ?? (float) $variant->price),
                    'original_price' => (float) ($variantPriceInfo['original_price'] ?? (float) $variant->price),
                    'price_type' => (string) ($variantPriceInfo['type'] ?? 'normal'),
                    'stock' => (int) ($variant->stock ?? 0), // Original stock from variants table
                    'warehouse_stock' => $warehouseStock, // Current stock from warehouse (import - export)
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
                    'physical_stock' => $physicalStock,
                    'flash_sale_stock' => $flashStock,
                    'deal_stock' => $dealStock,
                    'available_stock' => $warehouseStock,
                    'option_label' => $optLabel,
                ];
            });

            // Get Flash Sale info
            $flashSale = $this->getFlashSaleInfo($product->id);

            // Get Deal info
            $deal = $this->getDealInfo($product->id);

            // Get related products
            $relatedProducts = $this->getRelatedProducts($catId, $product->id);

            // Format rates
            $rates = $product->rates->map(function ($rate) {
                return [
                    'id' => $rate->id,
                    'rate' => (int) $rate->rate,
                    'comment' => $rate->comment,
                    'user_name' => $rate->user_name,
                    'created_at' => $rate->created_at?->toISOString(),
                ];
            })->toArray();

            // Prepare additional data for ProductDetailResource
            $firstVariantPriceInfo = $firstVariant ? $this->getVariantPriceInfo($firstVariant->id, $product->id) : null;

            $additionalData = [
                'category' => $category ? [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ] : null,
                'first_variant' => $firstVariant ? [
                    'id' => $firstVariant->id,
                    'sku' => $firstVariant->sku,
                    // Always expose final price from PriceEngine to prevent JS overwriting with base price
                    'price' => (float) (($firstVariantPriceInfo['final_price'] ?? null) ?: (float) $firstVariant->price),
                    'final_price' => (float) (($firstVariantPriceInfo['final_price'] ?? null) ?: (float) $firstVariant->price),
                    'original_price' => (float) (($firstVariantPriceInfo['original_price'] ?? null) ?: (float) $firstVariant->price),
                    'price_type' => (string) (($firstVariantPriceInfo['type'] ?? null) ?: 'normal'),
                    'price_info' => $firstVariantPriceInfo,
                    'stock' => (int) ($firstVariant->stock ?? 0),
                ] : null,
                'variants' => $variants->toArray(),
                'variants_count' => $variants->count(),
                'rating' => [
                    'average' => $averageRate,
                    'count' => $rateCount,
                    'sum' => $rateSum,
                ],
                'total_sold' => (int) $totalSold,
                'rates' => $rates,
                'flash_sale' => $flashSale,
                'deal' => $deal,
                'related_products' => $relatedProducts,
                'ingredient' => $ingredientData,
                'gallery' => $galleryImages,
                'image' => $this->formatImageUrl($product->image ?? null),
                'video' => $product->video ? $this->formatImageUrl($product->video) : null,
            ];

            // Use ProductDetailResource
            $resource = new ProductDetailResource($product, $additionalData);

            return response()->json([
                'success' => true,
                'data' => $resource->toArray(request()),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get product detail failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ',
            ], 500);
        }
    }

    /**
     * Get variant price info (Pricing Engine only).
     */
    private function getVariantPriceInfo(int $variantId, int $productId): array
    {
        try {
            $priceInfo = app(\App\Services\Pricing\PriceEngineServiceInterface::class)
                ->calculateDisplayPrice($productId, $variantId);

            $finalPrice = (float) ($priceInfo['price'] ?? 0);
            $originalPrice = (float) ($priceInfo['original_price'] ?? 0);
            $type = (string) ($priceInfo['type'] ?? 'normal');
            $label = (string) ($priceInfo['label'] ?? '');
            $percent = (int) ($priceInfo['discount_percent'] ?? 0);

            if ($type === 'normal') {
                return [
                    'final_price' => $finalPrice,
                    'original_price' => $originalPrice,
                    'type' => $type,
                    'label' => $label,
                    'discount_percent' => 0,
                    'html' => '<p>'.number_format($finalPrice).'đ</p>',
                ];
            }

            return [
                'final_price' => $finalPrice,
                'original_price' => $originalPrice,
                'type' => $type,
                'label' => $label,
                'discount_percent' => $percent,
                'html' => '<p>'.number_format($finalPrice).'đ</p><del>'.number_format($originalPrice).'đ</del><div class="tag"><span>-'.number_format($percent).'%</span></div>',
            ];
        } catch (\Throwable $e) {
            Log::error('Get variant price info failed: '.$e->getMessage(), [
                'variant_id' => $variantId,
                'product_id' => $productId,
            ]);

            return [
                'final_price' => 0,
                'original_price' => 0,
                'type' => 'normal',
                'label' => '',
                'discount_percent' => 0,
                'html' => '<p>Liên hệ</p>',
            ];
        }
    }

    /**
     * Get Flash Sale info for product.
     */
    private function getFlashSaleInfo(int $productId): ?array
    {
        try {
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = FlashSale::where([['status', '1'], ['start', '<=', $date], ['end', '>=', $date]])->first();

            if (! $flash) {
                return null;
            }

            $productSale = ProductSale::where([['flashsale_id', $flash->id], ['product_id', $productId]])->first();

            if ($productSale && $productSale->buy < $productSale->number) {
                return [
                    'id' => $flash->id,
                    'name' => $flash->name ?? "Flash Sale #{$flash->id}",
                    'start' => $flash->start,
                    'end' => $flash->end,
                    'end_date' => date('Y/m/d H:i:s', $flash->end),
                    'price_sale' => (float) $productSale->price_sale,
                    'number' => (int) $productSale->number,
                    'buy' => (int) $productSale->buy,
                    'remaining' => (int) ($productSale->number - $productSale->buy),
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Get Flash Sale info failed: '.$e->getMessage(), [
                'product_id' => $productId,
            ]);

            return null;
        }
    }

    /**
     * Get Deal info for product.
     */
    private function getDealInfo(int $productId): ?array
    {
        try {
            $now = strtotime(date('Y-m-d H:i:s'));
            $dealIds = ProductDeal::where('product_id', $productId)
                ->where('status', 1)
                ->pluck('deal_id')
                ->toArray();

            if (empty($dealIds)) {
                return null;
            }

            $activeDeal = Deal::whereIn('id', $dealIds)
                ->where('status', 1)
                ->where('start', '<=', $now)
                ->where('end', '>=', $now)
                ->first();

            if (! $activeDeal) {
                return null;
            }

            $saleDealsData = SaleDeal::where([['deal_id', $activeDeal->id], ['status', '1']])->get();

            $saleDeals = $saleDealsData->map(function ($saleDeal) {
                $dealProduct = Product::find($saleDeal->product_id);
                if (! $dealProduct) {
                    return null;
                }

                // Get first variant (sorted by position, then id)
                $dealVariant = Variant::where('product_id', $saleDeal->product_id)
                    ->orderBy('position', 'asc')
                    ->orderBy('id', 'asc')
                    ->first();

                if (! $dealVariant) {
                    return null;
                }

                // Tính quỹ còn lại và tồn kho vật lý
                $remaining = max(0, ((int) $saleDeal->qty) - ((int) ($saleDeal->buy ?? 0)));
                $stock = 0;
                try {
                    $stockData = $this->warehouseService->getVariantStock($dealVariant->id);
                    $stock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    Log::warning('Get warehouse stock for deal sale failed', [
                        'sale_deal_id' => $saleDeal->id,
                        'variant_id' => $dealVariant->id,
                        'error' => $e->getMessage(),
                    ]);
                    $stock = (int) ($dealVariant->stock ?? 0);
                }

                $available = $remaining > 0 && $stock > 0;

                return [
                    'id' => $saleDeal->id,
                    'product_id' => $saleDeal->product_id,
                    'product_name' => $dealProduct->name,
                    'product_image' => $this->formatImageUrl($dealProduct->image ?? null),
                    'variant_id' => $dealVariant->id,
                    'price' => (float) $saleDeal->price,
                    'original_price' => (float) $dealVariant->price,
                    'remaining_quota' => $remaining,
                    'physical_stock' => $stock,
                    'available' => $available,
                ];
            })->filter()->values()->toArray(); // Remove null values and reindex array

            // If no valid sale deals, return null
            if (empty($saleDeals)) {
                return null;
            }

            return [
                'id' => $activeDeal->id,
                'name' => $activeDeal->name,
                'limited' => (int) $activeDeal->limited,
                'sale_deals' => $saleDeals,
            ];
        } catch (\Exception $e) {
            Log::warning('Get Deal info failed: '.$e->getMessage(), [
                'product_id' => $productId,
            ]);

            return null;
        }
    }

    /**
     * Get related products by category.
     */
    private function getRelatedProducts(?int $catId, int $excludeProductId): array
    {
        if (! $catId) {
            return [];
        }

        try {
            $relatedProductsData = Product::with(['brand:id,name,slug'])
                ->join('variants', 'variants.product_id', '=', 'posts.id')
                ->select(
                    'posts.id',
                    'posts.stock',
                    'posts.name',
                    'posts.slug',
                    'posts.image',
                    'posts.brand_id',
                    'variants.price as price',
                    'variants.size_id as size_id',
                    'variants.color_id as color_id',
                    'posts.best',
                    'posts.is_new'
                )
                ->where([['posts.status', '1'], ['posts.type', ProductType::PRODUCT->value], ['posts.id', '!=', $excludeProductId]])
                ->where('posts.cat_id', 'like', '%"'.$catId.'"%')
                ->groupBy(
                    'posts.id',
                    'posts.stock',
                    'posts.name',
                    'posts.slug',
                    'posts.image',
                    'posts.brand_id',
                    'variants.price',
                    'variants.size_id',
                    'variants.color_id',
                    'posts.best',
                    'posts.is_new'
                )
                ->limit(9)
                ->orderBy('posts.created_at', 'desc')
                ->get();

            return $relatedProductsData->map(function ($p) {
                $variantPrice = (float) $p->price;

                // Calculate price info
                $priceInfo = $this->priceService->calculateProductPrice($p);

                $brandName = null;
                $brandSlug = null;
                if ($p->relationLoaded('brand') && $p->brand) {
                    $brandName = $p->brand->name;
                    $brandSlug = $p->brand->slug;
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'image' => $this->formatImageUrl($p->image ?? null),
                    'brand' => $brandName ? [
                        'id' => $p->brand_id,
                        'name' => $brandName,
                        'slug' => $brandSlug,
                    ] : null,
                    'price_info' => [
                        'price' => $priceInfo->price ?? $variantPrice,
                        'original_price' => $priceInfo->original_price ?? $variantPrice,
                        'type' => $priceInfo->type ?? 'normal',
                        'label' => $priceInfo->label ?? '',
                        'discount_percent' => $priceInfo->discount_percent ?? 0,
                    ],
                    'stock' => (int) $p->stock,
                    'best' => (int) $p->best,
                    'is_new' => (int) ($p->is_new ?? 0),
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::warning('Get related products failed: '.$e->getMessage(), [
                'cat_id' => $catId,
                'exclude_product_id' => $excludeProductId,
            ]);

            return [];
        }
    }
}
