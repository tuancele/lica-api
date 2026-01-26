<?php

declare(strict_types=1);
namespace App\Services;

use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Warehouse\WarehouseServiceInterface;
use Carbon\Carbon;

/**
 * Price Calculation Service
 * 
 * Centralized service for calculating product prices with priority:
 * 1. Flash Sale (highest priority)
 * 2. Marketing Campaign
 * 3. Normal Price
 */
class PriceCalculationService
{
    protected ?WarehouseServiceInterface $warehouseService;

    public function __construct(?WarehouseServiceInterface $warehouseService = null)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Set warehouse service (for dependency injection after construction)
     * 
     * @param WarehouseServiceInterface $warehouseService
     * @return void
     */
    public function setWarehouseService(WarehouseServiceInterface $warehouseService): void
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Calculate effective stock (min of Flash Sale remaining and warehouse stock)
     * 
     * Formula: min(flash_sale_remaining, warehouse_stock)
     * 
     * @param int|null $flashSaleRemaining Flash Sale remaining quantity
     * @param int $warehouseStock Warehouse stock quantity
     * @return int Effective stock
     */
    public function calculateEffectiveStock(?int $flashSaleRemaining, int $warehouseStock): int
    {
        // If no Flash Sale, use warehouse stock
        if ($flashSaleRemaining === null) {
            return $warehouseStock;
        }
        
        // Return minimum of Flash Sale remaining and warehouse stock
        return min($flashSaleRemaining, $warehouseStock);
    }
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
            // Get warehouse stock for effective stock calculation
            // For product-level Flash Sale, use first variant's stock or product stock
            $warehouseStock = 0;
            if ($variant && $this->warehouseService) {
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    // Fallback to variant stock
                    $warehouseStock = (int) ($variant->stock ?? 0);
                }
            } else {
                // Fallback to variant stock or product stock
                $warehouseStock = $variant ? (int) ($variant->stock ?? 0) : 0;
            }
            
            // Calculate effective stock: min(flash_sale_remaining, warehouse_stock)
            $flashSaleRemaining = $flashSaleProduct->remaining;
            $effectiveStock = $this->calculateEffectiveStock($flashSaleRemaining, $warehouseStock);
            
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
                    'remaining' => $flashSaleRemaining,
                    'effective_stock' => $effectiveStock, // ← Effective stock (min of Flash Sale and warehouse)
                    'warehouse_stock' => $warehouseStock, // ← Warehouse stock for reference
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

        // 3. Normal Price
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
            // Get warehouse stock for effective stock calculation
            $warehouseStock = 0;
            if ($this->warehouseService) {
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    // Fallback to variant stock
                    $warehouseStock = (int) ($variant->stock ?? 0);
                }
            } else {
                // Fallback to variant stock if no warehouse service
                $warehouseStock = (int) ($variant->stock ?? 0);
            }
            
            // Calculate effective stock: min(flash_sale_remaining, warehouse_stock)
            $flashSaleRemaining = $productSale->remaining;
            $effectiveStock = $this->calculateEffectiveStock($flashSaleRemaining, $warehouseStock);
            
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
                    'remaining' => $flashSaleRemaining,
                    'effective_stock' => $effectiveStock, // ← Effective stock (min of Flash Sale and warehouse)
                    'warehouse_stock' => $warehouseStock, // ← Warehouse stock for reference
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
            // Get warehouse stock for effective stock calculation
            $warehouseStock = 0;
            if ($this->warehouseService) {
                try {
                    $stockData = $this->warehouseService->getVariantStock($variant->id);
                    $warehouseStock = (int) ($stockData['current_stock'] ?? 0);
                } catch (\Exception $e) {
                    // Fallback to variant stock
                    $warehouseStock = (int) ($variant->stock ?? 0);
                }
            } else {
                // Fallback to variant stock if no warehouse service
                $warehouseStock = (int) ($variant->stock ?? 0);
            }
            
            // Calculate effective stock: min(flash_sale_remaining, warehouse_stock)
            $flashSaleRemaining = $productSaleFallback->remaining;
            $effectiveStock = $this->calculateEffectiveStock($flashSaleRemaining, $warehouseStock);
            
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
                    'remaining' => $flashSaleRemaining,
                    'effective_stock' => $effectiveStock, // ← Effective stock (min of Flash Sale and warehouse)
                    'warehouse_stock' => $warehouseStock, // ← Warehouse stock for reference
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
