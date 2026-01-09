<?php

namespace App\Modules\Deal\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDeal extends Model
{
    protected $table = "deal_products";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function deal(){
    	return $this->belongsTo('App\Modules\Deal\Models\Deal','deal_id','id');
    }

    public function product(){
    	return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
}
