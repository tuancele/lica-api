<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReceiptItem extends Model
{
    protected $table = 'stock_receipt_items';

    protected $fillable = [
        'receipt_id',
        'variant_id',
        'quantity',
        'unit_price',
        'stock_before',
        'stock_after',
        'batch_number',
        'manufacturing_date',
        'expiry_date',
        'serial_numbers',
        'condition',
        'notes',
    ];

    protected $casts = [
        'receipt_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'serial_numbers' => 'array',
    ];

    protected $appends = [
        'unit_price_formatted',
        'total_price_formatted',
        'product_name',
        'variant_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Constants
    |--------------------------------------------------------------------------
    */

    const CONDITION_NEW = 'new';
    const CONDITION_USED = 'used';
    const CONDITION_DAMAGED = 'damaged';
    const CONDITION_REFURBISHED = 'refurbished';

    const CONDITION_LABELS = [
        self::CONDITION_NEW => 'Mới',
        self::CONDITION_USED => 'Đã qua sử dụng',
        self::CONDITION_DAMAGED => 'Hư hỏng',
        self::CONDITION_REFURBISHED => 'Tân trang',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'receipt_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Variant::class, 'variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getUnitPriceFormattedAttribute(): string
    {
        return number_format($this->unit_price, 0, ',', '.').' đ';
    }

    public function getTotalPriceFormattedAttribute(): string
    {
        return number_format($this->total_price, 0, ',', '.').' đ';
    }

    public function getProductNameAttribute(): ?string
    {
        return $this->variant?->product?->name;
    }

    public function getVariantNameAttribute(): ?string
    {
        return $this->variant?->option1_value ?? 'Mặc định';
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITION_LABELS[$this->condition] ?? $this->condition;
    }

    public function getStockChangeAttribute(): int
    {
        if ($this->stock_before === null || $this->stock_after === null) {
            return 0;
        }

        return $this->stock_after - $this->stock_before;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now());
    }

    public function scopeWithBatch($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if item is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Check if item is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Calculate total price.
     */
    public function calculateTotal(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
