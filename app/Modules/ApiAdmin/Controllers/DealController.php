<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Deal\DealDetailResource;
use App\Http\Resources\Deal\DealResource;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\ProductDeal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Promotion\ProductStockValidatorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Deal API Controller for Admin
 * 
 * Handles all Deal management API endpoints following RESTful standards
 * Base URL: /admin/api/deals
 */
class DealController extends Controller
{
    protected ProductStockValidatorInterface $productStockValidator;

    public function __construct(ProductStockValidatorInterface $productStockValidator)
    {
        $this->productStockValidator = $productStockValidator;
    }

    /**
     * Get paginated list of Deals with filters
     * 
     * GET /admin/api/deals
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

            $query = Deal::query();

            // Filter by status
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }

            // Search by keyword
            if (!empty($keyword)) {
                $query->where('name', 'like', '%' . $keyword . '%');
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            // Paginate
            $deals = $query->paginate($limit, ['*'], 'page', $page);

            // Format response
            $formattedDeals = DealResource::collection($deals->items());

            return response()->json([
                'success' => true,
                'data' => $formattedDeals,
                'pagination' => [
                    'current_page' => $deals->currentPage(),
                    'per_page' => $deals->perPage(),
                    'total' => $deals->total(),
                    'last_page' => $deals->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Deals list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách Deal thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Get Deal detail with products
     * 
     * GET /admin/api/deals/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $deal = Deal::with([
                'products' => function($q) {
                    $q->with(['product', 'variant']);
                },
                'sales' => function($q) {
                    $q->with(['product', 'variant']);
                },
                'user'
            ])->find($id);

            if (!$deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal không tồn tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new DealDetailResource($deal),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Deal detail failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết Deal thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Create new Deal
     * 
     * POST /admin/api/deals
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start' => 'required|date',
                'end' => 'required|date|after:start',
                'status' => 'required|in:0,1',
                'limited' => 'required|integer|min:1',
                'products' => 'array',
                'products.*.product_id' => 'required|exists:posts,id',
                'products.*.variant_id' => 'nullable|exists:variants,id',
                'products.*.status' => 'required|in:0,1',
                'sale_products' => 'array',
                'sale_products.*.product_id' => 'required|exists:posts,id',
                'sale_products.*.variant_id' => 'nullable|exists:variants,id',
                'sale_products.*.price' => 'required|numeric|min:0',
                'sale_products.*.qty' => 'required|integer|min:1',
                'sale_products.*.status' => 'required|in:0,1',
            ], [
                'name.required' => 'Tên Deal không được bỏ trống',
                'start.required' => 'Thời gian bắt đầu không được bỏ trống',
                'end.required' => 'Thời gian kết thúc không được bỏ trống',
                'end.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu',
                'status.required' => 'Trạng thái không được bỏ trống',
                'limited.required' => 'Giới hạn số lượng không được bỏ trống',
                'products.*.product_id.exists' => 'Sản phẩm không tồn tại',
                'products.*.variant_id.exists' => 'Phân loại không tồn tại',
                'sale_products.*.product_id.exists' => 'Sản phẩm không tồn tại',
                'sale_products.*.variant_id.exists' => 'Phân loại không tồn tại',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate variants belong to products
            $validationErrors = $this->validateProductsAndVariants($request);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationErrors
                ], 422);
            }

            // Check for conflicts
            $products = $request->get('products', []);
            $conflicts = $this->checkProductConflict($products);
            if (!empty($conflicts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số sản phẩm đã thuộc Deal khác đang hoạt động',
                    'conflicts' => $conflicts
                ], 409);
            }

            // Validate product stock
            $allProducts = array_merge(
                $request->get('products', []),
                $request->get('sale_products', [])
            );
            $stockErrors = $this->productStockValidator->validateProductsStock($allProducts);
            if (!empty($stockErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Một số sản phẩm không có tồn kho, không thể tham gia Deal',
                    'errors' => $stockErrors
                ], 422);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Create Deal
                $deal = Deal::create([
                    'name' => $request->name,
                    'start' => strtotime($request->start),
                    'end' => strtotime($request->end),
                    'status' => $request->status,
                    'limited' => $request->limited,
                    'user_id' => Auth::id(),
                ]);

                // Add products
                if ($request->has('products') && is_array($request->products)) {
                    foreach ($request->products as $productData) {
                        ProductDeal::create([
                            'deal_id' => $deal->id,
                            'product_id' => $productData['product_id'],
                            'variant_id' => $productData['variant_id'] ?? null,
                            'status' => $productData['status'],
                        ]);
                    }
                }

                // Add sale products
                if ($request->has('sale_products') && is_array($request->sale_products)) {
                    foreach ($request->sale_products as $saleProductData) {
                        SaleDeal::create([
                            'deal_id' => $deal->id,
                            'product_id' => $saleProductData['product_id'],
                            'variant_id' => $saleProductData['variant_id'] ?? null,
                            'price' => $saleProductData['price'],
                            'qty' => $saleProductData['qty'],
                            'status' => $saleProductData['status'],
                        ]);
                    }
                }

                DB::commit();

                // Load relationships
                $deal->load([
                    'products' => function($q) {
                        $q->with(['product', 'variant']);
                    },
                    'sales' => function($q) {
                        $q->with(['product', 'variant']);
                    },
                    'user'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Tạo Deal thành công',
                    'data' => new DealDetailResource($deal),
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Create Deal failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tạo Deal thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Update Deal
     * 
     * PUT /admin/api/deals/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $deal = Deal::find($id);

            if (!$deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal không tồn tại'
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'start' => 'sometimes|required|date',
                'end' => 'sometimes|required|date|after:start',
                'status' => 'sometimes|required|in:0,1',
                'limited' => 'sometimes|required|integer|min:1',
                'products' => 'sometimes|array',
                'products.*.product_id' => 'required|exists:posts,id',
                'products.*.variant_id' => 'nullable|exists:variants,id',
                'products.*.status' => 'required|in:0,1',
                'sale_products' => 'sometimes|array',
                'sale_products.*.product_id' => 'required|exists:posts,id',
                'sale_products.*.variant_id' => 'nullable|exists:variants,id',
                'sale_products.*.price' => 'required|numeric|min:0',
                'sale_products.*.qty' => 'required|integer|min:1',
                'sale_products.*.status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate variants belong to products
            $validationErrors = $this->validateProductsAndVariants($request);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validationErrors
                ], 422);
            }

            // Check for conflicts (exclude current deal)
            if ($request->has('products')) {
                $products = $request->get('products', []);
                $conflicts = $this->checkProductConflict($products, $id);
                if (!empty($conflicts)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Một số sản phẩm đã thuộc Deal khác đang hoạt động',
                        'conflicts' => $conflicts
                    ], 409);
                }
            }

            // Validate product stock (if products are being updated)
            if ($request->has('products') || $request->has('sale_products')) {
                $allProducts = array_merge(
                    $request->get('products', []),
                    $request->get('sale_products', [])
                );
                if (!empty($allProducts)) {
                    $stockErrors = $this->productStockValidator->validateProductsStock($allProducts);
                    if (!empty($stockErrors)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Một số sản phẩm không có tồn kho, không thể tham gia Deal',
                            'errors' => $stockErrors
                        ], 422);
                    }
                }
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Update Deal
                if ($request->has('name')) {
                    $deal->name = $request->name;
                }
                if ($request->has('start')) {
                    $deal->start = strtotime($request->start);
                }
                if ($request->has('end')) {
                    $deal->end = strtotime($request->end);
                }
                if ($request->has('status')) {
                    $deal->status = $request->status;
                }
                if ($request->has('limited')) {
                    $deal->limited = $request->limited;
                }
                $deal->user_id = Auth::id();
                $deal->save();

                // Update products if provided
                if ($request->has('products')) {
                    // Delete old products
                    ProductDeal::where('deal_id', $id)->delete();

                    // Create new products
                    if (is_array($request->products)) {
                        foreach ($request->products as $productData) {
                            ProductDeal::create([
                                'deal_id' => $deal->id,
                                'product_id' => $productData['product_id'],
                                'variant_id' => $productData['variant_id'] ?? null,
                                'status' => $productData['status'],
                            ]);
                        }
                    }
                }

                // Update sale products if provided
                if ($request->has('sale_products')) {
                    // Delete old sale products
                    SaleDeal::where('deal_id', $id)->delete();

                    // Create new sale products
                    if (is_array($request->sale_products)) {
                        foreach ($request->sale_products as $saleProductData) {
                            SaleDeal::create([
                                'deal_id' => $deal->id,
                                'product_id' => $saleProductData['product_id'],
                                'variant_id' => $saleProductData['variant_id'] ?? null,
                                'price' => $saleProductData['price'],
                                'qty' => $saleProductData['qty'],
                                'status' => $saleProductData['status'],
                            ]);
                        }
                    }
                }

                DB::commit();

                // Load relationships
                $deal->load([
                    'products' => function($q) {
                        $q->with(['product', 'variant']);
                    },
                    'sales' => function($q) {
                        $q->with(['product', 'variant']);
                    },
                    'user'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cập nhật Deal thành công',
                    'data' => new DealDetailResource($deal),
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Update Deal failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Cập nhật Deal thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Delete Deal
     * 
     * DELETE /admin/api/deals/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deal = Deal::find($id);

            if (!$deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal không tồn tại'
                ], 404);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Delete related ProductDeals
                ProductDeal::where('deal_id', $id)->delete();

                // Delete related SaleDeals
                SaleDeal::where('deal_id', $id)->delete();

                // Delete Deal
                $deal->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Xóa Deal thành công',
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Delete Deal failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Xóa Deal thất bại',
                'error' => config('app.debug') ? $e->getMessage() : 'Lỗi máy chủ'
            ], 500);
        }
    }

    /**
     * Update Deal status
     * 
     * PATCH /admin/api/deals/{id}/status
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

            $deal = Deal::find($id);

            if (!$deal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Deal không tồn tại'
                ], 404);
            }

            $deal->status = $request->status;
            $deal->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => new DealResource($deal),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Update Deal status failed: ' . $e->getMessage(), [
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
     * Check if Deal is active
     * 
     * @param Deal $deal
     * @return bool
     */
    private function isDealActive(Deal $deal): bool
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        return $deal->status == '1' 
            && $deal->start <= $now 
            && $deal->end >= $now;
    }

    /**
     * Check for product conflicts with other active deals
     * 
     * @param array $products Mảng chứa ['product_id' => int, 'variant_id' => int|null]
     * @param int|null $excludeDealId ID Deal cần loại trừ (khi update)
     * @return array Mảng các cặp (product_id, variant_id) bị xung đột
     */
    private function checkProductConflict(array $products, ?int $excludeDealId = null): array
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $conflicts = [];
        
        foreach ($products as $product) {
            $productId = $product['product_id'];
            $variantId = $product['variant_id'] ?? null;
            
            $query = ProductDeal::where('product_id', $productId)
                ->whereHas('deal', function($q) use ($now) {
                    $q->where('status', '1')
                      ->where('start', '<=', $now)
                      ->where('end', '>=', $now);
                });
            
            if ($excludeDealId) {
                $query->where('deal_id', '!=', $excludeDealId);
            }
            
            // Check variant_id
            if ($variantId !== null) {
                $query->where(function($q) use ($variantId) {
                    $q->where('variant_id', $variantId)
                      ->orWhereNull('variant_id'); // Nếu Deal khác không chỉ định variant, cũng xung đột
                });
            } else {
                // Nếu không có variant_id, kiểm tra xem có Deal nào đã chỉ định variant của sản phẩm này không
                $query->whereNull('variant_id');
            }
            
            $existing = $query->first();
            if ($existing) {
                $conflicts[] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'conflict_deal_id' => $existing->deal_id
                ];
            }
        }
        
        return $conflicts;
    }

    /**
     * Validate products and variants
     * 
     * @param Request $request
     * @return array Validation errors
     */
    private function validateProductsAndVariants(Request $request): array
    {
        $errors = [];

        // Validate products
        if ($request->has('products') && is_array($request->products)) {
            foreach ($request->products as $index => $productData) {
                $productId = $productData['product_id'] ?? null;
                $variantId = $productData['variant_id'] ?? null;

                if ($productId) {
                    $product = Product::find($productId);
                    if ($product) {
                        // Check if product has variants
                        if ($product->has_variants == 1) {
                            // Must have variant_id
                            if (empty($variantId)) {
                                $errors["products.{$index}.variant_id"] = ["Sản phẩm có phân loại nhưng chưa chọn variant_id"];
                            } else {
                                // Validate variant belongs to product
                                if (!$this->validateVariantBelongsToProduct($productId, $variantId)) {
                                    $errors["products.{$index}.variant_id"] = ["Phân loại không thuộc về sản phẩm này"];
                                }
                            }
                        } else {
                            // Should not have variant_id
                            if (!empty($variantId)) {
                                $errors["products.{$index}.variant_id"] = ["Sản phẩm không có phân loại"];
                            }
                        }
                    }
                }
            }
        }

        // Validate sale_products
        if ($request->has('sale_products') && is_array($request->sale_products)) {
            foreach ($request->sale_products as $index => $saleProductData) {
                $productId = $saleProductData['product_id'] ?? null;
                $variantId = $saleProductData['variant_id'] ?? null;

                if ($productId) {
                    $product = Product::find($productId);
                    if ($product) {
                        // Check if product has variants
                        if ($product->has_variants == 1) {
                            // Must have variant_id
                            if (empty($variantId)) {
                                $errors["sale_products.{$index}.variant_id"] = ["Sản phẩm có phân loại nhưng chưa chọn variant_id"];
                            } else {
                                // Validate variant belongs to product
                                if (!$this->validateVariantBelongsToProduct($productId, $variantId)) {
                                    $errors["sale_products.{$index}.variant_id"] = ["Phân loại không thuộc về sản phẩm này"];
                                }
                            }
                        } else {
                            // Should not have variant_id
                            if (!empty($variantId)) {
                                $errors["sale_products.{$index}.variant_id"] = ["Sản phẩm không có phân loại"];
                            }
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate variant_id belongs to product_id
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return bool
     */
    private function validateVariantBelongsToProduct(int $productId, ?int $variantId = null): bool
    {
        if ($variantId === null) {
            return true; // NULL is valid
        }
        
        $variant = Variant::where('id', $variantId)
            ->where('product_id', $productId)
            ->first();
        
        return $variant !== null;
    }

    /**
     * Get original price from variant or product
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return float
     */
    private function getOriginalPrice(int $productId, ?int $variantId = null): float
    {
        if ($variantId) {
            $variant = Variant::find($variantId);
            if ($variant && $variant->product_id == $productId) {
                return (float) $variant->price;
            }
        }
        
        // If no variant_id, get first variant of product
        $product = Product::find($productId);
        if ($product) {
            $variant = $product->variant($productId);
            if ($variant) {
                return (float) $variant->price;
            }
        }
        
        return 0;
    }

    /**
     * Calculate savings amount
     * 
     * @param float $originalPrice
     * @param float $dealPrice
     * @param int $qty
     * @return float
     */
    private function calculateSavings(float $originalPrice, float $dealPrice, int $qty): float
    {
        return ($originalPrice - $dealPrice) * $qty;
    }
}
