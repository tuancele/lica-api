<?php

declare(strict_types=1);

namespace App\Modules\Showroom\Models;

use Illuminate\Database\Eloquent\Model;

class Showroom extends Model
{
    protected $table = 'showrooms';

    protected $fillable = [
        'name',
        'image',
        'address',
        'phone',
        'cat_id',
        'status',
        'sort',
        'user_id',
    ];

    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
        'cat_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
