<?php

namespace App\Services\Inventory;

use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Warehouse\Models\ProductWarehouse;
use App\Modules\History\Models\History;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $sPhy = $physicalStock['physical_stock'] ?? 0; // Luôn dùng physical_stock gốc
        
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
     * Giữ hàng cho Flash Sale hoặc Deal Sốc.
     */
    public function allocateStockForPromotion(int $variantId, int $quantity, string $type): array
    {
        $type = strtolower($type);
        if (!in_array($type, ['flash_sale', 'deal'], true)) {
            return [
                'success' => false,
                'message' => 'Type không hợp lệ. Chỉ hỗ trợ flash_sale hoặc deal',
            ];
        }

        return DB::transaction(function () use ($variantId, $quantity, $type) {
            $record = ProductWarehouse::where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$record) {
                $record = new ProductWarehouse();
                $record->variant_id = $variantId;
                $record->physical_stock = 0;
                $record->flash_sale_stock = 0;
                $record->deal_stock = 0;
                $record->qty = 0;
            }

            $available = $record->available_stock;
            if ($available < $quantity) {
                return [
                    'success' => false,
                    'message' => "Không đủ tồn kho khả dụng. available_stock={$available}, cần {$quantity}",
                ];
            }

            if ($type === 'flash_sale') {
                $record->flash_sale_stock = ($record->flash_sale_stock ?? 0) + $quantity;
            } else {
                $record->deal_stock = ($record->deal_stock ?? 0) + $quantity;
            }

            $record->syncAvailableStock();
            $record->save();

            return [
                'success' => true,
                'message' => 'Giữ hàng thành công',
                'data' => [
                    'variant_id' => $variantId,
                    'available_stock' => $record->available_stock,
                    'physical_stock' => $record->physical_stock,
                    'flash_sale_stock' => $record->flash_sale_stock,
                    'deal_stock' => $record->deal_stock,
                ],
            ];
        });
    }

    /**
     * Hoàn trả hàng từ kho khuyến mãi về kho khả dụng.
     */
    /**
     * Hoàn trả hàng từ kho khuyến mãi về kho khả dụng hoặc kho vật lý.
     * 
     * Logic mới (Hoàn kho khi hủy đơn):
     * - Nếu Flash Sale vẫn đang diễn ra: Cộng lại vào Flash Sale stock (vì Available không đổi)
     * - Nếu Flash Sale đã kết thúc: Cộng lại vào Physical stock (giúp tăng Available)
     */
    /**
     * Hoàn trả hàng từ kho khuyến mãi về kho khả dụng hoặc kho vật lý.
     * 
     * Logic mới (Hoàn kho khi hủy đơn):
     * - Nếu Flash Sale vẫn đang diễn ra: Cộng lại vào Flash Sale stock (vì Available không đổi)
     * - Nếu Flash Sale đã kết thúc: Cộng lại vào Physical stock (giúp tăng Available)
     */
    public function releaseStockFromPromotion(int $variantId, int $quantity, string $type): array
    {
        $type = strtolower($type);
        if (!in_array($type, ['flash_sale', 'deal'], true)) {
            return [
                'success' => false,
                'message' => 'Type không hợp lệ. Chỉ hỗ trợ flash_sale hoặc deal',
            ];
        }

        return DB::transaction(function () use ($variantId, $quantity, $type) {
            $record = ProductWarehouse::where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$record) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy bản ghi tồn kho cho variant',
                ];
            }

            // Kiểm tra trạng thái Flash Sale/Deal hiện tại
            $isPromotionActive = false;
            if ($type === 'flash_sale') {
                $now = time();
                $isPromotionActive = ProductSale::whereHas('flashsale', function($q) use ($now) {
                    $q->where('status', '1')->where('start', '<=', $now)->where('end', '>=', $now);
                })->where('variant_id', $variantId)->exists();
            } else {
                // Tương tự cho Deal Sốc nếu cần
                $isPromotionActive = true; 
            }

            if ($isPromotionActive) {
                // Nếu đang trong thời gian diễn ra: Cộng lại vào reserved stock (Flash Sale/Deal)
                if ($type === 'flash_sale') {
                    $record->flash_sale_stock = ($record->flash_sale_stock ?? 0) + $quantity;
                } else {
                    $record->deal_stock = ($record->deal_stock ?? 0) + $quantity;
                }
            } else {
                // Nếu đã kết thúc: Cộng trực tiếp vào Physical stock (Available sẽ tăng theo)
                $record->physical_stock = ($record->physical_stock ?? 0) + $quantity;
            }

            $record->syncAvailableStock();
            $record->save();

            return [
                'success' => true,
                'message' => 'Hoàn trả hàng thành công',
                'data' => [
                    'variant_id' => $variantId,
                    'available_stock' => $record->available_stock,
                    'physical_stock' => $record->physical_stock,
                    'flash_sale_stock' => $record->flash_sale_stock,
                    'deal_stock' => $record->deal_stock,
                ],
            ];
        });
    }

    /**
     * Trừ kho khi đặt hàng.
     * Logic: 
     * 1/ Nếu có flashsale: trừ vào Flash Sale stock (Available không đổi vì đã trừ khi tạo sale)
     * 2/ Nếu không có flashsale: trừ vào physical_stock (Available giảm theo)
     */
    public function deductStockForOrder(int $variantId, int $quantity, string $reason = 'order'): array
    {
        if ($quantity <= 0) return ['success' => false, 'message' => 'Số lượng phải > 0'];

        return DB::transaction(function () use ($variantId, $quantity, $reason) {
            $record = ProductWarehouse::where('variant_id', $variantId)->lockForUpdate()->first();
            if (!$record) {
                // Khởi tạo nếu chưa có bản ghi (cho legacy data)
                $record = new ProductWarehouse();
                $record->variant_id = $variantId;
                $record->physical_stock = 0;
                $record->flash_sale_stock = 0;
                $record->deal_stock = 0;
            }

            // Kiểm tra xem variant này có đang trong Flash Sale active không
            $now = time();
            $isFlashSaleActive = ProductSale::whereHas('flashsale', function($q) use ($now) {
                $q->where('status', '1')->where('start', '<=', $now)->where('end', '>=', $now);
            })->where('variant_id', $variantId)->exists();

            if ($isFlashSaleActive) {
                // 1. Nếu có Flash Sale: Trừ vào Flash Sale stock
                // Lưu ý: Available = physical - flash - deal. Khi flash giảm, Available sẽ tăng. 
                // Để Available KHÔNG ĐỔI, ta phải trừ đồng thời cả Physical.
                $record->flash_sale_stock = max(0, ($record->flash_sale_stock ?? 0) - $quantity);
                $record->physical_stock = max(0, ($record->physical_stock ?? 0) - $quantity);
            } else {
                // 2. Nếu không có Flash Sale: Trừ vào physical_stock (Available sẽ giảm theo)
                $record->physical_stock = max(0, ($record->physical_stock ?? 0) - $quantity);
            }

            $record->syncAvailableStock();
            $record->save();

            $this->recordHistory($isFlashSaleActive ? 'deduct_flash' : 'deduct_physical', $variantId, $quantity, $reason, $record);

            return [
                'success' => true,
                'data' => [
                    'variant_id' => $variantId,
                    'available_stock' => $record->available_stock,
                    'physical_stock' => $record->physical_stock,
                    'flash_sale_stock' => $record->flash_sale_stock,
                ]
            ];
        });
    }

    /**
     * Nhập kho thủ công: tăng physical_stock và sync available_stock.
     */
    public function importStock(int $variantId, int $quantity, string $reason = 'manual_import'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Số lượng nhập phải > 0'];
        }

        try {
            $result = DB::transaction(function () use ($variantId, $quantity, $reason) {
                // Lấy bản ghi tồn kho MỚI NHẤT của variant
                $record = ProductWarehouse::where('variant_id', $variantId)
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
                
                if (!$record) {
                    $record = new ProductWarehouse();
                    $record->variant_id = $variantId;
                    $record->physical_stock = 0;
                    $record->flash_sale_stock = 0;
                    $record->deal_stock = 0;
                    $record->qty = 0;
                }

                $record->physical_stock = ($record->physical_stock ?? 0) + $quantity;
                $record->syncAvailableStock();
                $record->save();

                $this->recordHistory('import', $variantId, $quantity, $reason, $record);

                Log::info('[Inventory] Import stock', [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'available_stock' => $record->available_stock,
                    'physical_stock' => $record->physical_stock,
                    'flash_sale_stock' => $record->flash_sale_stock,
                    'deal_stock' => $record->deal_stock,
                ]);

                return [
                    'success' => true,
                    'message' => 'Nhập kho thành công',
                    'data' => [
                        'variant_id' => $variantId,
                        'available_stock' => $record->available_stock,
                        'physical_stock' => $record->physical_stock,
                        'flash_sale_stock' => $record->flash_sale_stock,
                        'deal_stock' => $record->deal_stock,
                    ],
                ];
            });

            return $result;
        } catch (\Exception $e) {
            Log::error('Import stock failed: ' . $e->getMessage(), [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'reason' => $reason,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => 'Import stock thất bại: ' . $e->getMessage()];
        }
    }

    /**
     * Xuất kho thủ công: giảm physical_stock; ưu tiên available_stock, thiếu sẽ ném lỗi.
     */
    public function manualExportStock(int $variantId, int $quantity, string $reason = 'manual_export'): array
    {
        if ($quantity <= 0) {
            return ['success' => false, 'message' => 'Số lượng xuất phải > 0'];
        }

        try {
            $result = DB::transaction(function () use ($variantId, $quantity, $reason) {
                $record = ProductWarehouse::where('variant_id', $variantId)->lockForUpdate()->first();
                if (!$record) {
                    throw new \RuntimeException('Không tìm thấy bản ghi tồn kho');
                }

                $available = $record->available_stock;
                if ($available < $quantity) {
                    throw new \RuntimeException("Không đủ tồn khả dụng để xuất. available_stock={$available}, cần {$quantity}");
                }

                $record->physical_stock = max(0, ($record->physical_stock ?? 0) - $quantity);
                $record->syncAvailableStock();
                $record->save();

                $this->recordHistory('manual_export', $variantId, $quantity, $reason, $record);

                Log::info('[Inventory] Manual export', [
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'available_stock' => $record->available_stock,
                    'physical_stock' => $record->physical_stock,
                    'flash_sale_stock' => $record->flash_sale_stock,
                    'deal_stock' => $record->deal_stock,
                ]);

                return [
                    'success' => true,
                    'message' => 'Xuất kho thành công',
                    'data' => [
                        'variant_id' => $variantId,
                        'available_stock' => $record->available_stock,
                        'physical_stock' => $record->physical_stock,
                        'flash_sale_stock' => $record->flash_sale_stock,
                        'deal_stock' => $record->deal_stock,
                    ],
                ];
            });

            return $result;
        } catch (\RuntimeException $e) {
            Log::warning('Manual export blocked: ' . $e->getMessage(), [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Manual export failed: ' . $e->getMessage(), [
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'reason' => $reason,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => 'Manual export thất bại: ' . $e->getMessage()];
        }
    }

    /**
     * Ghi lịch sử thao tác vào bảng history (best effort).
     */
    protected function recordHistory(string $action, int $variantId, int $quantity, string $reason, ProductWarehouse $record): void
    {
        try {
            History::insert([
                'user_id' => Auth::id(),
                'content' => "[{$action}] variant_id={$variantId}, qty={$quantity}, reason={$reason}, physical={$record->physical_stock}, flash={$record->flash_sale_stock}, deal={$record->deal_stock}, available={$record->available_stock}",
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Record history failed: ' . $e->getMessage(), [
                'action' => $action,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        }
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
