<?php

declare(strict_types=1);
namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = "orderdetail";
    protected $fillable = [
        'order_id',
        'product_id',
        'brand_id',
        'color_id',
        'size_id',
        'variant_id',
        'name',
        'price',
        'qty',
        'weight',
        'image',
        'subtotal',
        'deal_id',
        'dealsale_id',
        'productsale_id',
    ];
    public function variant(){
    	return $this->belongsTo('App\Modules\Product\Models\Variant','variant_id','id');
    }
    public function product(){
        return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
    public function color(){
    	return $this->belongsTo('App\Modules\Color\Models\Color','color_id','id');
    }
    public function size(){
    	return $this->belongsTo('App\Modules\Size\Models\Size','size_id','id');
    }


}
