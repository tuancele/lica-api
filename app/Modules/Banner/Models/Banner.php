<?php

declare(strict_types=1);

namespace App\Modules\Banner\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'medias';

    protected $fillable = [
        'name',
        'link',
        'image',
        'content',
        'status',
        'type',
        'sort',
        'display',
        'user_id',
    ];

    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
