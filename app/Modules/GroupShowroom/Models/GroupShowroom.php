<?php

declare(strict_types=1);
namespace App\Modules\GroupShowroom\Models;

use Illuminate\Database\Eloquent\Model;

class GroupShowroom extends Model
{
    protected $table = "group_showroom";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function showroom(){
        return $this->hasMany('App\Modules\Showroom\Models\Showroom','cat_id','id');
    }
}
