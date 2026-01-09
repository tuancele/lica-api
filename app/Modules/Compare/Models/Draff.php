<?php
namespace App\Modules\Compare\Models;
use Illuminate\Database\Eloquent\Model;
class Draff extends Model
{
    protected $table = "brand_draffs";
    protected $fillable = [
        'store_id',
        'name',
        'status',
        'link'
    ];
    public function store(){
    	return $this->belongsTo('App\Modules\Compare\Models\Store','store_id','id');
    }
}