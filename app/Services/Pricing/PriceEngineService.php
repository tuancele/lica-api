<?php

namespace App\Services\Pricing;

use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Marketing\Models\MarketingCampaign;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Services\Warehouse\WarehouseServiceInterface;
use Carbon\Carbon;

/**
 * Price Engine Service
 * 
 * Tính toán giá hiển thị theo độ ưu tiên:
 * Priority 1: Flash Sale (nếu trong khung giờ và còn stock)
 * Priority 2: Promotion/Marketing Campaign (nếu trong khung giờ)
 * Priority 3: Base Price (giá gốc)
 */
class PriceEngineService implements PriceEngineServiceInterface
{
    protected ?WarehouseServiceInterface $warehouseService = null;
    
    /**
     * Set Warehouse Service (optional dependency injection)
     * 
     * @param WarehouseServiceInterface $warehouseService
     * @return void
     */
    public function setWarehouseService(WarehouseServiceInterface $warehouseService): void
    {
        $this->warehouseService = $warehouseService;
    }
    
    /**
     * Tính giá hiển thị cho sản phẩm
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (nếu có)
     * @return array Thông tin giá: ['price' => float, 'type' => string, 'label' => string, 'original_price' => float]
     */
    public function calculateDisplayPrice(int $productId, ?int $variantId = null): array
    {
        $now = Carbon::now();
        
        // Lấy giá gốc
        $originalPrice = $this->getOriginalPrice($productId, $variantId);
        
        // Priority 1: Kiểm tra Flash Sale
        $flashSalePrice = $this->getFlashSalePrice($productId, $variantId, $now);
        if ($flashSalePrice !== null) {
            return [
                'price' => $flashSalePrice['price'],
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale',
                'discount_percent' => $this->calculateDiscountPercent($originalPrice, $flashSalePrice['price']),
                'flash_sale_id' => $flashSalePrice['flash_sale_id'],
                'product_sale_id' => $flashSalePrice['product_sale_id'],
                'remaining_stock' => $flashSalePrice['remaining_stock'],
            ];
        }
        
        // Priority 2: Kiểm tra Promotion/Marketing Campaign
        $promotionPrice = $this->getPromotionPrice($productId, $now);
        if ($promotionPrice !== null) {
            return [
                'price' => $promotionPrice['price'],
                'original_price' => $originalPrice,
                'type' => 'promotion',
                'label' => 'Khuyến mãi',
                'discount_percent' => $this->calculateDiscountPercent($originalPrice, $promotionPrice['price']),
                'campaign_id' => $promotionPrice['campaign_id'],
            ];
        }
        
        // Priority 3: Giá gốc
        return [
            'price' => $originalPrice,
            'original_price' => $originalPrice,
            'type' => 'normal',
            'label' => '',
            'discount_percent' => 0,
        ];
    }
    
    /**
     * Lấy giá Flash Sale (nếu đang trong khung giờ và còn stock)
     * 
     * @param int $productId
     * @param int|null $variantId
     * @param Carbon $now
     * @return array|null ['price' => float, 'flash_sale_id' => int, 'product_sale_id' => int, 'remaining_stock' => int]
     */
    protected function getFlashSalePrice(int $productId, ?int $variantId, Carbon $now): ?array
    {
        // Tìm Flash Sale đang active
        $activeFlashSale = FlashSale::where('status', '1')
            ->where('start', '<=', $now->timestamp)
            ->where('end', '>=', $now->timestamp)
            ->first();
        
        if (!$activeFlashSale) {
            return null;
        }
        
        // Tìm ProductSale cho sản phẩm này
        $productSaleQuery = ProductSale::where('flashsale_id', $activeFlashSale->id)
            ->where('product_id', $productId);
        
        if ($variantId) {
            $productSaleQuery->where('variant_id', $variantId);
        } else {
            $productSaleQuery->whereNull('variant_id');
        }
        
        $productSale = $productSaleQuery->first();
        
        if (!$productSale) {
            return null;
        }
        
        // Kiểm tra còn stock không (flash_stock_sold < flash_stock_limit)
        // number = flash_stock_limit, buy = flash_stock_sold
        $remainingStock = $productSale->number - $productSale->buy;
        
        if ($remainingStock <= 0) {
            return null; // Hết stock Flash Sale, sẽ chuyển sang Promotion
        }
        
        return [
            'price' => (float) $productSale->price_sale,
            'flash_sale_id' => $activeFlashSale->id,
            'product_sale_id' => $productSale->id,
            'remaining_stock' => $remainingStock,
        ];
    }
    
