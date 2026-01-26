<?php

declare(strict_types=1);

namespace App\Services\Inventory;

interface InventoryServiceInterface
{
    /**
     * Xử lý đơn hàng và trừ tồn kho.
     */
    public function processOrder(array $orderItems): array;

    /**
     * Tính tồn kho khả dụng = Physical Stock - Flash Sale Virtual Stock.
     */
    public function getAvailableStock(int $productId, ?int $variantId = null): int;

    /**
     * Kiểm tra khi tạo Flash Sale: total_stock phải >= flash_stock_limit.
     */
    public function validateFlashSaleStock(int $productId, ?int $variantId, int $flashStockLimit): array;

    /**
     * Giữ hàng cho chương trình khuyến mãi (Flash Sale hoặc Deal).
     *
     * @param  string  $type  flash_sale|deal
     */
    public function allocateStockForPromotion(int $variantId, int $quantity, string $type): array;

    /**
     * Hoàn trả hàng từ kho khuyến mãi về kho khả dụng.
     *
     * @param  string  $type  flash_sale|deal
     */
    public function releaseStockFromPromotion(int $variantId, int $quantity, string $type): array;

    /**
     * Trừ kho khi đặt hàng.
     */
    public function deductStockForOrder(int $variantId, int $quantity, string $reason = 'order'): array;
}
