<?php

namespace App\Enums;

/**
 * Product Status Enum
 * 
 * Replaces magic strings like '1' and '0' with type-safe constants
 */
enum ProductStatus: string
{
    case ACTIVE = '1';
    case INACTIVE = '0';

    /**
     * Get human-readable label
     * 
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Hoạt động',
            self::INACTIVE => 'Không hoạt động',
        };
    }

    /**
     * Get CSS class for status badge
     * 
     * @return string
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'badge-success',
            self::INACTIVE => 'badge-danger',
        };
    }

    /**
     * Get all statuses as array for select dropdown
     * 
     * @return array
     */
    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->label(),
            self::INACTIVE->value => self::INACTIVE->label(),
        ];
    }
}
