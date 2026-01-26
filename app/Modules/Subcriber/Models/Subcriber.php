<?php

declare(strict_types=1);
namespace App\Modules\Subcriber\Models;

use Illuminate\Database\Eloquent\Model;

class Subcriber extends Model
{
    protected $table = "subcribers";
    
    protected $fillable = [
        'email',
    ];
}
