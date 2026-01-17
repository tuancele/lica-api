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