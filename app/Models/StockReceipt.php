<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockReceipt extends Model
{
    use SoftDeletes;

    protected $table = 'stock_receipts';

    protected $fillable = [
        'receipt_code',
        'type',
        'status',
        'from_warehouse_id',
        'to_warehouse_id',
        'reference_type',
        'reference_id',
        'reference_code',
        'supplier_id',
        'customer_id',
        'supplier_name',
        'customer_name',
        'subject',
        'content',
        'vat_invoice',
        'total_items',
        'total_quantity',
        'total_value',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_by',
        'completed_at',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
        'metadata',
    ];

    protected $casts = [
        'from_warehouse_id' => 'integer',
        'to_warehouse_id' => 'integer',
        'reference_id' => 'integer',
        'supplier_id' => 'integer',
        'customer_id' => 'integer',
        'total_items' => 'integer',
        'total_quantity' => 'integer',
        'total_value' => 'decimal:2',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'completed_by' => 'integer',
        'cancelled_by' => 'integer',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'type_label',
        'status_label',
        'total_value_formatted',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const TYPE_IMPORT = 'import';
    const TYPE_EXPORT = 'export';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_LABELS = [
        self::TYPE_IMPORT => 'Nhập kho',
        self::TYPE_EXPORT => 'Xuất kho',
        self::TYPE_TRANSFER => 'Chuyển kho',
        self::TYPE_ADJUSTMENT => 'Điều chỉnh',
        self::TYPE_RETURN => 'Trả hàng',
    ];

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Nháp',
        self::STATUS_PENDING => 'Chờ duyệt',
        self::STATUS_APPROVED => 'Đã duyệt',
        self::STATUS_COMPLETED => 'Hoàn thành',
        self::STATUS_CANCELLED => 'Đã hủy',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseV2::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseV2::class, 'to_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class, 'receipt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'completed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(\App\User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getTotalValueFormattedAttribute(): string
    {
        return number_format((float) $this->total_value, 0, ',', '.').' đ';
    }

    /**
     * Get the effective warehouse (to_warehouse for import, from_warehouse for export).
     */
    public function getWarehouseAttribute(): ?WarehouseV2
    {
        return match ($this->type) {
            self::TYPE_IMPORT, self::TYPE_RETURN => $this->toWarehouse,
            self::TYPE_EXPORT => $this->fromWarehouse,
            default => $this->toWarehouse ?? $this->fromWarehouse,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeImports($query)
    {
        return $query->ofType(self::TYPE_IMPORT);
    }

    public function scopeExports($query)
    {
        return $query->ofType(self::TYPE_EXPORT);
    }

    public function scopeTransfers($query)
    {
        return $query->ofType(self::TYPE_TRANSFER);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->ofStatus(self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->ofStatus(self::STATUS_PENDING);
    }

    public function scopeDraft($query)
    {
        return $query->ofStatus(self::STATUS_DRAFT);
    }

    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where(function ($q) use ($warehouseId) {
            $q->where('from_warehouse_id', $warehouseId)
                ->orWhere('to_warehouse_id', $warehouseId);
        });
    }

    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)
            ->where('reference_id', $id);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if receipt can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    /**
     * Check if receipt can be cancelled.
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Check if receipt can be completed.
     */
    public function canComplete(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED]);
    }

    /**
     * Mark as completed.
     */
    public function markCompleted(int $userId): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as cancelled.
     */
    public function markCancelled(int $userId, ?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_by' => $userId,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Recalculate totals from items.
     */
    public function recalculateTotals(): bool
    {
        $items = $this->items;

        return $this->update([
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum(fn ($item) => $item->quantity * $item->unit_price),
        ]);
    }

    /**
     * Generate receipt code.
     */
    public static function generateCode(string $type): string
    {
        $prefix = config("inventory.receipt_prefixes.{$type}", strtoupper(substr($type, 0, 3)));
        $date = now()->format('Ymd');

        $lastReceipt = static::where('receipt_code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('receipt_code', 'desc')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_code, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$prefix}-{$date}-".str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
