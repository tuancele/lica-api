<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashSale\FlashSaleDetailResource;
use App\Http\Resources\FlashSale\FlashSaleResource;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Promotion\ProductStockValidatorInterface;
use App\Services\Inventory\InventoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Flash Sale API Controller for Admin
 * 
 * Handles all Flash Sale management API endpoints following RESTful standards
 * Base URL: /admin/api/flash-sales
 */
class FlashSaleController extends Controller
{
    protected ProductStockValidatorInterface $productStockValidator;
    protected InventoryServiceInterface $inventoryService;

    public function __construct(
        ProductStockValidatorInterface $productStockValidator,
        InventoryServiceInterface $inventoryService
    ) {
        $this->productStockValidator = $productStockValidator;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get paginated list of Flash Sales with filters
     * 
     * GET /admin/api/flash-sales
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 10);
            $status = $request->get('status');
            $keyword = $request->get('keyword');

            // Validate limit
            if ($limit < 1 || $limit > 100) {
                $limit = 10;
            }

            $query = FlashSale::query();

            // Filter by status
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            // Search by keyword (if name field exists)
            if (!empty($keyword)) {
                // If FlashSale has name field, search by name
                // Otherwise, search by ID
                if (Schema::hasColumn('flashsales', 'name')) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                }
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            // Paginate
            $flashSales = $query->paginate($limit, ['*'], 'page', $page);

            // Format response
            $formattedFlashSales = FlashSaleResource::collection($flashSales->items());

            return response()->json([
                'success' => true,
                'data' => $formattedFlashSales,
                'pagination' => [
                    'current_page' => $flashSales->currentPage(),
                    'per_page' => $flashSales->perPage(),
                    'total' => $flashSales->total(),
                    'last_page' => $flashSales->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Flash Sales list failed: ' . $e->getMessage(), [
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
     * Get Flash Sale detail with products
     * 
     * GET /admin/api/flash-sales/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $flashSale = FlashSale::with(['products' => function($q) {
                $q->with(['product', 'variant']);
            }])->find($id);

            if (!$flashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flash Sale không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new FlashSaleDetailResource($flashSale),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Flash Sale detail failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Create new Flash Sale
     * 
     * POST /admin/api/flash-sales
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'start' => 'required|date',
                'end' => 'required|date|after:start',
                'status' => 'required|in:0,1',
                'products' => 'array',
                'products.*.product_id' => 'required|exists:posts,id',
                'products.*.variant_id' => 'nullable|exists:variants,id',
                'products.*.price_sale' => 'required|numeric|min:0',
                'products.*.number' => 'required|integer|min:1',
            ], [
                'start.required' => 'Thời gian bắt đầu không được bỏ trống.',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
                'end.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu',
                'status.required' => 'Trạng thái không được bỏ trống',
                'products.*.product_id.exists' => 'Sản phẩm không tồn tại',
                'products.*.variant_id.exists' => 'Phân loại không tồn tại',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            return DB::transaction(function () use ($request) {
                // Create Flash Sale
                $flashSale = FlashSale::create([
                    'start' => strtotime($request->start),
                    'end' => strtotime($request->end),
                    'status' => $request->status,
                    'user_id' => Auth::id(),
                ]);

                // Add products
                if ($request->has('products') && is_array($request->products)) {
                    foreach ($request->products as $index => $productData) {
                        // Validate variant belongs to product
                        if (!empty($productData['variant_id'])) {
                            $variant = Variant::where('id', $productData['variant_id'])
                                ->where('product_id', $productData['product_id'])
                                ->first();
                            
                            if (!$variant) {
                                throw new \Exception("Phân loại không thuộc sản phẩm ID {$productData['product_id']}", 422);
                            }
                        }

                        // Validate product stock > 0
                        $stock = $this->productStockValidator->getProductStock(
                            $productData['product_id'],
                            $productData['variant_id'] ?? null
                        );

                        if ($stock <= 0) {
                            $productName = Product::find($productData['product_id'])->name ?? "ID {$productData['product_id']}";
                            $variantInfo = !empty($productData['variant_id']) ? " (Variant ID {$productData['variant_id']})" : '';
                            
                            throw new \Exception("Sản phẩm \"{$productName}\"{$variantInfo} không có tồn kho, không thể tham gia Flash Sale", 422);
                        }
                        
                        // Validate: total_stock >= flash_stock_limit (number)
                        $flashStockLimit = $productData['number'];
                        $stockValidation = $this->inventoryService->validateFlashSaleStock(
                            $productData['product_id'],
                            $productData['variant_id'] ?? null,
                            $flashStockLimit
                        );
                        
                        if (!$stockValidation['valid']) {
                            $productName = Product::find($productData['product_id'])->name ?? "ID {$productData['product_id']}";
                            $variantInfo = !empty($productData['variant_id']) ? " (Variant ID {$productData['variant_id']})" : '';
                            
                            throw new \Exception("Sản phẩm \"{$productName}\"{$variantInfo}: " . $stockValidation['message'], 422);
                        }

                        $productSale = ProductSale::create([
                            'flashsale_id' => $flashSale->id,
                            'product_id' => $productData['product_id'],
                            'variant_id' => $productData['variant_id'] ?? null,
                            'price_sale' => $productData['price_sale'],
                            'number' => $productData['number'],
                            'buy' => 0,
                            'user_id' => Auth::id(),
                        ]);

                        // Allocate stock for promotion
                        $resolvedVariantId = $this->resolveVariantId($productData['product_id'], $productData['variant_id'] ?? null);
                        $allocate = $this->inventoryService->allocateStockForPromotion($resolvedVariantId, (int) $productSale->number, 'flash_sale');
                        if (empty($allocate['success'])) {
                            throw new \Exception($allocate['message'] ?? 'Không đủ tồn kho khả dụng', 422);
                        }
                    }
                }

                // Load relationships
                $flashSale->load(['products' => function($q) {
                    $q->with(['product', 'variant']);
                }]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tạo Flash Sale thành công',
                    'data' => new FlashSaleDetailResource($flashSale),
                ], 201);
            });

        } catch (\Exception $e) {
            if ((int)$e->getCode() === 422) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors' => ['stock' => [$e->getMessage()]],
                ], 422);
            }

