<?php

namespace App\Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model
{
    protected $table = "warehouse";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function product(){
        return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
    public function size(){
        return $this->belongsTo('App\Modules\Size\Models\Size','size_id','id');
    }
    public function items(){
        return $this->hasMany('App\Modules\Warehouse\Models\ProductWarehouse','warehouse_id','id');
    }
}
