<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    protected $table = 'stock_alerts';

    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'alert_type',
        'current_stock',
        'threshold',
        'status',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'email_sent',
        'email_sent_at',
        'metadata',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'variant_id' => 'integer',
        'current_stock' => 'integer',
        'threshold' => 'integer',
        'acknowledged_by' => 'integer',
        'resolved_by' => 'integer',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'alert_type_label',
        'status_label',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_OUT_OF_STOCK = 'out_of_stock';
    const TYPE_OVERSTOCK = 'overstock';
    const TYPE_EXPIRING_SOON = 'expiring_soon';
    const TYPE_EXPIRED = 'expired';
    const TYPE_REORDER_POINT = 'reorder_point';

    const STATUS_ACTIVE = 'active';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';

    const TYPE_LABELS = [
        self::TYPE_LOW_STOCK => 'Sắp hết hàng',
        self::TYPE_OUT_OF_STOCK => 'Hết hàng',
        self::TYPE_OVERSTOCK => 'Tồn kho cao',
        self::TYPE_EXPIRING_SOON => 'Sắp hết hạn',
        self::TYPE_EXPIRED => 'Đã hết hạn',
        self::TYPE_REORDER_POINT => 'Cần đặt hàng',
    ];

    const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Đang hoạt động',
        self::STATUS_ACKNOWLEDGED => 'Đã xác nhận',
        self::STATUS_RESOLVED => 'Đã xử lý',
        self::STATUS_IGNORED => 'Bỏ qua',
    ];

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

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'acknowledged_by');
    }

    public function resolvedByUser(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'resolved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getAlertTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->alert_type] ?? $this->alert_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
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

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeEmailNotSent($query)
    {
        return $query->where('email_sent', false);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function acknowledge(int $userId): bool
    {
        return $this->update([
            'status' => self::STATUS_ACKNOWLEDGED,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
    }

    public function resolve(int $userId, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function ignore(): bool
    {
        return $this->update([
            'status' => self::STATUS_IGNORED,
        ]);
    }

    public function markEmailSent(): bool
    {
        return $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Create alert if not exists
     */
    public static function createIfNotExists(
        int $warehouseId,
        int $variantId,
        string $alertType,
        int $currentStock,
        ?int $threshold = null
    ): ?self {
        $existing = static::active()
            ->forWarehouse($warehouseId)
            ->forVariant($variantId)
            ->ofType($alertType)
            ->first();
        
        if ($existing) {
            $existing->update(['current_stock' => $currentStock]);
            return $existing;
        }
        
        return static::create([
            'warehouse_id' => $warehouseId,
            'variant_id' => $variantId,
            'alert_type' => $alertType,
            'current_stock' => $currentStock,
            'threshold' => $threshold,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Auto-resolve alerts when stock restored
     */
    public static function autoResolve(int $warehouseId, int $variantId, int $currentStock, int $threshold): void
    {
        if ($currentStock > $threshold) {
            static::active()
                ->forWarehouse($warehouseId)
                ->forVariant($variantId)
                ->whereIn('alert_type', [self::TYPE_LOW_STOCK, self::TYPE_REORDER_POINT])
                ->update([
                    'status' => self::STATUS_RESOLVED,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Auto-resolved: Stock replenished',
                ]);
        }
        
        if ($currentStock > 0) {
            static::active()
                ->forWarehouse($warehouseId)
                ->forVariant($variantId)
                ->ofType(self::TYPE_OUT_OF_STOCK)
                ->update([
                    'status' => self::STATUS_RESOLVED,
                    'resolved_at' => now(),
                    'resolution_notes' => 'Auto-resolved: Stock replenished',
                ]);
        }
    }
}
