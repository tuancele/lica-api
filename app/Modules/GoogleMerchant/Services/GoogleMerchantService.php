<?php

declare(strict_types=1);

namespace App\Modules\GoogleMerchant\Services;

use App\Enums\ProductType;
use App\Modules\Deal\Models\Deal;
use App\Modules\Deal\Models\SaleDeal;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Gmc\GmcOfferId;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Price as GmcPrice;
use Google\Service\ShoppingContent\Product as GmcProduct;
use Illuminate\Support\Facades\Log;

class GoogleMerchantService
{
    private function makeContentService(): ShoppingContent
    {
        $jsonPath = storage_path('app/google/service-account.json');
        if (! is_file($jsonPath)) {
            throw new \RuntimeException('Google Merchant service account JSON is missing.');
        }

        $client = new GoogleClient;
        $client->setAuthConfig($jsonPath);
        $client->setScopes([ShoppingContent::CONTENT]);
        $client->setApplicationName('lica-google-merchant');

        return new ShoppingContent($client);
    }

    public function upsertProduct(Product $product, $variant = null): array
    {
        if ((string) ($product->type ?? '') !== ProductType::PRODUCT->value) {
            return ['success' => false, 'skipped' => true, 'message' => 'Not a product type'];
        }

        $merchantId = (string) config('gmc.merchant_id', '');
        $debug = (bool) config('gmc.debug', false);
        if ($merchantId === '') {
            return ['success' => false, 'skipped' => true, 'message' => 'GMC merchant_id is missing'];
        }

        // Rule 4: If product is VARIABLE (has_variants = 1) and no variant provided, skip
        $hasVariants = (int) ($product->has_variants ?? 0);
        if ($hasVariants === 1 && $variant === null) {
            return ['success' => false, 'skipped' => true, 'message' => 'VARIABLE product requires variant'];
        }

        $product->loadMissing(['brand', 'origin']);

        $variantModel = null;
        if ($variant instanceof Variant) {
            $variantModel = $variant;
        } elseif (is_int($variant)) {
            $variantModel = Variant::find($variant);
        }
        // For SIMPLE products, we still prefer using the first variant (if exists) as the canonical offerId,
        // to keep offerId stable and consistent with the rest of the system.
        if ($hasVariants === 0 && $variantModel === null) {
            $variantModel = $product->variants()
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc')
                ->first();
        }
        if ($variantModel) {
            $variantModel->loadMissing(['color', 'size']);
        }

        // Rule 1: Ensure offerId is always unique and consistent per product/variant.
        // IMPORTANT: offerId must NEVER change when a product joins/leaves a campaign.
        // We align offerId with the shared generator used across admin pages (GmcOfferId).
        $offerId = null;
        if ($variantModel !== null) {
            $offerId = app(GmcOfferId::class)->forVariant($variantModel);
        } else {
            // Fallback if product truly has no variants
            $offerId = 'PROD_'.(int) $product->id;
        }

        $title = $this->resolveTitle($product, $variantModel);
        $description = $this->buildHighQualityDescription($product, $variantModel);

        $link = $this->buildProductLink($product);
        $imageLink = $this->resolveImageLink($product, $variantModel);
        $additionalImages = $this->resolveAdditionalImageLinks($product, $variantModel);

        // Get stock from warehouse (availableStock)
        $availability = $this->resolveAvailability($product, $variantModel) > 0 ? 'in stock' : 'out of stock';

        // Get dynamic price (Priority: Campaign price if active, otherwise original price)
        $currentPriceInfo = $this->resolveCurrentPrice($product, $variantModel);
        $originalPrice = $currentPriceInfo['original_price'];
        $campaignPrice = $currentPriceInfo['campaign_price'];
        $salePriceInfo = $currentPriceInfo['sale_price_info'];

        $brand = $this->resolveBrand($product);
        $googleProductCategory = (string) config('gmc.google_product_category', '');

        $dimensions = $this->resolveDimensions($product, $variantModel);

        $g = new GmcProduct;
        $g->setOfferId($offerId);

        // Rule 2: Set itemGroupId ONLY for VARIABLE products to group variants under parent product id.
        if ($variantModel !== null && $hasVariants === 1) {
            $g->setItemGroupId((string) $product->id);

            // Set isDefaultVariant for the first variant (to help Google choose main display image)
            $isDefaultVariant = $this->isFirstVariant($product, $variantModel);
            if ($isDefaultVariant && method_exists($g, 'setIsDefaultVariant')) {
                try {
                    $g->setIsDefaultVariant(true);
                } catch (\Throwable $e) {
                    // Method may not exist in some API versions, silently skip
                }
            }
        }

        $g->setTitle($title);
        $g->setDescription($description);
        $g->setLink($link);
        $g->setImageLink($imageLink);
        if (count($additionalImages) > 0) {
            $g->setAdditionalImageLinks($additionalImages);
        }
        $g->setContentLanguage('vi');
        $g->setTargetCountry('VN');
        $g->setFeedLabel('VN');
        $g->setChannel('online');
        $g->setAvailability($availability);
        $g->setCondition('new');
        $g->setBrand($brand);
        if ($googleProductCategory !== '') {
            $g->setGoogleProductCategory($googleProductCategory);
        }

        // Shipping / packaging dimensions (use dynamic properties to align with raw JSON)
        $g->shippingWeight = [
            'value' => (string) $dimensions['weight'],
            'unit' => 'grams',
        ];
        $g->productLength = [
            'value' => (string) $dimensions['length'],
            'unit' => 'cm',
        ];
        $g->productWidth = [
            'value' => (string) $dimensions['width'],
            'unit' => 'cm',
        ];
        $g->productHeight = [
            'value' => (string) $dimensions['height'],
            'unit' => 'cm',
        ];

        // Price rules:
        // - price: always original/base price (NOT promotional price)
        // - salePrice: promotional/campaign price (if active)
        $price = new GmcPrice;
        $price->setValue(number_format(max(0.0, $originalPrice), 0, '.', ''));
        $price->setCurrency('VND');
        $g->setPrice($price);

        // Set sale price: If campaign is active, set salePrice to campaign price to show "Sale" badge
        // salePriceEffectiveDate will indicate the promotion period
        if ($salePriceInfo !== null && $campaignPrice !== null) {
            $salePrice = new GmcPrice;
            $salePrice->setValue(number_format(max(0.0, $campaignPrice), 0, '.', ''));
            $salePrice->setCurrency('VND');
            $g->setSalePrice($salePrice);
            $g->setSalePriceEffectiveDate($salePriceInfo['effective_date']);
        }

        try {
            if ($debug) {
                Log::info('[GoogleMerchant] Prepared product for upsert', [
                    'product_id' => $product->id,
                    'variant_id' => $variantModel?->id,
                    'merchant_id' => $merchantId,
                    'offer_id' => $offerId,
                    'title' => $title,
                    'link' => $link,
                    'image_link' => $imageLink,
                    'additional_images_count' => count($additionalImages),
                    'availability' => $availability,
                    'price_value' => $price->getValue(),
                    'price_currency' => $price->getCurrency(),
                    'brand' => $brand,
                    'google_product_category' => $googleProductCategory,
                    'description_length' => mb_strlen($description),
                    'shipping_weight_grams' => $dimensions['weight'],
                    'packaging_length_cm' => $dimensions['length'],
                    'packaging_width_cm' => $dimensions['width'],
                    'packaging_height_cm' => $dimensions['height'],
                    'original_price' => $originalPrice,
                    'campaign_price' => $campaignPrice,
                    'sale_price' => $salePriceInfo['price'] ?? null,
                    'sale_price_effective_date' => $salePriceInfo['effective_date'] ?? null,
                    'sale_price_type' => $salePriceInfo['type'] ?? null, // 'flashsale', 'deal', or 'marketing_campaign'
                    'price_strategy' => $campaignPrice !== null ? 'original+sale' : 'original_only',
                    'available_stock' => $this->resolveAvailability($product, $variantModel),
                    'item_group_id' => ($variantModel !== null && $hasVariants === 1) ? (string) $product->id : null,
                    'is_default_variant' => $variantModel !== null ? $this->isFirstVariant($product, $variantModel) : null,
                    'has_variants' => $hasVariants,
                ]);
            }

            $service = $this->makeContentService();
            // Note: products->insert() acts as UPSERT in Google API
            // If offerId already exists, it will update the entire product data (new price, new description, etc.)
            // This ensures that when a product joins a new campaign, GMC will update with the new campaign price
            $service->products->insert($merchantId, $g);

            return [
                'success' => true,
                'skipped' => false,
                'offer_id' => $offerId,
            ];
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant] upsertProduct failed', [
                'product_id' => $product->id,
                'variant_id' => $variantModel?->id,
                'merchant_id' => $merchantId,
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'code' => (int) $e->getCode(),
            ]);

            return [
                'success' => false,
                'skipped' => false,
                'offer_id' => $offerId,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function resolveImageLink(Product $product, ?Variant $variant): string
    {
        $image = '';
        if ($variant && ! empty($variant->image)) {
            $image = (string) $variant->image;
        }
        if ($image === '' && ! empty($product->image)) {
            $image = (string) $product->image;
        }
        if ($image === '') {
            $gallery = $this->decodeGallery((string) ($product->gallery ?? ''));
            if (count($gallery) > 0) {
                $image = (string) $gallery[0];
            }
        }

        if ($image === '') {
            return (string) asset('/public/image/no_image.png');
        }

        if (preg_match('/^https?:\/\//i', $image) === 1) {
            return (string) $image;
        }

        $r2BaseUrl = (string) config('filesystems.disks.r2.url', '');
        if ($r2BaseUrl !== '') {
            return rtrim($r2BaseUrl, '/').'/'.ltrim($image, '/');
        }

        return (string) asset($image);
    }

    private function decodeGallery(string $galleryJson): array
    {
        $decoded = json_decode($galleryJson, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function buildProductLink(Product $product): string
    {
        $base = trim((string) config('gmc.store_base_url', ''));
        if ($base !== '') {
            return rtrim($base, '/').'/'.ltrim((string) ($product->slug ?? ''), '/');
        }

        return function_exists('getSlug') ? (string) getSlug((string) $product->slug) : (string) url((string) $product->slug);
    }

    private function buildHighQualityDescription(Product $product, ?Variant $variant): string
    {
        $parts = [];

        $name = $this->cleanText((string) ($product->name ?? ''));
        if ($name !== '') {
            $parts[] = $name;
        }

        $brand = $this->cleanText($this->resolveBrand($product));
        if ($brand !== '') {
            $parts[] = 'Brand: '.$brand;
        }

        $originName = $this->cleanText((string) ($product->origin?->name ?? ''));
        if ($originName !== '') {
            $parts[] = 'Origin: '.$originName;
        }

        if ($variant) {
            $opt = $this->cleanText((string) ($variant->option1_value ?? ''));
            if ($opt !== '') {
                $parts[] = 'Variant: '.$opt;
            }
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

    private function resolveAdditionalImageLinks(Product $product, ?Variant $variant): array
    {
        $urls = [];
        $gallery = $this->decodeGallery((string) ($product->gallery ?? ''));
        foreach ($gallery as $img) {
            $img = (string) $img;
            if ($img === '') {
                continue;
            }
            $urls[] = $this->resolveAbsoluteImageUrl($img);
            if (count($urls) >= 10) {
                break;
            }
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function resolveAbsoluteImageUrl(string $image): string
    {
        if (preg_match('/^https?:\/\//i', $image) === 1) {
            return $image;
        }

        $r2BaseUrl = (string) config('filesystems.disks.r2.url', '');
        if ($r2BaseUrl !== '') {
            return rtrim($r2BaseUrl, '/').'/'.ltrim($image, '/');
        }

        return (string) asset($image);
    }

    /**
     * Resolve availability from warehouse (availableStock column).
     * Returns available stock count from InventoryService.
     */
    private function resolveAvailability(Product $product, ?Variant $variant): int
    {
        $warehouseId = config('gmc.warehouse_id', null);
        $inventoryService = app(InventoryServiceInterface::class);

        if ($variant) {
            $stockDto = $inventoryService->getStock((int) $variant->id, $warehouseId !== null ? (int) $warehouseId : null);

            return (int) ($stockDto->availableStock ?? 0);
        }

        // For product without variant, try to get from first variant or fallback to product stock
        $firstVariant = $product->variants()->first();
        if ($firstVariant) {
            $stockDto = $inventoryService->getStock((int) $firstVariant->id, $warehouseId !== null ? (int) $warehouseId : null);

            return (int) ($stockDto->availableStock ?? 0);
        }

        return (int) ($product->stock ?? 0);
    }

    /**
     * Resolve current price with dynamic campaign detection.
     * Returns array with original_price, campaign_price, and sale_price_info.
     *
     * Logic: If campaign is active, use campaign price as display price.
     * This ensures GMC always shows the current selling price.
     *
     * @return array{original_price:float, campaign_price:float|null, sale_price_info:array|null}
     */
    private function resolveCurrentPrice(Product $product, ?Variant $variant): array
    {
        // Get original price (base price from variant/product, before promotions)
        $originalPrice = $this->resolveOriginalPrice($product, $variant);

        // Get sale price info (Priority: Flash Sale > Deal > Marketing Campaign)
        $salePriceInfo = $this->resolveSalePriceInfo($product, $variant);

        // If campaign is active, use campaign price
        $campaignPrice = null;
        if ($salePriceInfo !== null) {
            $campaignPrice = (float) $salePriceInfo['price'];
        }

        return [
            'original_price' => $originalPrice,
            'campaign_price' => $campaignPrice,
            'sale_price_info' => $salePriceInfo,
        ];
    }

    /**
     * Resolve original price (base price from variant/product, NOT promotional price).
     * This is used for GMC 'price' field.
     */
    private function resolveOriginalPrice(Product $product, ?Variant $variant): float
    {
        // Prefer variant price if available
        if ($variant && $variant->price > 0) {
            return (float) $variant->price;
        }

        // Fallback to product price
        if ($product->price > 0) {
            return (float) $product->price;
        }

        // Last resort: try to get from first variant
        $firstVariant = $product->variants()->first();
        if ($firstVariant && $firstVariant->price > 0) {
            return (float) $firstVariant->price;
        }

        return 0.0;
    }

    private function resolveBrand(Product $product): string
    {
        $brandName = (string) ($product->brand?->name ?? '');
        if ($brandName !== '') {
            return $brandName;
        }

        $appName = (string) config('app.name', '');

        return $appName !== '' ? $appName : 'Website';
    }

    private function resolveTitle(Product $product, ?Variant $variant): string
    {
        $base = (string) ($product->name ?? '');
        if (! $variant) {
            return $base;
        }

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

    /**
     * Resolve sale price info (Priority: Flash Sale > Deal > Marketing Campaign).
     * Returns null if no active promotion, or array with 'price', 'effective_date', and 'type'.
     *
     * @return array{price:float,effective_date:string,type:string}|null
     */
    private function resolveSalePriceInfo(Product $product, ?Variant $variant): ?array
    {
        $now = Carbon::now();
        $nowTimestamp = $now->timestamp;

        // Priority 1: Flash Sale
        $flashSaleInfo = $this->resolveFlashSaleInfo($product, $variant, $nowTimestamp);
        if ($flashSaleInfo !== null) {
            return array_merge($flashSaleInfo, ['type' => 'flashsale']);
        }

        // Priority 2: Deal
        $dealInfo = $this->resolveDealInfo($product, $variant, $nowTimestamp);
        if ($dealInfo !== null) {
            return array_merge($dealInfo, ['type' => 'deal']);
        }

        // Priority 3: Marketing Campaign
        $marketingCampaignInfo = $this->resolveMarketingCampaignInfo($product, $variant, $now);
        if ($marketingCampaignInfo !== null) {
            return array_merge($marketingCampaignInfo, ['type' => 'marketing_campaign']);
        }

        return null;
    }

    /**
     * Resolve Flash Sale info (price and effective date range).
     *
     * @return array{price:float,effective_date:string}|null
     */
    private function resolveFlashSaleInfo(Product $product, ?Variant $variant, int $nowTimestamp): ?array
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
        $productSaleQuery = ProductSale::where('flashsale_id', $activeFlashSale->id)
            ->where('product_id', $product->id);

        if ($variant) {
            $productSaleQuery->where('variant_id', $variant->id);
        } else {
            $productSaleQuery->whereNull('variant_id');
        }

        $productSale = $productSaleQuery->first();

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
    private function resolveDealInfo(Product $product, ?Variant $variant, int $nowTimestamp): ?array
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
        $saleDealQuery = SaleDeal::where('deal_id', $activeDeal->id)
            ->where('product_id', $product->id)
            ->where('status', '1');

        if ($variant) {
            $saleDealQuery->where('variant_id', $variant->id);
        } else {
            $saleDealQuery->whereNull('variant_id');
        }

        $saleDeal = $saleDealQuery->first();

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
    private function resolveMarketingCampaignInfo(Product $product, ?Variant $variant, Carbon $now): ?array
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
     * @return array{weight:float,length:float,width:float,height:float}
     */
    private function resolveDimensions(Product $product, ?Variant $variant): array
    {
        // Prefer variant weight if present, otherwise product weight.
        $weight = (float) ($variant?->weight ?? $product->weight ?? 0.0);
        $length = (float) ($product->length ?? 0.0);
        $width = (float) ($product->width ?? 0.0);
        $height = (float) ($product->height ?? 0.0);

        // Default dimensions if missing (10x10x10 cm)
        if ($length <= 0.0) {
            $length = 10.0;
        }
        if ($width <= 0.0) {
            $width = 10.0;
        }
        if ($height <= 0.0) {
            $height = 10.0;
        }

        // If weight is missing, fall back to a small default (100g) to avoid 0-weight issues.
        if ($weight <= 0.0) {
            $weight = 100.0;
        }

        return [
            'weight' => $weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Delete product from GMC by offerId.
     *
     * @param  string  $offerId  The offerId to delete
     * @return array{success:bool,message:string}
     */
    public function deleteProduct(string $offerId): array
    {
        $merchantId = (string) config('gmc.merchant_id', '');
        $debug = (bool) config('gmc.debug', false);

        if ($merchantId === '') {
            return ['success' => false, 'message' => 'GMC merchant_id is missing'];
        }

        try {
            $service = $this->makeContentService();
            $service->products->delete($merchantId, $offerId);

            if ($debug) {
                Log::info('[GoogleMerchant] Product deleted from GMC', [
                    'merchant_id' => $merchantId,
                    'offer_id' => $offerId,
                ]);
            }

            return ['success' => true, 'message' => 'Product deleted successfully'];
        } catch (\Throwable $e) {
            Log::error('[GoogleMerchant] deleteProduct failed', [
                'merchant_id' => $merchantId,
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'code' => (int) $e->getCode(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if this variant is the first/default variant of the product.
     * Priority: position ASC -> id ASC.
     */
    private function isFirstVariant(Product $product, ?Variant $variant): bool
    {
        if ($variant === null) {
            return false;
        }

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
