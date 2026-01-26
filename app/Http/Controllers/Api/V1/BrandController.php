<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Brand\BrandResource;
use App\Http\Resources\Product\ProductResource;
use App\Modules\Brand\Models\Brand;
use App\Modules\Product\Models\Product;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Brand API Controller V1.
 *
 * RESTful API endpoints for brand management
 * Base URL: /api/v1/brands
 */
class BrandController extends Controller
{
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(WarehouseServiceInterface $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Get list of all brands.
     *
     * GET /api/v1/brands
     *
     * Query Parameters:
     * - page (integer, optional): Page number, default 1
     * - limit (integer, optional): Items per page, default 20
     * - status (string, optional): Filter by status (0/1)
     * - keyword (string, optional): Search by name
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);
            $status = $request->get('status');
            $keyword = $request->get('keyword');

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }

            $query = Brand::query();

            // Filter by status
            if ($status !== null) {
                $query->where('status', $status);
            } else {
                // Default: only active brands
                $query->where('status', '1');
            }

            // Search by keyword
            if (! empty($keyword)) {
                $query->where('name', 'like', '%'.$keyword.'%');
            }

            // Order by name
            $query->orderBy('name', 'asc');

            // Paginate results
            $brands = $query->paginate($limit, ['*'], 'page', $page);

            // Format response with BrandResource
            $formattedBrands = BrandResource::collection($brands->items());

            return response()->json([
                'success' => true,
                'data' => $formattedBrands,
                'pagination' => [
                    'current_page' => $brands->currentPage(),
                    'per_page' => $brands->perPage(),
                    'total' => $brands->total(),
                    'last_page' => $brands->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brands list failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ',
            ], 500);
        }
    }

    /**
     * Get brand options for select inputs.
     *
     * GET /api/v1/brands/options
     */
    public function options(Request $request): JsonResponse
    {
        try {
            $brands = Brand::query()
                ->select(['id', 'name'])
                ->where('status', '1')
                ->orderBy('name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $brands->map(function ($b) {
                    return [
                        'id' => (int) $b->id,
                        'name' => (string) $b->name,
                    ];
                })->values(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brand options failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách thương hiệu thất bại',
            ], 500);
        }
    }

    /**
     * Get brand detail by slug.
     *
     * GET /api/v1/brands/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $brand = Brand::where([
                ['slug', $slug],
                ['status', '1'],
            ])->first();

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại',
                ], 404);
            }

            // Count total products for this brand
            $totalProducts = Product::where([
                ['type', 'product'],
                ['status', '1'],
                ['brand_id', $brand->id],
            ])->count();

            // Add total_products to brand model temporarily
            $brand->total_products = $totalProducts;

            // Format response with BrandResource
            $formattedBrand = new BrandResource($brand);

            return response()->json([
                'success' => true,
                'data' => $formattedBrand,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brand detail failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin thương hiệu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ',
            ], 500);
        }
    }

    /**
     * Get products of a brand.
     *
     * GET /api/v1/brands/{slug}/products
     *
     * Query Parameters:
     * - page (integer, optional): Page number, default 1
     * - limit (integer, optional): Items per page, default 30
     * - stock (string, optional): Filter by stock status (0=out of stock, 1=in stock, all=all)
     * - sort (string, optional): Sort order (newest, oldest, price_asc, price_desc, name_asc, name_desc)
     */
    public function getProducts(Request $request, string $slug): JsonResponse
    {
        try {
            // Find brand
            $brand = Brand::where([
                ['slug', $slug],
                ['status', '1'],
            ])->first();

            if (! $brand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thương hiệu không tồn tại',
                ], 404);
            }

            // Get query parameters
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 30);
            $stockFilter = $request->get('stock', 'all'); // all, 0, 1
            $sort = $request->get('sort', 'newest'); // newest, oldest, price_asc, price_desc, name_asc, name_desc

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                $limit = 30;
            }

            // Build query with eager loading for performance optimization
            $query = Product::with([
                'brand:id,name,slug',
                'variants:id,product_id,price,sale,stock,sku',
                'rates:id,product_id,rate',
                'origin:id,name',
                'category:id,name,slug',
            ])->where([
                ['type', 'product'],
                ['status', '1'],
                ['brand_id', $brand->id],
            ]);

            // Filter by stock status
            if ($stockFilter !== 'all') {
                $query->where('stock', $stockFilter);
            }

            // Apply sorting
            // Note: Price sorting will be done after pagination for simplicity
            // For better performance with large datasets, consider using raw queries
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'name_asc':
                    $query->orderBy('name', 'asc');
                    break;
                case 'name_desc':
                    $query->orderBy('name', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // For price sorting, we'll handle it after getting results
            // to avoid conflicts with eager loading
            $needsPriceSort = in_array($sort, ['price_asc', 'price_desc']);

            // Paginate results
            $products = $query->paginate($limit, ['*'], 'page', $page);

            // Handle price sorting if needed
            if ($needsPriceSort) {
                $items = $products->items();
                usort($items, function ($a, $b) use ($sort) {
                    // Get price from first variant or use 0
                    $priceA = 0;
                    $priceB = 0;

                    if ($a->variants && $a->variants->count() > 0) {
                        $variantA = $a->variants->first();
                        $priceA = $variantA->sale > 0 ? $variantA->sale : $variantA->price;
                    }

                    if ($b->variants && $b->variants->count() > 0) {
                        $variantB = $b->variants->first();
                        $priceB = $variantB->sale > 0 ? $variantB->sale : $variantB->price;
                    }

                    if ($sort === 'price_asc') {
                        return $priceA <=> $priceB;
                    } else {
                        return $priceB <=> $priceA;
                    }
                });

                // Recreate paginator with sorted items
                $products->setCollection(collect($items));
            }

            // Add warehouse_stock to products before formatting with ProductResource
            $products->getCollection()->transform(function ($product) {
                // Get warehouse stock for first variant
                $warehouseStock = 0;
                $isOutOfStock = false;
                try {
                    $firstVariant = $product->variants->first();
                    if ($firstVariant) {
                        $stockData = $this->warehouseService->getVariantStock($firstVariant->id);
                        $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                        $isOutOfStock = $warehouseStock <= 0;

                        // Add warehouse_stock to variant
                        $firstVariant->warehouse_stock = $warehouseStock;
                        $firstVariant->is_out_of_stock = $isOutOfStock;
                    } else {
                        $warehouseStock = (int) ($product->stock ?? 0);
                        $isOutOfStock = $warehouseStock <= 0;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to get warehouse stock for product: '.$product->id);
                    $warehouseStock = (int) ($product->stock ?? 0);
                    $isOutOfStock = $warehouseStock <= 0;
                }

                // Add warehouse_stock to product
                $product->warehouse_stock = $warehouseStock;
                $product->is_out_of_stock = $isOutOfStock;

                // Add warehouse_stock to all variants
                $product->variants->each(function ($variant) {
                    try {
                        $stockData = $this->warehouseService->getVariantStock($variant->id);
                        $variant->warehouse_stock = (int) ($stockData['current_stock'] ?? 0);
                        $variant->is_out_of_stock = $variant->warehouse_stock <= 0;
                    } catch (\Exception $e) {
                        $variant->warehouse_stock = (int) ($variant->stock ?? 0);
                        $variant->is_out_of_stock = $variant->warehouse_stock <= 0;
                    }
                });

                return $product;
            });

            // Format response with ProductResource
            $formattedProducts = ProductResource::collection($products->items());

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'brand' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                ],
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get brand products failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'slug' => $slug,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ',
            ], 500);
        }
    }

    /**
     * Get available products (in stock) of a brand.
     *
     * GET /api/v1/brands/{slug}/products/available
     */
    public function getAvailableProducts(Request $request, string $slug): JsonResponse
    {
        // Set stock filter to 1 (in stock) and call getProducts
        $request->merge(['stock' => '1']);

        return $this->getProducts($request, $slug);
    }

    /**
     * Get out of stock products of a brand.
     *
     * GET /api/v1/brands/{slug}/products/out-of-stock
     */
    public function getOutOfStockProducts(Request $request, string $slug): JsonResponse
    {
        // Set stock filter to 0 (out of stock) and call getProducts
        $request->merge(['stock' => '0']);

        return $this->getProducts($request, $slug);
    }

    /**
     * Get featured brands for home page.
     *
     * GET /api/v1/brands/featured
     *
     * Query Parameters:
     * - limit (integer, optional): Number of brands to return, default 14, max 50
     */
    public function getFeatured(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 14);

            // Validate limit
            if ($limit < 1 || $limit > 50) {
                $limit = 14;
            }

            // Bypass cache for real-time data integrity
            $brands = Brand::select('id', 'name', 'slug', 'image')
                ->where('status', '1')
                ->orderBy('sort', 'asc')
                ->limit($limit)
                ->get();

            // Format response with BrandResource
            $formattedBrands = BrandResource::collection($brands);

            return response()->json([
                'success' => true,
                'data' => $formattedBrands,
                'count' => $formattedBrands->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get featured brands failed: '.$e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách thương hiệu nổi bật thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ',
            ], 500);
        }
    }
}
