<?php

declare(strict_types=1);

namespace App;

use App\Modules\Permission\Models\Permission;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo('App\Modules\Role\Models\Role', 'role_id', 'id');
    }

    public function hasPermission(Permission $permission)
    {
        return (bool) optional(optional($this->role)->permissions)->contains($permission);
    }

    public function posts()
    {
        return $this->hasMany('App\Post', 'user_id', 'id');
    }
}
