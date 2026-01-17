@extends('Website::layout',['image' => $detail->image,'canonical'=> getSlug($detail->slug)])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="banner">
	<div class="container-lg">
		<div class="br-10 overflow-hidden">
			<div class="skeleton--img-banner js-skeleton">
				<img src="{{getImage($detail->banner)}}" alt="{{$detail->name}}" width="1175" height="265" class="js-skeleton-img w-100">
			</div>
		</div>
	</div>
</section>
<section class="content-brand mb-3">
	<div class="wrapper-container2">
		<div class="logo-brand text-center">
			<div class="skeleton--img-md js-skeleton" style="width: 123px; height: 123px; margin: 0 auto;">
				<img src="{{getImage($detail->logo)}}" width="123" height="123" class="br-10 js-skeleton-img" alt="{{$detail->name}}">
			</div>
		</div>
		<h1 class="text-center title-brand">{{$detail->name}}</h1>
		<div class="text-center">
			<span>{{$total}} sản phẩm</span><span class="divider"></span><span>55.4K lượt mua</span>
		</div>
		<div class="detail-content">
			{!!$detail->content!!}
		</div>
	</div>
	<div class="container-lg">
		@if(isset($galleries) && !empty($galleries))
		<div class="list-gallery row">
			@foreach($galleries as $key => $value)
			<div class="col-12 mb-3 mb-md-0 col-md-4">
				<div class="br-10 overflow-hidden">
					<div class="skeleton--img-lg js-skeleton" style="height: 181px;">
						<img src="{{getImage($value)}}" alt="{{$detail->name}}" width="362" height="181" class="w-100 js-skeleton-img">
					</div>
				</div>
			</div>
			@endforeach
		</div>
		@endif
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
								<span class="sales-label">Đã bán</span>
								<span class="sales-number">{{number_format($totalSold)}}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			@endforeach
		</div>
		@endif
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
							<div class="brand-btn">
								@if($product->brand)<a href="{{route('home.brand',['url' => $product->brand->slug])}}">{{$product->brand->name}}</a>@endif
							</div>
							<div class="product-name">
								<a href="{{getSlug($product->slug)}}">{{$product->name}}</a>
							</div>
							<div class="price">
								{!!checkSale($product->id)!!}
							</div>
							<div class="rating">
								{!!getStar($product->rates->sum('rate'),$product->rates->count())!!}
								<div class="count-rate">({{$product->rates->count()??'0'}})</div>
							</div>
						</div>
				</div>
			</div>
			@endforeach
		</div>
		@endif
	</div>
</section>
@endsection