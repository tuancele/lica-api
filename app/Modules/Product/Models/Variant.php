<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
class Variant extends Model
{
    protected $table = "variants";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function product(){
    	return $this->belongsTo('App\Modules\Product\Models\Product','product_id','id');
    }
    public function color(){
		return $this->belongsTo('App\Modules\Color\Models\Color','color_id','id')->select('name','color','id');
    }
    public function size(){
    	return $this->belongsTo('App\Modules\Size\Models\Size','size_id','id')->select('name','unit','id');
    }
    public function checkcolor($size,$color,$id){
        $check =  Variant::where([['size_id',$size],['color_id',$color],['product_id',$id]])->get()->count();
        if($check > 0){
            return true;
        }else{
            return false;
        }
    }
}
