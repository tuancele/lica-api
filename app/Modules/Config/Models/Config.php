<?php

declare(strict_types=1);

namespace App\Modules\Config\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $table = 'configs';

    protected $fillable = [
        'name',
        'code',
        'key',
        'value',
        'content',
        'group',
        'status',
        'user_id',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
