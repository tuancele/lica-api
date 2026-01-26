<?php

declare(strict_types=1);
namespace App\Modules\Taxonomy\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'posts';
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function childen(){
        return $this->hasMany(Category::class, 'cat_id')->select('id','name','slug','image')->where([['type','taxonomy'],['status','1']])->orderBy('sort','asc');
    }
}
