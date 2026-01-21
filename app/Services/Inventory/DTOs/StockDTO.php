<?php

namespace App\Services\Inventory\DTOs;

use App\Models\InventoryStock;

class StockDTO
{
    public function __construct(
        public int $variantId,
        public int $warehouseId,
        public int $physicalStock = 0,
        public int $reservedStock = 0,
        public int $availableStock = 0,
        public int $flashSaleHold = 0,
        public int $dealHold = 0,
        public int $lowStockThreshold = 10,
        public int $reorderPoint = 20,
        public float $averageCost = 0,
        public float $lastCost = 0,
        public ?string $locationCode = null,
        public bool $isLowStock = false,
        public bool $isOutOfStock = false,
        public int $sellableStock = 0,
    ) {}

    /**
     * Create from InventoryStock model
     */
    public static function fromModel(InventoryStock $model): self
    {
        $sellable = max(0, $model->available_stock - $model->flash_sale_hold - $model->deal_hold);
        
        return new self(
            variantId: $model->variant_id,
            warehouseId: $model->warehouse_id,
            physicalStock: $model->physical_stock,
            reservedStock: $model->reserved_stock,
            availableStock: $model->available_stock,
            flashSaleHold: $model->flash_sale_hold,
            dealHold: $model->deal_hold,
            lowStockThreshold: $model->low_stock_threshold,
            reorderPoint: $model->reorder_point,
            averageCost: (float) $model->average_cost,
            lastCost: (float) $model->last_cost,
            locationCode: $model->location_code,
            isLowStock: $model->physical_stock > 0 && $model->physical_stock <= $model->low_stock_threshold,
            isOutOfStock: $model->available_stock <= 0,
            sellableStock: $sellable,
        );
    }

    /**
     * Create empty stock DTO (for non-existent stock records)
     */
    public static function empty(int $variantId, int $warehouseId): self
    {
        return new self(
            variantId: $variantId,
            warehouseId: $warehouseId,
            physicalStock: 0,
            reservedStock: 0,
            availableStock: 0,
            flashSaleHold: 0,
            dealHold: 0,
            lowStockThreshold: config('inventory.thresholds.low_stock', 10),
            reorderPoint: config('inventory.thresholds.reorder_point', 20),
            averageCost: 0,
            lastCost: 0,
            locationCode: null,
            isLowStock: false,
            isOutOfStock: true,
            sellableStock: 0,
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'warehouse_id' => $this->warehouseId,
            'physical_stock' => $this->physicalStock,
            'reserved_stock' => $this->reservedStock,
            'available_stock' => $this->availableStock,
            'flash_sale_hold' => $this->flashSaleHold,
            'deal_hold' => $this->dealHold,
            'low_stock_threshold' => $this->lowStockThreshold,
            'reorder_point' => $this->reorderPoint,
            'average_cost' => $this->averageCost,
            'last_cost' => $this->lastCost,
            'location_code' => $this->locationCode,
            'is_low_stock' => $this->isLowStock,
            'is_out_of_stock' => $this->isOutOfStock,
            'sellable_stock' => $this->sellableStock,
        ];
    }
}
