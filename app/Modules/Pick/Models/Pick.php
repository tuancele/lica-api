<?php

namespace App\Modules\Pick\Models;

use Illuminate\Database\Eloquent\Model;

class Pick extends Model
{
    protected $table = "picks";
    
    protected $fillable = [
        'name',
        'address',
        'tel',
        'province_id',
        'district_id',
        'ward_id',
        'cat_id',
        'status',
        'sort',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
        'province_id' => 'integer',
        'district_id' => 'integer',
        'ward_id' => 'integer',
        'cat_id' => 'integer',
    ];
    
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
