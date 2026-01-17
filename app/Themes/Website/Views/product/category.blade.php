@extends('Website::layout',['image' => $detail->image,'canonical'=>getSlug($detail->slug)])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description', $detail->seo_description)
@section('canonical',getSlug($detail->slug))
@section('content')
<section class="mt-3">
	<div class="container-lg">
		<div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="{{getSlug($detail->slug)}}">{{$detail->name}}</a></li>
            </ol>
        </div>
        <h1 class="text-uppercase title-cate">{{$detail->name}}</h1>
	</div>
</section>

<section class="products pb-5">
	<div class="container-lg">
		<div class="filter d-flex">
			@include('Website::product.filter')
			@include('Website::product.sort',['total' => $products->total()])
		</div>
		<div class="load-product">
		@if($products->count() > 0)
		<div class="list-product mt-3">
			@foreach($products as $product)
			<div class="product-col">
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
										$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
										if ($activeDeal && $variant) {
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
			</div>
			@endforeach
		</div>
		{{$products->links()}}
		@endif
		</div>
		@if($stocks->count() > 0)
		<h4 class="mt-5">HẾT HÀNG</h4>
		<div class="list-product mt-3">
			@foreach($stocks as $product)
			<div class="product-col">
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
										$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
										if ($activeDeal && $variant) {
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
			</div>
			@endforeach
		</div>
		@endif
		<div class="detail-content mt-3">
			{!!$detail->content!!}
		</div>
	</div>
</section>
@endsection
@section('footer')
<script>
	$('.sort').on('click','a',function(){
		var sort = $(this).attr('data-sort');
		var url = '{{$detail->slug}}';
		var text = $(this).text();
		$.ajax({
			type: 'post',
			url: '/load-sort',
			data: {sort:sort,url:url,cat_id:'{{$detail->id}}',page:'taxonomy'},
			headers:
			{
			  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			beforeSend: function () {
				$('.load-product').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
			},
			success: function (res) {
				console.log(text);
				$('#total_product').html(res.total+' Kết quả');
				$('.load-product').html(res.view);
				$('.sort button').html(text);
				if (typeof initSkeletonImages === 'function') {
					initSkeletonImages();
				}
			}
	  	})
	});
	$(".list-filter li input").click(function () {
		var brand = [],
			origin = [],
			color = [],
			size = [],
			price = [];
			url = '{{$detail->slug}}';
		$(".filter-brand ul li").each(function () {
			if($(this).find("input").is(':checked')){
				brand.push($(this).find("input").val());
			}
		})
		$(".filter-origin ul li").each(function () {
			if($(this).find("input").is(':checked')){
				origin.push($(this).find("input").val());
			}
		})
		$(".filter-color ul li").each(function () {
			if($(this).find("input").is(':checked')){
				color.push($(this).find("input").val());
			}
		})
		$(".filter-size ul li").each(function () {
			if($(this).find("input").is(':checked')){
				size.push($(this).find("input").val());
			}
		})
		$(".filter-price ul li").each(function () {
			if($(this).find("input").is(':checked')){
				price.push($(this).find("input").val());
			}
		})
		$.ajax({
			type: 'post',
			url: '/load-filter',
			data: {origin:origin,brand:brand,color:color,size:size,url:url,cat_id:'{{$detail->id}}',price:price,page:'taxonomy',orderby:''},
			headers:
			{
			  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			beforeSend: function () {
				$('.load-product').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
			},
			success: function (res) {
				$('#total_product').html(res.total+' Kết quả');
				$('.load-product').html(res.view);
				if (typeof initSkeletonImages === 'function') {
					initSkeletonImages();
				}
			}
	  	})
	});
</script>
@endsection