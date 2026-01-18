<?php

namespace App\Modules\Slider\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $table = "medias";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'link',
        'image',
        'content',
        'status',
        'type',
        'user_id',
        'display',
        'sort',
    ];
    
    /**
     * Get the user that created the slider.
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
