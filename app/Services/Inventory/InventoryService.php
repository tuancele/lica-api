<?php

namespace App\Services\Inventory;

use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Management Service
 * 
 * Quản lý tồn kho với hai loại:
 * - Physical Stock (S_phy): Tổng số lượng thực tế trong kho
 * - Flash Sale Virtual Stock (S_flash): Số lượng được "cắt" ra dành riêng cho Flash Sale
 */
class InventoryService implements InventoryServiceInterface
{
    protected WarehouseServiceInterface $warehouseService;
    
    public function __construct(WarehouseServiceInterface $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }
    
    /**
     * Xử lý đơn hàng và trừ tồn kho
     * 
     * @param array $orderItems [
     *   ['product_id' => int, 'variant_id' => int|null, 'quantity' => int, 'order_type' => 'flashsale'|'promotion'|'normal']
     * ]
     * @return array ['success' => bool, 'message' => string, 'errors' => array]
     */
    public function processOrder(array $orderItems): array
    {
        DB::beginTransaction();
        
        try {
            $warnings = [];
            
            foreach ($orderItems as $index => $item) {
                $productId = $item['product_id'];
                $variantId = $item['variant_id'] ?? null;
                $quantity = $item['quantity'];
                $orderType = $item['order_type'] ?? 'normal';
                
                if ($orderType === 'flashsale') {
                    // Xử lý đơn hàng Flash Sale
                    $result = $this->processFlashSaleOrder($productId, $variantId, $quantity);
                    if (!$result['success']) {
                        DB::rollBack();
                        return $result;
                    }
                    
                    // Thu thập cảnh báo nếu có
                    if (isset($result['warning'])) {
                        $warnings[] = [
                            'item_index' => $index,
                            'product_id' => $productId,
                            'variant_id' => $variantId,
                            'message' => $result['warning'],
                        ];
                    }
                } else {
                    // Xử lý đơn hàng thường (Promotion, Deal Sốc, hoặc giá gốc)
                    $result = $this->processNormalOrder($productId, $variantId, $quantity);
                    if (!$result['success']) {
                        DB::rollBack();
                        return $result;
                    }
                }
            }
            
            DB::commit();
            
            $response = [
                'success' => true,
                'message' => 'Xử lý đơn hàng thành công',
            ];
            
            if (!empty($warnings)) {
                $response['warnings'] = $warnings;
            }
            
            return $response;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Process Order Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'items' => $orderItems
            ]);
            
