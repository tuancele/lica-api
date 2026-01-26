<?php

declare(strict_types=1);

namespace App\Modules\Compare\Models;

use Illuminate\Database\Eloquent\Model;

class Compare extends Model
{
    protected $table = 'compares';

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    protected $fillable = [
        'store_id',
        'name',
        'price',
        'link',
        'is_link',
        'brand',
        'user_id',
        'status',
    ];

    public function store()
    {
        return $this->belongsTo('App\Modules\Compare\Models\Store', 'store_id', 'id');
    }
}
