<?php

namespace App\Modules\Slider\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $table = "medias";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
