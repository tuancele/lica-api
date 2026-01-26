<?php

declare(strict_types=1);
namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = "menus";
    
    protected $fillable = [
        'name',
        'url',
        'parent',
        'sort',
        'status',
        'group_id',
        'user_id',
    ];
    
    protected $casts = [
        'parent' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'group_id' => 'integer',
    ];
    
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    
	public function children() {
        return $this->hasMany(Menu::class, 'parent')->select('id','name','url','image')->orderBy('sort','asc');
    }
}
