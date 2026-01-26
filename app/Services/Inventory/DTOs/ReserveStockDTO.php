<?php

declare(strict_types=1);

namespace App\Services\Inventory\DTOs;

use Carbon\Carbon;

class ReserveStockDTO
{
    /**
     * @param  int  $variantId  ID variant cần giữ
     * @param  int  $quantity  Số lượng giữ
     * @param  string  $referenceType  Loại tham chiếu (order, cart, flash_sale)
     * @param  int  $referenceId  ID của order/cart
     * @param  int|null  $warehouseId  ID kho (null = default warehouse)
     * @param  Carbon|null  $expiresAt  Thời điểm hết hạn
     * @param  string|null  $referenceCode  Mã tham chiếu
     * @param  array|null  $metadata  Dữ liệu bổ sung
     */
    public function __construct(
        public int $variantId,
        public int $quantity,
        public string $referenceType,
        public int $referenceId,
        public ?int $warehouseId = null,
        public ?Carbon $expiresAt = null,
        public ?string $referenceCode = null,
        public ?array $metadata = null,
    ) {
        $this->validateQuantity();
        $this->setDefaultExpiry();
    }

    /**
     * Validate quantity.
     */
    private function validateQuantity(): void
    {
        if ($this->quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than 0');
        }
    }

    /**
     * Set default expiry based on reference type.
     */
    private function setDefaultExpiry(): void
    {
        if ($this->expiresAt === null) {
            $this->expiresAt = match ($this->referenceType) {
                'cart' => Carbon::now()->addMinutes(config('inventory.reservations.cart_minutes', 30)),
                'order' => Carbon::now()->addHours(config('inventory.reservations.order_hours', 24)),
                'flash_sale' => Carbon::now()->addMinutes(config('inventory.reservations.flash_sale_minutes', 15)),
                default => Carbon::now()->addHours(1),
            };
        }
    }

    /**
     * Create from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            variantId: $data['variant_id'],
            quantity: $data['quantity'],
            referenceType: $data['reference_type'],
            referenceId: $data['reference_id'],
            warehouseId: $data['warehouse_id'] ?? null,
            expiresAt: isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            referenceCode: $data['reference_code'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * Create for order.
     */
    public static function forOrder(int $orderId, int $variantId, int $quantity, ?string $orderCode = null): self
    {
        return new self(
            variantId: $variantId,
            quantity: $quantity,
            referenceType: 'order',
            referenceId: $orderId,
            referenceCode: $orderCode,
        );
    }

    /**
     * Create for cart.
     */
    public static function forCart(int $cartId, int $variantId, int $quantity): self
    {
        return new self(
            variantId: $variantId,
            quantity: $quantity,
            referenceType: 'cart',
            referenceId: $cartId,
        );
    }

    /**
     * Create for flash sale.
     */
    public static function forFlashSale(int $flashSaleId, int $variantId, int $quantity): self
    {
        return new self(
            variantId: $variantId,
            quantity: $quantity,
            referenceType: 'flash_sale',
            referenceId: $flashSaleId,
        );
    }
}
