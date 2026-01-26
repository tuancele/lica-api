<?php

declare(strict_types=1);

namespace App\Modules\Promotion\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'name',
        'code',
        'value',
        'unit',
        'number',
        'start',
        'end',
        'order_sale',
        'endow',
        'content',
        'payment',
        'status',
        'sort',
        'user_id',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'order_sale' => 'decimal:2',
        'number' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function order()
    {
        return $this->hasMany('App\Modules\Order\Models\Order', 'promotion_id', 'id');
    }
}