            return [
                'success' => false,
                'message' => 'Lỗi xử lý đơn hàng: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Xử lý đơn hàng Flash Sale (hỗ trợ mua vượt hạn mức)
     * 
     * Logic:
     * - Nếu Q <= S_flash_rem: Trừ flash_stock_sold và physical_stock bình thường
     * - Nếu Q > S_flash_rem: 
     *   + flash_stock_sold chạm trần (đặt = flash_stock_limit)
     *   + Trừ toàn bộ Q vào physical_stock
     * 
     * @param int $productId
     * @param int|null $variantId
     * @param int $quantity
     * @return array
     */
    protected function processFlashSaleOrder(int $productId, ?int $variantId, int $quantity): array
    {
        // Tìm ProductSale đang active
        $productSale = $this->findActiveProductSale($productId, $variantId);
        
        if (!$productSale) {
            return [
                'success' => false,
                'message' => 'Sản phẩm không có trong Flash Sale',
            ];
        }
        
        // Kiểm tra tồn kho thực tế
        // Nếu có variant_id, dùng variant_id; nếu không, tìm default variant của product
        $stockVariantId = $variantId;
        if (!$stockVariantId) {
            $product = Product::find($productId);
            if ($product) {
                $defaultVariant = $product->variant($productId);
                $stockVariantId = $defaultVariant ? $defaultVariant->id : $productId;
            } else {
                $stockVariantId = $productId;
            }
        }
        
        $physicalStock = $this->warehouseService->getVariantStock($stockVariantId);
        if ($physicalStock['current_stock'] < $quantity) {
            return [
                'success' => false,
                'message' => "Không đủ tồn kho. Tồn kho hiện tại: " . $physicalStock['current_stock'],
            ];
        }
        
        // Sử dụng Row Locking để tránh Race Condition
        $productSale = ProductSale::where('id', $productSale->id)
            ->lockForUpdate()
            ->first();
        
        // Tính toán số lượng Flash Sale còn lại
        // number = flash_stock_limit, buy = flash_stock_sold
        $currentSold = $productSale->buy;
        $stockLimit = $productSale->number;
        $flashRemaining = $stockLimit - $currentSold;
        
        // Xử lý theo logic mua vượt hạn mức
        if ($quantity <= $flashRemaining) {
            // Trong hạn mức: Tăng flash_stock_sold và trừ physical_stock
            $productSale->buy += $quantity;
            $productSale->save();
            
            // Trừ physical stock
            $this->warehouseService->deductStock($stockVariantId, $quantity, 'flashsale_order');
            
            $isFlashSaleExhausted = ($productSale->buy >= $productSale->number);
            
            return [
                'success' => true,
                'message' => 'Xử lý đơn hàng Flash Sale thành công',
                'flash_sale_exhausted' => $isFlashSaleExhausted,
                'flash_quantity' => $quantity,
                'normal_quantity' => 0,
            ];
        } else {
            // Vượt hạn mức: flash_stock_sold chạm trần, trừ toàn bộ Q vào physical_stock
            $flashQuantity = $flashRemaining;
            $normalQuantity = $quantity - $flashRemaining;
            
            // Lấy thông tin sản phẩm để logging
            $product = Product::find($productId);
            $productName = $product ? $product->name : "Product ID: {$productId}";
            
            // Tính giá để tính chênh lệch doanh thu
            $flashPrice = (float) $productSale->price_sale;
            $originalPrice = $this->getOriginalPriceForLogging($productId, $variantId);
            $extraRevenue = ($originalPrice - $flashPrice) * $normalQuantity;
            
            // Đặt flash_stock_sold = flash_stock_limit (chạm trần)
            $productSale->buy = $stockLimit;
            $productSale->save();
            
            // Trừ toàn bộ quantity vào physical stock
            $this->warehouseService->deductStock($stockVariantId, $quantity, 'flashsale_order');
            
            // Logging: Ghi lại đơn hàng mua vượt hạn mức Flash Sale
            Log::info('[FlashSale_MixedPrice] Order processed with mixed pricing', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'product_name' => $productName,
                'flash_quantity' => $flashQuantity,
                'normal_quantity' => $normalQuantity,
                'total_quantity' => $quantity,
                'flash_price' => $flashPrice,
                'normal_price' => $originalPrice,
                'extra_revenue' => $extraRevenue,
                'flash_sale_id' => $productSale->flashsale_id,
                'product_sale_id' => $productSale->id,
            ]);
            
            return [
                'success' => true,
                'message' => 'Xử lý đơn hàng Flash Sale thành công (mua vượt hạn mức)',
                'flash_sale_exhausted' => true,
                'flash_quantity' => $flashQuantity,
                'normal_quantity' => $normalQuantity,
                'warning' => "Chỉ còn {$flashQuantity} sản phẩm giá Flash Sale, {$normalQuantity} sản phẩm còn lại sẽ được tính theo giá thường",
            ];
        }
    }
    
    /**
     * Xử lý đơn hàng thường (Promotion, Deal Sốc, hoặc giá gốc)
     * 
     * @param int $productId
     * @param int|null $variantId
     * @param int $quantity
     * @return array
     */
    protected function processNormalOrder(int $productId, ?int $variantId, int $quantity): array
    {
        // Nếu có variant_id, dùng variant_id; nếu không, tìm default variant của product
        $stockVariantId = $variantId;
        if (!$stockVariantId) {
            $product = Product::find($productId);
            if ($product) {
                $defaultVariant = $product->variant($productId);
                $stockVariantId = $defaultVariant ? $defaultVariant->id : $productId;
            } else {
                $stockVariantId = $productId;
            }
        }
        
        // Tính tồn kho khả dụng (Physical Stock - Flash Sale Virtual Stock)
        $availableStock = $this->getAvailableStock($productId, $variantId);
        
        if ($availableStock < $quantity) {
            return [
                'success' => false,
                'message' => "Không đủ tồn kho. Tồn kho khả dụng: " . $availableStock,
            ];
        }
        
        // Giảm total_stock (Physical Stock) thông qua Warehouse Service
        $this->warehouseService->deductStock($stockVariantId, $quantity, 'normal_order');
        
        return [
            'success' => true,
            'message' => 'Xử lý đơn hàng thành công',
        ];
    }
    
