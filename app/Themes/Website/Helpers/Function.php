<?php 
if (! function_exists('getImage')) {
	function getImage($image){
	    if($image != ""){
	        return $image;
	    }else{
	        return '/public/image/no_image.png';
	    }
	}
}

// 图片懒加载辅助函数
if (! function_exists('getImageLazy')) {
	function getImageLazy($image, $alt = '', $class = '', $width = '', $height = ''){
	    $src = getImage($image);
	    $placeholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E';
	    
	    $attributes = [];
	    if ($class) $attributes[] = 'class="' . htmlspecialchars($class) . '"';
	    if ($width) $attributes[] = 'width="' . htmlspecialchars($width) . '"';
	    if ($height) $attributes[] = 'height="' . htmlspecialchars($height) . '"';
	    
	    return '<img src="' . $placeholder . '" data-src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy" ' . implode(' ', $attributes) . '>';
	}
}
if (! function_exists('formatDate')) {
	function formatDate($date){
	    if($date != ""){
	        return date('d-m-Y',strtotime($date));
	    }else{
	        return "";
	    }
	}
}
if (! function_exists('getOption')) {
	function getOption($name){
	    $result = App\Modules\Option\Models\Option::where('name',$name)->first();
    	return (isset($result) && !empty($result))?$result->value:'';
	}
}
if (! function_exists('getSlug')) {
	function getSlug($slug){
	    if($slug != ""){
	        if (strlen(strstr($slug, 'https://')) > 0 || strlen(strstr($slug, 'http://'))) {
	            return $slug;
	        }else{
	            return asset($slug);
	        }
	    }else{
	        return "";
	    }
	}
}

if (! function_exists('formatPrice')) {
	function formatPrice($price){
	    return number_format($price).'₫';
	}
}

if (! function_exists('getPrice')) {
	function getPrice($id){
		$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id',$id)->first();
		if(isset($variant) && !empty($variant)){
			if($variant->sale != 0){
				$percent = round(($variant->price - $variant->sale)/($variant->price/100));
				return '<p>'.number_format($variant->sale).'đ</p><del>'.number_format($variant->price).'đ</del><div class="tag"><span>-'.$percent.'%</span></div>';
			}else{
				return '<p>'.number_format($variant->price).'đ</p>';
			}
		}else{
			return '<p>Liên hệ</p>';
		}
	}
}

if (! function_exists('getPrice2')) {
	function getPrice2($price,$sale){
		if($sale != 0){
			$percent = round(($price - $sale)/($price/100));
			return '<p>'.number_format($sale).'đ</p><del>'.number_format($price).'đ</del><div class="tag"><span>-'.$percent.'%</span></div>';
		}else{
			return '<p>'.number_format($price).'đ</p>';
		}
	}
}

if (! function_exists('checkSale')) {
	function checkSale($id){
        // 1. Check Flash Sale
		$date = strtotime(date('Y-m-d H:i:s'));
		$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
		$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id',$id)->first();
        if(!$variant) return '<p>Liên hệ</p>';

		if(isset($flash) && !empty($flash)){
			$product = App\Modules\FlashSale\Models\ProductSale::select('product_id','price_sale','number','buy')->where([['flashsale_id',$flash->id],['product_id',$id]])->first();
			if(isset($product) && !empty($product)){
                if($product->buy < $product->number){
                    $percent = round(($variant->price - $product->price_sale)/($variant->price/100));
                    return '<p>'.number_format($product->price_sale).'đ</p><del>'.number_format($variant->price).'đ</del><div class="tag"><span>-'.$percent.'%</span></div>';
                }
			}
		}

        // 2. Check Marketing Campaign
        $nowDate = \Carbon\Carbon::now();
        $campaignProduct = App\Modules\Marketing\Models\MarketingCampaignProduct::where('product_id', $id)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                  ->where('start_at', '<=', $nowDate)
                  ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            $percent = round(($variant->price - $campaignProduct->price)/($variant->price/100));
            return '<p>'.number_format($campaignProduct->price).'đ</p><del>'.number_format($variant->price).'đ</del><div class="tag"><span>-'.$percent.'%</span></div>';
        }

        // 3. Fallback to Original/Old Sale
		return getPrice($id);
	}
} 

