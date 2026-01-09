<?php

namespace App\Modules\Selling\Models;

use Illuminate\Database\Eloquent\Model;

class Selling extends Model
{
    protected $table = "medias";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function product(){
        return $this->belongsTo('App\Modules\Product\Models\Product','link','id');
    }
}
