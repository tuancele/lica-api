<?php

namespace App\Services\Promotion;

/**
 * Interface for Product Stock Validator
 * 
 * Defines the contract for product stock validation in promotion activities
 */
interface ProductStockValidatorInterface
{
    /**
     * Get product stock quantity
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null if product has no variants)
     * @return int Current stock quantity
     */
    public function getProductStock(int $productId, ?int $variantId = null): int;

    /**
     * Validate if product has stock > 0
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null if product has no variants)
     * @return bool True if stock > 0, false otherwise
     */
    public function validateProductStock(int $productId, ?int $variantId = null): bool;

    /**
     * Validate multiple products stock
     * Returns array of validation errors
     * 
     * @param array $products Array of products with ['product_id' => int, 'variant_id' => int|null]
     * @return array Validation errors in format ['products.0.stock' => ['Tồn kho phải lớn hơn 0']]
     */
    public function validateProductsStock(array $products): array;
}
