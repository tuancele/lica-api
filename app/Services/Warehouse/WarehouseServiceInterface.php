<?php

declare(strict_types=1);

namespace App\Services\Warehouse;

use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Warehouse Service.
 *
 * Defines the contract for warehouse business logic operations
 */
interface WarehouseServiceInterface
{
    /**
     * Get inventory list with filters.
     */
    public function getInventory(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get inventory detail for a variant.
     */
    public function getVariantInventory(int $variantId): array;

    /**
     * Get import receipts list with filters.
     */
    public function getImportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get import receipt detail with items.
     */
    public function getImportReceipt(int $id): Warehouse;

    /**
     * Create a new import receipt.
     */
    public function createImportReceipt(array $data): Warehouse;

    /**
     * Update an existing import receipt.
     */
    public function updateImportReceipt(int $id, array $data): Warehouse;

    /**
     * Delete an import receipt.
     */
    public function deleteImportReceipt(int $id): bool;

    /**
     * Get export receipts list with filters.
     */
    public function getExportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get export receipt detail with items.
     */
    public function getExportReceipt(int $id): Warehouse;

    /**
     * Create a new export receipt.
     */
    public function createExportReceipt(array $data): Warehouse;

    /**
     * Update an existing export receipt.
     */
    public function updateExportReceipt(int $id, array $data): Warehouse;

    /**
     * Delete an export receipt.
     */
    public function deleteExportReceipt(int $id): bool;

    /**
     * Search products by keyword.
     */
    public function searchProducts(string $keyword, int $limit = 50): array;

    /**
     * Get variants for a product.
     */
    public function getProductVariants(int $productId): array;

    /**
     * Get stock information for a variant.
     */
    public function getVariantStock(int $variantId): array;

    /**
     * Get suggested price for a variant.
     */
    public function getVariantPrice(int $variantId, string $type = 'export'): array;

    /**
     * Get quantity statistics.
     */
    public function getQuantityStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get revenue statistics.
     */
    public function getRevenueStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get warehouse summary statistics.
     */
    public function getSummaryStatistics(array $filters = []): array;

    /**
     * Deduct stock for a variant (create export receipt automatically).
     *
     * @param  int  $variantId  Variant ID (or Product ID if no variant)
     * @param  int  $quantity  Quantity to deduct
     * @param  string  $reason  Reason for deduction (e.g., 'flashsale_order', 'normal_order')
     */
    public function deductStock(int $variantId, int $quantity, string $reason = 'order'): bool;

    /**
     * Process stock deduction for an order
     * Centralized stock management for Warehouse V2.
     *
     * @param  int  $orderId  Order ID
     * @return bool Success status
     *
     * @throws \Exception
     */
    public function processOrderStock(int $orderId): bool;

    /**
     * Rollback stock for a cancelled order
     * Reverse stock deduction when order is cancelled.
     *
     * @param  int  $orderId  Order ID
     * @return bool Success status
     *
     * @throws \Exception
     */
    public function rollbackOrderStock(int $orderId): bool;

    /**
     * Legacy helper: get total imported/exported quantity for a product
     * from ProductWarehouse table (used by Deal views).
     *
     * @param  int  $productId
     * @param  string  $type  'import' or 'export'
     */
    public function getLegacyProductWarehouseQuantity(int $productId, string $type): int;
}
