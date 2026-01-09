<?php

namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    protected $table = "searchs";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
