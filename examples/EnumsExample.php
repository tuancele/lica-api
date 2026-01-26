<?php

namespace App\Enums;

/**
 * Product Status Enum.
 *
 * Replaces magic strings like '1' and '0' with type-safe constants
 */
enum ProductStatus: string
{
    case ACTIVE = '1';
    case INACTIVE = '0';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Hoạt động',
            self::INACTIVE => 'Không hoạt động',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::ACTIVE => 'badge-success',
            self::INACTIVE => 'badge-danger',
        };
    }

    /**
     * Get all statuses as array for select dropdown.
     */
    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::INACTIVE->value => self::INACTIVE->label(),
        ];
    }
}

/**
 * Product Type Enum.
 */
enum ProductType: string
{
    case PRODUCT = 'product';
    case TAXONOMY = 'taxonomy';
    case POST = 'post';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRODUCT => 'Sản phẩm',
            self::TAXONOMY => 'Danh mục',
            self::POST => 'Bài viết',
        };
    }
}

/**
 * Order Status Enum.
 */
enum OrderStatus: string
{
    case PENDING = '0';
    case CONFIRMED = '1';
    case CANCELLED = '2';
    case COMPLETED = '3';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ xử lý',
            self::CONFIRMED => 'Đã xác nhận',
            self::CANCELLED => 'Đã hủy',
            self::COMPLETED => 'Hoàn thành',
        };
    }

    /**
     * Get CSS class for status badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDING => 'badge-warning',
            self::CONFIRMED => 'badge-info',
            self::CANCELLED => 'badge-danger',
            self::COMPLETED => 'badge-success',
        };
    }
}

/**
 * Payment Status Enum.
 */
enum PaymentStatus: int
{
    case UNPAID = 0;
    case PAID = 1;

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Chưa thanh toán',
            self::PAID => 'Đã thanh toán',
        };
    }
}

/**
 * Shipping Status Enum.
 */
enum ShippingStatus: int
{
    case NOT_SHIPPED = 0;
    case SHIPPING = 1;
    case DELIVERED = 2;

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::NOT_SHIPPED => 'Chưa giao hàng',
            self::SHIPPING => 'Đang giao hàng',
            self::DELIVERED => 'Đã giao hàng',
        };
    }
}

/*
 * Usage Example:
 *
 * // Instead of:
 * $product->status = '1';
 * if ($product->status == '1') { ... }
 *
 * // Use:
 * $product->status = ProductStatus::ACTIVE->value;
 * if (ProductStatus::from($product->status) === ProductStatus::ACTIVE) { ... }
 *
 * // In queries:
 * Product::where('status', ProductStatus::ACTIVE->value)->get();
 *
 * // In views:
 * {{ ProductStatus::from($product->status)->label() }}
 * <span class="{{ ProductStatus::from($product->status)->badgeClass() }}">
 *     {{ ProductStatus::from($product->status)->label() }}
 * </span>
 */
