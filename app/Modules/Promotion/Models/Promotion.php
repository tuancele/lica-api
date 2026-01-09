<?php

namespace App\Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = "promotions";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function order(){
    	return $this->hasMany('App\Modules\Order\Models\Order','promotion_id','id');
    }
}
