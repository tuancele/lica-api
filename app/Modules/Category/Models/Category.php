<?php

namespace App\Modules\Category\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'posts';
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function children(){
    	return $this->hasMany('App\Modules\Category\Models\Category','cat_id','id')->where('type','category');
    }
    public function parent(){
    	return $this->belongsTo('App\Modules\Category\Models\Category','cat_id','id')->where('type','category');
    }
}
