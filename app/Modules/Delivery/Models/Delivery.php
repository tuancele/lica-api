<?php

declare(strict_types=1);
namespace App\Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $table = "deliveries";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
