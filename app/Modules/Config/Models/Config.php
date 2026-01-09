<?php

namespace App\Modules\Config\Models;

use Illuminate\Database\Eloquent\Model;
class Config extends Model
{
    protected $table = "configs";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
