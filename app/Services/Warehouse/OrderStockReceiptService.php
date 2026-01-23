<?php

namespace App\Services\Warehouse;

use App\Models\StockReceipt;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Services\Warehouse\StockReceiptService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Order Stock Receipt Service
 * Handles automatic creation and management of stock receipts from orders
 */
class OrderStockReceiptService
{
    public function __construct(
        private StockReceiptService $stockReceiptService
    ) {}

    /**
     * Create export receipt from order
     * 
     * @param Order $order
     * @param string $status Receipt status (draft, completed)
     * @return StockReceipt|null
     */
    public function createExportReceiptFromOrder(Order $order, string $status = 'completed'): ?StockReceipt
    {
        // Check if receipt already exists for this order
        $existingReceipt = StockReceipt::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        if ($existingReceipt) {
            Log::info('Export receipt already exists for order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'receipt_id' => $existingReceipt->id,
                'receipt_code' => $existingReceipt->receipt_code,
            ]);
            return $existingReceipt;
        }

        try {
            DB::beginTransaction();

            // Get order details
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
            
            if ($orderDetails->isEmpty()) {
                Log::warning('Cannot create export receipt: Order has no items', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                ]);
                DB::rollBack();
                return null;
            }

            // Build full address from order
            $fullAddress = $order->address ?? '';
            if ($order->ward) {
                $fullAddress .= ($fullAddress ? ', ' : '') . $order->ward->name;
            }
            if ($order->district) {
                $fullAddress .= ($fullAddress ? ', ' : '') . $order->district->name;
            }
            if ($order->province) {
                $fullAddress .= ($fullAddress ? ', ' : '') . $order->province->name;
            }

            // Prepare receipt data
            // Note: Create with 'draft' status first, then complete it
            // Because completeReceipt() requires receipt to be in 'draft' or 'approved' status
            $receiptData = [
                'type' => 'export',
                'status' => StockReceipt::STATUS_DRAFT, // Always create as draft first
                'subject' => 'Đơn hàng ' . $order->code, // Use order code instead of id
                'content' => $fullAddress, // Full delivery address
                'customer_name' => $order->name,
                'customer_phone' => $order->phone ?? null,
                'customer_address' => $fullAddress, // Also set in customer_address field
                'customer_id' => $order->member_id ?? null,
                'from_warehouse_id' => 1, // Default warehouse
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'reference_code' => (string)$order->code,
                'items' => [],
            ];
            
