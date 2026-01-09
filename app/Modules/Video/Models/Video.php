<?php

namespace App\Modules\Video\Models;

use Illuminate\Database\Eloquent\Model;
class Video extends Model
{
    protected $table = "posts";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
