<?php

namespace App\Modules\Brand\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = "brands";
    
    protected $fillable = [
        'name',
        'slug',
        'content',
        'image',
        'banner',
        'logo',
        'gallery',
        'seo_title',
        'seo_description',
        'status',
        'sort',
        'user_id',
    ];
    
    protected $casts = [
        'gallery' => 'array',
        'status' => 'integer',
        'sort' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    
    public function product(){
    	return $this->hasMany('App\Modules\Post\Models\Post','brand_id','id')->select('id');
    }
}
