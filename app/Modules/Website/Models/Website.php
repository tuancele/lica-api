<?php

declare(strict_types=1);
namespace App\Modules\Website\Models;
use Illuminate\Database\Eloquent\Model;
class Website extends Model
{
    protected $table = 'website';
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
