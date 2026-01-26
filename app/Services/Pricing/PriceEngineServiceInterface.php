<?php

declare(strict_types=1);

namespace App\Services\Pricing;

interface PriceEngineServiceInterface
{
    /**
     * Tính giá hiển thị cho sản phẩm.
     *
     * @param  int  $productId  Product ID
     * @param  int|null  $variantId  Variant ID (nếu có)
     * @return array Thông tin giá
     */
    public function calculateDisplayPrice(int $productId, ?int $variantId = null): array;

    /**
     * Tính giá với số lượng (hỗ trợ giá hỗn hợp khi mua vượt hạn mức Flash Sale).
     *
     * @param  int  $productId  Product ID
     * @param  int|null  $variantId  Variant ID (nếu có)
     * @param  int  $quantity  Số lượng mua
     * @return array Thông tin giá chi tiết với breakdown
     */
    public function calculatePriceWithQuantity(int $productId, ?int $variantId, int $quantity): array;
}
