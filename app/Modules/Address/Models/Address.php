<?php

declare(strict_types=1);

namespace App\Modules\Address\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'address';

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function province()
    {
        return $this->belongsTo('App\Modules\Location\Models\Province', 'provinceid', 'provinceid');
    }

    public function District()
    {
        return $this->belongsTo('App\Modules\Location\Models\District', 'districtid', 'districtid');
    }

    public function Ward()
    {
        return $this->belongsTo('App\Modules\Location\Models\Ward', 'wardid', 'wardid');
    }
}
