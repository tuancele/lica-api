<?php

declare(strict_types=1);
namespace App\Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
class Setting extends Model
{
    protected $table = "settings";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
