<?php

declare(strict_types=1);
namespace App\Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
class Post extends Model
{
    protected $table = "posts";
    public function user(){
    	return $this->belongsTo('App\User','user_id','id');
    }
    public function brand(){
    	return $this->belongsTo('App\Modules\Brand\Models\Brand','brand_id','id');
    }
    public function origin(){
    	return $this->belongsTo('App\Modules\Origin\Models\Origin','origin_id','id');
    }
    public function variants(){
    	return $this->hasMany('App\Modules\Product\Models\Variant','product_id','id');
    }
    public function wishlists(){
    	return $this->hasMany('App\Themes\Website\Models\Wishlist','product_id','id');
    }
    public function arrayCate($id,$type){
		$array = array($id);
		$category = Post::where([['status','1'],['cat_id',$id],['type',$type]])->get();
		if($category->count() > 0){
			foreach ($category as $value) {
				array_push($array, $value->id);
				$category2 = Post::where([['status','1'],['cat_id',$value->id],['type',$type]])->get();
				if($category2->count() > 0){
					foreach ($category2 as $value2) {
						array_push($array, $value2->id);
						$category3 = Post::where([['status','1'],['cat_id',$value2->id],['type',$type]])->get();
						if($category3->count() > 0){
							foreach ($category3 as $value3) {
								array_push($array, $value3->id);
							}
						}
					}
				}
			}
		}
		return $array;
	}
	public function category(){
		return $this->belongsTo(Post::class, 'cat_id')->select('id','name','slug');
	}
	public function children(){
		return $this->hasMany(Post::class, 'cat_id')->select('id','name','slug');
	}
	public function rates(){
		return $this->hasMany('App\Modules\Rate\Models\Rate','product_id','id');
	}
}
