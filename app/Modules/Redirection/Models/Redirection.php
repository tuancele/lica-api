<?php

namespace App\Modules\Redirection\Models;

use Illuminate\Database\Eloquent\Model;

class Redirection extends Model
{
    protected $table = "redirections";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
