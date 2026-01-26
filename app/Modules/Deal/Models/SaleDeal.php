<?php

declare(strict_types=1);
namespace App\Modules\Deal\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDeal extends Model
{
    protected $table = "deal_sales";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'deal_id',
        'product_id',
        'variant_id',
        'price',
        'qty',
        'buy',
        'status',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function deal(){
    	return $this->belongsTo('App\Modules\Deal\Models\Deal','deal_id','id');
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
}
