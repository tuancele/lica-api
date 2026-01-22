<?php

namespace App\Modules\FlashSale\Observers;

use App\Modules\FlashSale\Models\ProductSale;
use App\Services\FlashSale\FlashSaleStockService;
use Illuminate\Support\Facades\Log;

/**
 * ProductSale Observer
 * 
 * Handles stock reversion when ProductSale is deleted or updated
 */
class ProductSaleObserver
{
    /**
     * Get FlashSaleStockService instance
     */
    private function getStockService(): FlashSaleStockService
    {
        return app(FlashSaleStockService::class);
    }

    /**
     * Handle the ProductSale "deleting" event.
     * Revert stock before deletion
     */
    public function deleting(ProductSale $productSale): void
    {
        try {
            $result = $this->getStockService()->revertStock($productSale);
            
            if ($result['success']) {
                Log::info('[ProductSaleObserver] Stock reverted before deletion', [
                    'product_sale_id' => $productSale->id,
                    'variant_id' => $productSale->variant_id,
                    'released' => $result['released']
                ]);
            } else {
                Log::warning('[ProductSaleObserver] Failed to revert stock before deletion', [
                    'product_sale_id' => $productSale->id,
                    'variant_id' => $productSale->variant_id,
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[ProductSaleObserver] Exception during stock reversion', [
                'product_sale_id' => $productSale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception to prevent deletion failure
            // Stock will be handled by cronjob if needed
        }
    }

    /**
     * Handle the ProductSale "updating" event.
     * Release stock if quantity decreased
     */
    public function updating(ProductSale $productSale): void
    {
        // Only process if quantity changed
        if (!$productSale->isDirty('number')) {
            return;
        }

        $oldQuantity = $productSale->getOriginal('number');
        $newQuantity = $productSale->number;

        // Only release if quantity decreased
        if ($newQuantity < $oldQuantity) {
            try {
                $result = $this->getStockService()->handleQuantityChange($productSale, $oldQuantity);
                
                if ($result['success']) {
                    Log::info('[ProductSaleObserver] Stock released due to quantity decrease', [
                        'product_sale_id' => $productSale->id,
                        'variant_id' => $productSale->variant_id,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'released' => $result['released']
                    ]);
                } else {
                    Log::warning('[ProductSaleObserver] Failed to release stock on quantity decrease', [
                        'product_sale_id' => $productSale->id,
                        'variant_id' => $productSale->variant_id,
                        'error' => $result['message']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[ProductSaleObserver] Exception during stock release on update', [
                    'product_sale_id' => $productSale->id,
                    'error' => $e->getMessage()
                ]);
                // Don't throw exception to allow update to proceed
            }
        }
    }
}

