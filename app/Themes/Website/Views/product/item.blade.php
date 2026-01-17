<div class="item-product text-center">
    <div class="card-cover">
        <a href="{{getSlug($product->slug)}}">
            <div class="skeleton--img-md js-skeleton">
                <img src="{{getImage($product->image)}}" alt="{{$product->name}}" width="212" height="212" class="js-skeleton-img" loading="lazy">
            </div>
        </a>
        <div class="group-wishlist-{{$product->id}}">
            {!!wishList($product->id)!!}
        </div>
        @php
            // 计算折扣百分比 - 使用与 checkSale 相同的逻辑
            $date = strtotime(date('Y-m-d H:i:s'));
            $flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
            $variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
            $discountPercent = 0;
            $hasDiscount = false;
            
            if($variant) {
                // 检查 Flash Sale
                if(isset($flash) && !empty($flash)){
                    $productSale = App\Modules\FlashSale\Models\ProductSale::select('product_id','price_sale','number','buy')->where([['flashsale_id',$flash->id],['product_id',$product->id]])->first();
                    if(isset($productSale) && !empty($productSale) && $productSale->buy < $productSale->number){
                        $discountPercent = round(($variant->price - $productSale->price_sale)/($variant->price/100));
                        $hasDiscount = true;
                    }
                }
                
                // 检查 Marketing Campaign
                if(!$hasDiscount) {
                    $nowDate = \Carbon\Carbon::now();
                    $campaignProduct = App\Modules\Marketing\Models\MarketingCampaignProduct::where('product_id', $product->id)
                        ->whereHas('campaign', function ($q) use ($nowDate) {
                            $q->where('status', 1)
                              ->where('start_at', '<=', $nowDate)
                              ->where('end_at', '>=', $nowDate);
                        })->first();
                    
                    if ($campaignProduct) {
                        $discountPercent = round(($variant->price - $campaignProduct->price)/($variant->price/100));
                        $hasDiscount = true;
                    }
                }
                
                // 检查普通折扣
                if(!$hasDiscount && $variant->sale > 0 && $variant->price > $variant->sale) {
                    $discountPercent = round(($variant->price - $variant->sale)/($variant->price/100));
                    $hasDiscount = true;
                }
            }
        @endphp
        @if($hasDiscount && $discountPercent > 0)
        <div class="tag tag-discount"><span>-{{$discountPercent}}%</span></div>
        @endif
        @if($product->stock == 0)
        <div class="out-stock">Hết hàng</div>
        @endif
        <div class="status-product">
            @if($product->best)
            <div class="deal-hot mb-2">Deal<br/>Hot</div>
            @endif
            @if($product->is_new)
            <div class="is-new mb-2">Mới</div>
            @endif
        </div>
    </div>
    <div class="card-content mt-2">
        <div class="price">
            {!!checkSale($product->id)!!}
        </div>
        <div class="brand-btn">
            @if($product->brand)<a href="{{route('home.brand',['url' => $product->brand->slug])}}">{{$product->brand->name}}</a>@endif
        </div>
        <div class="product-name">
            <a href="{{getSlug($product->slug)}}">{{$product->name}}</a>
        </div>
        @php
            // 检查产品是否参与 deal sốc
            $activeDeal = null;
            $dealDiscountPercent = 0;
            try {
                $now = strtotime(date('Y-m-d H:i:s'));
                $deal_id = App\Modules\Deal\Models\ProductDeal::where('product_id', $product->id)->where('status', 1)->pluck('deal_id')->toArray();
                if (!empty($deal_id)) {
                    $activeDeal = App\Modules\Deal\Models\Deal::whereIn('id', $deal_id)
                        ->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
                        ->first();
                    
                    // 计算 deal 折扣百分比
                    if ($activeDeal && isset($variant) && $variant) {
                        $saleDeal = App\Modules\Deal\Models\SaleDeal::where([['deal_id', $activeDeal->id], ['product_id', $product->id], ['status', '1']])->first();
                        if ($saleDeal && isset($saleDeal->price) && isset($variant->price) && $variant->price > 0) {
                            $dealDiscountPercent = round(($variant->price - $saleDeal->price) / ($variant->price / 100));
                        }
                    }
                }
            } catch (\Exception $e) {
                // 静默处理错误，不显示 deal voucher
                $activeDeal = null;
            }
        @endphp
        @if($activeDeal)
        <div class="deal-voucher">
            <div class="deal-discount-badge">{{$dealDiscountPercent > 0 ? $dealDiscountPercent . '%' : ''}}</div>
            <span class="deal-name">{{$activeDeal->name ?? 'Deal sốc'}}</span>
        </div>
        @endif
        <div class="rating-info">
            @php
                $rateCount = $product->rates->count() ?? 0;
                $rateSum = $product->rates->sum('rate') ?? 0;
                $averageRate = $rateCount > 0 ? round($rateSum / $rateCount, 1) : 0;
                
                // 获取购买数量
                $totalSold = \Illuminate\Support\Facades\DB::table('orderdetail')
                    ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                    ->where('orderdetail.product_id', $product->id)
                    ->where('orders.ship', 2)
                    ->where('orders.status', '!=', 2)
                    ->sum('orderdetail.qty') ?? 0;
            @endphp
            <div class="rating-score">
                <span class="rating-value">{{number_format($averageRate, 1)}}</span>
                <span class="rating-count">({{$rateCount}})</span>
            </div>
            <div class="sales-count">
                <span class="sales-label">Đã bán {{number_format($totalSold)}}/tháng</span>
            </div>
        </div>
    </div>
</div>