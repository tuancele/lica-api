<?php

namespace App\Modules\Brand\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = "brands";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function product(){
    	return $this->hasMany('App\Modules\Post\Models\Post','brand_id','id')->select('id');
    }
}
