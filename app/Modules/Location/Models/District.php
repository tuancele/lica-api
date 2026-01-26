<?php

declare(strict_types=1);
namespace App\Modules\Location\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = "district";
    protected $primaryKey = "districtid";
}
