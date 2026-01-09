<div class="title-wish">
	<h3>Giỏ hàng của tôi</h3>
	<a href="javascript:;" class="close-cart">x</a>
</div>
<div class="list-cart pl-20 pr-20 mt-3">
	@if(Session::has('cart'))
	@foreach($products as $variant)
	@php $product = App\Modules\Product\Models\Product::find($variant['item']['product_id']);@endphp
	@if(isset($product) && !empty($product))
	<div class="item-cart d-flex space-between mt-3 mb-3 item-cart-{{$variant['item']['id']}}">
		<div class="img-thumb">
			<img src="{{getImage($product->image)}}" width="60" height="60" alt="{{$product->name}}">
		</div>
		<div class="des-cart ms-2">
			<div class="header-cart d-flex space-between">
				<div>
					<a class="product-name fw-600 fs-12 d-block" href="{{getSlug($product->slug)}}">{{$product->name}}</a>
					<div class="fs-12 d-block">@if($variant['item']->color)<span class="me-3">Màu sắc: {{$variant['item']->color->name}}</span>@endif @if($variant['item']->size)<span>Kích thước: {{$variant['item']->size->name}}{{$variant['item']->size->unit}}</span>@endif</div>
				</div>
				<div class="action-del ms-2">
					<button class="remove-cart" type="button" data-id="{{$variant['item']['id']}}"><span role="img" aria-label="minus" class="icon"><svg viewBox="64 64 896 896" focusable="false" data-icon="minus" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M872 474H152c-4.4 0-8 3.6-8 8v60c0 4.4 3.6 8 8 8h720c4.4 0 8-3.6 8-8v-60c0-4.4-3.6-8-8-8z"></path></svg></span></button>
				</div>
			</div>
			<div class="d-flex space-between mt-1">
				<div class="quantity align-center">
                    <button class="btn_minus qtyminus" type="button" data-id="{{$variant['item']['id']}}">
                        <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                    </button>
                    <input type="text" name="" min="0" id="quantity-{{$variant['item']['id']}}" class="form-quatity" value="{{$variant['qty']}}">
                    <button class="btn_plus qtyplus" type="button" data-id="{{$variant['item']['id']}}">
                        <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                    </button>
                </div>
				<div class="price fw-600">
					<span class="fw-600 price-item-{{$variant['item']['id']}}">{{number_format($variant['price'])}}đ</span>
				</div>
			</div>
		</div>
	</div>
	@endif
	@endforeach
	@endif
</div>
<div class="divider-horizontal"></div>
<div class="bottom-cart pl-20 pr-20">
	<div class="align-center space-between">
		<label class="fw-600">Tạm tính</label>
		<div class="total-price fw-600">{{number_format($totalPrice)}}đ</div>
	</div>
</div>
<div class="mt-3 align-center space-between pl-20 pr-20 footer-cart">
	<a href="/cart/gio-hang" class="btn">Giỏ hàng</a>
	<a href="/cart/thanh-toan" class="btn bg-gradient">Thanh toán</a>
</div>