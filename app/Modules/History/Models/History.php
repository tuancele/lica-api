<?php

namespace App\Modules\History\Models;

use Illuminate\Database\Eloquent\Model;
use App\Rate;
class History extends Model
{
    protected $table = "history";
    public function user(){
    	return $this->belongsTo('App\Modules\User\Models\User','user_id','id');
    }
}