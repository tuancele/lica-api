<?php

namespace App\Services\Inventory;

interface InventoryServiceInterface
{
    /**
     * Xử lý đơn hàng và trừ tồn kho
     * 
     * @param array $orderItems
     * @return array
     */
    public function processOrder(array $orderItems): array;
    
    /**
     * Tính tồn kho khả dụng = Physical Stock - Flash Sale Virtual Stock
     * 
     * @param int $productId
     * @param int|null $variantId
     * @return int
     */
    public function getAvailableStock(int $productId, ?int $variantId = null): int;
    
    /**
     * Kiểm tra khi tạo Flash Sale: total_stock phải >= flash_stock_limit
     * 
     * @param int $productId
     * @param int|null $variantId
     * @param int $flashStockLimit
     * @return array
     */
    public function validateFlashSaleStock(int $productId, ?int $variantId, int $flashStockLimit): array;
}
