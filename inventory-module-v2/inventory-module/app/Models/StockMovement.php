<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    public $timestamps = false;

    protected $fillable = [
        'warehouse_id',
        'variant_id',
        'movement_type',
        'quantity',
        'physical_before',
        'physical_after',
        'reserved_before',
        'reserved_after',
        'available_before',
        'available_after',
        'reference_type',
        'reference_id',
        'reference_code',
        'reason',
        'metadata',
        'unit_cost',
        'total_cost',
        'created_by',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'physical_before' => 'integer',
        'physical_after' => 'integer',
        'reserved_before' => 'integer',
        'reserved_after' => 'integer',
        'available_before' => 'integer',
        'available_after' => 'integer',
        'reference_id' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_by' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected $appends = [
        'movement_type_label',
        'is_increase',
        'product_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    const TYPE_SALE = 'sale';
    const TYPE_SALE_CANCEL = 'sale_cancel';
    const TYPE_RETURN = 'return';
    const TYPE_TRANSFER_OUT = 'transfer_out';
    const TYPE_TRANSFER_IN = 'transfer_in';
    const TYPE_ADJUSTMENT_PLUS = 'adjustment_plus';
    const TYPE_ADJUSTMENT_MINUS = 'adjustment_minus';
    const TYPE_RESERVE = 'reserve';
    const TYPE_RELEASE = 'release';
    const TYPE_FLASH_SALE_HOLD = 'flash_sale_hold';
    const TYPE_FLASH_SALE_RELEASE = 'flash_sale_release';
    const TYPE_DEAL_HOLD = 'deal_hold';
    const TYPE_DEAL_RELEASE = 'deal_release';
    const TYPE_DAMAGE = 'damage';
    const TYPE_LOST = 'lost';
    const TYPE_FOUND = 'found';
    const TYPE_INITIAL = 'initial';

    const TYPE_LABELS = [
        self::TYPE_IMPORT => 'Nhập kho',
        self::TYPE_EXPORT => 'Xuất kho',
        self::TYPE_SALE => 'Bán hàng',
        self::TYPE_SALE_CANCEL => 'Hủy đơn',
        self::TYPE_RETURN => 'Trả hàng',
        self::TYPE_TRANSFER_OUT => 'Chuyển kho đi',
        self::TYPE_TRANSFER_IN => 'Chuyển kho đến',
        self::TYPE_ADJUSTMENT_PLUS => 'Điều chỉnh tăng',
        self::TYPE_ADJUSTMENT_MINUS => 'Điều chỉnh giảm',
        self::TYPE_RESERVE => 'Giữ hàng',
        self::TYPE_RELEASE => 'Thả hàng',
        self::TYPE_FLASH_SALE_HOLD => 'Giữ Flash Sale',
        self::TYPE_FLASH_SALE_RELEASE => 'Thả Flash Sale',
        self::TYPE_DEAL_HOLD => 'Giữ Deal',
        self::TYPE_DEAL_RELEASE => 'Thả Deal',
        self::TYPE_DAMAGE => 'Hàng hỏng',
        self::TYPE_LOST => 'Mất mát',
        self::TYPE_FOUND => 'Tìm lại',
        self::TYPE_INITIAL => 'Tồn đầu kỳ',
    ];

    // Types that increase physical stock
    const INCREASE_TYPES = [
        self::TYPE_IMPORT,
        self::TYPE_SALE_CANCEL,
        self::TYPE_RETURN,
        self::TYPE_TRANSFER_IN,
        self::TYPE_ADJUSTMENT_PLUS,
        self::TYPE_FOUND,
        self::TYPE_INITIAL,
    ];

    // Types that decrease physical stock
    const DECREASE_TYPES = [
        self::TYPE_EXPORT,
        self::TYPE_SALE,
        self::TYPE_TRANSFER_OUT,
        self::TYPE_ADJUSTMENT_MINUS,
        self::TYPE_DAMAGE,
        self::TYPE_LOST,
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getMovementTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->movement_type] ?? $this->movement_type;
    }

    public function getIsIncreaseAttribute(): bool
    {
        return in_array($this->movement_type, self::INCREASE_TYPES);
    }

    public function getIsDecreaseAttribute(): bool
    {
        return in_array($this->movement_type, self::DECREASE_TYPES);
    }

    public function getProductNameAttribute(): ?string
    {
        return $this->variant?->product?->name;
    }

    public function getPhysicalChangeAttribute(): int
    {
        return $this->physical_after - $this->physical_before;
    }

    public function getReservedChangeAttribute(): int
    {
        return $this->reserved_after - $this->reserved_before;
    }

    public function getAvailableChangeAttribute(): int
    {
        return $this->available_after - $this->available_before;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeOfType($query, string|array $type)
    {
        if (is_array($type)) {
            return $query->whereIn('movement_type', $type);
        }

        return $query->where('movement_type', $type);
    }

    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    public function scopeIncreases($query)
    {
        return $query->whereIn('movement_type', self::INCREASE_TYPES);
    }

    public function scopeDecreases($query)
    {
        return $query->whereIn('movement_type', self::DECREASE_TYPES);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new movement record.
     */
    public static function record(array $data): self
    {
        $data['created_at'] = $data['created_at'] ?? now();
        $data['ip_address'] = $data['ip_address'] ?? request()->ip();
        $data['user_agent'] = $data['user_agent'] ?? substr(request()->userAgent() ?? '', 0, 500);

        return static::create($data);
    }

    /**
     * Get summary of movements for a variant in date range.
     */
    public static function getSummary(int $variantId, int $warehouseId, $startDate, $endDate): array
    {
        $movements = static::forVariant($variantId)
            ->forWarehouse($warehouseId)
            ->inDateRange($startDate, $endDate)
            ->get();

        return [
            'total_in' => $movements->filter(fn ($m) => $m->is_increase)->sum('quantity'),
            'total_out' => abs($movements->filter(fn ($m) => $m->is_decrease)->sum('quantity')),
            'count' => $movements->count(),
            'by_type' => $movements->groupBy('movement_type')->map->sum('quantity'),
        ];
    }
}
