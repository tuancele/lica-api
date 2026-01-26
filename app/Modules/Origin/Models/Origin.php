<?php

declare(strict_types=1);

namespace App\Modules\Origin\Models;

use Illuminate\Database\Eloquent\Model;

class Origin extends Model
{
    protected $table = 'origins';

    protected $fillable = [
        'name',
        'slug',
        'content',
        'image',
        'seo_title',
        'seo_description',
        'status',
        'sort',
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
