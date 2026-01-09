<?php

namespace App\Modules\Right\Models;

use Illuminate\Database\Eloquent\Model;

class Right extends Model
{
    protected $table = "medias";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
