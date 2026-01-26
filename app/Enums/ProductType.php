<?php

declare(strict_types=1);
namespace App\Enums;

/**
 * Product Type Enum
 * 
 * Defines the different types of posts/products in the system
 */
enum ProductType: string
{
    case PRODUCT = 'product';
    case TAXONOMY = 'taxonomy';
    case POST = 'post';

    /**
     * Get human-readable label
     * 
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PRODUCT => 'Sản phẩm',
            self::TAXONOMY => 'Danh mục',
            self::POST => 'Bài viết',
        };
    }

    /**
     * Get all types as array for select dropdown
     * 
     * @return array
     */
    public static function toArray(): array
    {
        return [
            self::PRODUCT->value => self::PRODUCT->label(),
            self::TAXONOMY->value => self::TAXONOMY->label(),
            self::POST->value => self::POST->label(),
        ];
    }
}
