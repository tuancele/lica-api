@php $products = App\Modules\Product\Models\Product::select('id','name','slug','image','price','sale')->where([['status','1'],['type','product']])->where('cat_id','like','%'.$category->id.'%')->limit(6)->get(); @endphp
<div class="block_product products">
	<div class="title_">
        <h2>{{$category->name}}</h2>
        <a href="{{getSlug($category->slug)}}" class="view_all">Xem tất cả</a>
    </div>
    @if($products->count() > 0)
    <div class="list-product">
	    @foreach($products as $product)
		<div class="item-product text-center">
			<div class="image_">
				<a href="{{getSlug($product->slug)}}" class="box-image">
					<img src="{{getImage($product->image)}}" alt="{{$product->name}}">
				</a>
				<div class="addCart ajax_add_to_cart" data-id="{{$product->id}}"><i class="fa fa-cart-plus" aria-hidden="true"></i></div>
			</div>
			<div class="box_description">
				<h2><a href="{{getSlug($product->slug)}}">{{$product->name}}</a></h2>
				<p class="price">{!!getPrice($product->price,$product->sale)!!}</p>
			</div>
			{!!getSale($product->price,$product->sale)!!}
		</div>
		@endforeach
	</div>
    @endif
</div>