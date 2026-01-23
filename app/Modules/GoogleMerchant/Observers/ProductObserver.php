<?php

namespace App\Modules\GoogleMerchant\Observers;

use App\Enums\ProductType;
use App\Modules\GoogleMerchant\Jobs\PushProductToGmcJob;
use App\Modules\Product\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    public function saved(Product $product): void
    {
        try {
            if ((string) ($product->type ?? '') !== ProductType::PRODUCT->value) {
                return;
            }

            PushProductToGmcJob::dispatch((int) $product->id);
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Observer] Product saved hook failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}



