<?php
namespace App\Modules\Member\Models;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\MemberResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    protected $table = "members";
    
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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new MemberResetPasswordNotification($token));
    }

    public function order(){
        return $this->hasMany('App\Modules\Order\Models\Order','member_id','id');
    }
    public function address(){
        return $this->hasMany('App\Modules\Address\Models\Address','member_id','id');
    }
}
