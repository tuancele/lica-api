<?php

namespace App\Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientPaulas extends Model
{
    protected $table = "ingredient_paulas";
    protected $casts = [
        'cat_id' => 'array',
        'benefit_id' => 'array',
    ];

    protected $guarded = [];

    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function rate(){
    	return $this->belongsTo('App\Modules\Dictionary\Models\IngredientRate','rate_id','id');
    }
}
