<?php

declare(strict_types=1);
namespace App\Modules\Role\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Permission\Models\Permission;

class Role extends Model
{
    protected $table = "roles";
    
    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
    ];
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class,'role_permission');
    }
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}