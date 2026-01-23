<?php

namespace App\Modules\GoogleMerchant\Services;

use App\Enums\ProductType;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Pricing\PriceEngineService;
use Google\Client as GoogleClient;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Product as GmcProduct;
use Google\Service\ShoppingContent\Price as GmcPrice;
use Illuminate\Support\Facades\Log;

class GoogleMerchantService
{
    private function makeContentService(): ShoppingContent
    {
        $jsonPath = storage_path('app/google/service-account.json');
        if (!is_file($jsonPath)) {
            throw new \RuntimeException('Google Merchant service account JSON is missing.');
        }

        $client = new GoogleClient();
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

        $product->loadMissing(['brand', 'origin']);

        $variantModel = null;
        if ($variant instanceof Variant) {
            $variantModel = $variant;
        } elseif (is_int($variant)) {
            $variantModel = Variant::find($variant);
        }
        if ($variantModel) {
            $variantModel->loadMissing(['color', 'size']);
        }

        $offerId = $variantModel
            ? ('PROD_' . (int) $product->id . '_VAR_' . (int) $variantModel->id)
            : ('PROD_' . (int) $product->id . '_VAR_0');

        $title = $this->resolveTitle($product, $variantModel);
        $description = $this->buildHighQualityDescription($product, $variantModel);

        $link = $this->buildProductLink($product);
        $imageLink = $this->resolveImageLink($product, $variantModel);
        $additionalImages = $this->resolveAdditionalImageLinks($product, $variantModel);

        $availability = $this->resolveAvailability($product, $variantModel) > 0 ? 'in stock' : 'out of stock';
        $unitPrice = $this->resolvePrice($product, $variantModel);

        $brand = $this->resolveBrand($product);
        $googleProductCategory = (string) config('gmc.google_product_category', '');

        $g = new GmcProduct();
        $g->setOfferId($offerId);
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

        $price = new GmcPrice();
        $price->setValue(number_format(max(0.0, $unitPrice), 0, '.', ''));
        $price->setCurrency('VND');
        $g->setPrice($price);

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
                ]);
            }

            $service = $this->makeContentService();
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
        if ($variant && !empty($variant->image)) {
            $image = (string) $variant->image;
        }
        if ($image === '' && !empty($product->image)) {
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
            return rtrim($r2BaseUrl, '/') . '/' . ltrim($image, '/');
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
            return rtrim($base, '/') . '/' . ltrim((string) ($product->slug ?? ''), '/');
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
            $parts[] = 'Brand: ' . $brand;
        }

        $originName = $this->cleanText((string) ($product->origin?->name ?? ''));
        if ($originName !== '') {
            $parts[] = 'Origin: ' . $originName;
        }

        if ($variant) {
            $opt = $this->cleanText((string) ($variant->option1_value ?? ''));
            if ($opt !== '') {
                $parts[] = 'Variant: ' . $opt;
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
            $parts[] = 'Ingredients: ' . mb_substr($ingredient, 0, 800);
        }

        $text = trim(implode('. ', array_filter($parts)));
        $text = $this->cleanText($text);

        if (mb_strlen($text) < 30) {
            $text = $name !== '' ? ($name . '. ' . 'High quality product for daily use.') : 'High quality product for daily use.';
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
            return rtrim($r2BaseUrl, '/') . '/' . ltrim($image, '/');
        }

        return (string) asset($image);
    }

    private function resolveAvailability(Product $product, ?Variant $variant): int
    {
        if ($variant) {
            return (int) ($variant->stock ?? 0);
        }
        return (int) ($product->stock ?? 0);
    }

    private function resolvePrice(Product $product, ?Variant $variant): float
    {
        $variantId = $variant ? (int) $variant->id : null;
        $svc = app(PriceEngineService::class);
        $info = $svc->calculateDisplayPrice((int) $product->id, $variantId);
        return (float) ($info['price'] ?? 0.0);
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
        if (!$variant) {
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

        return $base . ' - ' . implode(' - ', $parts);
    }
}


