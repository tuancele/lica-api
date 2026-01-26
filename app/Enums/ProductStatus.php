<?php

declare(strict_types=1);

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
