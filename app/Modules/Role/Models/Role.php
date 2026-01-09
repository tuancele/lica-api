<?php
namespace App\Modules\Role\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Permission\Models\Permission;
class Role extends Model
{
    public function permissions()
    {
        return $this->belongsToMany(Permission::class,'role_permission');
    }
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}