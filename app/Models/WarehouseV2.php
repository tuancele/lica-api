<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseV2 extends Model
{
    use SoftDeletes;

    protected $table = 'warehouses_v2';

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'manager_name',
        'is_default',
        'is_active',
        'allow_negative_stock',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'warehouse_id');
    }

    public function incomingReceipts(): HasMany
    {
        return $this->hasMany(StockReceipt::class, 'to_warehouse_id');
    }

    public function outgoingReceipts(): HasMany
    {
        return $this->hasMany(StockReceipt::class, 'from_warehouse_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'warehouse_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class, 'warehouse_id');
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(StockAlert::class, 'warehouse_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the default warehouse
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Set this warehouse as default
     */
    public function setAsDefault(): bool
    {
        // Remove default from other warehouses
        static::where('is_default', true)->update(['is_default' => false]);
        
        // Set this as default
        return $this->update(['is_default' => true]);
    }

    /**
     * Get stock for a specific variant
     */
    public function getStockForVariant(int $variantId): ?InventoryStock
    {
        return $this->stocks()->where('variant_id', $variantId)->first();
    }

    /**
     * Get total stock value in this warehouse
     */
    public function getTotalStockValue(): float
    {
        return $this->stocks()
            ->selectRaw('SUM(physical_stock * average_cost) as total')
            ->value('total') ?? 0;
    }

    /**
     * Get count of low stock items
     */
    public function getLowStockCount(): int
    {
        return $this->stocks()
            ->whereRaw('physical_stock <= low_stock_threshold')
            ->where('physical_stock', '>', 0)
            ->count();
    }

    /**
     * Get count of out of stock items
     */
    public function getOutOfStockCount(): int
    {
        return $this->stocks()
            ->where('available_stock', '<=', 0)
            ->count();
    }
}
