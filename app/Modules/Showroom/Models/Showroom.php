<?php

namespace App\Modules\Showroom\Models;

use Illuminate\Database\Eloquent\Model;

class Showroom extends Model
{
    protected $table = "showrooms";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