if (! function_exists('checkStartFlash')) {
	function checkStartFlash($id){
		$date = strtotime(date('Y-m-d H:i:s'));
		$newdate = strtotime ('+24 hour' ,$date) ;
		$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$newdate],['end','>=',$date]])->first();
		if(isset($flash) && !empty($flash)){
			$product = App\Modules\FlashSale\Models\ProductSale::select('product_id','buy','number')->where([['flashsale_id',$flash->id],['product_id',$id]])->first();
			if(isset($product) && !empty($product)){
				if($product->buy < $product->number){
					return true;
				}else{
					return false;
				}
				
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}

if (! function_exists('checkFlash')) {
	function checkFlash($id){
		$date = strtotime(date('Y-m-d H:i:s'));
		$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
		if(isset($flash) && !empty($flash)){
			$product = App\Modules\FlashSale\Models\ProductSale::select('product_id','buy','number')->where([['flashsale_id',$flash->id],['product_id',$id]])->first();
			if(isset($product) && !empty($product)){
				if($product->buy < $product->number){
					return true;
				}else{
					return false;
				}
				
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}

if (! function_exists('getSale')) {
	function getSale($price,$sale){
		if($sale > 0 && $price > $sale){
			return 	'<div class="sale">-' .round(($price - $sale)/($price / 100)).'%</div>';
		}else{
			return '';
		}
	}
}


if (! function_exists('wishList')) {
	function wishList($id){
		$member = auth()->guard('member')->user();
		if(isset($member) && !empty($member)){
			$wish =  App\Themes\Website\Models\Wishlist::where([['product_id',$id],['member_id',$member['id']]])->first();
			if(isset($wish) && !empty($wish)){
				return '<button class="btn_remove_wishlist" type="button" data-id="'.$id.'"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M 21.001 0 C 18.445 0 16.1584 1.24169 14.6403 3.19326 C 13.1198 1.24169 10.8355 0 8.27952 0 C 3.70634 0 0 3.97108 0 8.86991 C 0 15.1815 9.88903 23.0112 13.4126 25.5976 C 14.1436 26.1341 15.1369 26.1341 15.8679 25.5976 C 19.3915 23.0088 29.2805 15.1815 29.2805 8.86991 C 29.2782 3.97108 25.5718 0 21.001 0 Z" fill="black"></path></svg></button>';
			}else{
				return '<button class="btn_wishlist" type="button" data-id="'.$id.'"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></button>';
			}
		}else{
			return '<button class="btn_login_wishlist" type="button" data-bs-toggle="modal" data-bs-target="#myLogin"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></button>';
		}
	}
}

if (! function_exists('getTotal')) {
	function getTotal($data){
		if(isset($data) && !empty($data)){
			$total = App\Modules\Product\Models\Product::select('id')->where([['status','1'],['type','product']])->where('cat_id','like','%'.$data->id.'%')->get()->count();
			return $total;
		}
	}
}
if (! function_exists('breadcrumbs')) {
	function breadcrumbs($cat_id,$id, $str = ""){
	    $html = "";
	    $parent = App\Modules\Post\Models\Post::select('id','name','slug','cat_id')->where([['id',$cat_id],['status','1']])->first();
	    if(isset($parent) && !empty($parent)){
	        $html .= breadcrumbs($parent->cat_id,$parent->id, ";");
	        $html .= getSlug($parent->slug).'::'.$parent->name.$str;
	    }
	    return $html;
	}
}
if (! function_exists('getStar')) {
    function getStar($sum,$total){
    	if($total != 0){
    		$number = ceil($sum/$total);
    	}else{
    		$number = 0;
    	}
        $html = "<ul class='list-rate'>";
        for($i = 0; $i < $number; $i++){
            $html .= '<li class="icon-star active"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3z"></path></svg></li>';
        }
        for($j = 1; $j <= 5 - $number; $j++){
            $html .= '<li class="icon-star"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3z"></path></svg></li>';
        }
        $html .='</ul>';
        return $html;
    }
}

if (! function_exists('checkStock')) {
	function checkStock($id){
		return true;
	}
}

if (! function_exists('itemStar')) {
	function itemStar($id,$number){
		$total = App\Modules\Rate\Models\Rate::where([['product_id',$id],['rate',$number]])->get()->count();
		return $total;
	}
}

if (! function_exists('checkWishlist')) {
	function checkWishlist($id){
		$member = auth()->guard('member')->user();
		if(isset($member) && !empty($member)){
			$total = App\Themes\Website\Models\Wishlist::where([['product_id',$id],['member_id',$member['id']]])->get()->count();
			if($total > 0){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}

if (! function_exists('checkPromotion')) {
	function checkPromotion($id){
		$member = auth()->guard('member')->user();
		if(isset($member) && !empty($member)){
			$total = App\Themes\Website\Models\MemberPromotion::where([['promotion_id',$id],['member_id',$member['id']]])->get()->count();
			if($total > 0){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}

if (! function_exists('checkVariant')) {
	function checkVariant($product,$color,$size){
		$detail = App\Modules\Product\Models\Variant::select('id')->where([['product_id',$product],['color_id',$color],['size_id',$size]])->first();
		if(isset($detail) && !empty($detail)){
			$first = App\Modules\Product\Models\Variant::select('id','size_id')->where([['product_id',$product],['color_id',$color]])->first();
			if($first->size_id == $size){
				return "active";
			}else{
				return "";
			}
		}else{
			return 'disable';
		}
	}
}

if (! function_exists('getSizes')) {
	function getSizes($product,$color){
		if($color == 0){
			return '';
		}else{
			$variants = App\Modules\Product\Models\Variant::select('id','color_id','size_id')->where([['product_id',$product],['color_id',$color]])->get();
			$html = '';
			if($variants->count() > 0 && $variants[0]->size){
				$html .= '<div class="label">
							<strong>Kích thước:</strong>
							<span>'.$variants[0]->size->name.$variants[0]->size->unit.'</span>
							<input type="hidden" name="size_id" value="'.$variants[0]->size_id.'">
						</div>';
				$html .= '<div class="list-variant">';
				foreach($variants as $key => $variant){
					if($variant->size){
						$active = ($key == 0)?'active':'';
						$html .= '<div class="item-variant '.$active.'" data-id="'.$variant->size_id.'" data-text="'.$variant->size->name.$variant->size->unit.'">
									<span>'.$variant->size->name.$variant->size->unit.'</span>
								</div>';
					}
				}
				$html .= '</div>';
			}
			return $html;
		}
		
	}
}

if (! function_exists('totalIngredientCat')) {
	function totalIngredientCat($id){
	    $result = App\Modules\Dictionary\Models\IngredientPaulas::select('id')->where('cat_id','like','%"'.$id.'"%')->get()->count();
    	return $result;
	}
}
if (! function_exists('totalIngredientBen')) {
	function totalIngredientBen($id){
	    $result = App\Modules\Dictionary\Models\IngredientPaulas::select('id')->where('benefit_id','like','%"'.$id.'"%')->get()->count();
    	return $result;
	}
}
if (! function_exists('totalIngredientRate')) {
	function totalIngredientRate($id){
	    $result = App\Modules\Dictionary\Models\IngredientPaulas::select('id')->where('rate_id',$id)->get()->count();
    	return $result;
	}
}

if (! function_exists('to_ascii')) {
	function to_ascii($str) {
		$str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
		$str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
		$str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
		$str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
		$str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
		$str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
		$str = preg_replace("/(đ)/", 'd', $str);
		$str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
		$str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
		$str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
		$str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
		$str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
		$str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
		$str = preg_replace("/(Đ)/", 'D', $str);
		return $str;
	}
}
