<?php

namespace App\Services\FlashSale;

use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\FlashSale\Models\FlashSale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Flash Sale Stock Service
 * 
 * Handles Flash Sale stock updates with race condition protection
 * Uses DB transactions and row-level locking to prevent overselling
 */
class FlashSaleStockService
{
    /**
     * Increment Flash Sale buy count with race condition protection
     * 
     * This method uses DB transaction and lockForUpdate() to ensure
     * that concurrent requests don't oversell Flash Sale products.
     * 
     * Logic:
     * 1. Start transaction
     * 2. Lock ProductSale row with lockForUpdate()
     * 3. Check if buy < number (still available)
     * 4. Increment buy by qty
     * 5. Commit transaction
     * 
     * @param int $flashSaleId Flash Sale ID
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null for product-level Flash Sale)
     * @param int $qty Quantity to increment
     * @return array Result with success status and remaining stock
     * @throws \Exception If Flash Sale is out of stock or invalid
     */
    public function incrementBuy(
        int $flashSaleId,
        int $productId,
        ?int $variantId,
        int $qty
    ): array {
        if ($qty <= 0) {
            throw new \Exception('Số lượng phải lớn hơn 0');
        }

        return DB::transaction(function () use ($flashSaleId, $productId, $variantId, $qty) {
            // Build query with lockForUpdate() to prevent race conditions
            $query = ProductSale::where('flashsale_id', $flashSaleId)
                ->where('product_id', $productId);
            
            if ($variantId !== null) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }
            
            // Lock the row for update (prevents concurrent modifications)
            $productSale = $query->lockForUpdate()->first();
            
            if (!$productSale) {
                throw new \Exception('Sản phẩm không có trong Flash Sale');
            }
            
            // Check if Flash Sale is still active
            $flashSale = FlashSale::find($flashSaleId);
            if (!$flashSale || !$flashSale->is_active) {
                throw new \Exception('Flash Sale không còn hoạt động');
            }
            
            // Check current availability (CRITICAL: Must check after lock)
            $currentBuy = (int) $productSale->buy;
            $maxNumber = (int) $productSale->number;
            $remaining = $maxNumber - $currentBuy;
            
            if ($currentBuy >= $maxNumber) {
                throw new \Exception('Sản phẩm Flash Sale đã hết hàng');
            }
            
            if ($qty > $remaining) {
                throw new \Exception(
                    "Số lượng yêu cầu ({$qty}) vượt quá số lượng còn lại ({$remaining})"
                );
            }
            
            // Increment buy count
            $productSale->increment('buy', $qty);
            
            // Refresh to get updated values
            $productSale->refresh();
            
            Log::info('Flash Sale stock incremented', [
                'flash_sale_id' => $flashSaleId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => $qty,
                'buy_before' => $currentBuy,
                'buy_after' => $productSale->buy,
                'remaining' => $productSale->remaining,
            ]);
            
            return [
                'success' => true,
                'buy' => (int) $productSale->buy,
                'remaining' => $productSale->remaining,
                'message' => 'Cập nhật tồn kho Flash Sale thành công',
            ];
        });
    }
    
    /**
     * Decrement Flash Sale buy count (for order cancellation/refund)
     * 
     * @param int $flashSaleId Flash Sale ID
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null for product-level Flash Sale)
     * @param int $qty Quantity to decrement
     * @return array Result with success status
     * @throws \Exception If invalid
     */
    public function decrementBuy(
        int $flashSaleId,
        int $productId,
        ?int $variantId,
        int $qty
    ): array {
        if ($qty <= 0) {
            throw new \Exception('Số lượng phải lớn hơn 0');
        }

        return DB::transaction(function () use ($flashSaleId, $productId, $variantId, $qty) {
            // Build query with lockForUpdate()
            $query = ProductSale::where('flashsale_id', $flashSaleId)
                ->where('product_id', $productId);
            
            if ($variantId !== null) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }
            
            // Lock the row for update
            $productSale = $query->lockForUpdate()->first();
            
            if (!$productSale) {
                throw new \Exception('Sản phẩm không có trong Flash Sale');
            }
            
            $currentBuy = (int) $productSale->buy;
            
            // Decrement buy count (ensure it doesn't go below 0)
            $newBuy = max(0, $currentBuy - $qty);
            $productSale->update(['buy' => $newBuy]);
            
            Log::info('Flash Sale stock decremented', [
                'flash_sale_id' => $flashSaleId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty' => $qty,
                'buy_before' => $currentBuy,
                'buy_after' => $newBuy,
            ]);
            
            return [
                'success' => true,
                'buy' => $newBuy,
                'remaining' => $productSale->remaining,
                'message' => 'Hoàn trả tồn kho Flash Sale thành công',
            ];
        });
    }
    
    /**
     * Check Flash Sale availability with lock
     * 
     * Useful for validating before adding to cart
     * 
     * @param int $flashSaleId Flash Sale ID
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null for product-level Flash Sale)
     * @param int $requestedQty Requested quantity
     * @return array Availability info
     */
    public function checkAvailability(
        int $flashSaleId,
        int $productId,
        ?int $variantId,
        int $requestedQty = 1
    ): array {
        return DB::transaction(function () use ($flashSaleId, $productId, $variantId, $requestedQty) {
            $query = ProductSale::where('flashsale_id', $flashSaleId)
                ->where('product_id', $productId);
            
            if ($variantId !== null) {
                $query->where('variant_id', $variantId);
            } else {
                $query->whereNull('variant_id');
            }
            
            // Lock for read consistency
            $productSale = $query->lockForUpdate()->first();
            
            if (!$productSale) {
                return [
                    'available' => false,
                    'remaining' => 0,
                    'message' => 'Sản phẩm không có trong Flash Sale',
                ];
            }
            
            $remaining = $productSale->remaining;
            $isAvailable = $remaining >= $requestedQty;
            
            return [
                'available' => $isAvailable,
                'remaining' => $remaining,
                'buy' => (int) $productSale->buy,
                'number' => (int) $productSale->number,
                'message' => $isAvailable 
                    ? "Còn {$remaining} sản phẩm" 
                    : "Chỉ còn {$remaining} sản phẩm",
            ];
        });
    }
}
