<?php

declare(strict_types=1);
namespace App\Modules\Permission\Models;
use Illuminate\Database\Eloquent\Model;
class Permission extends Model
{
    protected $table = "permissions";
    public function roles(){
    	return $this->belongsToMany(Role::class,'role_permission');
    }
    public function childen(){
        return $this->hasMany(Permission::class, 'parent_id')->orderBy('sort','asc');
    }
}