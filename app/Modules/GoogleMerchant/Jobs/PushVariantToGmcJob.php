<?php

declare(strict_types=1);

namespace App\Modules\GoogleMerchant\Jobs;

use App\Modules\GoogleMerchant\Services\GoogleMerchantService;
use App\Modules\Product\Models\Variant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushVariantToGmcJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $variantId
    ) {}

    public function handle(GoogleMerchantService $service): void
    {
        try {
            $variant = Variant::with('product')->find($this->variantId);
            if (! $variant || ! $variant->product) {
                Log::warning('[GoogleMerchant][Job] Variant not found', ['variant_id' => $this->variantId]);

                return;
            }

            $service->upsertProduct($variant->product, $variant);
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Job] PushVariantToGmcJob failed', [
                'variant_id' => $this->variantId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
