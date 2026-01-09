<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'posts';
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
