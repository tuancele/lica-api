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
}
