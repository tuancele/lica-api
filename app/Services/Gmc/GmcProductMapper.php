<?php

namespace App\Services\Gmc;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Pricing\PriceEngineServiceInterface;
use App\Services\Inventory\Contracts\InventoryServiceInterface;
use Google\Service\ShoppingContent\Product as GmcProduct;
use Google\Service\ShoppingContent\Price as GmcPrice;

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

        $qty = 1;
        $priceInfo = $this->priceEngine->calculatePriceWithQuantity((int) $product->id, (int) $variant->id, $qty);
        $unitPrice = $qty > 0 ? ((float) ($priceInfo['total_price'] ?? 0) / $qty) : 0.0;

        $stockDto = $this->inventoryService->getStock((int) $variant->id, $warehouseId !== null ? (int) $warehouseId : null);
        $sellable = (int) ($stockDto->sellableStock ?? 0);
        $availability = $sellable > 0 ? 'in stock' : 'out of stock';

        $product->loadMissing(['brand', 'origin']);
        $variant->loadMissing(['color', 'size']);

        $link = $this->buildProductLink($product);
        $imageLink = $this->formatImageUrl($product->image);
        $additionalImages = $this->formatAdditionalImages($product);

        $offerId = $this->offerId->forVariant($variant);

        $g = new GmcProduct();
        $g->setOfferId($offerId);
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
        $price = new GmcPrice();
        $price->setValue(number_format(max(0.0, $unitPrice), 0, '.', ''));
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
            return rtrim($base, '/') . '/' . ltrim((string) ($product->slug ?? ''), '/');
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

        return $base . ' - ' . implode(' - ', $parts);
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
            $parts[] = 'Brand: ' . $brand;
        }

        $originName = $this->cleanText((string) optional($product->origin)->name);
        if ($originName !== '') {
            $parts[] = 'Origin: ' . $originName;
        }

        $opt = $this->cleanText((string) ($variant->option1_value ?? ''));
        if ($opt !== '') {
            $parts[] = 'Variant: ' . $opt;
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
                return rtrim($r2Domain, '/') . '/public/image/no_image.png';
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
        $cleanPath = str_replace($checkR2 . '/', '', $cleanPath);
        $cleanPath = str_replace($checkR2, '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath) ?? $cleanPath;
        $cleanPath = preg_replace('#(uploads/)+#', 'uploads/', $cleanPath) ?? $cleanPath;
        $cleanPath = ltrim($cleanPath, '/');

        return $r2DomainClean . '/' . $cleanPath;
    }
}