    /**
     * Tính tồn kho khả dụng = Physical Stock - Flash Sale Virtual Stock
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return int
     */
    public function getAvailableStock(int $productId, ?int $variantId = null): int
    {
        // Nếu có variant_id, dùng variant_id; nếu không, tìm default variant của product
        $stockVariantId = $variantId;
        if (!$stockVariantId) {
            $product = Product::find($productId);
            if ($product) {
                $defaultVariant = $product->variant($productId);
                $stockVariantId = $defaultVariant ? $defaultVariant->id : $productId;
            } else {
                $stockVariantId = $productId;
            }
        }
        
        // Lấy Physical Stock từ Warehouse
        $physicalStock = $this->warehouseService->getVariantStock($stockVariantId);
        $sPhy = $physicalStock['current_stock'] ?? 0;
        
        // Lấy Flash Sale Virtual Stock (S_flash = number - buy)
        $sFlash = $this->getFlashSaleVirtualStock($productId, $variantId);
        
        // Tồn kho khả dụng = S_phy - S_flash
        return max(0, $sPhy - $sFlash);
    }
    
    /**
     * Lấy Flash Sale Virtual Stock (S_flash)
     * S_flash = number - buy (số lượng còn lại trong Flash Sale)
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return int
     */
    protected function getFlashSaleVirtualStock(int $productId, ?int $variantId = null): int
    {
        $productSale = $this->findActiveProductSale($productId, $variantId);
        
        if (!$productSale) {
            return 0; // Không có Flash Sale active
        }
        
        // S_flash = number (flash_stock_limit) - buy (flash_stock_sold)
        return max(0, $productSale->number - $productSale->buy);
    }
    
    /**
     * Tìm ProductSale đang active
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return ProductSale|null
     */
    protected function findActiveProductSale(int $productId, ?int $variantId = null): ?ProductSale
    {
        $now = time();
        
        $query = ProductSale::whereHas('flashsale', function($q) use ($now) {
            $q->where('status', '1')
              ->where('start', '<=', $now)
              ->where('end', '>=', $now);
        })
        ->where('product_id', $productId);
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }
        
        return $query->first();
    }
    
    /**
     * Kiểm tra khi tạo Flash Sale: total_stock phải >= flash_stock_limit
     * 
     * @param int $productId
     * @param int|null $variantId
     * @param int $flashStockLimit
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateFlashSaleStock(int $productId, ?int $variantId, int $flashStockLimit): array
    {
        // Nếu có variant_id, dùng variant_id; nếu không, tìm default variant của product
        $stockVariantId = $variantId;
        if (!$stockVariantId) {
            $product = Product::find($productId);
            if ($product) {
                $defaultVariant = $product->variant($productId);
                $stockVariantId = $defaultVariant ? $defaultVariant->id : $productId;
            } else {
                $stockVariantId = $productId;
            }
        }
        
        $physicalStock = $this->warehouseService->getVariantStock($stockVariantId);
        $sPhy = $physicalStock['current_stock'] ?? 0;
        
        if ($sPhy < $flashStockLimit) {
            return [
                'valid' => false,
                'message' => "Tồn kho thực tế ($sPhy) không đủ để tạo Flash Sale với số lượng ($flashStockLimit)",
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Tồn kho hợp lệ',
        ];
    }
    
    /**
     * Lấy giá gốc của sản phẩm để logging
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return float
     */
    protected function getOriginalPriceForLogging(int $productId, ?int $variantId = null): float
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
}
