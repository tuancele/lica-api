<?php

declare(strict_types=1);

namespace App\Services\Gmc;

use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use App\Services\Pricing\PriceEngineServiceInterface;
use Carbon\Carbon;
use Google\Service\ShoppingContent\Price as GmcPrice;
use Google\Service\ShoppingContent\Product as GmcProduct;
use Google\Service\ShoppingContent\ProductDimension as GmcProductDimension;
use Google\Service\ShoppingContent\ProductShippingWeight as GmcProductShippingWeight;

class GmcProductMapper
{
    public function __construct(
        private PriceEngineServiceInterface $priceEngine,
        private InventoryServiceInterface $inventoryService,
        private GmcOfferId $offerId
    ) {}

    public function map(Product $product, Variant $variant): GmcProduct
    {
        $channel = (string) config('gmc.channel', 'online');
        $lang = (string) config('gmc.content_language', 'vi');
        $country = (string) config('gmc.target_country', 'VN');
        $currency = (string) config('gmc.currency', 'VND');
        $warehouseId = config('gmc.warehouse_id', null);

        // Get original price (base price from variant/product, NOT promotional price)
        $originalPrice = (float) ($variant->price ?? 0.0);
        if ($originalPrice <= 0.0) {
            // Fallback to product price if variant price is missing
            $originalPrice = (float) ($product->price ?? 0.0);
        }

        // Get stock from warehouse (availableStock column)
        $stockDto = $this->inventoryService->getStock((int) $variant->id, $warehouseId !== null ? (int) $warehouseId : null);
        $available = (int) ($stockDto->availableStock ?? 0);
        $availability = $available > 0 ? 'in stock' : 'out of stock';

        $product->loadMissing(['brand', 'origin']);
        $variant->loadMissing(['color', 'size']);

        $link = $this->buildProductLink($product);
        $imageLink = $this->formatImageUrl($product->image);
        $additionalImages = $this->formatAdditionalImages($product);

        $offerId = $this->offerId->forVariant($variant);

        $g = new GmcProduct;
        $g->setOfferId($offerId);

        // Rule 2: Set itemGroupId for variants (all variants of same product share same itemGroupId)
        // itemGroupId = product_id (to group all variants together)
        $g->setItemGroupId((string) $product->id);

        // Set isDefaultVariant for the first variant (to help Google choose main display image)
        $isDefaultVariant = $this->isFirstVariant($product, $variant);
        if ($isDefaultVariant && method_exists($g, 'setIsDefaultVariant')) {
            try {
                $g->setIsDefaultVariant(true);
            } catch (\Throwable $e) {
                // Method may not exist in some API versions, silently skip
            }
        }

        $g->setTitle($this->buildVariantTitle($product, $variant));
        $g->setDescription($this->buildHighQualityDescription($product, $variant));
        $g->setLink($link);
        $g->setImageLink($imageLink);
        if (count($additionalImages) > 0) {
            $g->setAdditionalImageLinks($additionalImages);
        }
        $g->setChannel($channel);
        $g->setContentLanguage($lang);
        $g->setTargetCountry($country);
        $g->setAvailability($availability);
        $g->setCondition('new');

        // Set original price (base price, NOT promotional price)
        $price = new GmcPrice;
        $price->setValue(number_format(max(0.0, $originalPrice), 0, '.', ''));
        $price->setCurrency($currency);
        $g->setPrice($price);

        $brandName = (string) optional($product->brand)->name;
        if ($brandName === '') {
            $brandName = (string) config('app.name', '');
        }
        if ($brandName !== '') {
            $g->setBrand($brandName);
        }

        $googleProductCategory = (string) config('gmc.google_product_category', '');
        if ($googleProductCategory !== '') {
            $g->setGoogleProductCategory($googleProductCategory);
        }

        // Set sale price and effective date (Priority: Flash Sale > Deal > Marketing Campaign)
        $salePriceInfo = $this->resolveSalePriceInfo($product, $variant);
        if ($salePriceInfo !== null) {
            $salePrice = new GmcPrice;
            $salePrice->setValue(number_format(max(0.0, $salePriceInfo['price']), 0, '.', ''));
            $salePrice->setCurrency($currency);
            $g->setSalePrice($salePrice);
            $g->setSalePriceEffectiveDate($salePriceInfo['effective_date']);
        }

        // Set packaging dimensions (always set, with fallback defaults)
        $dimensions = $this->resolveDimensions($product, $variant);

        $shippingWeight = new GmcProductShippingWeight;
        $shippingWeight->setValue(max(0.0, $dimensions['weight']));
        $shippingWeight->setUnit('grams');
        $g->setShippingWeight($shippingWeight);

        $productLength = new GmcProductDimension;
        $productLength->setValue(max(0.0, $dimensions['length']));
        $productLength->setUnit('cm');
        $g->setProductLength($productLength);

        $productWidth = new GmcProductDimension;
        $productWidth->setValue(max(0.0, $dimensions['width']));
        $productWidth->setUnit('cm');
        $g->setProductWidth($productWidth);

        $productHeight = new GmcProductDimension;
        $productHeight->setValue(max(0.0, $dimensions['height']));
        $productHeight->setUnit('cm');
        $g->setProductHeight($productHeight);

        // Store dimensions source in a private property for logging (if needed)
        // Note: This is for internal use only, not sent to GMC

        return $g;
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function buildProductLink(Product $product): string
    {
        $base = trim((string) config('gmc.store_base_url', ''));
        if ($base !== '') {
            return rtrim($base, '/').'/'.ltrim((string) ($product->slug ?? ''), '/');
        }

        return function_exists('getSlug') ? (string) getSlug((string) $product->slug) : (string) url((string) $product->slug);
    }

    private function buildVariantTitle(Product $product, Variant $variant): string
    {
        $base = (string) ($product->name ?? '');

        $parts = [];
        $colorName = (string) ($variant->color?->name ?? '');
        if ($colorName !== '') {
            $parts[] = $colorName;
        }
        $sizeName = (string) ($variant->size?->name ?? '');
        if ($sizeName !== '') {
            $parts[] = $sizeName;
        }

        if (count($parts) === 0) {
            return $base;
        }

        return $base.' - '.implode(' - ', $parts);
    }

    private function buildHighQualityDescription(Product $product, Variant $variant): string
    {
        $parts = [];

        $name = $this->cleanText((string) ($product->name ?? ''));
        if ($name !== '') {
            $parts[] = $name;
        }

        $brand = $this->cleanText((string) optional($product->brand)->name);
        if ($brand === '') {
            $brand = $this->cleanText((string) config('app.name', ''));
        }
        if ($brand !== '') {
            $parts[] = 'Brand: '.$brand;
        }

        $originName = $this->cleanText((string) optional($product->origin)->name);
        if ($originName !== '') {
            $parts[] = 'Origin: '.$originName;
        }

        $opt = $this->cleanText((string) ($variant->option1_value ?? ''));
        if ($opt !== '') {
            $parts[] = 'Variant: '.$opt;
        }

        $desc = $this->cleanText((string) ($product->description ?? $product->seo_description ?? ''));
        $content = $this->cleanText((string) ($product->content ?? ''));
        $ingredient = $this->cleanText((string) ($product->ingredient ?? ''));

        if ($desc !== '') {
            $parts[] = $desc;
        } elseif ($content !== '') {
            $parts[] = mb_substr($content, 0, 1200);
        }

        if ($ingredient !== '') {
            $parts[] = 'Ingredients: '.mb_substr($ingredient, 0, 800);
        }

        $text = trim(implode('. ', array_filter($parts)));
        $text = $this->cleanText($text);

        if (mb_strlen($text) < 30) {
            $text = $name !== '' ? ($name.'. '.'High quality product for daily use.') : 'High quality product for daily use.';
        }

        return mb_substr($text, 0, 4800);
    }

    private function formatAdditionalImages(Product $product): array
    {
        $gallery = $this->decodeGallery((string) ($product->gallery ?? ''));
        $urls = [];
        foreach ($gallery as $img) {
            $img = trim((string) $img);
            if ($img === '') {
                continue;
            }
            $urls[] = $this->formatImageUrl($img);
            if (count($urls) >= 10) {
                break;
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function decodeGallery(string $galleryJson): array
    {
        $decoded = json_decode($galleryJson, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function formatImageUrl(?string $image): string
    {
        $image = trim((string) $image);
        if ($image === '') {
            $r2Domain = (string) config('filesystems.disks.r2.url', '');
            if ($r2Domain !== '') {
                return rtrim($r2Domain, '/').'/public/image/no_image.png';
            }

            return (string) asset('/public/image/no_image.png');
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        $r2Domain = (string) config('filesystems.disks.r2.url', '');
        if ($r2Domain === '') {
            return (string) asset($image);
        }

        $r2DomainClean = rtrim($r2Domain, '/');
        $checkR2 = str_replace(['http://', 'https://'], '', $r2DomainClean);
        $cleanPath = str_replace(['http://', 'https://'], '', $image);
        $cleanPath = str_replace($checkR2.'/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath) ?? $cleanPath;
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath) ?? $cleanPath;
        $cleanPath = ltrim($cleanPath, '/');

        return $r2DomainClean.'/'.$cleanPath;
    }

    /**
     * Resolve sale price info (Priority: Flash Sale > Deal > Marketing Campaign).
     * Returns null if no active promotion, or array with 'price' and 'effective_date'.
     *
     * @return array{price:float,effective_date:string}|null
     */
    private function resolveSalePriceInfo(Product $product, Variant $variant): ?array
    {
        $now = Carbon::now();
        $nowTimestamp = $now->timestamp;

        // Priority 1: Flash Sale
        $flashSaleInfo = $this->resolveFlashSaleInfo($product, $variant, $nowTimestamp);
        if ($flashSaleInfo !== null) {
            return $flashSaleInfo;
        }

        // Priority 2: Deal
        $dealInfo = $this->resolveDealInfo($product, $variant, $nowTimestamp);
        if ($dealInfo !== null) {
            return $dealInfo;
        }

        // Priority 3: Marketing Campaign
        $marketingCampaignInfo = $this->resolveMarketingCampaignInfo($product, $variant, $now);
        if ($marketingCampaignInfo !== null) {
            return $marketingCampaignInfo;
        }

        return null;
    }

    /**
     * Resolve Flash Sale info (price and effective date range).
     *
     * @return array{price:float,effective_date:string}|null
     */
    private function resolveFlashSaleInfo(Product $product, Variant $variant, int $nowTimestamp): ?array
    {
        // Find active Flash Sale
        $activeFlashSale = FlashSale::where('status', '1')
            ->where('start', '<=', $nowTimestamp)
            ->where('end', '>=', $nowTimestamp)
            ->first();

        if (! $activeFlashSale) {
            return null;
        }

        // Find ProductSale for this product/variant
        $productSale = ProductSale::where('flashsale_id', $activeFlashSale->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->first();

        if (! $productSale) {
            return null;
        }

        // Check if still has stock
        $remainingStock = $productSale->number - $productSale->buy;
        if ($remainingStock <= 0) {
            return null;
        }

        // Format effective date: YYYY-MM-DDTHH:mm:ssZ/YYYY-MM-DDTHH:mm:ssZ
        $startDate = Carbon::createFromTimestamp($activeFlashSale->start)->utc();
        $endDate = Carbon::createFromTimestamp($activeFlashSale->end)->utc();
        $effectiveDate = $startDate->format('Y-m-d\TH:i:s\Z').'/'.$endDate->format('Y-m-d\TH:i:s\Z');

        return [
            'price' => (float) $productSale->price_sale,
            'effective_date' => $effectiveDate,
        ];
    }

    /**
     * Resolve Deal info (price and effective date range).
     *
     * @return array{price:float,effective_date:string}|null
     */
    private function resolveDealInfo(Product $product, Variant $variant, int $nowTimestamp): ?array
    {
        // Find active Deal
        $activeDeal = Deal::where('status', '1')
            ->where('start', '<=', $nowTimestamp)
            ->where('end', '>=', $nowTimestamp)
            ->first();

        if (! $activeDeal) {
            return null;
        }

        // Find SaleDeal for this product/variant
        $saleDeal = SaleDeal::where('deal_id', $activeDeal->id)
            ->where('product_id', $product->id)
            ->where('variant_id', $variant->id)
            ->where('status', '1')
            ->first();

        if (! $saleDeal || ! $saleDeal->price || $saleDeal->price <= 0) {
            return null;
        }

        // Format effective date: YYYY-MM-DDTHH:mm:ssZ/YYYY-MM-DDTHH:mm:ssZ
        $startDate = Carbon::createFromTimestamp($activeDeal->start)->utc();
        $endDate = Carbon::createFromTimestamp($activeDeal->end)->utc();
        $effectiveDate = $startDate->format('Y-m-d\TH:i:s\Z').'/'.$endDate->format('Y-m-d\TH:i:s\Z');

        return [
            'price' => (float) $saleDeal->price,
            'effective_date' => $effectiveDate,
        ];
    }

    /**
     * Resolve Marketing Campaign info (price and effective date range).
     *
     * @return array{price:float,effective_date:string}|null
     */
    private function resolveMarketingCampaignInfo(Product $product, Variant $variant, Carbon $now): ?array
    {
        // Find active Marketing Campaign for this product
        $campaignProduct = MarketingCampaignProduct::where('product_id', $product->id)
            ->whereHas('campaign', function ($q) use ($now) {
                $q->where('status', '1')
                    ->where('start_at', '<=', $now)
                    ->where('end_at', '>=', $now);
            })
            ->orderByDesc('id')
            ->first();

        if (! $campaignProduct || ! $campaignProduct->campaign) {
            return null;
        }

        $campaign = $campaignProduct->campaign;

        // Format effective date: YYYY-MM-DDTHH:mm:ssZ/YYYY-MM-DDTHH:mm:ssZ
        $startDate = Carbon::parse($campaign->start_at)->utc();
        $endDate = Carbon::parse($campaign->end_at)->utc();
        $effectiveDate = $startDate->format('Y-m-d\TH:i:s\Z').'/'.$endDate->format('Y-m-d\TH:i:s\Z');

        return [
            'price' => (float) $campaignProduct->price,
            'effective_date' => $effectiveDate,
        ];
    }

    /**
     * Resolve packaging dimensions (weight in grams, size in cm) with safe defaults.
     *
     * Priority: variant > product > default values
     * Defaults: weight=100g, dimensions=10x10x10cm
     *
     * @return array{weight:float,length:float,width:float,height:float,source:array}
     */
    private function resolveDimensions(Product $product, Variant $variant): array
    {
        // Prefer variant dimensions if present, otherwise product dimensions
        $weight = (float) ($variant->weight ?? $product->weight ?? 0.0);
        $length = (float) ($variant->length ?? $product->length ?? 0.0);
        $width = (float) ($variant->width ?? $product->width ?? 0.0);
        $height = (float) ($variant->height ?? $product->height ?? 0.0);

        // Track source for debugging
        $source = [
            'weight' => $variant->weight !== null ? 'variant' : ($product->weight !== null ? 'product' : 'default'),
            'length' => $variant->length !== null ? 'variant' : ($product->length !== null ? 'product' : 'default'),
            'width' => $variant->width !== null ? 'variant' : ($product->width !== null ? 'product' : 'default'),
            'height' => $variant->height !== null ? 'variant' : ($product->height !== null ? 'product' : 'default'),
        ];

        // Default dimensions if missing (10x10x10 cm)
        if ($length <= 0.0) {
            $length = 10.0;
            $source['length'] = 'default';
        }
        if ($width <= 0.0) {
            $width = 10.0;
            $source['width'] = 'default';
        }
        if ($height <= 0.0) {
            $height = 10.0;
            $source['height'] = 'default';
        }

        // If weight is missing, fall back to a small default (100g) to avoid 0-weight issues
        if ($weight <= 0.0) {
            $weight = 100.0;
            $source['weight'] = 'default';
        }

        return [
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'source' => $source, // For debugging/logging
        ];
    }

    /**
     * Check if this variant is the first/default variant of the product.
     * Priority: position ASC -> id ASC.
     */
    private function isFirstVariant(Product $product, Variant $variant): bool
    {
        // Load all variants for this product
        $variants = $product->variants()
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($variants->isEmpty()) {
            return false;
        }

        // Get the first variant (lowest position, then lowest id)
        $firstVariant = $variants->first();

        // Check if current variant is the first one
        return $firstVariant->id === $variant->id;
    }
}
