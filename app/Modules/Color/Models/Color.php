<?php

declare(strict_types=1);
namespace App\Modules\Color\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = "colors";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
