<?php

namespace App\Modules\Tag\Models;

use Illuminate\Database\Eloquent\Model;
class Tag extends Model
{
    protected $table = "tags";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
