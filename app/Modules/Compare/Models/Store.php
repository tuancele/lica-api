<?php

namespace App\Modules\Compare\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = "stores";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function compares(){
    	return $this->hasMany('App\Modules\Compare\Models\Compare','store_id','id');
    }
}
