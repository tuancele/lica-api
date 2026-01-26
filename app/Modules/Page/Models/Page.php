<?php

declare(strict_types=1);
namespace App\Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'posts';
    
    protected $fillable = [
        'name',
        'slug',
        'image',
        'description',
        'content',
        'status',
        'type',
        'view',
        'seo_title',
        'seo_description',
        'cat_id',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
        'view' => 'integer',
        'cat_id' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
