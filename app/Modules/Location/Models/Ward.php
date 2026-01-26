<?php

declare(strict_types=1);

namespace App\Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    protected $table = 'ward';
    protected $primaryKey = 'wardid';
}