            Log::error('Create Flash Sale failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tạo Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Update Flash Sale
     * 
     * PUT /admin/api/flash-sales/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $flashSale = FlashSale::find($id);

            if (!$flashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flash Sale không tồn tại'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'start' => 'sometimes|required|date',
                'end' => 'sometimes|required|date|after:start',
                'status' => 'sometimes|required|in:0,1',
                'products' => 'array',
                'products.*.product_id' => 'required|exists:posts,id',
                'products.*.variant_id' => 'nullable|exists:variants,id',
                'products.*.price_sale' => 'required|numeric|min:0',
                'products.*.number' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                return DB::transaction(function () use ($request, $flashSale, $id) {
                    // Update Flash Sale
                    if ($request->has('start')) {
                        $flashSale->start = strtotime($request->start);
                    }
                    if ($request->has('end')) {
                        $flashSale->end = strtotime($request->end);
                    }
                    if ($request->has('status')) {
                        $flashSale->status = $request->status;
                    }
                    $flashSale->user_id = Auth::id();
                    $flashSale->save();

                    // Update products if provided
                    if ($request->has('products')) {
                        ProductSale::where('flashsale_id', $id)->delete();

                        if (is_array($request->products)) {
                            foreach ($request->products as $index => $productData) {
                                // Validate variant belongs to product
                                if (!empty($productData['variant_id'])) {
                                    $variant = Variant::where('id', $productData['variant_id'])
                                        ->where('product_id', $productData['product_id'])
                                        ->first();
                                    
                                    if (!$variant) {
                                        throw new \Exception("Phân loại không thuộc sản phẩm ID {$productData['product_id']}", 422);
                                    }
                                }

                                // Validate product stock > 0
                                $stock = $this->productStockValidator->getProductStock(
                                    $productData['product_id'],
                                    $productData['variant_id'] ?? null
                                );

                                if ($stock <= 0) {
                                    $productName = Product::find($productData['product_id'])->name ?? "ID {$productData['product_id']}";
                                    $variantInfo = !empty($productData['variant_id']) ? " (Variant ID {$productData['variant_id']})" : '';
                                    
                                    throw new \Exception("Sản phẩm \"{$productName}\"{$variantInfo} không có tồn kho, không thể tham gia Flash Sale", 422);
                                }
                                
                                if (isset($productData['number'])) {
                                    $flashStockLimit = $productData['number'];
                                    $stockValidation = $this->inventoryService->validateFlashSaleStock(
                                        $productData['product_id'],
                                        $productData['variant_id'] ?? null,
                                        $flashStockLimit
                                    );
                                    
                                    if (!$stockValidation['valid']) {
                                        $productName = Product::find($productData['product_id'])->name ?? "ID {$productData['product_id']}";
                                        $variantInfo = !empty($productData['variant_id']) ? " (Variant ID {$productData['variant_id']})" : '';
                                        
                                        throw new \Exception("Sản phẩm \"{$productName}\"{$variantInfo}: " . $stockValidation['message'], 422);
                                    }
                                }

                                $productSale = ProductSale::create([
                                    'flashsale_id' => $id,
                                    'product_id' => $productData['product_id'],
                                    'variant_id' => $productData['variant_id'] ?? null,
                                    'price_sale' => $productData['price_sale'],
                                    'number' => $productData['number'],
                                    'buy' => 0,
                                    'user_id' => Auth::id(),
                                ]);

                                $resolvedVariantId = $this->resolveVariantId($productData['product_id'], $productData['variant_id'] ?? null);
                                $allocate = $this->inventoryService->allocateStockForPromotion($resolvedVariantId, (int) $productSale->number, 'flash_sale');
                                if (empty($allocate['success'])) {
                                    throw new \Exception($allocate['message'] ?? 'Không đủ tồn kho khả dụng', 422);
                                }
                            }
                        }
                    }

                    // Load relationships
                    $flashSale->load(['products' => function($q) {
                        $q->with(['product', 'variant']);
                    }]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Cập nhật Flash Sale thành công',
                        'data' => new FlashSaleDetailResource($flashSale),
                    ], 200);
                });
            } catch (\Exception $e) {
                if ((int)$e->getCode() === 422) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => ['stock' => [$e->getMessage()]],
                    ], 422);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Update Flash Sale failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Delete Flash Sale
     * 
     * DELETE /admin/api/flash-sales/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $flashSale = FlashSale::find($id);

            if (!$flashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flash Sale không tồn tại'
                ], 404);
            }

            // Delete related ProductSales
            ProductSale::where('flashsale_id', $id)->delete();

            // Delete Flash Sale
            $flashSale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa Flash Sale thành công',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Delete Flash Sale failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Xóa Flash Sale thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Update Flash Sale status
     * 
     * POST /admin/api/flash-sales/{id}/status
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            $flashSale = FlashSale::find($id);

            if (!$flashSale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flash Sale không tồn tại'
                ], 404);
            }

            $flashSale->status = $request->status;
            $flashSale->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => new FlashSaleResource($flashSale),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update Flash Sale status failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật trạng thái thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Search products for Flash Sale
     * 
     * POST /admin/api/flash-sales/search-products
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'keyword' => 'required|string|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Từ khóa tìm kiếm không được bỏ trống',
                    'errors' => $validator->errors()
                ], 422);
            }

            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 50);

            if ($limit < 1 || $limit > 100) {
                $limit = 50;
            }

            $keyword = $request->keyword;

            $products = Product::select('id', 'name', 'image', 'stock', 'has_variants')
                ->where([['status', '1'], ['type', 'product']])
                ->where('name', 'like', '%' . $keyword . '%')
                ->with(['variants' => function($q) {
                    $q->select('id', 'product_id', 'option1_value', 'price', 'stock', 'sku');
                }])
                ->orderBy('id', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            // Filter products with stock > 0 and format response
            $formattedProducts = $products->map(function($product) {
                // Get actual stock from warehouse system
                $stock = $this->productStockValidator->getProductStock($product->id);
                
                // If product has no variants, check product stock
                if ($product->has_variants == 0) {
                    // Filter out products with stock = 0
                    if ($stock <= 0) {
                        return null;
                    }
                    
                    $variant = $product->variant($product->id);
                    $price = $variant ? $variant->price : 0;
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'image' => getImage($product->image),
                        'has_variants' => false,
                        'price' => (float) $price,
                        'stock' => $stock,
                        'variants' => [],
                    ];
                } else {
                    // Product has variants: filter variants with stock > 0
                    $productId = $product->id; // Capture for use in closure
                    $variantsWithStock = $product->variants->map(function($v) use ($productId) {
                        // Get stock once and store it
                        $variantStock = $this->productStockValidator->getProductStock(
                            $productId,
                            $v->id
                        );
                        return [
                            'variant' => $v,
                            'stock' => $variantStock,
                        ];
                    })->filter(function($item) {
                        // Filter out variants with stock <= 0
                        return $item['stock'] > 0;
                    })->map(function($item) {
                        // Format variant data
                        $v = $item['variant'];
                        return [
                            'id' => $v->id,
                            'sku' => $v->sku,
                            'option1_value' => $v->option1_value,
                            'price' => (float) $v->price,
                            'stock' => $item['stock'],
                        ];
                    });
                    
                    // If no variants have stock, filter out the product
                    if ($variantsWithStock->isEmpty()) {
                        return null;
                    }
                    
                    // Get default variant for price display
                    $variant = $product->variant($product->id);
                    $price = $variant ? $variant->price : ($variantsWithStock->first()['price'] ?? 0);
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'image' => getImage($product->image),
                        'has_variants' => true,
                        'price' => (float) $price,
                        'stock' => $variantsWithStock->sum('stock'), // Total stock of all variants
                        'variants' => $variantsWithStock->values()->all(),
                    ];
                }
            })->filter(function($product) {
                // Remove null products (filtered out due to zero stock)
                return $product !== null;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Search products failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tìm kiếm sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Resolve a variant id; fallback to default variant of product if null.
     */
    protected function resolveVariantId(int $productId, ?int $variantId = null): int
    {
        if ($variantId) {
            return $variantId;
        }

        $product = Product::find($productId);
        if ($product) {
            $defaultVariant = $product->variant($productId);
            if ($defaultVariant) {
                return (int) $defaultVariant->id;
            }
        }

        return $productId;
    }
}
