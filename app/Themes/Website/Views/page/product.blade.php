@extends('Website::layout',['image' => $detail->image,'canonical'=>getSlug($detail->slug)])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
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
                                <img src="{{getImage($product->image)}}" alt="{{$product->name}}" width="212" height="212">
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
        {{$products->links()}}
        @endif
        </div>
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
            data: {sort:sort,url:url,cat_id:'',page:'product'},
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
            data: {origin:origin,brand:brand,color:color,size:size,url:url,cat_id:'',price:price,page:'product',orderby:''},
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
            }
        })
    });
</script>
@endsection