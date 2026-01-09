<?php

namespace App\Modules\Product\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Product\Models\Variant;
use App\Modules\FlashSale\Models\ProductSale;
use App\Modules\FlashSale\Models\FlashSale;
use App\Modules\Marketing\Models\MarketingCampaignProduct;
use App\Modules\Marketing\Models\MarketingCampaign;
use Carbon\Carbon;

class Product extends Model
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

	public function variant($id){
    	return Variant::where('product_id',$id)->first();
    }

    public function arrayCate($id,$type){
		$array = array($id);
		$category = Product::select('id','cat_id')->where([['status','1'],['cat_id',$id],['type',$type]])->get();
		if($category->count() > 0){
			foreach ($category as $value) {
				array_push($array, $value->id);
				$category2 = Product::select('id','cat_id')->where([['status','1'],['cat_id',$value->id],['type',$type]])->get();
				if($category2->count() > 0){
					foreach ($category2 as $value2) {
						array_push($array, $value2->id);
					}
				}
			}
		}
		return $array;
	}
	public function category(){
		return $this->belongsTo(Product::class, 'cat_id')->select('id','name','slug');
	}
	public function children(){
		return $this->hasMany(Product::class, 'cat_id')->select('id','name','slug');
	}
	public function rates(){
		return $this->hasMany('App\Modules\Rate\Models\Rate','product_id','id')->where('status','1');
	}

    /**
     * Get the display price information based on priority:
     * 1. Flash Sale
     * 2. Marketing Campaign
     * 3. Original Price
     *
     * @return object
     */
    public function getPriceInfoAttribute()
    {
        $now = time(); // FlashSale uses timestamp
        $nowDate = Carbon::now(); // Campaign uses timestamp/datetime

        $variant = $this->variant($this->id);
        $originalPrice = $variant ? $variant->price : 0;
        
        // 1. Check Flash Sale
        // Assuming FlashSale stores timestamp in start/end
        $flashSaleProduct = ProductSale::where('product_id', $this->id)
            ->whereHas('flashsale', function ($q) use ($now) {
                $q->where('status', 1)
                  ->where('start', '<=', $now)
                  ->where('end', '>=', $now);
            })->first();

        if ($flashSaleProduct) {
            return (object) [
                'price' => $flashSaleProduct->price_sale,
                'original_price' => $originalPrice,
                'type' => 'flashsale',
                'label' => 'Flash Sale'
            ];
        }

        // 2. Check Marketing Campaign
        // MarketingCampaign uses standard timestamp or datetime string
        $campaignProduct = MarketingCampaignProduct::where('product_id', $this->id)
            ->whereHas('campaign', function ($q) use ($nowDate) {
                $q->where('status', 1)
                  ->where('start_at', '<=', $nowDate)
                  ->where('end_at', '>=', $nowDate);
            })->first();

        if ($campaignProduct) {
            return (object) [
                'price' => $campaignProduct->price,
                'original_price' => $originalPrice,
                'type' => 'campaign',
                'label' => 'Khuyến mại'
            ];
        }

        // 3. Normal Price
        // Check if variant has 'sale' price (old promotion logic)
        $salePrice = $variant ? $variant->sale : 0;
        if($salePrice > 0 && $salePrice < $originalPrice){
             return (object) [
                'price' => $salePrice,
                'original_price' => $originalPrice,
                'type' => 'sale',
                'label' => 'Giảm giá'
            ];
        }

        return (object) [
            'price' => $originalPrice,
            'original_price' => $originalPrice,
            'type' => 'normal',
            'label' => ''
        ];
    }
}
