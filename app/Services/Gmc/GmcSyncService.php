<?php

declare(strict_types=1);

namespace App\Services\Gmc;

use App\Modules\Product\Models\Variant;
use Illuminate\Support\Facades\Log;

class GmcSyncService
{
    public function __construct(
        private GmcClientFactory $clientFactory,
        private GmcProductMapper $mapper
    ) {}

    /**
     * @return array{offer_id:string, sent:bool}
     */
    public function syncVariant(Variant $variant, bool $dryRun = false): array
    {
        $merchantId = (string) config('gmc.merchant_id', '');
        $debug = (bool) config('gmc.debug', false);

        $variant->loadMissing(['product.brand']);
        if (! $variant->product) {
            throw new \RuntimeException('Variant product is missing.');
        }

        $gmcProduct = $this->mapper->map($variant->product, $variant);
        $offerId = (string) $gmcProduct->getOfferId();

        if ($debug) {
            $salePrice = $gmcProduct->getSalePrice();
            $shippingWeight = $gmcProduct->getShippingWeight();
            $productLength = $gmcProduct->getProductLength();
            $productWidth = $gmcProduct->getProductWidth();
            $productHeight = $gmcProduct->getProductHeight();

            Log::info('[GMC] Prepared product for sync', [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'merchant_id' => $merchantId,
                'offer_id' => $offerId,
                'item_group_id' => $gmcProduct->getItemGroupId(),
                'is_default_variant' => method_exists($gmcProduct, 'getIsDefaultVariant') ? $gmcProduct->getIsDefaultVariant() : null,
                'title' => $gmcProduct->getTitle(),
                'link' => $gmcProduct->getLink(),
                'image_link' => $gmcProduct->getImageLink(),
                'additional_images_count' => is_array($gmcProduct->getAdditionalImageLinks()) ? count($gmcProduct->getAdditionalImageLinks()) : 0,
                'availability' => $gmcProduct->getAvailability(),
                'price_value' => optional($gmcProduct->getPrice())->getValue(),
                'price_currency' => optional($gmcProduct->getPrice())->getCurrency(),
                'sale_price_value' => $salePrice ? optional($salePrice)->getValue() : null,
                'sale_price_currency' => $salePrice ? optional($salePrice)->getCurrency() : null,
                'sale_price_effective_date' => $gmcProduct->getSalePriceEffectiveDate(),
                'shipping_weight_value' => $shippingWeight ? $shippingWeight->getValue() : null,
                'shipping_weight_unit' => $shippingWeight ? $shippingWeight->getUnit() : null,
                'product_length_value' => $productLength ? $productLength->getValue() : null,
                'product_length_unit' => $productLength ? $productLength->getUnit() : null,
                'product_width_value' => $productWidth ? $productWidth->getValue() : null,
                'product_width_unit' => $productWidth ? $productWidth->getUnit() : null,
                'product_height_value' => $productHeight ? $productHeight->getValue() : null,
                'product_height_unit' => $productHeight ? $productHeight->getUnit() : null,
                'brand' => $gmcProduct->getBrand(),
                'google_product_category' => $gmcProduct->getGoogleProductCategory(),
                'description_length' => mb_strlen((string) $gmcProduct->getDescription()),
                'dry_run' => $dryRun,
            ]);
        }

        if ($dryRun) {
            return ['offer_id' => $offerId, 'sent' => false];
        }

        if ($merchantId === '') {
            throw new \RuntimeException('GMC merchant_id is missing.');
        }

        $service = $this->clientFactory->makeContentService();

        try {
            $service->products->insert($merchantId, $gmcProduct);

            if ($debug) {
                Log::info('[GMC] Sync variant succeeded', [
                    'variant_id' => $variant->id,
                    'product_id' => $variant->product->id,
                    'merchant_id' => $merchantId,
                    'offer_id' => $offerId,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[GMC] Sync variant failed', [
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'merchant_id' => $merchantId,
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'code' => (int) $e->getCode(),
            ]);
            throw $e;
        }

        return ['offer_id' => $offerId, 'sent' => true];
    }
}
