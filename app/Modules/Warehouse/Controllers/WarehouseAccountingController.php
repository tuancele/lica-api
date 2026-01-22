<?php

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Warehouse\StockReceiptService;
use App\Models\StockReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Warehouse Accounting Controller
 * Handles warehouse accounting form (A4 format)
 */
class WarehouseAccountingController extends Controller
{
    public function __construct(
        private StockReceiptService $stockReceiptService
    ) {}

    /**
     * List receipts (Index page)
     */
    public function index(Request $request)
    {
        active('warehouse', 'accounting');
        return view('Warehouse::accounting-index', [
            'apiToken' => Auth::user()?->api_token ?? '',
        ]);
    }

    /**
     * Show accounting form (Create/Edit)
     */
    public function create(Request $request)
    {
        active('warehouse', 'accounting');
        
        $data = [
            'receipt' => null,
            'apiToken' => Auth::user()?->api_token ?? '',
        ];

        // If editing existing receipt
        if ($request->has('id') && $request->id) {
            try {
                $data['receipt'] = $this->stockReceiptService->getReceipt((int)$request->id);
            } catch (\Exception $e) {
                // Receipt not found, continue with new receipt
            }
        }

        return view('Warehouse::accounting', $data);
    }

    /**
     * Get receipts list (API for DataTable)
     */
    public function list(Request $request)
    {
        $filters = [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'keyword' => $request->get('keyword'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $perPage = $request->get('per_page', $request->get('length', 20));
        $receipts = $this->stockReceiptService->listReceipts($filters, $perPage);

        // Format for DataTable
        return response()->json([
            'draw' => (int)$request->get('draw', 1),
            'recordsTotal' => $receipts->total(),
            'recordsFiltered' => $receipts->total(),
            'data' => $receipts->items(),
        ]);
    }

    /**
     * Void receipt (Hủy phiếu và hoàn kho)
     */
    public function void(Request $request, int $id)
    {
        try {
            $receipt = $this->stockReceiptService->voidReceipt($id, Auth::id());
            
            return response()->json([
                'success' => true,
                'message' => 'Hủy phiếu thành công',
                'data' => [
                    'id' => $receipt->id,
                    'status' => $receipt->status,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hủy phiếu thất bại: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Print receipt (Load receipt for printing)
     */
    public function print(int $id)
    {
        $receipt = $this->stockReceiptService->getReceipt($id);
        return view('Warehouse::accounting-print', ['receipt' => $receipt]);
    }

    /**
     * Generate QR code image for receipt
     */
    public function qrCode(string $receiptCode)
    {
        try {
            // Use SVG format (doesn't require imagick extension)
            return response(
                QrCode::format('svg')
                    ->size(120)
                    ->margin(1)
                    ->generate($receiptCode),
                200,
                ['Content-Type' => 'image/svg+xml']
            );
        } catch (\Exception $e) {
            \Log::error('QR Code generation failed: ' . $e->getMessage(), [
                'receipt_code' => $receiptCode,
                'trace' => $e->getTraceAsString()
            ]);
            // Return a simple error image or 404
            abort(404, 'QR Code not found');
        }
    }

    /**
     * Store receipt
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:import,export',
            'receipt_code' => 'nullable|string|max:50|unique:stock_receipts,receipt_code',
            'subject' => 'required|string|max:255',
            'content' => 'nullable|string',
            'vat_invoice' => 'nullable|string|max:100',
            'supplier_name' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'status' => 'nullable|in:draft,pending,approved,completed',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $receipt = $this->stockReceiptService->createReceipt($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Lưu phiếu thành công',
                'data' => [
                    'id' => $receipt->id,
                    'receipt_code' => $receipt->receipt_code,
                    'view_url' => route('warehouse.accounting.create', ['id' => $receipt->id]),
                ],
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lưu phiếu thất bại: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete receipt
     */
    public function complete(Request $request, int $id)
    {
        try {
            $receipt = $this->stockReceiptService->completeReceipt($id, Auth::id());
            
            return response()->json([
                'success' => true,
                'message' => 'Hoàn thành phiếu thành công',
                'data' => [
                    'id' => $receipt->id,
                    'status' => $receipt->status,
                ],
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hoàn thành phiếu thất bại: ' . $e->getMessage(),
            ], 422);
        }
    }
}

