<div class="title-wish">
	<h3>Ưa thích</h3>
	<a href="javascript:;" class="remove-all-wishlist">Xóa hết</a>
</div>
<div class="list-wishlist">
	@if($list->count() > 0)
	@foreach($list as $value)
	@if($value->product)
	<div class="item-wishlist">
		<div class="img-thumb">
			<div class="skeleton--img-sm js-skeleton">
				<img src="{{getImage($value->product->image)}}" alt="{{$value->product->name}}" class="js-skeleton-img">
			</div>
		</div>
		<div class="des-wishlist">
			<a class="product-name" href="{{getSlug($value->product->slug)}}">{{$value->product->name}}</a>
			<div class="bottom-wishlist">
				<div class="price">
					{!!checkSale($value->product->id)!!}
				</div>
				<a class="remove-wishlist" href="javascript:;" data-id="{{$value->product->id}}">Xóa</a>
			</div>
		</div>
	</div>
	@endif
	@endforeach
	@else
	<p class="none-wishlist">Nhấn nút trái tim ở mỗi sản phẩm để lưu vào ưa thích</p>
	@endif
</div>