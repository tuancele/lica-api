<?php
namespace App\Modules\Page\Models;
use Illuminate\Database\Eloquent\Model;
class Page extends Model
{
    protected $table = 'posts';
    protected $fillable = [
        'name', 
        'slug',
    ];
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
}
