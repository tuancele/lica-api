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
@else
<p class="mt-3">Không tìm thấy dữ liệu nào</p>
@endif