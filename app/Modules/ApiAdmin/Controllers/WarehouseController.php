<?php

namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreImportReceiptRequest;
use App\Http\Requests\Warehouse\UpdateImportReceiptRequest;
use App\Http\Requests\Warehouse\StoreExportReceiptRequest;
use App\Http\Requests\Warehouse\UpdateExportReceiptRequest;
use App\Http\Resources\Warehouse\InventoryResource;
use App\Http\Resources\Warehouse\ImportReceiptResource;
use App\Http\Resources\Warehouse\ImportReceiptCollection;
use App\Http\Resources\Warehouse\ExportReceiptResource;
use App\Http\Resources\Warehouse\ExportReceiptCollection;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Services\Warehouse\WarehouseServiceInterface;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Warehouse Management API Controller (Admin)
 * 
 * Handles all warehouse management API endpoints following RESTful standards
 */
class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseServiceInterface $warehouseService
    ) {}

    /**
     * Get inventory list with filters
     * 
     * GET /admin/api/v1/warehouse/inventory
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getInventory(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('variant_id') && $request->variant_id !== '') {
                $filters['variant_id'] = $request->variant_id;
            }
            
            if ($request->has('product_id') && $request->product_id !== '') {
                $filters['product_id'] = $request->product_id;
            }
            
            if ($request->has('min_stock') && $request->min_stock !== '') {
                $filters['min_stock'] = (int) $request->min_stock;
            }
            
            if ($request->has('max_stock') && $request->max_stock !== '') {
                $filters['max_stock'] = (int) $request->max_stock;
            }
            
            if ($request->has('sort_by') && $request->sort_by !== '') {
                $filters['sort_by'] = $request->sort_by;
            }
            
            if ($request->has('sort_order') && $request->sort_order !== '') {
                $filters['sort_order'] = $request->sort_order;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            // Get inventory using service
            $inventory = $this->warehouseService->getInventory($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => InventoryResource::collection($inventory),
                'pagination' => [
                    'current_page' => $inventory->currentPage(),
                    'per_page' => $inventory->perPage(),
                    'total' => $inventory->total(),
                    'last_page' => $inventory->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get inventory list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách tồn kho thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get inventory per product (variants) with physical, reserved and available stocks.
     *
     * GET /admin/api/v1/warehouse/inventory/by-product/{productId}
     */
    public function inventoryByProduct(int $productId): JsonResponse
    {
        try {
            $product = Product::with(['variants' => function ($q) {
                $q->orderBy('position', 'asc')->orderBy('id', 'asc');
            }])->findOrFail($productId);

            $variants = $product->variants;

            // Fallback: single product without variants -> create a default variant to unblock stock workflows.
            if ($variants->isEmpty() && (int) $product->has_variants === 0) {
                $fallbackSku = $product->slug
                    ? 'SKU-' . strtoupper(str_replace(' ', '-', $product->slug))
                    : 'PROD-' . $productId . '-DEFAULT';

                $fallbackVariant = Variant::firstOrCreate(
                    ['product_id' => $productId],
                    [
                        'sku' => $fallbackSku,
                        'option1_value' => $product->option1_name ?: 'Default',
                        'price' => (float) ($product->getAttribute('price') ?? 0),
                        'stock' => (int) ($product->getAttribute('stock') ?? 0),
                        'position' => 0,
                        'user_id' => Auth::id(),
                    ]
                );

                $fallbackVariant->setAttribute('is_default_variant', true);
                $variants = collect([$fallbackVariant]);
            }

            $hasNewColumns =
                \Schema::hasColumn('product_warehouse', 'physical_stock') &&
                \Schema::hasColumn('product_warehouse', 'flash_sale_stock') &&
                \Schema::hasColumn('product_warehouse', 'deal_stock');

            $now = time();
            $dealRemainingByVariant = \DB::table('deal_sales as ds')
                ->join('deals as d', 'd.id', '=', 'ds.deal_id')
                ->where('d.status', '1')
                ->where('d.start', '<=', $now)
                ->where('d.end', '>=', $now)
                ->where('ds.status', '1')
                ->where('ds.product_id', $product->id)
                ->selectRaw('COALESCE(ds.variant_id, 0) as k, SUM(ds.qty - COALESCE(ds.buy,0)) as remaining')
                ->groupBy('k')
                ->pluck('remaining', 'k')
                ->toArray();

            $rows = $variants->map(function ($variant) use ($product, $hasNewColumns, $dealRemainingByVariant) {
                if ($hasNewColumns) {
                    $latest = ProductWarehouse::where('variant_id', $variant->id)
                        ->orderByDesc('id')
                        ->first();

                    $physical = $latest ? (int) ($latest->physical_stock ?? 0) : 0;
                    $flash = $latest ? (int) ($latest->flash_sale_stock ?? 0) : 0;
                    // Deal is computed realtime from active deals (remaining = qty - buy).
                    // If deal_sales.variant_id is NULL (no-variant product), it is stored under key 0.
                    $deal = (int) ($dealRemainingByVariant[$variant->id] ?? $dealRemainingByVariant[0] ?? 0);
                    $available = max(0, $physical - $flash - $deal);
                } else {
                    // Fallback when migration not applied: rely on InventoryService snapshot
                    $stock = app(InventoryServiceInterface::class)->getStock($variant->id);
                    $physical = (int) ($stock->physicalStock ?? 0);
                    $flash = (int) ($stock->flashSaleHold ?? 0);
                    $deal = (int) ($stock->dealHold ?? 0);
                    $available = (int) ($stock->sellableStock ?? max(0, $physical - $flash - $deal));
                }

                return [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'variant_option' => $variant->option1_value ?? 'Mặc định',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->image,
                    'physical_stock' => $physical,
                    'flash_sale_stock' => $flash,
                    'deal_stock' => $deal,
                    'available_stock' => $available,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => InventoryResource::collection($rows->map(fn($row) => (object) $row)),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Get inventory by product failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $productId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lấy tồn kho theo sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get inventory detail for a variant
     * 
     * GET /admin/api/v1/warehouse/inventory/{variantId}
     * 
     * @param int $variantId
     * @return JsonResponse
     */
    public function getVariantInventory(int $variantId): JsonResponse
    {
        try {
            $inventory = $this->warehouseService->getVariantInventory($variantId);
            
            return response()->json([
                'success' => true,
                'data' => $inventory,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phân loại sản phẩm không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get variant inventory failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'variant_id' => $variantId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết tồn kho thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get import receipts list with filters
     * 
     * GET /admin/api/v1/warehouse/import-receipts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getImportReceipts(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('code') && $request->code !== '') {
                $filters['code'] = $request->code;
            }
            
            if ($request->has('user_id') && $request->user_id !== '') {
                $filters['user_id'] = (int) $request->user_id;
            }
            
            if ($request->has('date_from') && $request->date_from !== '') {
                $filters['date_from'] = $request->date_from;
            }
            
            if ($request->has('date_to') && $request->date_to !== '') {
                $filters['date_to'] = $request->date_to;
            }
            
            if ($request->has('sort_by') && $request->sort_by !== '') {
                $filters['sort_by'] = $request->sort_by;
            }
            
            if ($request->has('sort_order') && $request->sort_order !== '') {
                $filters['sort_order'] = $request->sort_order;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            // Get import receipts using service
            $receipts = $this->warehouseService->getImportReceipts($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => ImportReceiptCollection::make($receipts)->collection,
                'pagination' => [
                    'current_page' => $receipts->currentPage(),
                    'per_page' => $receipts->perPage(),
                    'total' => $receipts->total(),
                    'last_page' => $receipts->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get import receipts list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách phiếu nhập hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get import receipt detail with items
     * 
     * GET /admin/api/v1/warehouse/import-receipts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getImportReceipt(int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->getImportReceipt($id);
            
            return response()->json([
                'success' => true,
                'data' => new ImportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu nhập hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get import receipt detail failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết phiếu nhập hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Create a new import receipt
     * 
     * POST /admin/api/v1/warehouse/import-receipts
     * 
     * @param StoreImportReceiptRequest $request
     * @return JsonResponse
     */
    public function createImportReceipt(StoreImportReceiptRequest $request): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->createImportReceipt($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Import receipt created',
                'data' => new ImportReceiptResource($receipt),
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Create import receipt failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Create import receipt failed',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update an existing import receipt
     * 
     * PUT /admin/api/v1/warehouse/import-receipts/{id}
     * 
     * @param UpdateImportReceiptRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateImportReceipt(UpdateImportReceiptRequest $request, int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->updateImportReceipt($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phiếu nhập hàng thành công',
                'data' => new ImportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu nhập hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Update import receipt failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật phiếu nhập hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Delete an import receipt
     * 
     * DELETE /admin/api/v1/warehouse/import-receipts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deleteImportReceipt(int $id): JsonResponse
    {
        try {
            $this->warehouseService->deleteImportReceipt($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Xóa phiếu nhập hàng thành công',
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu nhập hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Delete import receipt failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Xóa phiếu nhập hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get import receipt print data
     * 
     * GET /admin/api/v1/warehouse/import-receipts/{id}/print
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getImportReceiptPrint(int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->getImportReceipt($id);
            
            return response()->json([
                'success' => true,
                'data' => new ImportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu nhập hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get import receipt print data failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu in phiếu nhập hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get export receipts list with filters
     * 
     * GET /admin/api/v1/warehouse/export-receipts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExportReceipts(Request $request): JsonResponse
    {
        try {
            // Prepare filters from query parameters
            $filters = [];
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('code') && $request->code !== '') {
                $filters['code'] = $request->code;
            }
            
            if ($request->has('user_id') && $request->user_id !== '') {
                $filters['user_id'] = (int) $request->user_id;
            }
            
            if ($request->has('date_from') && $request->date_from !== '') {
                $filters['date_from'] = $request->date_from;
            }
            
            if ($request->has('date_to') && $request->date_to !== '') {
                $filters['date_to'] = $request->date_to;
            }
            
            if ($request->has('sort_by') && $request->sort_by !== '') {
                $filters['sort_by'] = $request->sort_by;
            }
            
            if ($request->has('sort_order') && $request->sort_order !== '') {
                $filters['sort_order'] = $request->sort_order;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            // Get export receipts using service
            $receipts = $this->warehouseService->getExportReceipts($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => ExportReceiptCollection::make($receipts)->collection,
                'pagination' => [
                    'current_page' => $receipts->currentPage(),
                    'per_page' => $receipts->perPage(),
                    'total' => $receipts->total(),
                    'last_page' => $receipts->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get export receipts list failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get export receipt detail with items
     * 
     * GET /admin/api/v1/warehouse/export-receipts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getExportReceipt(int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->getExportReceipt($id);
            
            return response()->json([
                'success' => true,
                'data' => new ExportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu xuất hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get export receipt detail failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy chi tiết phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Create a new export receipt
     * 
     * POST /admin/api/v1/warehouse/export-receipts
     * 
     * @param StoreExportReceiptRequest $request
     * @return JsonResponse
     */
    public function createExportReceipt(Request $request): JsonResponse
    {
        try {
            $payload = $request->json()->all();
            $items = $payload['items'] ?? [];

            if (!is_array($items) || empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu danh sách items',
                ], 422);
            }

            $errors = [];
            foreach ($items as $idx => $row) {
                if (!isset($row['variant_id']) || !isset($row['quantity'])) {
                    $errors["items.{$idx}"] = ['variant_id và quantity là bắt buộc'];
                } elseif ((int)$row['quantity'] <= 0) {
                    $errors["items.{$idx}.quantity"] = ['quantity phải > 0'];
                }
            }
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tham số không hợp lệ',
                    'errors' => $errors,
                ], 422);
            }

            $updated = [];
            DB::beginTransaction();
            try {
                foreach ($items as $row) {
                    $variantId = (int)$row['variant_id'];
                    $qty = (int)$row['quantity'];
                    $result = $this->inventoryService->manualExportStock($variantId, $qty, 'api_export_receipt');
                    if (!$result['success']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => $result['message'] ?? 'Xuất kho thất bại',
                        ], 422);
                    }
                    $updated[] = $result['data'];
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Xuất kho thành công',
                'data' => InventoryResource::collection(collect($updated)),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Create export receipt failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tạo phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Update an existing export receipt
     * 
     * PUT /admin/api/v1/warehouse/export-receipts/{id}
     * 
     * @param UpdateExportReceiptRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateExportReceipt(UpdateExportReceiptRequest $request, int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->updateExportReceipt($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phiếu xuất hàng thành công',
                'data' => new ExportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu xuất hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errors = null;
            
            // Try to parse JSON error message
            if (strpos($errorMessage, '{') === 0) {
                $errorData = json_decode($errorMessage, true);
                if (isset($errorData['errors'])) {
                    $errors = $errorData['errors'];
                }
            }
            
            Log::error('Update export receipt failed: ' . $errorMessage, [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($errors) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không đủ tồn kho để xuất hàng',
                    'errors' => $errors,
                ], 422);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $errorMessage : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Delete an export receipt
     * 
     * DELETE /admin/api/v1/warehouse/export-receipts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deleteExportReceipt(int $id): JsonResponse
    {
        try {
            $this->warehouseService->deleteExportReceipt($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Xóa phiếu xuất hàng thành công',
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu xuất hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Delete export receipt failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Xóa phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get export receipt print data
     * 
     * GET /admin/api/v1/warehouse/export-receipts/{id}/print
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getExportReceiptPrint(int $id): JsonResponse
    {
        try {
            $receipt = $this->warehouseService->getExportReceipt($id);
            
            return response()->json([
                'success' => true,
                'data' => new ExportReceiptResource($receipt),
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phiếu xuất hàng không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get export receipt print data failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy dữ liệu in phiếu xuất hàng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Search products by keyword
     * 
     * GET /admin/api/v1/warehouse/products/search
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchProducts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|min:2',
                'limit' => 'sometimes|integer|min:1|max:100',
            ]);

            $keyword = $request->get('q', '');
            $limit = (int) $request->get('limit', 50);
            
            $products = $this->warehouseService->searchProducts($keyword, $limit);
            
            return response()->json([
                'success' => true,
                'data' => $products,
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tham số không hợp lệ',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Search products failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Tìm kiếm sản phẩm thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get variants for a product
     * 
     * GET /admin/api/v1/warehouse/products/{productId}/variants
     * 
     * @param int $productId
     * @return JsonResponse
     */
    public function getProductVariants(int $productId): JsonResponse
    {
        try {
            $variants = $this->warehouseService->getProductVariants($productId);
            
            return response()->json([
                'success' => true,
                'data' => $variants,
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại hoặc không phải loại product',
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get product variants failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'product_id' => $productId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách phân loại thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get stock information for a variant
     * 
     * GET /admin/api/v1/warehouse/variants/{variantId}/stock
     * 
     * @param int $variantId
     * @return JsonResponse
     */
    public function getVariantStock(int $variantId): JsonResponse
    {
        try {
            $stock = $this->warehouseService->getVariantStock($variantId);
            
            return response()->json([
                'success' => true,
                'data' => $stock,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phân loại sản phẩm không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get variant stock failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'variant_id' => $variantId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy thông tin tồn kho thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get suggested price for a variant
     * 
     * GET /admin/api/v1/warehouse/variants/{variantId}/price
     * 
     * @param Request $request
     * @param int $variantId
     * @return JsonResponse
     */
    public function getVariantPrice(Request $request, int $variantId): JsonResponse
    {
        try {
            $type = $request->get('type', 'export');
            
            $price = $this->warehouseService->getVariantPrice($variantId, $type);
            
            return response()->json([
                'success' => true,
                'data' => $price,
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Phân loại sản phẩm không tồn tại'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Get variant price failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'variant_id' => $variantId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy giá đề xuất thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get quantity statistics
     * 
     * GET /admin/api/v1/warehouse/statistics/quantity
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getQuantityStatistics(Request $request): JsonResponse
    {
        try {
            // Prepare filters
            $filters = [];
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('sort_by') && $request->sort_by !== '') {
                $filters['sort_by'] = $request->sort_by;
            }
            
            if ($request->has('sort_order') && $request->sort_order !== '') {
                $filters['sort_order'] = $request->sort_order;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $statistics = $this->warehouseService->getQuantityStatistics($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $statistics->items(),
                'pagination' => [
                    'current_page' => $statistics->currentPage(),
                    'per_page' => $statistics->perPage(),
                    'total' => $statistics->total(),
                    'last_page' => $statistics->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get quantity statistics failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy thống kê số lượng thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get revenue statistics
     * 
     * GET /admin/api/v1/warehouse/statistics/revenue
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueStatistics(Request $request): JsonResponse
    {
        try {
            // Prepare filters
            $filters = [];
            
            if ($request->has('keyword') && $request->keyword !== '') {
                $filters['keyword'] = $request->keyword;
            }
            
            if ($request->has('sort_by') && $request->sort_by !== '') {
                $filters['sort_by'] = $request->sort_by;
            }
            
            if ($request->has('sort_order') && $request->sort_order !== '') {
                $filters['sort_order'] = $request->sort_order;
            }
            
            // Get pagination parameters
            $perPage = (int) $request->get('limit', 10);
            $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;
            
            $statistics = $this->warehouseService->getRevenueStatistics($filters, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $statistics->items(),
                'pagination' => [
                    'current_page' => $statistics->currentPage(),
                    'per_page' => $statistics->perPage(),
                    'total' => $statistics->total(),
                    'last_page' => $statistics->lastPage(),
                ],
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get revenue statistics failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy thống kê doanh thu thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }

    /**
     * Get warehouse summary statistics
     * 
     * GET /admin/api/v1/warehouse/statistics/summary
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSummaryStatistics(Request $request): JsonResponse
    {
        try {
            // Prepare filters
            $filters = [];
            
            if ($request->has('date_from') && $request->date_from !== '') {
                $filters['date_from'] = $request->date_from;
            }
            
            if ($request->has('date_to') && $request->date_to !== '') {
                $filters['date_to'] = $request->date_to;
            }
            
            $statistics = $this->warehouseService->getSummaryStatistics($filters);
            
            return response()->json([
                'success' => true,
                'data' => $statistics,
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get summary statistics failed: ' . $e->getMessage(), [
                'method' => __METHOD__,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Lấy thống kê tổng hợp thất bại',
                'error' => config('app.debug') ? $e->getMessage() : '服务器内部错误'
            ], 500);
        }
    }
}
