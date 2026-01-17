@php 
	$date = strtotime(date('Y-m-d H:i:s'));
	$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
@endphp
@if(isset($flash) && !empty($flash))
@php $products = App\Modules\FlashSale\Models\ProductSale::where('flashsale_id',$flash->id)->get(); @endphp
<section class="flashsale">
	<div class="container-lg">
		<div class="box-flashsale">
			<div class="head-flashsale d-md-flex d-block">
				<div class="icon_flash"><img src="/public/image/flashsale.webp" alt=""></div>
				<div class="run-time">
					<div class="title_time">Thời gian còn lại</div>
					<div class="box-time">
						<div>00<span>Ngày</span></div><div>00<span>GIỜ</span></div><div>00<span>PHÚT</span></div><div>00<span>GIÂY </span></div>
					</div>
				</div>
				<a href="/flash-sale-hot" class="d-none d-md-inline-block view_flash">Xem tất cả</a>
			</div>
			<div class="list-flash mt-3">
	        @foreach($products as $value)
	        	@php $product = $value->product; @endphp
	        	@if($value->buy < $value->number)
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
				    <div class="process-buy">
				    	@php $stock = $value->number - $value->buy;
				    		$ws = ceil($stock / ($value->number/100));
				    	@endphp
				    	<span style="width:{{$ws}}%" class="process-status"></span>
				    	<span class="process-title">Còn {{$stock}} sản phẩm</span>
				    </div>
				</div>
				@endif
	        @endforeach
	        </div>
	        <div class="text-center d-block d-md-none">
	        	<a href="/flash-sale-hot" class="view_flash">Xem tất cả</a>
	    	</div>
	    </div>
	</div>
</section>	
<script>
	function toUnit(time, a, b) {
	    return String(Math.floor((time % a) / b)).padStart(2, '0');
	}
	function formatTimer(time) {
	    if (Number.isNaN(parseInt(time, 10))) {
	        return '';
	    }
	    const days = Math.floor(time / 86400);
	    const hours = toUnit(time, 86400, 3600);
	    const minutes = toUnit(time, 3600, 60);
	    const seconds = toUnit(time, 60, 1);

	    if (days > 0) {
	        return `<div>${days} ${days > 1 ?'':''}<span>NGÀY</span></div>`+'<div>'+`${hours}`+'<span> GIỜ </span></div><div>'+`${minutes} `+'<span>PHÚT</span></div><div>'+`${seconds}`+'<span>GIÂY</span></div>';
	    }
	    if (hours > 0) {
	        return `<div>00<span>Ngày</span></div><div>${hours}`+'<span>GIỜ</span></div><div>'+`${minutes}`+'<span>PHÚT</span></div><div>'+`${seconds}`+'<span>GIÂY</span></div>';
	    }
	    if(days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0){
	    	$('.flashsale').remove();
	    	return `<div>00<span>Ngày</span></div><div>00<span>GIỜ</span></div><div>00<span>PHÚT</span></div><div>00<span>GIÂY </span></div>`;
	    }
	    return `<div>00<span>Ngày</span></div><div>00<span>GIỜ</span></div><div>${minutes}`+'<span>PHÚT</span></div><div>'+`${seconds}`+'<span>GIÂY</span></div>';
	}

	const deadline = new Date('{{date("Y/m/d H:i:s",$flash->end)}}');
	let remainingTime = (deadline - new Date) / 1000;
	setInterval(function () {
	    remainingTime--;
	    $('.box-time').html(formatTimer(remainingTime));
	}, 1000);
</script>
@endif