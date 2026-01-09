<?php

namespace App\Modules\Origin\Models;

use Illuminate\Database\Eloquent\Model;

class Origin extends Model
{
    protected $table = "origins";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
