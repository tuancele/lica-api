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
								// 排除 Flash Sale 产品 - 如果产品正在 Flash Sale 中，不显示 Deal voucher
								$activeDeal = null;
								$dealDiscountPercent = 0;
								$isInFlashSale = false;
								
								try {
									// 检查是否在 Flash Sale 中
									$date = strtotime(date('Y-m-d H:i:s'));
									$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
									if(isset($flash) && !empty($flash)){
										$productSale = App\Modules\FlashSale\Models\ProductSale::select('product_id','price_sale','number','buy')->where([['flashsale_id',$flash->id],['product_id',$product->id]])->first();
										if(isset($productSale) && !empty($productSale) && $productSale->buy < $productSale->number){
											$isInFlashSale = true;
										}
									}
									
									// 如果不在 Flash Sale 中，检查 Deal
									if (!$isInFlashSale) {
										$now = strtotime(date('Y-m-d H:i:s'));
										
										// 获取产品的默认 variant (第一个 variant 或 null)
										$defaultVariant = null;
										if ($product->has_variants) {
											$defaultVariant = App\Modules\Product\Models\Variant::where('product_id', $product->id)
												->orderBy('position', 'asc')
												->orderBy('id', 'asc')
												->first();
										}
										
										// 查询 ProductDeal - 支持 variant_id
										$productDealQuery = App\Modules\Deal\Models\ProductDeal::where('product_id', $product->id)
											->where('status', 1);
										
										// 如果有 variant，优先查询匹配 variant_id 的，否则查询 variant_id 为 null 的
										if ($defaultVariant) {
											$productDealQuery->where(function($q) use ($defaultVariant) {
												$q->where('variant_id', $defaultVariant->id)
												  ->orWhereNull('variant_id');
											});
										} else {
											$productDealQuery->whereNull('variant_id');
										}
										
										$deal_id = $productDealQuery->pluck('deal_id')->toArray();
										
										if (!empty($deal_id)) {
											$activeDeal = App\Modules\Deal\Models\Deal::whereIn('id', $deal_id)
												->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
												->first();
											
											// 计算 deal 折扣百分比
											if ($activeDeal) {
												$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
												$variantToUse = $defaultVariant ?? $variant;
												
												if ($variantToUse) {
													// 查询 SaleDeal - 支持 variant_id
													$saleDealQuery = App\Modules\Deal\Models\SaleDeal::where('deal_id', $activeDeal->id)
														->where('product_id', $product->id)
														->where('status', '1');
													
													if ($defaultVariant) {
														$saleDealQuery->where(function($q) use ($defaultVariant) {
															$q->where('variant_id', $defaultVariant->id)
															  ->orWhereNull('variant_id');
														});
													} else {
														$saleDealQuery->whereNull('variant_id');
													}
													
													$saleDeal = $saleDealQuery->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 0 ELSE 1 END')
														->first();
													
													if ($saleDeal && isset($saleDeal->price) && isset($variantToUse->price) && $variantToUse->price > 0) {
														$dealDiscountPercent = round(($variantToUse->price - $saleDeal->price) / ($variantToUse->price / 100));
													}
												}
											}
										}
									}
								} catch (\Exception $e) {
									// 静默处理错误，不显示 deal voucher
									$activeDeal = null;
								}
							@endphp
							@if($activeDeal && !$isInFlashSale)
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
								// 排除 Flash Sale 产品 - 如果产品正在 Flash Sale 中，不显示 Deal voucher
								$activeDeal = null;
								$dealDiscountPercent = 0;
								$isInFlashSale = false;
								
								try {
									// 检查是否在 Flash Sale 中
									$date = strtotime(date('Y-m-d H:i:s'));
									$flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
									if(isset($flash) && !empty($flash)){
										$productSale = App\Modules\FlashSale\Models\ProductSale::select('product_id','price_sale','number','buy')->where([['flashsale_id',$flash->id],['product_id',$product->id]])->first();
										if(isset($productSale) && !empty($productSale) && $productSale->buy < $productSale->number){
											$isInFlashSale = true;
										}
									}
									
									// 如果不在 Flash Sale 中，检查 Deal
									if (!$isInFlashSale) {
										$now = strtotime(date('Y-m-d H:i:s'));
										
										// 获取产品的默认 variant (第一个 variant 或 null)
										$defaultVariant = null;
										if ($product->has_variants) {
											$defaultVariant = App\Modules\Product\Models\Variant::where('product_id', $product->id)
												->orderBy('position', 'asc')
												->orderBy('id', 'asc')
												->first();
										}
										
										// 查询 ProductDeal - 支持 variant_id
										$productDealQuery = App\Modules\Deal\Models\ProductDeal::where('product_id', $product->id)
											->where('status', 1);
										
										// 如果有 variant，优先查询匹配 variant_id 的，否则查询 variant_id 为 null 的
										if ($defaultVariant) {
											$productDealQuery->where(function($q) use ($defaultVariant) {
												$q->where('variant_id', $defaultVariant->id)
												  ->orWhereNull('variant_id');
											});
										} else {
											$productDealQuery->whereNull('variant_id');
										}
										
										$deal_id = $productDealQuery->pluck('deal_id')->toArray();
										
										if (!empty($deal_id)) {
											$activeDeal = App\Modules\Deal\Models\Deal::whereIn('id', $deal_id)
												->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
												->first();
											
											// 计算 deal 折扣百分比
											if ($activeDeal) {
												$variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
												$variantToUse = $defaultVariant ?? $variant;
												
												if ($variantToUse) {
													// 查询 SaleDeal - 支持 variant_id
													$saleDealQuery = App\Modules\Deal\Models\SaleDeal::where('deal_id', $activeDeal->id)
														->where('product_id', $product->id)
														->where('status', '1');
													
													if ($defaultVariant) {
														$saleDealQuery->where(function($q) use ($defaultVariant) {
															$q->where('variant_id', $defaultVariant->id)
															  ->orWhereNull('variant_id');
														});
													} else {
														$saleDealQuery->whereNull('variant_id');
													}
													
													$saleDeal = $saleDealQuery->orderByRaw('CASE WHEN variant_id IS NOT NULL THEN 0 ELSE 1 END')
														->first();
													
													if ($saleDeal && isset($saleDeal->price) && isset($variantToUse->price) && $variantToUse->price > 0) {
														$dealDiscountPercent = round(($variantToUse->price - $saleDeal->price) / ($variantToUse->price / 100));
													}
												}
											}
										}
									}
								} catch (\Exception $e) {
									// 静默处理错误，不显示 deal voucher
									$activeDeal = null;
								}
							@endphp
							@if($activeDeal && !$isInFlashSale)
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