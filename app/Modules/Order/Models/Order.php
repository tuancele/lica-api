<?php

declare(strict_types=1);
namespace App\Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = "orders";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function ward(){
    	return $this->belongsTo('App\Modules\Location\Models\Ward','wardid','wardid');
    }
    public function district(){
    	return $this->belongsTo('App\Modules\Location\Models\District','districtid','districtid');
    }
    public function province(){
    	return $this->belongsTo('App\Modules\Location\Models\Province','provinceid','provinceid');
    }
    public function detail(){
        return $this->hasMany('App\Modules\Order\Models\OrderDetail','order_id','id');
    }
    public function promotion(){
        return $this->belongsTo('App\Modules\Promotion\Models\Promotion','promotion_id','id');
    }
    public function member(){
        return $this->belongsTo('App\User','user_id','id');
    }
}
