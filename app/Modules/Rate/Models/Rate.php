<?php

namespace App\Modules\Rate\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $table = "rates";
    
    protected $fillable = [
        'product_id',
        'user_id',
        'rate',
        'comment',
        'status',
    ];
    
    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'rate' => 'integer',
        'status' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    
    public function product(){
    	return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
}
