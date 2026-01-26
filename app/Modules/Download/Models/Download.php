<?php

declare(strict_types=1);

namespace App\Modules\Download\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $table = 'posts';

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
