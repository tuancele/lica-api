<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\StoreVariantRequest;
use App\Http\Requests\Product\UpdateVariantRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductCollection;
use App\Http\Resources\Product\VariantResource;
use App\Services\Product\ProductServiceInterface;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Order\Models\OrderDetail;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\ProductNotFoundException;
use App\Exceptions\ProductCreationException;
use App\Exceptions\ProductUpdateException;
use App\Exceptions\ProductDeletionException;

/**
 * Product API Controller for Admin
 * 
 * Handles all product management API endpoints following RESTful standards
 */
class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    /**
     * Get paginated list of products with filters
     * 
     * GET /admin/api/products
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('status') && $request->status !== '') {
                $filters['status'] = $request->status;
            }
            
            if ($request->has('cat_id') && $request->cat_id !== '') {
                $filters['cat_id'] = $request->cat_id;
            }
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('feature') && $request->feature !== '') {
                $filters['feature'] = $request->feature;
            }
            
            if ($request->has('best') && $request->best !== '') {
                $filters['best'] = $request->best;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            // Get products using service
            $products = $this->productService->getProducts($filters, $perPage);
            
            // Format response using ProductCollection
            $formattedProducts = new ProductCollection($products);
            
            return response()->json([
                'success' => true,
                'data' => $formattedProducts->collection,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取产品列表失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取产品列表失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get single product details with relations
     * 
     * GET /admin/api/products/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            // Get product with relations using service
            $product = $this->productService->getProductWithRelations($id);
            
            return response()->json([
                'success' => true,
                'data' => new ProductResource($product),
            ], 200);
            
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '产品不存在'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('获取产品详情失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
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
     * Create a new product
     * 
     * POST /admin/api/products
     * 
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            // Create product using service
            $product = $this->productService->createProduct($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => '产品创建成功',
                'data' => new ProductResource($product),
            ], 201);
            
        } catch (ProductCreationException $e) {
            return response()->json([
                'success' => false,
                'message' => '产品创建失败: ' . $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('创建产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '产品创建失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update an existing product
     * 
     * PUT /admin/api/products/{id}
     * 
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            // Merge ID from URL into request data
            $data = $request->validated();
            $data['id'] = $id;
            
            // Log for debugging
            Log::debug('Product update request', [
                'product_id' => $id,
                'data_keys' => array_keys($data),
                'has_variants' => $data['has_variants'] ?? null,
            ]);
            
            // Update product using service
            $product = $this->productService->updateProduct($id, $data);
            
            return response()->json([
                'success' => true,
                'message' => '产品更新成功',
                'data' => new ProductResource($product),
            ], 200);
            
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '产品不存在'
            ], 404);
            
        } catch (ProductUpdateException $e) {
            return response()->json([
                'success' => false,
                'message' => '产品更新失败: ' . $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('更新产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '产品更新失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Delete a product
     * 
     * DELETE /admin/api/products/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Delete product using service
            $this->productService->deleteProduct($id);
            
            return response()->json([
                'success' => true,
                'message' => '产品删除成功'
            ], 200);
            
        } catch (ProductNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '产品不存在'
            ], 404);
            
        } catch (ProductDeletionException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('删除产品失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '删除产品失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update product status
     * 
     * PATCH /admin/api/products/{id}/status
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            // Validate status parameter
            $validator = Validator::make($request->all(), [
                'status' => ['required', 'in:0,1']
            ], [
                'status.required' => '状态值不能为空',
                'status.in' => '状态值必须是0或1'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Update status
            $product->status = $request->status;
            $product->save();
            
            return response()->json([
                'success' => true,
                'message' => '状态更新成功',
                'data' => [
                    'id' => $product->id,
                    'status' => $product->status
                ]
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('更新产品状态失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '更新状态失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Bulk actions on products
     * 
     * POST /admin/api/products/bulk-action
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'checklist' => ['required', 'array', 'min:1'],
                'checklist.*' => ['integer', 'exists:posts,id'],
                'action' => ['required', 'in:0,1,2']
            ], [
                'checklist.required' => '请选择要操作的产品',
                'checklist.array' => '产品列表格式不正确',
                'checklist.min' => '至少选择一个产品',
                'action.required' => '请选择操作类型',
                'action.in' => '操作类型无效'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            $checklist = $request->checklist;
            $action = (int) $request->action;
            $affectedCount = 0;
            
            if ($action === 0) {
                // Hide products (status = 0)
                $affectedCount = Product::whereIn('id', $checklist)
                    ->where('type', ProductType::PRODUCT->value)
                    ->update(['status' => ProductStatus::INACTIVE->value]);
                    
                return response()->json([
                    'success' => true,
                    'message' => '批量隐藏成功',
                    'affected_count' => $affectedCount
                ], 200);
                
            } elseif ($action === 1) {
                // Show products (status = 1)
                $affectedCount = Product::whereIn('id', $checklist)
                    ->where('type', ProductType::PRODUCT->value)
                    ->update(['status' => ProductStatus::ACTIVE->value]);
                    
                return response()->json([
                    'success' => true,
                    'message' => '批量显示成功',
                    'affected_count' => $affectedCount
                ], 200);
                
            } else {
                // Delete products (action = 2)
                $deletedCount = 0;
                $errors = [];
                
                foreach ($checklist as $productId) {
                    try {
                        $this->productService->deleteProduct($productId);
                        $deletedCount++;
                    } catch (ProductDeletionException $e) {
                        $errors[] = "产品 ID {$productId}: " . $e->getMessage();
                    } catch (\Exception $e) {
                        Log::error("批量删除产品失败: ID {$productId}", [
                            'error' => $e->getMessage()
                        ]);
                        $errors[] = "产品 ID {$productId}: 删除失败";
                    }
                }
                
                if (count($errors) > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => "部分删除成功，已删除 {$deletedCount} 个产品",
                        'affected_count' => $deletedCount,
                        'errors' => $errors
                    ], 200);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => '批量删除成功',
                    'affected_count' => $deletedCount
                ], 200);
            }
            
        } catch (\Exception $e) {
            Log::error('批量操作失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '批量操作失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update product sort order
     * 
     * PATCH /admin/api/products/sort
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSort(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'sort' => ['required', 'array']
            ], [
                'sort.required' => '排序数据不能为空',
                'sort.array' => '排序数据格式不正确'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => '验证失败',
                    'errors' => $validator->errors()
                ], 400);
            }
            
            $sort = $request->sort;
            $updatedCount = 0;
            
            foreach ($sort as $productId => $sortValue) {
                $productId = (int) $productId;
                $sortValue = (int) $sortValue;
                
                Product::where('id', $productId)
                    ->where('type', ProductType::PRODUCT->value)
                    ->update(['sort' => $sortValue]);
                    
                $updatedCount++;
            }
            
            return response()->json([
                'success' => true,
                'message' => '排序更新成功',
                'affected_count' => $updatedCount
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('更新排序失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '更新排序失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get all variants for a product
     * 
     * GET /admin/api/products/{id}/variants
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getVariants(int $id): JsonResponse
    {
        try {
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Get variants with relations
            $variants = Variant::where('product_id', $id)
                ->with(['color', 'size'])
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => VariantResource::collection($variants)
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取变体列表失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取变体列表失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get single variant details
     * 
     * GET /admin/api/products/{id}/variants/{code}
     * 
     * @param int $id
     * @param int $code
     * @return JsonResponse
     */
    public function getVariant(int $id, int $code): JsonResponse
    {
        try {
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Get variant with relations
            $variant = Variant::where('id', $code)
                ->where('product_id', $id)
                ->with(['color', 'size'])
                ->first();
            
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => '变体不存在或不属于该产品'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => new VariantResource($variant)
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('获取变体详情失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'variant_id' => $code,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '获取变体详情失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Create a new variant for a product
     * 
     * POST /admin/api/products/{id}/variants
     * 
     * @param StoreVariantRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function createVariant(StoreVariantRequest $request, int $id): JsonResponse
    {
        try {
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Merge product_id from URL
            $data = $request->validated();
            $data['product_id'] = $id;
            $data['user_id'] = auth()->id();
            
            // Create variant
            $variant = Variant::create($data);
            
            // Load relations
            $variant->load(['color', 'size']);
            
            return response()->json([
                'success' => true,
                'message' => '变体创建成功',
                'data' => new VariantResource($variant)
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('创建变体失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '创建变体失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update an existing variant
     * 
     * PUT /admin/api/products/{id}/variants/{code}
     * 
     * @param UpdateVariantRequest $request
     * @param int $id
     * @param int $code
     * @return JsonResponse
     */
    public function updateVariant(UpdateVariantRequest $request, int $id, int $code): JsonResponse
    {
        try {
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Check if variant exists and belongs to product
            $variant = Variant::where('id', $code)
                ->where('product_id', $id)
                ->first();
            
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => '变体不存在或不属于该产品'
                ], 404);
            }
            
            // Update variant
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            
            $variant->update($data);
            
            // Reload with relations
            $variant->load(['color', 'size']);
            
            return response()->json([
                'success' => true,
                'message' => '变体更新成功',
                'data' => new VariantResource($variant)
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('更新变体失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'variant_id' => $code,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '更新变体失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Delete a variant
     * 
     * DELETE /admin/api/products/{id}/variants/{code}
     * 
     * @param int $id
     * @param int $code
     * @return JsonResponse
     */
    public function deleteVariant(int $id, int $code): JsonResponse
    {
        try {
            // Check if product exists
            $product = Product::where('id', $id)
                ->where('type', ProductType::PRODUCT->value)
                ->first();
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => '产品不存在'
                ], 404);
            }
            
            // Check if variant exists and belongs to product
            $variant = Variant::where('id', $code)
                ->where('product_id', $id)
                ->first();
            
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => '变体不存在或不属于该产品'
                ], 404);
            }
            
            // Check if variant has orders
            $hasOrders = OrderDetail::where('variant_id', $code)->exists();
            
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm đã có đơn hàng không thể xóa!'
                ], 400);
            }
            
            // Delete variant
            $variant->delete();
            
            return response()->json([
                'success' => true,
                'message' => '变体删除成功'
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('删除变体失败: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $id,
                'variant_id' => $code,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '删除变体失败',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }
}
