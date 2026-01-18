<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\FlashSale\FlashSaleDetailResource;
use App\Http\Resources\FlashSale\FlashSaleResource;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
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

            // Create Flash Sale
            $flashSale = FlashSale::create([
                'start' => strtotime($request->start),
                'end' => strtotime($request->end),
                'status' => $request->status,
                'user_id' => Auth::id(),
            ]);

            // Add products
            if ($request->has('products') && is_array($request->products)) {
                foreach ($request->products as $productData) {
                    // Validate variant belongs to product
                    if (!empty($productData['variant_id'])) {
                        $variant = Variant::where('id', $productData['variant_id'])
                            ->where('product_id', $productData['product_id'])
                            ->first();
                        
                        if (!$variant) {
                            return response()->json([
                                'success' => false,
                                'message' => "Phân loại không thuộc sản phẩm ID {$productData['product_id']}"
                            ], 422);
                        }
                    }

                    ProductSale::create([
                        'flashsale_id' => $flashSale->id,
                        'product_id' => $productData['product_id'],
                        'variant_id' => $productData['variant_id'] ?? null,
                        'price_sale' => $productData['price_sale'],
                        'number' => $productData['number'],
                        'buy' => 0,
                        'user_id' => Auth::id(),
                    ]);
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

        } catch (\Exception $e) {
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
                // Get existing product IDs (with variant_id)
                $existingKeys = [];
                if (is_array($request->products)) {
                    foreach ($request->products as $productData) {
                        $key = $productData['product_id'] . '_' . ($productData['variant_id'] ?? 'null');
                        $existingKeys[] = $key;
                    }
                }

                // Delete products not in request
                ProductSale::where('flashsale_id', $id)
                    ->where(function($q) use ($existingKeys, $request) {
                        if (is_array($request->products)) {
                            foreach ($request->products as $productData) {
                                $q->where(function($subQ) use ($productData) {
                                    $subQ->where('product_id', $productData['product_id']);
                                    if (!empty($productData['variant_id'])) {
                                        $subQ->where('variant_id', $productData['variant_id']);
                                    } else {
                                        $subQ->whereNull('variant_id');
                                    }
                                });
                            }
                        }
                    }, 'AND', true)
                    ->delete();

                // Update or create products
                if (is_array($request->products)) {
                    foreach ($request->products as $productData) {
                        // Validate variant belongs to product
                        if (!empty($productData['variant_id'])) {
                            $variant = Variant::where('id', $productData['variant_id'])
                                ->where('product_id', $productData['product_id'])
                                ->first();
                            
                            if (!$variant) {
                                continue; // Skip invalid variant
                            }
                        }

                        $productSale = ProductSale::where('flashsale_id', $id)
                            ->where('product_id', $productData['product_id'])
                            ->where(function($q) use ($productData) {
                                if (!empty($productData['variant_id'])) {
                                    $q->where('variant_id', $productData['variant_id']);
                                } else {
                                    $q->whereNull('variant_id');
                                }
                            })
                            ->first();

                        if ($productSale) {
                            $productSale->update([
                                'price_sale' => $productData['price_sale'],
                                'number' => $productData['number'],
                            ]);
                        } else {
                            ProductSale::create([
                                'flashsale_id' => $id,
                                'product_id' => $productData['product_id'],
                                'variant_id' => $productData['variant_id'] ?? null,
                                'price_sale' => $productData['price_sale'],
                                'number' => $productData['number'],
                                'buy' => 0,
                                'user_id' => Auth::id(),
                            ]);
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

            $formattedProducts = $products->map(function($product) {
                $variant = $product->variant($product->id);
                $price = $variant ? $variant->price : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => getImage($product->image),
                    'has_variants' => (bool) $product->has_variants,
                    'price' => (float) $price,
                    'stock' => (int) $product->stock,
                    'variants' => $product->has_variants ? $product->variants->map(function($v) {
                        return [
                            'id' => $v->id,
                            'sku' => $v->sku,
                            'option1_value' => $v->option1_value,
                            'price' => (float) $v->price,
                            'stock' => (int) $v->stock,
                        ];
                    }) : [],
                ];
            });

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
}