    /**
     * Lấy giá Promotion/Marketing Campaign
     * 
     * @param int $productId
     * @param Carbon $now
     * @return array|null ['price' => float, 'campaign_id' => int]
     */
    protected function getPromotionPrice(int $productId, Carbon $now): ?array
    {
        // Tìm Marketing Campaign đang active
        $activeCampaign = MarketingCampaign::where('status', '1')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
        
        if (!$activeCampaign) {
            return null;
        }
        
        // Tìm sản phẩm trong campaign
        $campaignProduct = MarketingCampaignProduct::where('campaign_id', $activeCampaign->id)
            ->where('product_id', $productId)
            ->first();
        
        if (!$campaignProduct) {
            return null;
        }
        
        return [
            'price' => (float) $campaignProduct->price,
            'campaign_id' => $activeCampaign->id,
        ];
    }
    
    /**
     * Lấy giá gốc của sản phẩm
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return float
     */
    protected function getOriginalPrice(int $productId, ?int $variantId = null): float
    {
        if ($variantId) {
            $variant = Variant::find($variantId);
            return $variant ? (float) $variant->price : 0;
        }
        
        $product = Product::find($productId);
        if (!$product) {
            return 0;
        }
        
        $variant = $product->variant($productId);
        return $variant ? (float) $variant->price : 0;
    }
    
    /**
     * Tính phần trăm giảm giá
     * 
     * @param float $originalPrice
     * @param float $salePrice
     * @return int
     */
    protected function calculateDiscountPercent(float $originalPrice, float $salePrice): int
    {
        if ($originalPrice <= 0) {
            return 0;
        }
        
        return (int) round((($originalPrice - $salePrice) / $originalPrice) * 100);
    }
    
