<?php

declare(strict_types=1);

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

            // Rule 1: Only push SIMPLE products (has_variants = 0)
            // If product has variants (has_variants = 1), skip pushing parent product
            // Let VariantObserver handle pushing variants instead
            $hasVariants = (int) ($product->has_variants ?? 0);
            if ($hasVariants === 1) {
                // VARIABLE product: Skip pushing parent, only variants will be pushed
                Log::info('[GoogleMerchant][Observer] Skipping VARIABLE product, variants will be handled by VariantObserver', [
                    'product_id' => $product->id,
                    'has_variants' => $hasVariants,
                ]);

                return;
            }

            // SIMPLE product (has_variants = 0): Push parent product
            PushProductToGmcJob::dispatch((int) $product->id);
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Observer] Product saved hook failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
