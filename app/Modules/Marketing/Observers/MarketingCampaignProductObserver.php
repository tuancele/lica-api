<?php

namespace App\Modules\Marketing\Observers;

use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\GoogleMerchant\Jobs\PushVariantToGmcJob;
use App\Modules\GoogleMerchant\Jobs\PushProductToGmcJob;
use Illuminate\Support\Facades\Log;

/**
 * MarketingCampaignProduct Observer
 * 
 * Auto-push product/variant to GMC when added to Marketing Campaign
 */
class MarketingCampaignProductObserver
{
    /**
     * Handle the MarketingCampaignProduct "created" event.
     * Auto-push product/variant to GMC when added to Marketing Campaign
     */
    public function created(MarketingCampaignProduct $campaignProduct): void
    {
        $this->pushToGmc($campaignProduct, 'created');
    }

    /**
     * Handle the MarketingCampaignProduct "updated" event.
     * Auto-push product/variant to GMC when Marketing Campaign is updated
     */
    public function updated(MarketingCampaignProduct $campaignProduct): void
    {
        $this->pushToGmc($campaignProduct, 'updated');
    }

    /**
     * Push product/variant to GMC
     */
    private function pushToGmc(MarketingCampaignProduct $campaignProduct, string $event): void
    {
        try {
            // Load relationships
            $campaignProduct->loadMissing(['product', 'campaign']);
            
            if (!$campaignProduct->product || !$campaignProduct->campaign) {
                return;
            }

            // Check if Marketing Campaign is active
            if ($campaignProduct->campaign->status != '1') {
                return;
            }

            $now = now();
            if ($campaignProduct->campaign->start_at > $now || $campaignProduct->campaign->end_at < $now) {
                return; // Marketing Campaign not active yet or expired
            }

            // Rule 5: Respect VARIABLE product rule - only push variants, not parent
            $hasVariants = (int) ($campaignProduct->product->has_variants ?? 0);
            
            if ($hasVariants === 1) {
                // VARIABLE product: Push all variants of this product
                $variants = $campaignProduct->product->variants()->get();
                foreach ($variants as $variant) {
                    PushVariantToGmcJob::dispatch((int) $variant->id);
                }
                Log::info('[MarketingCampaignProductObserver] Auto-pushed variants to GMC', [
                    'event' => $event,
                    'campaign_product_id' => $campaignProduct->id,
                    'product_id' => $campaignProduct->product_id,
                    'variants_count' => $variants->count(),
                    'campaign_id' => $campaignProduct->campaign_id,
                ]);
            } else {
                // SIMPLE product: Push product (variant will be handled separately if exists)
                PushProductToGmcJob::dispatch((int) $campaignProduct->product_id);
                Log::info('[MarketingCampaignProductObserver] Auto-pushed product to GMC', [
                    'event' => $event,
                    'campaign_product_id' => $campaignProduct->id,
                    'product_id' => $campaignProduct->product_id,
                    'campaign_id' => $campaignProduct->campaign_id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[MarketingCampaignProductObserver] Failed to push to GMC', [
                'event' => $event,
                'campaign_product_id' => $campaignProduct->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw exception to prevent blocking Marketing Campaign operations
        }
    }
}

