<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $table = 'stock_reservations';

    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'quantity',
        'reference_type',
        'reference_id',
        'reference_code',
        'status',
        'expires_at',
        'confirmed_at',
        'released_at',
        'released_by',
        'release_reason',
        'metadata',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'reference_id' => 'integer',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'released_at' => 'datetime',
        'released_by' => 'integer',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'is_active',
        'is_expired',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const STATUS_ACTIVE = 'active';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_RELEASED = 'released';
    const STATUS_EXPIRED = 'expired';

    const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Đang giữ',
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_RELEASED => 'Đã thả',
        self::STATUS_EXPIRED => 'Hết hạn',
    ];

    const REFERENCE_ORDER = 'order';
    const REFERENCE_CART = 'cart';
    const REFERENCE_FLASH_SALE = 'flash_sale';
    const REFERENCE_DEAL = 'deal';

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

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'released_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsExpiredAttribute(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }
        
        if ($this->status === self::STATUS_ACTIVE && $this->expires_at) {
            return $this->expires_at->isPast();
        }
        
        return false;
    }

    public function getIsConfirmedAttribute(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function getTimeLeftAttribute(): ?int
    {
        if (!$this->is_active || !$this->expires_at) {
            return null;
        }
        
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('expires_at', '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('reference_type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Mark reservation as confirmed (stock deducted)
     */
    public function confirm(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        return $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark reservation as released
     */
    public function release(?int $userId = null, ?string $reason = null): bool
    {
        if (!in_array($this->status, [self::STATUS_ACTIVE])) {
            return false;
        }
        
        return $this->update([
            'status' => self::STATUS_RELEASED,
            'released_at' => now(),
            'released_by' => $userId,
            'release_reason' => $reason,
        ]);
    }

    /**
     * Mark reservation as expired
     */
    public function markExpired(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        return $this->update([
            'status' => self::STATUS_EXPIRED,
            'released_at' => now(),
            'release_reason' => 'Auto-expired',
        ]);
    }

    /**
     * Extend expiration time
     */
    public function extend(int $minutes): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }
        
        return $this->update([
            'expires_at' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Get total reserved quantity for a variant
     */
    public static function getTotalReserved(int $variantId, ?int $warehouseId = null): int
    {
        $query = static::active()
            ->forVariant($variantId)
            ->notExpired();
        
        if ($warehouseId) {
            $query->forWarehouse($warehouseId);
        }
        
        return $query->sum('quantity');
    }

    /**
     * Find reservation for order
     */
    public static function findForOrder(int $orderId, int $variantId): ?self
    {
        return static::forReference(self::REFERENCE_ORDER, $orderId)
            ->forVariant($variantId)
            ->first();
    }

    /**
     * Get all reservations for an order
     */
    public static function getForOrder(int $orderId)
    {
        return static::forReference(self::REFERENCE_ORDER, $orderId)->get();
    }
}
