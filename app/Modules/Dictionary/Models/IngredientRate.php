<?php

declare(strict_types=1);
namespace App\Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientRate extends Model
{
    protected $table = "ingredient_rate";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
