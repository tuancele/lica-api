<?php

declare(strict_types=1);
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashSale\FlashSaleResource;
use App\Http\Resources\FlashSale\ProductSaleResource;
use App\Http\Resources\Product\ProductResource;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Services\PriceCalculationService;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Flash Sale API Controller V1
 * 
 * RESTful API endpoints for Flash Sale
 * Base URL: /api/v1/flash-sales
 */
class FlashSaleController extends Controller
{
    protected PriceCalculationService $priceService;
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(
        PriceCalculationService $priceService,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->priceService = $priceService;
        $this->warehouseService = $warehouseService;
    }

    /**
     * Get active Flash Sales
     * 
     * GET /api/v1/flash-sales/active
     * 
     * Query Parameters:
     * - limit (integer, optional): Number of results, default 10, max 50
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->get('limit', 10);
            
            // Validate limit
            if ($limit < 1 || $limit > 50) {
                $limit = 10;
            }

            // Get active Flash Sales
            $flashSales = FlashSale::active()
                ->orderBy('start', 'desc')
                ->limit($limit)
                ->get();

            // Format response
            $formattedFlashSales = FlashSaleResource::collection($flashSales);

            return response()->json([
                'success' => true,
                'data' => $formattedFlashSales,
                'count' => $flashSales->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get active Flash Sales failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Get products in a Flash Sale
     * 
     * GET /api/v1/flash-sales/{id}/products
     * 
     * Query Parameters:
     * - page (integer, optional): Page number, default 1
     * - limit (integer, optional): Items per page, default 20, max 100
     * - available_only (boolean, optional): Only available products (buy < number), default true
     * 
     * @param Request $request
     * @param int $id Flash Sale ID
     * @return JsonResponse
     */
    public function getProducts(Request $request, int $id): JsonResponse
    {
        try {
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);
            $availableOnly = filter_var($request->get('available_only', true), FILTER_VALIDATE_BOOLEAN);

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                $limit = 20;
            }

            // Get Flash Sale
            $flashSale = FlashSale::find($id);
            
            if (!$flashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chương trình Flash Sale không tồn tại'
                ], 404);
            }

            // Check if Flash Sale is active
            if (!$flashSale->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chương trình Flash Sale không đang diễn ra'
                ], 400);
            }

            // Query ProductSales with Eager Loading
            $query = ProductSale::where('flashsale_id', $id)
                ->with([
                    'product' => function($q) {
                        $q->with(['brand:id,name,slug,image', 'origin:id,name']);
                    },
                    'variant' => function($q) {
                        $q->with(['color:id,name,color', 'size:id,name,unit']);
                    }
                ]);

            // Filter by availability
            if ($availableOnly) {
                $query->whereRaw('buy < number');
            }

            // Paginate
            $productSales = $query->paginate($limit, ['*'], 'page', $page);

            // Format products with price info
            $products = [];
            foreach ($productSales->items() as $productSale) {
                $product = $productSale->product;
                
                if (!$product) {
                    continue;
                }

                // Get price info using service
                $priceInfo = $this->priceService->calculateProductPrice($product, $id);

                // Build product data
                $productData = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'image' => getImage($product->image),
                    'has_variants' => (bool) $product->has_variants,
                ];

                // Add brand and origin
                if ($product->brand) {
                    $productData['brand'] = [
                        'id' => $product->brand->id,
                        'name' => $product->brand->name,
                        'slug' => $product->brand->slug,
                    ];
                }

                if ($product->origin) {
                    $productData['origin'] = [
                        'id' => $product->origin->id,
                        'name' => $product->origin->name,
                    ];
                }

                // Handle variants
                if ($product->has_variants && $productSale->variant_id) {
                    // Single variant Flash Sale
                    $variant = $productSale->variant;
                    if ($variant) {
                        $variantPriceInfo = $this->priceService->calculateVariantPrice($variant, $product->id, $id);
                        
                        // Get warehouse stock for variant
                        $warehouseStock = 0;
                        try {
                            $stockData = $this->warehouseService->getVariantStock($variant->id);
                            $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                        } catch (\Exception $e) {
                            Log::warning('Failed to get warehouse stock for variant: ' . $variant->id);
                            $warehouseStock = (int) ($variant->stock ?? 0);
                        }
                        
                        $productData['variants'] = [
                            [
                                'id' => $variant->id,
                                'sku' => $variant->sku,
                                'option1_value' => $variant->option1_value,
                                'price' => (float) $variant->price,
                                'stock' => (int) $variant->stock,
                                'warehouse_stock' => $warehouseStock,
                                'is_out_of_stock' => $warehouseStock <= 0,
                                'flash_sale_info' => [
                                    'price_sale' => (float) $productSale->price_sale,
                                    'original_price' => (float) $variant->price,
                                    'discount_percent' => $variantPriceInfo->discount_percent,
                                    'number' => (int) $productSale->number,
                                    'buy' => (int) $productSale->buy,
                                    'remaining' => $productSale->remaining,
                                ],
                                'price_info' => $variantPriceInfo,
                            ]
                        ];
                    }
                } else {
                    // Product-level Flash Sale or no variants
                    $productData['flash_sale_info'] = [
                        'price_sale' => (float) $productSale->price_sale,
                        'original_price' => $priceInfo->original_price,
                        'discount_percent' => $priceInfo->discount_percent,
                        'number' => (int) $productSale->number,
                        'buy' => (int) $productSale->buy,
                        'remaining' => $productSale->remaining,
                    ];
                    $productData['price_info'] = $priceInfo;

                    // Add variants if product has variants
                    if ($product->has_variants) {
                        $variants = $product->variants;
                        $productData['variants'] = $variants->map(function($variant) use ($product, $id) {
                            $variantPriceInfo = $this->priceService->calculateVariantPrice($variant, $product->id, $id);
                            
                            // Get warehouse stock
                            $warehouseStock = 0;
                            try {
                                $stockData = $this->warehouseService->getVariantStock($variant->id);
                                $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                            } catch (\Exception $e) {
                                Log::warning('Failed to get warehouse stock for variant: ' . $variant->id);
                                $warehouseStock = (int) ($variant->stock ?? 0);
                            }
                            
                            return [
                                'id' => $variant->id,
                                'sku' => $variant->sku,
                                'option1_value' => $variant->option1_value,
                                'price' => (float) $variant->price,
                                'stock' => (int) $variant->stock,
                                'warehouse_stock' => $warehouseStock,
                                'is_out_of_stock' => $warehouseStock <= 0,
                                'price_info' => $variantPriceInfo,
                            ];
                        })->toArray();
                    }
                }

                $products[] = $productData;
            }

            // Calculate total unique products in Flash Sale
            $totalUniqueProducts = ProductSale::where('flashsale_id', $id)
                ->distinct('product_id')
                ->count('product_id');
            
            // Get Flash Sale resource and add total_products if not already included
            $flashSaleResource = new FlashSaleResource($flashSale);
            $flashSaleData = $flashSaleResource->toArray($request);
            if (!isset($flashSaleData['total_products'])) {
                $flashSaleData['total_products'] = $totalUniqueProducts;
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'flash_sale' => $flashSaleData,
                    'products' => $products,
                    'pagination' => [
                        'current_page' => $productSales->currentPage(),
                        'per_page' => $productSales->perPage(),
                        'total' => $productSales->total(), // Total ProductSale entries
                        'last_page' => $productSales->lastPage(),
                    ],
                    'total_unique_products' => $totalUniqueProducts, // Total unique products in Flash Sale
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Flash Sale products failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'flash_sale_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách sản phẩm Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }
}
