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
					<button class="btn-quickview" data-id="{{$product->id}}" type="button">Xem nhanh</button>
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