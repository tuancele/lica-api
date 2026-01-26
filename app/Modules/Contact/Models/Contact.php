<?php

namespace App\Modules\Contact\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contacts';
    
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'content',
        'status',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
    ];
}
