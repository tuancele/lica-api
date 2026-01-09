<?php

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
}
