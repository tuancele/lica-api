<?php

declare(strict_types=1);

namespace App\Modules\GoogleMerchant\Jobs;

use App\Modules\GoogleMerchant\Services\GoogleMerchantService;
use App\Modules\Product\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushProductToGmcJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $productId
    ) {}

    public function handle(GoogleMerchantService $service): void
    {
        try {
            $product = Product::find($this->productId);
            if (! $product) {
                Log::warning('[GoogleMerchant][Job] Product not found', ['product_id' => $this->productId]);

                return;
            }

            $service->upsertProduct($product, null);
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant][Job] PushProductToGmcJob failed', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
