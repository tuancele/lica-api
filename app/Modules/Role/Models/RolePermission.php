<?php

declare(strict_types=1);
namespace App\Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = "role_permission";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
