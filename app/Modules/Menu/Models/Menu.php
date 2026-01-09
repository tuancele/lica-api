<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = "menus";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
	public function children() {
        return $this->hasMany(Menu::class, 'parent')->select('id','name','url','image')->orderBy('sort','asc');
    }
}
