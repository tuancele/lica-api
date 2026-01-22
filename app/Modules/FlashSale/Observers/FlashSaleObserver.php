<?php

namespace App\Modules\FlashSale\Observers;

use App\Modules\FlashSale\Models\FlashSale;
use App\Services\FlashSale\FlashSaleStockService;
use Illuminate\Support\Facades\Log;

/**
 * FlashSale Observer
 * 
 * Handles stock reversion when FlashSale campaign is deleted
 */
class FlashSaleObserver
{
    /**
     * Get FlashSaleStockService instance
     */
    private function getStockService(): FlashSaleStockService
    {
        return app(FlashSaleStockService::class);
    }

    /**
     * Handle the FlashSale "deleting" event.
     * Revert stock for all ProductSales before deletion
     */
    public function deleting(FlashSale $flashSale): void
    {
        try {
            // Load products relationship if not already loaded
            if (!$flashSale->relationLoaded('products')) {
                $flashSale->load('products');
            }

            $result = $this->getStockService()->revertStockForCampaign($flashSale);
            
            if ($result['success']) {
                Log::info('[FlashSaleObserver] Stock reverted for campaign before deletion', [
                    'flash_sale_id' => $flashSale->id,
                    'total_released' => $result['total_released'],
                    'items_count' => count($result['items'])
                ]);
            } else {
                Log::warning('[FlashSaleObserver] Failed to revert stock for campaign', [
                    'flash_sale_id' => $flashSale->id,
                    'error' => $result['message']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[FlashSaleObserver] Exception during stock reversion for campaign', [
                'flash_sale_id' => $flashSale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw exception to prevent deletion failure
            // Stock will be handled by cronjob if needed
        }
    }
}