    /**
     * Tính giá với số lượng (hỗ trợ giá hỗn hợp khi mua vượt hạn mức Flash Sale)
     * 
     * Logic:
     * - Nếu Q <= S_flash_rem: Tất cả tính theo flash_price
     * - Nếu Q > S_flash_rem: 
     *   + S_flash_rem tính theo flash_price
     *   + (Q - S_flash_rem) tính theo promo_price hoặc original_price
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (nếu có)
     * @param int $quantity Số lượng mua
     * @return array Thông tin giá chi tiết với breakdown:
     *   [
     *     'total_price' => float,
     *     'price_breakdown' => [
     *       ['type' => 'flashsale', 'quantity' => int, 'unit_price' => float, 'subtotal' => float],
     *       ['type' => 'promotion'|'normal', 'quantity' => int, 'unit_price' => float, 'subtotal' => float]
     *     ],
     *     'flash_sale_remaining' => int,
     *     'warning' => string|null
     *   ]
     */
    public function calculatePriceWithQuantity(int $productId, ?int $variantId = null, int $quantity): array
    {
        $now = Carbon::now();
        
        // QUAN TRỌNG: Kiểm tra tồn kho thực tế từ Warehouse API
        $totalPhysicalStock = null;
        $isAvailable = true;
        $stockErrorMessage = null;
        
        if ($this->warehouseService && $variantId) {
            try {
                $stockInfo = $this->warehouseService->getVariantStock($variantId);
                $totalPhysicalStock = (int)($stockInfo['current_stock'] ?? 0);
                
                // Kiểm tra nếu số lượng vượt quá tồn kho thực tế
                if ($quantity > $totalPhysicalStock) {
                    $isAvailable = false;
                    $stockErrorMessage = "Rất tiếc, sản phẩm này chỉ còn tối đa {$totalPhysicalStock} sản phẩm trong kho. Vui lòng điều chỉnh lại số lượng.";
                }
            } catch (\Exception $e) {
                // Nếu không lấy được tồn kho, vẫn cho phép nhưng log lỗi
                \Illuminate\Support\Facades\Log::warning('[PriceEngineService] Failed to get warehouse stock', [
                    'variant_id' => $variantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Nếu không có tồn kho hoặc vượt quá tồn kho, trả về lỗi ngay
        if (!$isAvailable) {
            return [
                'total_price' => 0,
                'price_breakdown' => [],
                'flash_sale_remaining' => 0,
                'warning' => null,
                'total_physical_stock' => $totalPhysicalStock,
                'is_available' => false,
                'stock_error' => $stockErrorMessage,
            ];
        }
        
        // Lấy giá gốc
        $originalPrice = $this->getOriginalPrice($productId, $variantId);
        
        // Lấy thông tin Flash Sale
        $flashSalePrice = $this->getFlashSalePrice($productId, $variantId, $now);
        
        // Nếu không có Flash Sale hoặc hết stock Flash Sale
        if ($flashSalePrice === null || $flashSalePrice['remaining_stock'] <= 0) {
            // Tính theo Promotion hoặc giá gốc
            $promotionPrice = $this->getPromotionPrice($productId, $now);
            $normalPrice = $promotionPrice ? $promotionPrice['price'] : $originalPrice;
            $priceType = $promotionPrice ? 'promotion' : 'normal';
            
            return [
                'total_price' => $normalPrice * $quantity,
                'price_breakdown' => [
                    [
                        'type' => $priceType,
                        'quantity' => $quantity,
                        'unit_price' => $normalPrice,
                        'subtotal' => $normalPrice * $quantity,
                    ]
                ],
                'flash_sale_remaining' => 0,
                'warning' => null,
                'total_physical_stock' => $totalPhysicalStock,
                'is_available' => true,
            ];
        }
        
        // Có Flash Sale và còn stock
        $flashRemaining = $flashSalePrice['remaining_stock'];
        $flashPrice = $flashSalePrice['price'];
        $priceBreakdown = [];
        $totalPrice = 0;
        $warning = null;
        
        if ($quantity <= $flashRemaining) {
            // Trong hạn mức: Tất cả tính theo flash_price
            $subtotal = $flashPrice * $quantity;
            $priceBreakdown[] = [
                'type' => 'flashsale',
                'quantity' => $quantity,
                'unit_price' => $flashPrice,
                'subtotal' => $subtotal,
            ];
            $totalPrice = $subtotal;
        } else {
            // Vượt hạn mức: Tính giá hỗn hợp
            // Phần trong hạn mức tính theo flash_price
            $flashSubtotal = $flashPrice * $flashRemaining;
            $priceBreakdown[] = [
                'type' => 'flashsale',
                'quantity' => $flashRemaining,
                'unit_price' => $flashPrice,
                'subtotal' => $flashSubtotal,
            ];
            
            // Phần vượt hạn mức tính theo giá ưu tiên tiếp theo
            $excessQuantity = $quantity - $flashRemaining;
            $promotionPrice = $this->getPromotionPrice($productId, $now);
            $normalPrice = $promotionPrice ? $promotionPrice['price'] : $originalPrice;
            $normalPriceType = $promotionPrice ? 'promotion' : 'normal';
            
            $normalSubtotal = $normalPrice * $excessQuantity;
            $priceBreakdown[] = [
                'type' => $normalPriceType,
                'quantity' => $excessQuantity,
                'unit_price' => $normalPrice,
                'subtotal' => $normalSubtotal,
            ];
            
            $totalPrice = $flashSubtotal + $normalSubtotal;
            
            // Cảnh báo cho Frontend
            $warning = "Chỉ còn {$flashRemaining} sản phẩm giá Flash Sale, {$excessQuantity} sản phẩm còn lại sẽ được tính theo giá " . 
                      ($normalPriceType === 'promotion' ? 'khuyến mãi' : 'thường');
        }
        
        return [
            'total_price' => $totalPrice,
            'price_breakdown' => $priceBreakdown,
            'flash_sale_remaining' => $flashRemaining,
            'warning' => $warning,
            'flash_sale_id' => $flashSalePrice['flash_sale_id'],
            'product_sale_id' => $flashSalePrice['product_sale_id'],
            'total_physical_stock' => $totalPhysicalStock,
            'is_available' => true,
        ];
    }
}
