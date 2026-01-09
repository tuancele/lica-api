<?php

namespace App\Modules\Pick\Models;

use Illuminate\Database\Eloquent\Model;

class Pick extends Model
{
    protected $table = "picks";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function ward(){
    	return $this->belongsTo('App\Modules\Location\Models\Ward','ward_id','wardid');
    }
    public function district(){
    	return $this->belongsTo('App\Modules\Location\Models\District','district_id','districtid');
    }
    public function province(){
    	return $this->belongsTo('App\Modules\Location\Models\Province','province_id','provinceid');
    }
}
