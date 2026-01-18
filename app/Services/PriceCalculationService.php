<?php

namespace App\Services;

use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use Carbon\Carbon;

/**
 * Price Calculation Service
 * 
 * Centralized service for calculating product prices with priority:
 * 1. Flash Sale (highest priority)
 * 2. Marketing Campaign
 * 3. Variant Sale Price
 * 4. Normal Price
 */
class PriceCalculationService
{
    /**
     * Calculate price for a Product
     * 
     * @param Product $product
     * @param int|null $flashSaleId Optional Flash Sale ID to check
     * @return object PriceInfo object
     */
    public function calculateProductPrice(Product $product, ?int $flashSaleId = null): object
    {
        $now = time();
        $nowDate = Carbon::now();
        
        $variant = $product->variant($product->id);
        $originalPrice = $variant ? $variant->price : 0;
        
        // 1. Check Flash Sale (highest priority)
        $flashSaleProduct = ProductSale::where('product_id', $product->id)
            ->whereNull('variant_id') // Product-level Flash Sale
            ->whereHas('flashsale', function ($q) use ($now, $flashSaleId) {
                $q->where('status', 1)
                  ->where('start', '<=', $now)
                  ->where('end', '>=', $now);
                if ($flashSaleId) {
                    $q->where('id', $flashSaleId);
                }
            })
            ->first();

        if ($flashSaleProduct && $flashSaleProduct->is_available) {
            return (object) [
                'price' => $flashSaleProduct->price_sale,
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale',
                'discount_percent' => $originalPrice > 0 
                    ? round(($originalPrice - $flashSaleProduct->price_sale) / ($originalPrice / 100)) 
                    : 0,
                'flash_sale_info' => (object) [
                    'flashsale_id' => $flashSaleProduct->flashsale_id,
                    'price_sale' => $flashSaleProduct->price_sale,
                    'number' => $flashSaleProduct->number,
                    'buy' => $flashSaleProduct->buy,
                    'remaining' => $flashSaleProduct->remaining,
                ],
                'variant_id' => null,
            ];
        }

        // 2. Check Marketing Campaign
        $campaignProduct = MarketingCampaignProduct::where('product_id', $product->id)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                  ->where('start_at', '<=', $nowDate)
                  ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            return (object) [
                'price' => $campaignProduct->price,
                'original_price' => $originalPrice,
                'type' => 'campaign',
                'label' => 'Khuyến mại',
                'discount_percent' => $originalPrice > 0 
                    ? round(($originalPrice - $campaignProduct->price) / ($originalPrice / 100)) 
                    : 0,
                'variant_id' => null,
            ];
        }

        // 3. Check Variant Sale Price
        $salePrice = $variant ? $variant->sale : 0;
        if ($salePrice > 0 && $salePrice < $originalPrice) {
            return (object) [
                'price' => $salePrice,
                'original_price' => $originalPrice,
                'type' => 'sale',
                'label' => 'Giảm giá',
                'discount_percent' => round(($originalPrice - $salePrice) / ($originalPrice / 100)),
                'variant_id' => $variant->id ?? null,
            ];
        }

        // 4. Normal Price
        return (object) [
            'price' => $originalPrice,
            'original_price' => $originalPrice,
            'type' => 'normal',
            'label' => '',
            'discount_percent' => 0,
            'variant_id' => $variant->id ?? null,
        ];
    }

    /**
     * Calculate price for a Variant (with Flash Sale support)
     * 
     * @param Variant $variant
     * @param int|null $productId Optional product ID (if not loaded)
     * @param int|null $flashSaleId Optional Flash Sale ID to check
     * @return object PriceInfo object
     */
    public function calculateVariantPrice(Variant $variant, ?int $productId = null, ?int $flashSaleId = null): object
    {
        $now = time();
        $nowDate = Carbon::now();
        $productId = $productId ?: $variant->product_id;
        $originalPrice = $variant->price;
        $finalPrice = $originalPrice;

        // 1. Check Flash Sale for this specific variant (highest priority)
        $productSale = ProductSale::where('product_id', $productId)
            ->where('variant_id', $variant->id)
            ->whereHas('flashsale', function ($q) use ($now, $flashSaleId) {
                $q->where('status', 1)
                  ->where('start', '<=', $now)
                  ->where('end', '>=', $now);
                if ($flashSaleId) {
                    $q->where('id', $flashSaleId);
                }
            })
            ->first();

        if ($productSale && $productSale->is_available) {
            return (object) [
                'price' => $productSale->price_sale,
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale',
                'discount_percent' => $originalPrice > 0 
                    ? round(($originalPrice - $productSale->price_sale) / ($originalPrice / 100)) 
                    : 0,
                'flash_sale_info' => (object) [
                    'flashsale_id' => $productSale->flashsale_id,
                    'price_sale' => $productSale->price_sale,
                    'number' => $productSale->number,
                    'buy' => $productSale->buy,
                    'remaining' => $productSale->remaining,
                ],
                'variant_id' => $variant->id,
            ];
        }

        // 2. Check Flash Sale at product level (fallback)
        $productSaleFallback = ProductSale::where('product_id', $productId)
            ->whereNull('variant_id')
            ->whereHas('flashsale', function ($q) use ($now, $flashSaleId) {
                $q->where('status', 1)
                  ->where('start', '<=', $now)
                  ->where('end', '>=', $now);
                if ($flashSaleId) {
                    $q->where('id', $flashSaleId);
                }
            })
            ->first();

        if ($productSaleFallback && $productSaleFallback->is_available) {
            return (object) [
                'price' => $productSaleFallback->price_sale,
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale',
                'discount_percent' => $originalPrice > 0 
                    ? round(($originalPrice - $productSaleFallback->price_sale) / ($originalPrice / 100)) 
                    : 0,
                'flash_sale_info' => (object) [
                    'flashsale_id' => $productSaleFallback->flashsale_id,
                    'price_sale' => $productSaleFallback->price_sale,
                    'number' => $productSaleFallback->number,
                    'buy' => $productSaleFallback->buy,
                    'remaining' => $productSaleFallback->remaining,
                ],
                'variant_id' => $variant->id,
            ];
        }

        // 3. Check Marketing Campaign
        $campaignProduct = MarketingCampaignProduct::where('product_id', $productId)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                  ->where('start_at', '<=', $nowDate)
                  ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            return (object) [
                'price' => $campaignProduct->price,
                'original_price' => $originalPrice,
                'type' => 'campaign',
                'label' => 'Khuyến mại',
                'discount_percent' => $originalPrice > 0 
                    ? round(($originalPrice - $campaignProduct->price) / ($originalPrice / 100)) 
                    : 0,
                'variant_id' => $variant->id,
            ];
        }

        // 4. Check Variant Sale Price
        if ($variant->sale > 0 && $variant->sale < $originalPrice) {
            return (object) [
                'price' => $variant->sale,
                'original_price' => $originalPrice,
                'type' => 'sale',
                'label' => 'Giảm giá',
                'discount_percent' => round(($originalPrice - $variant->sale) / ($originalPrice / 100)),
                'variant_id' => $variant->id,
            ];
        }

        // 5. Normal Price
        return (object) [
            'price' => $originalPrice,
            'original_price' => $originalPrice,
            'type' => 'normal',
            'label' => '',
            'discount_percent' => 0,
            'variant_id' => $variant->id,
        ];
    }
}
