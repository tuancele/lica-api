<?php

declare(strict_types=1);

namespace App\Services\Warehouse;

use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Modules\Product\Models\Variant;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stock Receipt Service
 * Handles business logic for stock receipts (import/export).
 */
class StockReceiptService
{
    public function __construct(
        private InventoryServiceInterface $inventoryService,
        private WarehouseServiceInterface $warehouseService
    ) {}

    /**
     * Generate receipt code
     * Format: [PN/PX] + [yymmdd] + [4 ký tự hash].
     */
    public function generateReceiptCode(string $type): string
    {
        $prefix = $type === 'import' ? 'PN' : 'PX';
        $date = now()->format('ymd');
        $hash = strtoupper(Str::random(4));

        return "{$prefix}{$date}{$hash}";
    }

    /**
     * Create stock receipt.
     */
    public function createReceipt(array $data): StockReceipt
    {
        DB::beginTransaction();

        try {
            // Generate receipt code if not provided
            if (empty($data['receipt_code'])) {
                $data['receipt_code'] = $this->generateReceiptCode($data['type']);
            }

            // Ensure receipt code is unique
            while (StockReceipt::where('receipt_code', $data['receipt_code'])->exists()) {
                $data['receipt_code'] = $this->generateReceiptCode($data['type']);
            }

            // Create receipt
            $receipt = StockReceipt::create([
                'receipt_code' => $data['receipt_code'],
                'type' => $data['type'],
                'status' => $data['status'] ?? StockReceipt::STATUS_DRAFT,
                'subject' => $data['subject'],
                'content' => $data['content'] ?? null,
                'vat_invoice' => $data['vat_invoice'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'supplier_phone' => $data['supplier_phone'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'supplier_address' => $data['supplier_address'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'supplier_tax_id' => $data['supplier_tax_id'] ?? null,
                'customer_tax_id' => $data['customer_tax_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'to_warehouse_id' => $data['to_warehouse_id'] ?? 1,
                'from_warehouse_id' => $data['from_warehouse_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_code' => $data['reference_code'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Create items
            $totalItems = 0;
            $totalQuantity = 0;
            $totalValue = 0;

            foreach ($data['items'] ?? [] as $itemData) {
                $variant = Variant::findOrFail($itemData['variant_id']);

                // Get stock before
                $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                $stockBefore = $stockInfo['physical_stock'] ?? 0;

                // Calculate total_price if not provided
                $totalPrice = $itemData['total_price'] ?? ($itemData['quantity'] * ($itemData['unit_price'] ?? 0));

                // Create item
                $item = StockReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'variant_id' => $itemData['variant_id'],
                    'quantity' => $itemData['quantity'],
                    'quantity_requested' => $itemData['quantity_requested'] ?? $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'total_price' => $totalPrice,
                    'stock_before' => $stockBefore,
                    'stock_after' => null, // Will be updated when completed
                    'batch_number' => $itemData['batch_number'] ?? null,
                    'serial_number' => $itemData['serial_number'] ?? null,
                    'condition' => $itemData['condition'] ?? 'new',
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $totalItems++;
                $totalQuantity += $itemData['quantity'];
                $totalValue += $totalPrice;
            }

            // Update receipt totals
            $receipt->update([
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ]);

            // If status is completed, update stock immediately
            if ($receipt->status === StockReceipt::STATUS_COMPLETED) {
                $this->completeReceipt($receipt->id, Auth::id());
            }

            DB::commit();

            return $receipt->load(['items.variant.product', 'creator']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create stock receipt failed: '.$e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Complete receipt (update stock).
     *
     * @param  bool  $updateStock  If true, update warehouse stock; if false, only update status (for receipts created from orders)
     */
    public function completeReceipt(int $receiptId, int $userId, bool $updateStock = true): StockReceipt
    {
        DB::beginTransaction();

        try {
            $receipt = StockReceipt::with('items.variant')->findOrFail($receiptId);

            // Validation: If receipt is created from order, force updateStock = false
            // This ensures stock is not updated when completing receipts from orders
            if ($receipt->reference_type === 'order') {
                if ($updateStock) {
                    Log::warning('Attempted to complete order receipt with stock update - forcing updateStock=false', [
                        'receipt_id' => $receiptId,
                        'receipt_code' => $receipt->receipt_code,
                        'reference_id' => $receipt->reference_id,
                        'reference_code' => $receipt->reference_code,
                    ]);
                }
                $updateStock = false; // Force no stock update for order receipts
            }

            if ($receipt->status !== StockReceipt::STATUS_APPROVED && $receipt->status !== StockReceipt::STATUS_DRAFT) {
                throw new \Exception('Chỉ có thể hoàn thành phiếu ở trạng thái nháp hoặc đã duyệt');
            }

            // Only update stock if $updateStock is true (manual receipts)
            if ($updateStock) {
                foreach ($receipt->items as $item) {
                    $variant = $item->variant;
                    $quantity = $item->quantity;

                    // Get stock before
                    $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                    $stockBefore = $stockInfo['physical_stock'] ?? 0;

                    // Update stock
                    if ($receipt->type === 'import') {
                        // Import: increase stock
                        $result = $this->inventoryService->importStock(
                            $variant->id,
                            $quantity,
                            'warehouse_import: '.$receipt->receipt_code
                        );
                        $stockAfter = $result['after'] ?? ($stockBefore + $quantity);
                    } else {
                        // Export: decrease stock
                        // Validate stock availability
                        $availableStock = $stockInfo['available_stock'] ?? 0;
                        if ($quantity > $availableStock) {
                            throw new \Exception("Không đủ tồn kho cho variant {$variant->sku}. Yêu cầu: {$quantity}, Có sẵn: {$availableStock}");
                        }

                        $result = $this->inventoryService->manualExportStock(
                            $variant->id,
                            $quantity,
                            'warehouse_export: '.$receipt->receipt_code
                        );
                        $stockAfter = $result['after'] ?? ($stockBefore - $quantity);
                    }

                    // Update item with stock after
                    $item->update([
                        'stock_after' => $stockAfter,
                    ]);
                }
            } else {
                // For receipts from orders, just record stock_after without updating warehouse
                foreach ($receipt->items as $item) {
                    $variant = $item->variant;
                    $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                    $stockAfter = $stockInfo['physical_stock'] ?? 0;

                    // Record current stock without updating
                    $item->update([
                        'stock_after' => $stockAfter,
                    ]);
                }
            }

            // Update receipt status
            $receipt->update([
                'status' => StockReceipt::STATUS_COMPLETED,
                'completed_by' => $userId,
                'completed_at' => now(),
            ]);

            DB::commit();

            return $receipt->load(['items.variant.product', 'creator', 'completer']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Complete stock receipt failed: '.$e->getMessage(), [
                'receipt_id' => $receiptId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update receipt.
     */
    public function updateReceipt(int $id, array $data): StockReceipt
    {
        DB::beginTransaction();

        try {
            $receipt = StockReceipt::with('items')->findOrFail($id);

            if (! $receipt->canEdit()) {
                throw new \Exception('Phiếu đã hoàn thành, không thể chỉnh sửa');
            }

            // Update receipt fields
            $receiptFields = [
                'to_warehouse_id', 'from_warehouse_id',
                'supplier_name', 'customer_name',
                'supplier_phone', 'customer_phone',
                'supplier_address', 'customer_address',
                'supplier_tax_id', 'customer_tax_id',
                'subject', 'vat_invoice',
                'updated_by',
            ];

            foreach ($receiptFields as $field) {
                if (isset($data[$field])) {
                    $receipt->$field = $data[$field];
                }
            }

            $receipt->save();

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $receipt->items()->delete();

                // Create new items
                foreach ($data['items'] as $itemData) {
                    $variant = Variant::findOrFail($itemData['variant_id']);

                    // Calculate total_price if not provided
                    if (! isset($itemData['total_price'])) {
                        $itemData['total_price'] = $itemData['quantity'] * $itemData['unit_price'];
                    }

                    // Get stock before (for audit)
                    $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                    $itemData['stock_before'] = $stockInfo['physical_stock'] ?? 0;
                    $itemData['stock_after'] = $itemData['stock_before']; // Will be updated when completed

                    $receipt->items()->create($itemData);
                }

                // Recalculate totals
                $receipt->recalculateTotals();
            }

            DB::commit();

            return $receipt->fresh(['items.variant.product', 'creator', 'toWarehouse', 'fromWarehouse']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update receipt failed: '.$e->getMessage(), [
                'receipt_id' => $id,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Get receipt with items.
     */
    public function getReceipt(int $id): StockReceipt
    {
        return StockReceipt::with([
            'items.variant.product',
            'creator',
            'approver',
            'completer',
            'toWarehouse',
            'fromWarehouse',
        ])->findOrFail($id);
    }

    /**
     * List receipts with filters.
     */
    public function listReceipts(array $filters = [], int $perPage = 20, int $page = 1)
    {
        $query = StockReceipt::with(['creator', 'items.variant.product']);

        if (isset($filters['type']) && ! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status']) && ! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['receipt_code']) && ! empty($filters['receipt_code'])) {
            $query->where('receipt_code', 'like', "%{$filters['receipt_code']}%");
        }

        if (isset($filters['partner_name']) && ! empty($filters['partner_name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('supplier_name', 'like', "%{$filters['partner_name']}%")
                    ->orWhere('customer_name', 'like', "%{$filters['partner_name']}%");
            });
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('receipt_code', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Support both 'keyword' (legacy) and 'search' (new)
        if (isset($filters['keyword']) && ! empty($filters['keyword']) && ! isset($filters['search'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('receipt_code', 'like', "%{$keyword}%")
                    ->orWhere('subject', 'like', "%{$keyword}%")
                    ->orWhere('supplier_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['date_from']) && ! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && ! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Void receipt (Hủy phiếu và hoàn kho)
     * Chỉ có thể hủy phiếu đã completed.
     *
     * @param  bool  $updateStock  If true, reverse warehouse stock; if false, only update status (for receipts created from orders)
     */
    public function voidReceipt(int $receiptId, int $userId, bool $updateStock = true): StockReceipt
    {
        DB::beginTransaction();

        try {
            $receipt = StockReceipt::with('items.variant')->findOrFail($receiptId);

            // Validation: Receipts created from orders cannot be voided manually
            // They can only be cancelled when the order is cancelled
            if ($receipt->reference_type === 'order' && $updateStock) {
                throw new \Exception('Không thể hủy phiếu được tạo từ đơn hàng. Phiếu chỉ có thể bị hủy khi đơn hàng bị hủy.');
            }

            // If receipt is from order, force updateStock = false
            if ($receipt->reference_type === 'order') {
                if ($updateStock) {
                    Log::warning('Attempted to void order receipt with stock update - forcing updateStock=false', [
                        'receipt_id' => $receiptId,
                        'receipt_code' => $receipt->receipt_code,
                        'reference_id' => $receipt->reference_id,
                        'reference_code' => $receipt->reference_code,
                    ]);
                }
                $updateStock = false; // Force no stock update for order receipts
            }

            if ($receipt->status !== StockReceipt::STATUS_COMPLETED) {
                throw new \Exception('Chỉ có thể hủy phiếu đã hoàn thành');
            }

            if ($receipt->status === StockReceipt::STATUS_CANCELLED) {
                throw new \Exception('Phiếu đã được hủy trước đó');
            }

            // Only reverse stock if $updateStock is true (manual receipts)
            if ($updateStock) {
                // Reverse stock changes
                foreach ($receipt->items as $item) {
                    $variant = $item->variant;
                    $quantity = $item->quantity;

                    // Get current stock
                    $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                    $stockBefore = $stockInfo['physical_stock'] ?? 0;

                    // Reverse: Import -> subtract, Export -> add
                    if ($receipt->type === 'import') {
                        // Import was added, so subtract
                        $result = $this->inventoryService->manualExportStock(
                            $variant->id,
                            $quantity,
                            'warehouse_void_import: '.$receipt->receipt_code
                        );
                        $stockAfter = $result['after'] ?? ($stockBefore - $quantity);
                    } else {
                        // Export was subtracted, so add back
                        $result = $this->inventoryService->importStock(
                            $variant->id,
                            $quantity,
                            'warehouse_void_export: '.$receipt->receipt_code
                        );
                        $stockAfter = $result['after'] ?? ($stockBefore + $quantity);
                    }

                    // Update item with void info
                    $item->update([
                        'stock_after' => $stockAfter,
                        'notes' => ($item->notes ?? '').' [Đã hủy: '.now()->format('Y-m-d H:i:s').']',
                    ]);
                }
            } else {
                // For receipts from orders, just record current stock without reversing
                foreach ($receipt->items as $item) {
                    $variant = $item->variant;
                    $stockInfo = $this->warehouseService->getVariantStock($variant->id);
                    $stockAfter = $stockInfo['physical_stock'] ?? 0;

                    // Update item with void info (no stock reversal)
                    $item->update([
                        'stock_after' => $stockAfter,
                        'notes' => ($item->notes ?? '').' [Đã hủy: '.now()->format('Y-m-d H:i:s').']',
                    ]);
                }
            }

            // Update receipt status
            $receipt->update([
                'status' => StockReceipt::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
            ]);

            DB::commit();

            Log::info('Receipt voided successfully', [
                'receipt_id' => $receiptId,
                'receipt_code' => $receipt->receipt_code,
                'voided_by' => $userId,
            ]);

            return $receipt->fresh(['items.variant.product', 'creator', 'canceller']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Void receipt failed: '.$e->getMessage(), [
                'receipt_id' => $receiptId,
                'user_id' => $userId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
