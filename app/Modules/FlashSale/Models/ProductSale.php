<?php

declare(strict_types=1);
namespace App\Modules\FlashSale\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    protected $table = "productsales";
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function flashsale(){
    	return $this->belongsTo('App\Modules\FlashSale\Models\FlashSale','flashsale_id','id');
    }

    public function product(){
    	return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }

    /**
     * Relationship with Variant
     */
    public function variant(){
        return $this->belongsTo('App\Modules\Product\Models\Variant','variant_id','id');
    }

    /**
     * Scope to filter by variant
     */
    public function scopeForVariant($query, $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope to filter by product (without variant)
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId)
            ->whereNull('variant_id');
    }

    /**
     * Check if product sale is still available (has remaining stock)
     */
    public function getIsAvailableAttribute(): bool
    {
        return $this->buy < $this->number;
    }

    /**
     * Get remaining quantity
     */
    public function getRemainingAttribute(): int
    {
        return max(0, $this->number - $this->buy);
    }

    /**
     * Get discount percent
     */
    public function getDiscountPercentAttribute(): ?int
    {
        if ($this->variant && $this->variant->price > 0) {
            return round(($this->variant->price - $this->price_sale) / ($this->variant->price / 100));
        }
        return null;
    }
}
