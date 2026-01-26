<?php

namespace App\Modules\Feedback\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = "feedbacks";
    
    protected $fillable = [
        'name',
        'position',
        'image',
        'content',
        'status',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