            Log::info('Creating export receipt from order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'order_status' => $order->status,
                'target_receipt_status' => $status,
                'initial_receipt_status' => StockReceipt::STATUS_DRAFT,
                'order_details_count' => $orderDetails->count(),
            ]);

            // Prepare items from order details
            $totalValue = 0;
            foreach ($orderDetails as $detail) {
                if (!$detail->variant_id) {
                    Log::warning('Order detail missing variant_id', [
                        'order_id' => $order->id,
                        'detail_id' => $detail->id,
                    ]);
                    continue;
                }

                $receiptData['items'][] = [
                    'variant_id' => $detail->variant_id,
                    'quantity' => $detail->qty,
                    'quantity_requested' => $detail->qty,
                    'unit_price' => $detail->price,
                    'total_price' => $detail->subtotal,
                ];

                $totalValue += $detail->subtotal;
            }

            if (empty($receiptData['items'])) {
                Log::warning('Cannot create export receipt: No valid items', [
                    'order_id' => $order->id,
                ]);
                DB::rollBack();
                return null;
            }

            // Create receipt (always as draft first)
            Log::info('Calling StockReceiptService::createReceipt', [
                'receipt_data' => $receiptData,
                'items_count' => count($receiptData['items']),
            ]);
            
            $receipt = $this->stockReceiptService->createReceipt($receiptData);
            
            Log::info('Receipt created, now completing if needed', [
                'receipt_id' => $receipt->id,
                'receipt_code' => $receipt->receipt_code,
                'receipt_status' => $receipt->status,
                'target_status' => $status,
            ]);

            // If target status is completed, complete the receipt immediately
            // Note: For receipts created from orders, do NOT update warehouse stock
            // Stock is already managed by order checkout process
            if ($status === StockReceipt::STATUS_COMPLETED) {
                if ($receipt->status === StockReceipt::STATUS_COMPLETED) {
                    Log::info('Receipt already completed by createReceipt', ['receipt_id' => $receipt->id]);
                } else {
                    Log::info('Completing receipt from order (without stock update)', [
                        'receipt_id' => $receipt->id,
                        'current_status' => $receipt->status,
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                    ]);
                    // Complete receipt WITHOUT updating warehouse stock
                    // Stock is already managed by order checkout/fulfillment process
                    $receipt = $this->stockReceiptService->completeReceipt($receipt->id, Auth::id() ?? 1, false);
                }
            }

            DB::commit();

            Log::info('Export receipt created from order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'receipt_id' => $receipt->id,
                'receipt_code' => $receipt->receipt_code,
                'status' => $receipt->status,
            ]);

            return $receipt;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create export receipt from order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Cancel or delete export receipt when order is cancelled
     * 
     * @param Order $order
     * @param bool $delete If true, delete the receipt; if false, mark as cancelled
     * @return bool
     */
    public function cancelExportReceiptFromOrder(Order $order, bool $delete = false): bool
    {
        try {
            $receipt = StockReceipt::where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->first();

            if (!$receipt) {
                Log::info('No export receipt found for cancelled order', [
                    'order_id' => $order->id,
                    'order_code' => $order->code,
                ]);
                return true; // No receipt to cancel, consider it success
            }

            DB::beginTransaction();

            if ($delete) {
                // Delete the receipt (do NOT reverse stock - stock is managed by order cancellation)
                $this->stockReceiptService->voidReceipt($receipt->id, Auth::id() ?? 1, false);
                $receipt->delete();
                
                Log::info('Export receipt deleted for cancelled order (without stock reversal)', [
                    'order_id' => $order->id,
                    'receipt_id' => $receipt->id,
                    'receipt_code' => $receipt->receipt_code,
                ]);
            } else {
                // Mark as cancelled (do NOT reverse stock - stock is managed by order cancellation)
                $this->stockReceiptService->voidReceipt($receipt->id, Auth::id() ?? 1, false);
                
                Log::info('Export receipt cancelled for cancelled order (without stock reversal)', [
                    'order_id' => $order->id,
                    'receipt_id' => $receipt->id,
                    'receipt_code' => $receipt->receipt_code,
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel/delete export receipt for order', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Update export receipt status based on order status
     * 
     * @param Order $order
     * @param string $oldStatus Previous order status
     * @param string $newStatus New order status
     * @return bool
     */
    public function updateExportReceiptFromOrderStatus(Order $order, string $oldStatus, string $newStatus): bool
    {
        // Status mapping:
        // Order status '0' (chờ xác nhận) -> Receipt status 'completed'
        // Order status '2' or '4' (đã hủy) -> Receipt status 'cancelled' or delete

        $receipt = StockReceipt::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        if (!$receipt) {
            // If order status changed to '0' and no receipt exists, create one
            if ($newStatus === '0' || $newStatus === 0) {
                $this->createExportReceiptFromOrder($order, StockReceipt::STATUS_COMPLETED);
            }
            return true;
        }

        try {
            // If order is cancelled (status 2 or 4), cancel the receipt (mark as cancelled, not delete)
            if (in_array((int)$newStatus, [2, 4], true)) {
                // Mark as cancelled (will reverse stock via voidReceipt)
                return $this->cancelExportReceiptFromOrder($order, false); // Don't delete, just mark as cancelled
            }

            // If order status is '0' (chờ xác nhận) and receipt is not completed, complete it
            // Note: Do NOT update stock for receipts from orders
            if (($newStatus === '0' || $newStatus === 0) && $receipt->status !== StockReceipt::STATUS_COMPLETED) {
                $this->stockReceiptService->completeReceipt($receipt->id, Auth::id() ?? 1, false);
                
                Log::info('Export receipt completed for order status change (without stock update)', [
                    'order_id' => $order->id,
                    'receipt_id' => $receipt->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update export receipt from order status', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

