<?php

declare(strict_types=1);

namespace App\Modules\Size\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $table = 'sizes';

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
