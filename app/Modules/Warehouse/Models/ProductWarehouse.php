<?php

namespace App\Modules\Warehouse\Models;

use Illuminate\Database\Eloquent\Model;
class ProductWarehouse extends Model
{
    protected $table = "product_warehouse";
    public function variant(){
        return $this->belongsTo('App\Modules\Product\Models\Variant','variant_id','id');
    }
    public function warehouse(){
        return $this->belongsTo('App\Modules\Warehouse\Models\Warehouse','warehouse_id','id');
    }
}
