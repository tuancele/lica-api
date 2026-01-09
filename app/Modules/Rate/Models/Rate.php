<?php

namespace App\Modules\Rate\Models;

use Illuminate\Database\Eloquent\Model;
class Rate extends Model
{
    protected $table = "rates";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function product(){
    	return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
}
