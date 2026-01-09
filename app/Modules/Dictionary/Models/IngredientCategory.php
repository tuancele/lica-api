<?php

namespace App\Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientCategory extends Model
{
    protected $table = "ingredient_category";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
