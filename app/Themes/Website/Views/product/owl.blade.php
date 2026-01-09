@if($products->count() > 0)
<div class="list-watch g-scrolling-carousel mt-3"><div class="items">
@foreach($products as $product)
@include('Website::product.item',['product' => $product])
@endforeach
</div>
</div>
@endif
<div class="text-center mt-3">
    <a href="{{getSlug($slug)}}" class="btn-view-all">Xem tất cả</a>
</div>
