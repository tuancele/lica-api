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
}



