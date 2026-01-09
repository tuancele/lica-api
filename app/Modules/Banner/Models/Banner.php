<?php

namespace App\Modules\Banner\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = "medias";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
