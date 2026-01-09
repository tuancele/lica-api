<?php

namespace App\Modules\Ingredient\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $table = "ingredients";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
