<?php

namespace App\Services\Inventory\Contracts;

use App\Models\StockReceipt;
use App\Models\StockReservation;
use App\Services\Inventory\DTOs\StockDTO;
use App\Services\Inventory\DTOs\ImportStockDTO;
use App\Services\Inventory\DTOs\ExportStockDTO;
use App\Services\Inventory\DTOs\TransferStockDTO;
use App\Services\Inventory\DTOs\AdjustStockDTO;
use App\Services\Inventory\DTOs\ReserveStockDTO;
use Illuminate\Support\Collection;

interface InventoryServiceInterface
{
    /*
    |--------------------------------------------------------------------------
    | Stock Queries
    |--------------------------------------------------------------------------
    */

    /**
     * Get stock info for a variant
     *
     * @param int $variantId
     * @param int|null $warehouseId
     * @return StockDTO
     */
    public function getStock(int $variantId, ?int $warehouseId = null): StockDTO;

    /**
     * Get stock for multiple variants (batch query)
     *
     * @param array $variantIds
     * @param int|null $warehouseId
     * @return Collection<StockDTO>
     */
    public function getStockBatch(array $variantIds, ?int $warehouseId = null): Collection;

    /**
     * Check if quantity is available for sale
     *
     * @param int $variantId
     * @param int $quantity
     * @param int|null $warehouseId
     * @return bool
     */
    public function isAvailable(int $variantId, int $quantity, ?int $warehouseId = null): bool;

    /**
     * Check availability for multiple items
     *
     * @param array $items [{variant_id, quantity}]
     * @param int|null $warehouseId
     * @return array [{variant_id, quantity, available, is_available}]
     */
    public function checkAvailabilityBatch(array $items, ?int $warehouseId = null): array;

    /**
     * Get low stock items
     *
     * @param int|null $warehouseId
     * @return Collection
     */
    public function getLowStockItems(?int $warehouseId = null): Collection;

    /**
     * Get out of stock items
     *
     * @param int|null $warehouseId
     * @return Collection
     */
    public function getOutOfStockItems(?int $warehouseId = null): Collection;

    /*
    |--------------------------------------------------------------------------
    | Stock Mutations
    |--------------------------------------------------------------------------
    */

    /**
     * Import stock (nhập kho)
     *
     * @param ImportStockDTO $data
     * @return StockReceipt
     */
    public function import(ImportStockDTO $data): StockReceipt;

    /**
     * Export stock (xuất kho)
     *
     * @param ExportStockDTO $data
     * @return StockReceipt
     */
    public function export(ExportStockDTO $data): StockReceipt;

    /**
     * Transfer stock between warehouses
     *
     * @param TransferStockDTO $data
     * @return StockReceipt
     */
    public function transfer(TransferStockDTO $data): StockReceipt;

    /**
     * Adjust stock (kiểm kê, điều chỉnh)
     *
     * @param AdjustStockDTO $data
     * @return StockReceipt
     */
    public function adjust(AdjustStockDTO $data): StockReceipt;

    /*
    |--------------------------------------------------------------------------
    | Reservation System
    |--------------------------------------------------------------------------
    */

    /**
     * Reserve stock for order/cart
     *
     * @param ReserveStockDTO $data
     * @return StockReservation
     */
    public function reserve(ReserveStockDTO $data): StockReservation;

    /**
     * Reserve stock for multiple items at once
     *
     * @param array $items Array of ReserveStockDTO or arrays
     * @return Collection<StockReservation>
     */
    public function reserveBatch(array $items): Collection;

    /**
     * Confirm reservation (when order is paid - deduct from physical stock)
     *
     * @param int $reservationId
     * @return bool
     */
    public function confirmReservation(int $reservationId): bool;

    /**
     * Release reservation (when order cancelled - return to available)
     *
     * @param int $reservationId
     * @param int|null $userId
     * @param string|null $reason
     * @return bool
     */
    public function releaseReservation(int $reservationId, ?int $userId = null, ?string $reason = null): bool;

    /**
     * Release all reservations for a reference
     *
     * @param string $referenceType
     * @param int $referenceId
     * @return int Number of released reservations
     */
    public function releaseReservationsByReference(string $referenceType, int $referenceId): int;

    /**
     * Release expired reservations (for cron job)
     *
     * @return int Number of released reservations
     */
    public function releaseExpiredReservations(): int;

    /*
    |--------------------------------------------------------------------------
    | Order Integration
    |--------------------------------------------------------------------------
    */

    /**
     * Deduct stock when order completed/shipped
     *
     * @param int $orderId
     * @return bool
     */
    public function deductForOrder(int $orderId): bool;

    /**
     * Restore stock when order cancelled
     *
     * @param int $orderId
     * @return bool
     */
    public function restoreForOrder(int $orderId): bool;

    /**
     * Process return - add stock back
     *
     * @param int $orderId
     * @param array $items [{variant_id, quantity}]
     * @return StockReceipt
     */
    public function processReturn(int $orderId, array $items): StockReceipt;

    /*
    |--------------------------------------------------------------------------
    | Reports & History
    |--------------------------------------------------------------------------
    */

    /**
     * Get stock movement history for a variant
     *
     * @param int $variantId
     * @param array $filters
     * @return Collection
     */
    public function getMovementHistory(int $variantId, array $filters = []): Collection;

    /**
     * Get inventory valuation report
     *
     * @param int|null $warehouseId
     * @return array
     */
    public function getInventoryValuation(?int $warehouseId = null): array;

    /**
     * Get stock summary (total in, total out, current) for date range
     *
     * @param int $variantId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $warehouseId
     * @return array
     */
    public function getStockSummary(int $variantId, string $startDate, string $endDate, ?int $warehouseId = null): array;
}
