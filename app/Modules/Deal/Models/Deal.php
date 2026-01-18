<?php

namespace App\Modules\Deal\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $table = "deals";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'start',
        'end',
        'status',
        'limited',
        'user_id',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }

    public function products(){
    	return $this->hasMany('App\Modules\Deal\Models\ProductDeal','deal_id','id');
    }

    public function sales(){
    	return $this->hasMany('App\Modules\Deal\Models\SaleDeal','deal_id','id');
    }
}
