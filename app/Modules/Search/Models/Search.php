<?php

declare(strict_types=1);
namespace App\Modules\Search\Models;

use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
    protected $table = "searchs";
    
    protected $fillable = [
        'name',
        'status',
        'sort',
        'user_id',
    ];
    
    protected $casts = [
        'status' => 'integer',
        'sort' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
