<?php

namespace App\Modules\FlashSale\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $table = "flashsales";
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function products(){
    	return $this->hasMany('App\Modules\FlashSale\Models\ProductSale','flashsale_id','id');
    }

    /**
     * Scope to get active Flash Sales
     * Active means: status = 1 AND start <= now AND end >= now
     */
    public function scopeActive($query)
    {
        $now = time();
        return $query->where('status', '1')
            ->where('start', '<=', $now)
            ->where('end', '>=', $now);
    }

    /**
     * Check if Flash Sale is currently active
     */
    public function getIsActiveAttribute(): bool
    {
        $now = time();
        return $this->status == '1' 
            && $this->start <= $now 
            && $this->end >= $now;
    }

    /**
     * Get countdown seconds (remaining time until end)
     */
    public function getCountdownSecondsAttribute(): int
    {
        if ($this->is_active) {
            return max(0, $this->end - time());
        }
        return 0;
    }

    /**
     * Get total products count in this Flash Sale
     */
    public function getTotalProductsAttribute(): int
    {
        return $this->products()->count();
    }
}
