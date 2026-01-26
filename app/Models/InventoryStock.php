<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends Model
{
    protected $table = 'inventory_stocks';

    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'physical_stock',
        'reserved_stock',
        'flash_sale_hold',
        'deal_hold',
        'low_stock_threshold',
        'reorder_point',
        'average_cost',
        'last_cost',
        'location_code',
        'last_stock_check',
        'last_movement_at',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'variant_id' => 'integer',
        'physical_stock' => 'integer',
        'reserved_stock' => 'integer',
        'available_stock' => 'integer',
        'flash_sale_hold' => 'integer',
        'deal_hold' => 'integer',
        'low_stock_threshold' => 'integer',
        'reorder_point' => 'integer',
        'average_cost' => 'decimal:2',
        'last_cost' => 'decimal:2',
        'last_stock_check' => 'datetime',
        'last_movement_at' => 'datetime',
    ];

    protected $appends = [
        'is_low_stock',
        'is_out_of_stock',
        'sellable_stock',
    ];

    /**
     * Boot the model
     * Ensure flash_sale_hold and deal_hold never go negative
     * Note: available_stock is a generated column, calculated as: GREATEST(0, physical_stock - reserved_stock)
     */
    protected static function boot()
    {
        parent::boot();

        // Before saving, ensure flash_sale_hold and deal_hold are never negative
        static::saving(function ($inventoryStock) {
            // Ensure flash_sale_hold is never negative
            if ($inventoryStock->flash_sale_hold < 0) {
                $inventoryStock->flash_sale_hold = 0;
            }

            // Ensure deal_hold is never negative
            if ($inventoryStock->deal_hold < 0) {
                $inventoryStock->deal_hold = 0;
            }

            // Ensure reserved_stock is never negative
            if ($inventoryStock->reserved_stock < 0) {
                $inventoryStock->reserved_stock = 0;
            }

            // Ensure physical_stock is never negative (unless warehouse allows it)
            if ($inventoryStock->physical_stock < 0 && !($inventoryStock->warehouse && $inventoryStock->warehouse->allow_negative_stock)) {
                $inventoryStock->physical_stock = 0;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseV2::class, 'warehouse_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Variant::class, 'variant_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id', 'variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'variant_id', 'variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    public function activeReservations(): HasMany
    {
        return $this->reservations()->where('status', 'active');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'variant_id', 'variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Check if stock is low (at or below threshold)
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->physical_stock > 0 
            && $this->physical_stock <= $this->low_stock_threshold;
    }

    /**
     * Check if out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->available_stock <= 0;
    }

    /**
     * Get sellable stock (available - promotional holds)
     */
    public function getSellableStockAttribute(): int
    {
        $sellable = $this->available_stock - $this->flash_sale_hold - $this->deal_hold;
        return max(0, $sellable);
    }

    /**
     * Get total stock value
     */
    public function getStockValueAttribute(): float
    {
        return $this->physical_stock * $this->average_cost;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeLowStock($query)
    {
        return $query->whereRaw('physical_stock <= low_stock_threshold')
            ->where('physical_stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_stock', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('available_stock', '>', 0);
    }

    public function scopeAtReorderPoint($query)
    {
        return $query->whereRaw('physical_stock <= reorder_point');
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Increase physical stock
     */
    public function increaseStock(int $quantity): bool
    {
        return $this->increment('physical_stock', $quantity);
    }

    /**
     * Decrease physical stock
     */
    public function decreaseStock(int $quantity): bool
    {
        if ($this->physical_stock < $quantity && !$this->warehouse->allow_negative_stock) {
            return false;
        }
        
        return $this->decrement('physical_stock', $quantity);
    }

    /**
     * Increase reserved stock
     */
    public function increaseReserved(int $quantity): bool
    {
        return $this->increment('reserved_stock', $quantity);
    }

    /**
     * Decrease reserved stock
     */
    public function decreaseReserved(int $quantity): bool
    {
        $newReserved = max(0, $this->reserved_stock - $quantity);
        return $this->update(['reserved_stock' => $newReserved]);
    }

    /**
     * Update average cost using weighted average method
     */
    public function updateAverageCost(int $newQuantity, float $newCost): void
    {
        $currentValue = $this->physical_stock * $this->average_cost;
        $newValue = $newQuantity * $newCost;
        $totalQuantity = $this->physical_stock + $newQuantity;
        
        if ($totalQuantity > 0) {
            $this->average_cost = ($currentValue + $newValue) / $totalQuantity;
        }
        
        $this->last_cost = $newCost;
        $this->save();
    }

    /**
     * Check if quantity is available
     */
    public function hasAvailable(int $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Check if quantity can be reserved
     */
    public function canReserve(int $quantity): bool
    {
        return $this->sellable_stock >= $quantity;
    }

    /**
     * Get or create stock record for warehouse + variant
     */
    public static function getOrCreate(int $warehouseId, int $variantId): self
    {
        return static::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'variant_id' => $variantId,
            ],
            [
                'physical_stock' => 0,
                'reserved_stock' => 0,
                'low_stock_threshold' => config('inventory.thresholds.low_stock', 10),
                'reorder_point' => config('inventory.thresholds.reorder_point', 20),
            ]
        );
    }
}
