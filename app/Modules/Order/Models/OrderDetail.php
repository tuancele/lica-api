<?php

namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $table = "orderdetail";
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
