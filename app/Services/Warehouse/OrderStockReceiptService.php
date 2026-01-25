<?php

namespace App\Services\Warehouse;

use App\Models\StockReceipt;
use App\Models\InventoryStock;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderDetail;
use App\Services\Warehouse\StockReceiptService;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Deal\Models\SaleDeal;
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
        private StockReceiptService $stockReceiptService,
        private InventoryServiceInterface $inventoryService
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

            // If target status is completed, deduct stock from correct source (Flash Sale/Deal/Available)
            // and complete the receipt
            if ($status === StockReceipt::STATUS_COMPLETED) {
                if ($receipt->status === StockReceipt::STATUS_COMPLETED) {
                    Log::info('Receipt already completed by createReceipt', ['receipt_id' => $receipt->id]);
                } else {
                    Log::info('Deducting stock from correct source and completing receipt', [
                        'receipt_id' => $receipt->id,
                        'current_status' => $receipt->status,
                        'order_id' => $order->id,
                        'order_code' => $order->code,
                    ]);
                    
                    // Deduct stock from correct source (Flash Sale/Deal/Available) based on OrderDetail
                    $this->deductStockFromOrderDetails($orderDetails);
                    
                    // Complete receipt WITHOUT updating warehouse stock again
                    // Stock is already deducted above
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
     * Also restore stock from correct source (Flash Sale/Deal/Available)
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

            // Get order details to restore stock
            $orderDetails = OrderDetail::where('order_id', $order->id)->get();
            
            // Restore stock from correct source (Flash Sale/Deal/Available) based on OrderDetail
            if ($orderDetails->isNotEmpty()) {
                $this->restoreStockFromOrderDetails($orderDetails);
            }

            if ($delete) {
                // Delete the receipt after restoring stock
                $this->stockReceiptService->voidReceipt($receipt->id, Auth::id() ?? 1, false);
                $receipt->delete();
                
                Log::info('Export receipt deleted for cancelled order (stock restored)', [
                    'order_id' => $order->id,
                    'receipt_id' => $receipt->id,
                    'receipt_code' => $receipt->receipt_code,
                ]);
            } else {
                // Mark as cancelled after restoring stock
                $this->stockReceiptService->voidReceipt($receipt->id, Auth::id() ?? 1, false);
                
                Log::info('Export receipt cancelled for cancelled order (stock restored)', [
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

    /**
     * Deduct stock from correct source (Flash Sale/Deal/Available) based on OrderDetail
     * 
     * @param \Illuminate\Database\Eloquent\Collection $orderDetails
     * @return void
     */
    private function deductStockFromOrderDetails($orderDetails): void
    {
        foreach ($orderDetails as $detail) {
            if (!$detail->variant_id || $detail->qty <= 0) {
                continue;
            }

            $variantId = $detail->variant_id;
            $qty = $detail->qty;
            $stockSource = 'available'; // Default source

            try {
                // Priority 1: Check if item is from Deal (Deal has priority over Flash Sale)
                // If an item has dealsale_id, it MUST be deducted from Deal stock, not Flash Sale
                if ($detail->dealsale_id) {
                    $saleDeal = SaleDeal::find($detail->dealsale_id);
                    if ($saleDeal) {
                        // Deduct from physical_stock and reduce deal_hold
                        $result = $this->inventoryService->deductStockForOrderFulfillment($variantId, $qty, 'deal', 'order_fulfillment_deal');
                        // Also increment SaleDeal.buy (if not already done in checkout)
                        // Note: This might already be done in checkout, but we ensure it here
                        $saleDeal->increment('buy', $qty);
                        $stockSource = 'deal';
                        
                        Log::info('[OrderStockReceiptService] Stock deducted from Deal (Priority 1)', [
                            'order_detail_id' => $detail->id,
                            'variant_id' => $variantId,
                            'qty' => $qty,
                            'dealsale_id' => $detail->dealsale_id,
                            'deal_id' => $detail->deal_id,
                            'has_productsale_id' => !empty($detail->productsale_id),
                            'result' => $result,
                        ]);
                        continue; // Skip to next item - Deal has priority
                    }
                }

                // Priority 2: Check if item is from Flash Sale (only if NOT a Deal item)
                if ($detail->productsale_id) {
                    $productSale = ProductSale::find($detail->productsale_id);
                    if ($productSale) {
                        // Deduct from physical_stock and reduce flash_sale_hold
                        $result = $this->inventoryService->deductStockForOrderFulfillment($variantId, $qty, 'flash_sale', 'order_fulfillment_flash_sale');
                        // Also increment ProductSale.buy (centralized here, not in checkout)
                        // This ensures stock deduction and buy tracking happen together
                        $productSale->increment('buy', $qty);
                        $stockSource = 'flash_sale';
                        
                        Log::info('[OrderStockReceiptService] Stock deducted from Flash Sale (Priority 2)', [
                            'order_detail_id' => $detail->id,
                            'variant_id' => $variantId,
                            'qty' => $qty,
                            'productsale_id' => $detail->productsale_id,
                            'buy_before' => $productSale->buy - $qty,
                            'buy_after' => $productSale->buy,
                            'result' => $result,
                        ]);
                        continue; // Skip to next item
                    }
                }

                // Priority 3: Deduct from available stock (physical_stock only)
                $result = $this->inventoryService->deductStockForOrderFulfillment($variantId, $qty, 'available', 'order_fulfillment_available');
                $stockSource = 'available';
                
                Log::info('[OrderStockReceiptService] Stock deducted from Available', [
                    'order_detail_id' => $detail->id,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                ]);

            } catch (\Exception $e) {
                Log::error('[OrderStockReceiptService] Failed to deduct stock for order detail', [
                    'order_detail_id' => $detail->id,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'productsale_id' => $detail->productsale_id,
                    'dealsale_id' => $detail->dealsale_id,
                    'deal_id' => $detail->deal_id,
                    'stock_source' => $stockSource,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue with next item instead of failing entire operation
            }
        }
    }

    /**
     * Restore stock to correct source (Flash Sale/Deal/Available) based on OrderDetail
     * This is called when order is cancelled
     * 
     * @param \Illuminate\Database\Eloquent\Collection $orderDetails
     * @return void
     */
    private function restoreStockFromOrderDetails($orderDetails): void
    {
        foreach ($orderDetails as $detail) {
            if (!$detail->variant_id || $detail->qty <= 0) {
                continue;
            }

            $variantId = $detail->variant_id;
            $qty = $detail->qty;
            $stockSource = 'available'; // Default source

            try {
                // Priority 1: Check if item is from Deal (Deal has priority over Flash Sale)
                // If an item has dealsale_id, it MUST be restored to Deal stock, not Flash Sale
                if ($detail->dealsale_id) {
                    $saleDeal = SaleDeal::find($detail->dealsale_id);
                    if ($saleDeal) {
                        // Restore to physical_stock first
                        $this->inventoryService->importStock($variantId, $qty, 'order_cancellation_deal');
                        // Then restore deal_hold (manually to avoid sellableStock check)
                        // Default warehouse ID is 1
                        $warehouseId = 1;
                        $stock = InventoryStock::lockForUpdate()
                            ->firstOrCreate(
                                ['warehouse_id' => $warehouseId, 'variant_id' => $variantId],
                                ['physical_stock' => 0, 'reserved_stock' => 0, 'flash_sale_hold' => 0, 'deal_hold' => 0]
                            );
                        $stock->increment('deal_hold', $qty);
                        $stock->update(['last_movement_at' => now()]);
                        // Decrement SaleDeal.buy and increment SaleDeal.qty
                        $saleDeal->decrement('buy', $qty);
                        $saleDeal->increment('qty', $qty);
                        $stockSource = 'deal';
                        
                        Log::info('[OrderStockReceiptService] Stock restored to Deal (Priority 1)', [
                            'order_detail_id' => $detail->id,
                            'variant_id' => $variantId,
                            'qty' => $qty,
                            'dealsale_id' => $detail->dealsale_id,
                            'deal_id' => $detail->deal_id,
                            'has_productsale_id' => !empty($detail->productsale_id),
                        ]);
                        continue; // Skip to next item - Deal has priority
                    }
                }

                // Priority 2: Check if item is from Flash Sale (only if NOT a Deal item)
                if ($detail->productsale_id) {
                    $productSale = ProductSale::find($detail->productsale_id);
                    if ($productSale) {
                        // Restore to physical_stock first
                        $this->inventoryService->importStock($variantId, $qty, 'order_cancellation_flash_sale');
                        // Then restore flash_sale_hold (manually to avoid sellableStock check)
                        // Default warehouse ID is 1
                        $warehouseId = 1;
                        $stock = InventoryStock::lockForUpdate()
                            ->firstOrCreate(
                                ['warehouse_id' => $warehouseId, 'variant_id' => $variantId],
                                ['physical_stock' => 0, 'reserved_stock' => 0, 'flash_sale_hold' => 0, 'deal_hold' => 0]
                            );
                        $stock->increment('flash_sale_hold', $qty);
                        $stock->update(['last_movement_at' => now()]);
                        // Decrement ProductSale.buy
                        $productSale->decrement('buy', $qty);
                        $stockSource = 'flash_sale';
                        
                        Log::info('[OrderStockReceiptService] Stock restored to Flash Sale (Priority 2)', [
                            'order_detail_id' => $detail->id,
                            'variant_id' => $variantId,
                            'qty' => $qty,
                            'productsale_id' => $detail->productsale_id,
                        ]);
                        continue; // Skip to next item
                    }
                }

                // Priority 3: Restore to available stock (physical_stock only)
                $this->inventoryService->importStock($variantId, $qty, 'order_cancellation_available');
                $stockSource = 'available';
                
                Log::info('[OrderStockReceiptService] Stock restored to Available', [
                    'order_detail_id' => $detail->id,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                ]);

            } catch (\Exception $e) {
                Log::error('[OrderStockReceiptService] Failed to restore stock for order detail', [
                    'order_detail_id' => $detail->id,
                    'variant_id' => $variantId,
                    'qty' => $qty,
                    'productsale_id' => $detail->productsale_id,
                    'dealsale_id' => $detail->dealsale_id,
                    'deal_id' => $detail->deal_id,
                    'stock_source' => $stockSource,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Continue with next item instead of failing entire operation
            }
        }
    }
}

