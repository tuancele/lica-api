<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\StockReceipt;
use App\Models\WarehouseV2;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use App\Services\Inventory\DTOs\AdjustStockDTO;
use App\Services\Inventory\DTOs\ExportStockDTO;
use App\Services\Inventory\DTOs\ImportStockDTO;
use App\Services\Inventory\DTOs\TransferStockDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private InventoryServiceInterface $inventory) {}

    // Stock endpoints
    public function stocks(Request $request): JsonResponse
    {
        $query = InventoryStock::with(['variant.product', 'warehouse']);

        if ($request->warehouse_id) {
            $query->forWarehouse($request->warehouse_id);
        }
        if ($request->keyword) {
            $query->whereHas(
                'variant',
                fn ($q) => $q->where('sku', 'like', "%{$request->keyword}%")
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$request->keyword}%"))
            );
        }
        if ($request->low_stock_only) {
            $query->lowStock();
        }
        if ($request->out_of_stock_only) {
            $query->outOfStock();
        }

        $stocks = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $stocks->items(),
            'pagination' => [
                'current_page' => $stocks->currentPage(),
                'last_page' => $stocks->lastPage(),
                'per_page' => $stocks->perPage(),
                'total' => $stocks->total(),
            ],
        ]);
    }

    public function stockShow(int $variantId, Request $request): JsonResponse
    {
        $stock = $this->inventory->getStock($variantId, $request->warehouse_id);

        return response()->json(['success' => true, 'data' => $stock->toArray()]);
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $results = $this->inventory->checkAvailabilityBatch($request->items, $request->warehouse_id);
        $allAvailable = collect($results)->every('is_available');

        return response()->json([
            'success' => true,
            'data' => $results,
            'all_available' => $allAvailable,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $items = $this->inventory->getLowStockItems($request->warehouse_id);

        return response()->json(['success' => true, 'data' => $items]);
    }

    // Receipt endpoints
    public function receipts(Request $request): JsonResponse
    {
        $query = StockReceipt::with(['creator', 'fromWarehouse', 'toWarehouse']);

        if ($request->type) {
            $query->ofType($request->type);
        }
        if ($request->status) {
            $query->ofStatus($request->status);
        }
        if ($request->keyword) {
            $query->where(
                fn ($q) => $q->where('receipt_code', 'like', "%{$request->keyword}%")
                    ->orWhere('subject', 'like', "%{$request->keyword}%")
            );
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $receipts->items(),
            'pagination' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'total' => $receipts->total(),
            ],
        ]);
    }

    public function receiptShow(int $id): JsonResponse
    {
        $receipt = StockReceipt::with(['items.variant.product', 'creator', 'fromWarehouse', 'toWarehouse'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $receipt]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'subject' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $receipt = $this->inventory->import(ImportStockDTO::fromArray(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            )));

            return response()->json([
                'success' => true,
                'message' => 'Nhập kho thành công',
                'data' => $receipt,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'subject' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $receipt = $this->inventory->export(ExportStockDTO::fromArray(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            )));

            return response()->json([
                'success' => true,
                'message' => 'Xuất kho thành công',
                'data' => $receipt,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'from_warehouse_id' => 'required|integer|exists:warehouses_v2,id',
            'to_warehouse_id' => 'required|integer|exists:warehouses_v2,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
        ]);

        try {
            $receipt = $this->inventory->transfer(TransferStockDTO::fromArray(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            )));

            return response()->json(['success' => true, 'data' => $receipt], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function adjust(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.new_quantity' => 'required|integer|min:0',
        ]);

        try {
            $receipt = $this->inventory->adjust(AdjustStockDTO::fromArray(array_merge(
                $request->all(),
                ['created_by' => auth()->id()]
            )));

            return response()->json(['success' => true, 'data' => $receipt], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receiptDestroy(int $id): JsonResponse
    {
        $receipt = StockReceipt::findOrFail($id);

        if (! in_array($receipt->status, ['draft', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể xóa phiếu ở trạng thái nháp hoặc chờ duyệt',
            ], 422);
        }

        $receipt->delete();

        return response()->json(['success' => true, 'message' => 'Đã xóa phiếu']);
    }

    // Warehouse endpoints
    public function warehouses(): JsonResponse
    {
        $warehouses = app(\App\Services\Warehouse\WarehouseV2ServiceInterface::class)->getActive();

        return response()->json(['success' => true, 'data' => $warehouses]);
    }

    // Movement history
    public function movements(Request $request): JsonResponse
    {
        $request->validate(['variant_id' => 'required|integer']);

        $movements = $this->inventory->getMovementHistory($request->variant_id, [
            'warehouse_id' => $request->warehouse_id,
            'limit' => $request->limit ?? 50,
        ]);

        return response()->json(['success' => true, 'data' => $movements]);
    }

    // Reports
    public function valuation(Request $request): JsonResponse
    {
        $data = $this->inventory->getInventoryValuation($request->warehouse_id);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
