<?php

namespace App\Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientBenefit extends Model
{
    protected $table = "ingredient_benefit";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
