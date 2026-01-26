<?php

declare(strict_types=1);
namespace App\Modules\ApiAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Warehouse\StockReceiptService;
use App\Models\StockReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Warehouse Accounting API Controller (V2)
 * 
 * Handles all warehouse accounting API endpoints for stock receipts
 */
class WarehouseAccountingController extends Controller
{
    public function __construct(
        private StockReceiptService $stockReceiptService
    ) {}

    /**
     * List stock receipts with filters
     * 
     * GET /admin/api/v2/warehouse/accounting/receipts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'type' => $request->get('type'), // import, export
                'status' => $request->get('status'), // draft, pending, approved, completed, cancelled
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'receipt_code' => $request->get('receipt_code'),
                'partner_name' => $request->get('partner_name'),
                'search' => $request->get('search'), // Search by code or partner name
            ];

            $perPage = (int)$request->get('per_page', 15);
            $page = (int)$request->get('page', 1);

            $receipts = $this->stockReceiptService->listReceipts($filters, $perPage, $page);

            return response()->json([
                'success' => true,
                'data' => $receipts->items(),
                'pagination' => [
                    'current_page' => $receipts->currentPage(),
                    'last_page' => $receipts->lastPage(),
                    'per_page' => $receipts->perPage(),
                    'total' => $receipts->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('List receipts failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách phiếu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single receipt by ID
     * 
     * GET /admin/api/v2/warehouse/accounting/receipts/{id}
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $receipt = $this->stockReceiptService->getReceipt($id);
            
            return response()->json([
                'success' => true,
                'data' => $this->formatReceipt($receipt),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get receipt failed: ' . $e->getMessage(), [
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy phiếu hoặc có lỗi xảy ra: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create new receipt
     * 
     * POST /admin/api/v2/warehouse/accounting/receipts
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:import,export',
            'receipt_code' => 'nullable|string|max:50|unique:stock_receipts,receipt_code',
            'to_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'from_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'supplier_name' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'supplier_phone' => 'nullable|string|max:20',
            'customer_phone' => 'nullable|string|max:20',
            'supplier_address' => 'nullable|string|max:500',
            'customer_address' => 'nullable|string|max:500',
            'supplier_tax_id' => 'nullable|string|max:50',
            'customer_tax_id' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:500',
            'vat_invoice' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.quantity_requested' => 'nullable|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'nullable|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.serial_number' => 'nullable|string|max:100',
            'items.*.condition' => 'nullable|in:new,used,damaged',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['created_by'] = Auth::id();
            
            $receipt = $this->stockReceiptService->createReceipt($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Tạo phiếu thành công',
                'data' => $this->formatReceipt($receipt),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Create receipt failed: ' . $e->getMessage(), [
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Tạo phiếu thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update receipt
     * 
     * PUT /admin/api/v2/warehouse/accounting/receipts/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $receipt = $this->stockReceiptService->getReceipt($id);
            
            if (!$receipt->canEdit()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phiếu đã hoàn thành, không thể chỉnh sửa',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'to_warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'from_warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'supplier_name' => 'nullable|string|max:255',
                'customer_name' => 'nullable|string|max:255',
                'supplier_phone' => 'nullable|string|max:20',
                'customer_phone' => 'nullable|string|max:20',
                'supplier_address' => 'nullable|string|max:500',
                'customer_address' => 'nullable|string|max:500',
                'supplier_tax_id' => 'nullable|string|max:50',
                'customer_tax_id' => 'nullable|string|max:50',
                'subject' => 'nullable|string|max:500',
                'vat_invoice' => 'nullable|string|max:100',
                'items' => 'nullable|array|min:1',
                'items.*.variant_id' => 'required|integer|exists:variants,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.quantity_requested' => 'nullable|numeric|min:0',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.total_price' => 'nullable|numeric|min:0',
                'items.*.batch_number' => 'nullable|string|max:100',
                'items.*.serial_number' => 'nullable|string|max:100',
                'items.*.condition' => 'nullable|in:new,used,damaged',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['updated_by'] = Auth::id();
            
            $receipt = $this->stockReceiptService->updateReceipt($id, $data);
            
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật phiếu thành công',
                'data' => $this->formatReceipt($receipt),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Update receipt failed: ' . $e->getMessage(), [
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Cập nhật phiếu thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete receipt (mark as completed and update stock)
     * 
     * POST /admin/api/v2/warehouse/accounting/receipts/{id}/complete
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $receipt = $this->stockReceiptService->completeReceipt($id, Auth::id());
            
            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành phiếu thành công',
                'data' => $this->formatReceipt($receipt),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Complete receipt failed: ' . $e->getMessage(), [
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Hoàn thành phiếu thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Void receipt (cancel and reverse stock)
     * 
     * POST /admin/api/v2/warehouse/accounting/receipts/{id}/void
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function void(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Check if receipt is from order - if so, prevent manual void
            $receipt = \App\Models\StockReceipt::findOrFail($id);
            if ($receipt->reference_type === 'order') {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể hủy phiếu được tạo từ đơn hàng. Phiếu chỉ có thể bị hủy khi đơn hàng bị hủy.',
                ], 403);
            }
            
            $receipt = $this->stockReceiptService->voidReceipt($id, Auth::id());
            
            return response()->json([
                'success' => true,
                'message' => 'Hủy phiếu thành công',
                'data' => $this->formatReceipt($receipt),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Void receipt failed: ' . $e->getMessage(), [
                'receipt_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Hủy phiếu thất bại: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get receipt statistics
     * 
     * GET /admin/api/v2/warehouse/accounting/statistics
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = [
                'type' => $request->get('type'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $stats = [
                'total_receipts' => StockReceipt::when($filters['type'], fn($q, $type) => $q->where('type', $type))
                    ->when($filters['date_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($filters['date_to'], fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ->count(),
                'total_value' => StockReceipt::when($filters['type'], fn($q, $type) => $q->where('type', $type))
                    ->when($filters['date_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($filters['date_to'], fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ->sum('total_value'),
                'import_count' => StockReceipt::where('type', 'import')
                    ->when($filters['date_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($filters['date_to'], fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ->count(),
                'export_count' => StockReceipt::where('type', 'export')
                    ->when($filters['date_from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                    ->when($filters['date_to'], fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get statistics failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format receipt for API response
     * 
     * @param StockReceipt $receipt
     * @return array
     */
    private function formatReceipt(StockReceipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'receipt_code' => $receipt->receipt_code,
            'type' => $receipt->type,
            'type_label' => $receipt->type === 'import' ? 'Nhập kho' : 'Xuất kho',
            'status' => $receipt->status,
            'status_label' => $receipt->getStatusLabel(),
            'to_warehouse_id' => $receipt->to_warehouse_id,
            'from_warehouse_id' => $receipt->from_warehouse_id,
            'supplier_name' => $receipt->supplier_name,
            'customer_name' => $receipt->customer_name,
            'supplier_phone' => $receipt->supplier_phone,
            'customer_phone' => $receipt->customer_phone,
            'supplier_address' => $receipt->supplier_address,
            'customer_address' => $receipt->customer_address,
            'supplier_tax_id' => $receipt->supplier_tax_id,
            'customer_tax_id' => $receipt->customer_tax_id,
            'subject' => $receipt->subject,
            'vat_invoice' => $receipt->vat_invoice,
            'total_value' => (float)$receipt->total_value,
            'total_value_formatted' => number_format($receipt->total_value, 0, ',', '.') . ' đ',
            'created_at' => $receipt->created_at->format('Y-m-d H:i:s'),
            'created_at_formatted' => $receipt->created_at->format('d/m/Y H:i'),
            'updated_at' => $receipt->updated_at->format('Y-m-d H:i:s'),
            'completed_at' => $receipt->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $receipt->cancelled_at?->format('Y-m-d H:i:s'),
            'created_by' => $receipt->created_by,
            'creator' => $receipt->creator ? [
                'id' => $receipt->creator->id,
                'name' => $receipt->creator->name,
            ] : null,
            'items' => $receipt->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'variant_id' => $item->variant_id,
                    'variant' => [
                        'id' => $item->variant->id,
                        'sku' => $item->variant->sku,
                        'product' => [
                            'id' => $item->variant->product->id,
                            'name' => $item->variant->product->name,
                        ],
                        'option1_value' => $item->variant->option1_value,
                    ],
                    'quantity' => (float)$item->quantity,
                    'quantity_requested' => (float)$item->quantity_requested,
                    'unit_price' => (float)$item->unit_price,
                    'total_price' => (float)$item->total_price,
                    'stock_before' => $item->stock_before,
                    'stock_after' => $item->stock_after,
                    'batch_number' => $item->batch_number,
                    'serial_number' => $item->serial_number,
                    'condition' => $item->condition,
                ];
            }),
            'can_edit' => $receipt->canEdit(),
            'can_void' => $receipt->status === StockReceipt::STATUS_COMPLETED,
            'public_url' => route('warehouse.receipt.public', ['receiptCode' => $receipt->receipt_code]),
        ];
    }
}

