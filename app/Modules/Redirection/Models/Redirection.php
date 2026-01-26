<?php

declare(strict_types=1);
namespace App\Modules\Redirection\Models;

use Illuminate\Database\Eloquent\Model;

class Redirection extends Model
{
    protected $table = "redirections";
    
    protected $fillable = [
        'link_from',
        'link_to',
        'type',
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
