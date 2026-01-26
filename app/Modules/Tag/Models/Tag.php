<?php

declare(strict_types=1);
namespace App\Modules\Tag\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = "tags";
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'seo_title',
        'seo_description',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
