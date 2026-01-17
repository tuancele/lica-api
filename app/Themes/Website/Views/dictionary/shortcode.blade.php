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