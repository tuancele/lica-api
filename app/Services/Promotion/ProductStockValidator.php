<?php

declare(strict_types=1);
namespace App\Services\Promotion;

use App\Modules\Product\Models\Product;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * Service class for validating product stock in promotion activities
 * 
 * This service handles stock validation to ensure products with zero stock
 * cannot participate in any promotion activities (FlashSale, Deal, MarketingCampaign)
 */
class ProductStockValidator implements ProductStockValidatorInterface
{
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(WarehouseServiceInterface $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Get product stock quantity
     * 
     * For products with variants: Get stock from warehouse system
     * For products without variants: Get stock from product.stock field or default variant
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null if product has no variants)
     * @return int Current stock quantity
     */
    public function getProductStock(int $productId, ?int $variantId = null): int
    {
        try {
            // If variant_id is provided, get stock from warehouse system
            if ($variantId) {
                $stockInfo = $this->warehouseService->getVariantStock($variantId);
                return (int) ($stockInfo['current_stock'] ?? 0);
            }

            // No variant_id: Check product
            $product = Product::find($productId);
            if (!$product) {
                Log::warning("Product not found for stock check", [
                    'product_id' => $productId
                ]);
                return 0;
            }

            // If product has variants, get stock from default variant via warehouse
            if ($product->has_variants == 1) {
                $defaultVariant = $product->variant($productId);
                if ($defaultVariant) {
                    $stockInfo = $this->warehouseService->getVariantStock($defaultVariant->id);
                    return (int) ($stockInfo['current_stock'] ?? 0);
                }
            }

            // Otherwise, use product's stock field
            return (int) ($product->stock ?? 0);

        } catch (\Exception $e) {
            Log::error("Error getting product stock", [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'error' => $e->getMessage()
            ]);
            return 0; // Return 0 on error to be safe
        }
    }

    /**
     * Validate if product has stock > 0
     * 
     * @param int $productId Product ID
     * @param int|null $variantId Variant ID (null if product has no variants)
     * @return bool True if stock > 0, false otherwise
     */
    public function validateProductStock(int $productId, ?int $variantId = null): bool
    {
        $stock = $this->getProductStock($productId, $variantId);
        return $stock > 0;
    }

    /**
     * Validate multiple products stock
     * Returns array of validation errors
     * 
     * @param array $products Array of products with ['product_id' => int, 'variant_id' => int|null]
     * @return array Validation errors in format ['products.0.stock' => ['Tồn kho phải lớn hơn 0']]
     */
    public function validateProductsStock(array $products): array
    {
        $errors = [];

        foreach ($products as $index => $productData) {
            $productId = $productData['product_id'] ?? null;
            $variantId = $productData['variant_id'] ?? null;

            if (!$productId) {
                continue; // Skip invalid entries
            }

            $stock = $this->getProductStock($productId, $variantId);
            
            if ($stock <= 0) {
                $errors["products.{$index}.stock"] = ["Tồn kho phải lớn hơn 0"];
            }
        }

        return $errors;
    }
}
