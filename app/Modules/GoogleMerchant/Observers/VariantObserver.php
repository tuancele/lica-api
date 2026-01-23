<?php

namespace App\Modules\GoogleMerchant\Observers;

use App\Modules\GoogleMerchant\Jobs\PushVariantToGmcJob;
use App\Modules\Product\Models\Variant;
use Illuminate\Support\Facades\Log;

class VariantObserver
{
    public function saved(Variant $variant): void
    {
        try {
            PushVariantToGmcJob::dispatch((int) $variant->id);
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Observer] Variant saved hook failed', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Rule 3: Delete variant from GMC when deleted on website
     */
    public function deleted(Variant $variant): void
    {
        try {
            if (!$variant->product) {
                $variant->load('product');
            }

            if (!$variant->product) {
                Log::warning('[GoogleMerchant][Observer] Variant product not found for deletion', [
                    'variant_id' => $variant->id,
                ]);
                return;
            }

            // Generate offerId for this variant
            $offerId = 'PROD_' . (int) $variant->product->id . '_VAR_' . (int) $variant->id;

            // Delete from GMC
            $service = app(\App\Modules\GoogleMerchant\Services\GoogleMerchantService::class);
            $result = $service->deleteProduct($offerId);

            if ($result['success']) {
                Log::info('[GoogleMerchant][Observer] Variant deleted from GMC', [
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product->id,
                    'offer_id' => $offerId,
                ]);
            } else {
                Log::warning('[GoogleMerchant][Observer] Failed to delete variant from GMC', [
                    'variant_id' => $variant->id,
                    'offer_id' => $offerId,
                    'message' => $result['message'],
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Observer] Variant deleted hook failed', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}



