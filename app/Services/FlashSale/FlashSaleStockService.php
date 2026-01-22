<?php

namespace App\Services\FlashSale;

use App\Modules\FlashSale\Models\ProductSale;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Flash Sale Stock Service
 * 
 * Handles stock allocation and release for Flash Sale campaigns
 */
class FlashSaleStockService
{
    public function __construct(
        private InventoryServiceInterface $inventoryService
    ) {}

    /**
     * Revert stock for a ProductSale
     * Calculates remaining quantity (number - buy) and releases it back to warehouse
     * 
     * @param int|ProductSale $productSale ProductSale ID or instance
     * @return array ['success' => bool, 'message' => string, 'released' => int]
     */
    public function revertStock($productSale): array
    {
        if (is_numeric($productSale)) {
            $productSale = ProductSale::find($productSale);
        }

        if (!$productSale || !($productSale instanceof ProductSale)) {
            return ['success' => false, 'message' => 'ProductSale not found', 'released' => 0];
        }

        // Calculate remaining quantity (not sold yet)
        $remaining = max(0, $productSale->number - $productSale->buy);
        
        if ($remaining <= 0) {
            return ['success' => true, 'message' => 'No stock to revert (all sold)', 'released' => 0];
        }

        // Only revert if variant_id exists (V2 warehouse works with variants)
        if (!$productSale->variant_id) {
            Log::warning('[FlashSaleStockService] ProductSale has no variant_id, skipping revert', [
                'product_sale_id' => $productSale->id,
                'product_id' => $productSale->product_id
            ]);
            return ['success' => false, 'message' => 'ProductSale has no variant_id', 'released' => 0];
        }

        try {
            return DB::transaction(function () use ($productSale, $remaining) {
                // Release stock from flash_sale_hold back to available
                $result = $this->inventoryService->releaseStockFromPromotion(
                    $productSale->variant_id,
                    $remaining,
                    'flash_sale'
                );

                if ($result['success']) {
                    Log::info('[FlashSaleStockService] Stock reverted successfully', [
                        'product_sale_id' => $productSale->id,
                        'variant_id' => $productSale->variant_id,
                        'released' => $remaining,
                        'before' => $result['before'] ?? 0,
                        'after' => $result['after'] ?? 0
                    ]);
                }

                return [
                    'success' => $result['success'],
                    'message' => $result['success'] ? 'Stock reverted successfully' : ($result['message'] ?? 'Failed to revert stock'),
                    'released' => $result['success'] ? $remaining : 0
                ];
            });
        } catch (\Exception $e) {
            Log::error('[FlashSaleStockService] Error reverting stock', [
                'product_sale_id' => $productSale->id,
                'variant_id' => $productSale->variant_id,
                'remaining' => $remaining,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error reverting stock: ' . $e->getMessage(),
                'released' => 0
            ];
        }
    }

    /**
     * Revert stock for all ProductSales in a FlashSale campaign
     * 
     * @param int|FlashSale $flashSale FlashSale ID or instance
     * @return array ['success' => bool, 'message' => string, 'total_released' => int, 'items' => array]
     */
    public function revertStockForCampaign($flashSale): array
    {
        if (is_numeric($flashSale)) {
            $flashSale = \App\Modules\FlashSale\Models\FlashSale::with('products')->find($flashSale);
        }

        if (!$flashSale || !($flashSale instanceof \App\Modules\FlashSale\Models\FlashSale)) {
            return [
                'success' => false,
                'message' => 'FlashSale not found',
                'total_released' => 0,
                'items' => []
            ];
        }

        $totalReleased = 0;
        $items = [];

        foreach ($flashSale->products as $productSale) {
            $result = $this->revertStock($productSale);
            $items[] = [
                'product_sale_id' => $productSale->id,
                'variant_id' => $productSale->variant_id,
                'result' => $result
            ];
            if ($result['success']) {
                $totalReleased += $result['released'];
            }
        }

        return [
            'success' => true,
            'message' => "Reverted stock for {$flashSale->products->count()} items",
            'total_released' => $totalReleased,
            'items' => $items
        ];
    }

    /**
     * Handle quantity change in ProductSale
     * If quantity decreased, release the difference back to warehouse
     * 
     * @param ProductSale $productSale
     * @param int $oldQuantity Old quantity value
     * @return array ['success' => bool, 'message' => string, 'released' => int]
     */
    public function handleQuantityChange(ProductSale $productSale, int $oldQuantity): array
    {
        $newQuantity = $productSale->number;
        $difference = $oldQuantity - $newQuantity;

        // Only release if quantity decreased
        if ($difference <= 0) {
            return ['success' => true, 'message' => 'No stock to release (quantity increased or unchanged)', 'released' => 0];
        }

        // Calculate how much to release (considering already sold items)
        // We can only release the difference, not more than what's held
        $maxReleaseable = max(0, $oldQuantity - $productSale->buy);
        $toRelease = min($difference, $maxReleaseable);

        if ($toRelease <= 0) {
            return ['success' => true, 'message' => 'No stock to release (all sold)', 'released' => 0];
        }

        if (!$productSale->variant_id) {
            return ['success' => false, 'message' => 'ProductSale has no variant_id', 'released' => 0];
        }

        try {
            return DB::transaction(function () use ($productSale, $toRelease) {
                $result = $this->inventoryService->releaseStockFromPromotion(
                    $productSale->variant_id,
                    $toRelease,
                    'flash_sale'
                );

                if ($result['success']) {
                    Log::info('[FlashSaleStockService] Stock released due to quantity decrease', [
                        'product_sale_id' => $productSale->id,
                        'variant_id' => $productSale->variant_id,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $productSale->number,
                        'released' => $toRelease
                    ]);
                }

                return [
                    'success' => $result['success'],
                    'message' => $result['success'] ? 'Stock released successfully' : ($result['message'] ?? 'Failed to release stock'),
                    'released' => $result['success'] ? $toRelease : 0
                ];
            });
        } catch (\Exception $e) {
            Log::error('[FlashSaleStockService] Error releasing stock on quantity change', [
                'product_sale_id' => $productSale->id,
                'variant_id' => $productSale->variant_id,
                'to_release' => $toRelease,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error releasing stock: ' . $e->getMessage(),
                'released' => 0
            ];
        }
    }
}
