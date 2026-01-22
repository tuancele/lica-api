<?php

namespace App\Services\Warehouse;

use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface for Warehouse Service
 * 
 * Defines the contract for warehouse business logic operations
 */
interface WarehouseServiceInterface
{
    /**
     * Get inventory list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInventory(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get inventory detail for a variant
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantInventory(int $variantId): array;

    /**
     * Get import receipts list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getImportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get import receipt detail with items
     * 
     * @param int $id
     * @return Warehouse
     */
    public function getImportReceipt(int $id): Warehouse;

    /**
     * Create a new import receipt
     * 
     * @param array $data
     * @return Warehouse
     */
    public function createImportReceipt(array $data): Warehouse;

    /**
     * Update an existing import receipt
     * 
     * @param int $id
     * @param array $data
     * @return Warehouse
     */
    public function updateImportReceipt(int $id, array $data): Warehouse;

    /**
     * Delete an import receipt
     * 
     * @param int $id
     * @return bool
     */
    public function deleteImportReceipt(int $id): bool;

    /**
     * Get export receipts list with filters
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getExportReceipts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get export receipt detail with items
     * 
     * @param int $id
     * @return Warehouse
     */
    public function getExportReceipt(int $id): Warehouse;

    /**
     * Create a new export receipt
     * 
     * @param array $data
     * @return Warehouse
     */
    public function createExportReceipt(array $data): Warehouse;

    /**
     * Update an existing export receipt
     * 
     * @param int $id
     * @param array $data
     * @return Warehouse
     */
    public function updateExportReceipt(int $id, array $data): Warehouse;

    /**
     * Delete an export receipt
     * 
     * @param int $id
     * @return bool
     */
    public function deleteExportReceipt(int $id): bool;

    /**
     * Search products by keyword
     * 
     * @param string $keyword
     * @param int $limit
     * @return array
     */
    public function searchProducts(string $keyword, int $limit = 50): array;

    /**
     * Get variants for a product
     * 
     * @param int $productId
     * @return array
     */
    public function getProductVariants(int $productId): array;

    /**
     * Get stock information for a variant
     * 
     * @param int $variantId
     * @return array
     */
    public function getVariantStock(int $variantId): array;

    /**
     * Get suggested price for a variant
     * 
     * @param int $variantId
     * @param string $type
     * @return array
     */
    public function getVariantPrice(int $variantId, string $type = 'export'): array;

    /**
     * Get quantity statistics
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getQuantityStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get revenue statistics
     * 
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getRevenueStatistics(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get warehouse summary statistics
     * 
     * @param array $filters
     * @return array
     */
    public function getSummaryStatistics(array $filters = []): array;

    /**
     * Deduct stock for a variant (create export receipt automatically)
     * 
     * @param int $variantId Variant ID (or Product ID if no variant)
     * @param int $quantity Quantity to deduct
     * @param string $reason Reason for deduction (e.g., 'flashsale_order', 'normal_order')
     * @return bool
     */
    public function deductStock(int $variantId, int $quantity, string $reason = 'order'): bool;

    /**
     * Process stock deduction for an order
     * Centralized stock management for Warehouse V2
     * 
     * @param int $orderId Order ID
     * @return bool Success status
     * @throws \Exception
     */
    public function processOrderStock(int $orderId): bool;

    /**
     * Rollback stock for a cancelled order
     * Reverse stock deduction when order is cancelled
     * 
     * @param int $orderId Order ID
     * @return bool Success status
     * @throws \Exception
     */
    public function rollbackOrderStock(int $orderId): bool;
}
