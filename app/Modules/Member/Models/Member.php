<?php

declare(strict_types=1);

namespace App\Modules\Member\Models;

use App\Notifications\MemberResetPasswordNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    protected $table = 'members';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'password',
        'status',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'integer',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MemberResetPasswordNotification($token));
    }

    public function order()
    {
        return $this->hasMany('App\Modules\Order\Models\Order', 'member_id', 'id');
    }

    public function address()
    {
        return $this->hasMany('App\Modules\Address\Models\Address', 'member_id', 'id');
    }
}
