@extends('Website::layout',['image' => $detail->image,'class' => 'home'])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<link rel="stylesheet" href="/public/website/owl-carousel/owl.carousel-2.0.0.css">
<script src="/public/website/owl-carousel/owl.carousel-2.0.0.min.js"></script>
@endsection
@section('content')
@if(count($sliders) > 0)
<section class="section slider-section d-none d-md-block">
    <div class="container-lg">
        <div class="slider_home">
            @foreach($sliders as $slider)
            <a href="{{$slider->link}}">
                <img src="{{getImage($slider->image)}}" alt="{{$slider->name}}" width="100%" height="auto">
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@if(count($sliderms) > 0)
<section class="section slider-section d-block d-md-none">
    <div class="container-lg">
        <div class="slider_home">
            @foreach($sliderms as $sliderm)
            <a href="{{$sliderm->link}}">
                <img src="{{getImage($sliderm->image)}}" alt="{{$sliderm->name}}" width="100%" height="auto">
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@include('Website::product.flashsale')
@if(count($brands) > 0)
<section class="brand-shop mt-3">
    <div class="container-lg">
        <div class="list-brand">
        @foreach($brands as $brand)
        <div class="item-brand">
            <a class="box-icon" href="{{route('home.brand',['url' => $brand->slug])}}">
                <img class="br-5" src="{{getImage($brand->image)}}" alt="{{$brand->name}}" loading="lazy">
            </a>
        </div>
        @endforeach
        </div>
    </div>
</section>
@endif
@if(isset($deals) && count($deals) > 0)
<section class="product_home mt-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Top sản phẩm bán chạy</h2>
        <div class="list-watch mt-3">
            @foreach($deals as $deal)
            @include('Website::product.item',['product' => $deal])
            @endforeach
        </div>
    </div>
</section>
@endif
@if(count($banners) > 0)
<section class="banner-home mt-3">
    <div class="container-lg">
        <div class="list-banner">
            @foreach($banners as $banner)
            <a href="{{$banner->link}}">
                <img src="{{getImage($banner->image)}}" class="br-5" alt="{{$banner->name}}" loading="lazy">
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(count($categories) > 0)
<section class="product_home mt-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Danh mục nổi bật</h2>
        <div class="list-taxonomy mt-3">
            @foreach($categories as $category)
                <div class="col8 pt-2">
                    <a href="{{getSlug($category->slug)}}">
                    <div class="taxonomy-item">
                        <div class="taxonomy-cover">
                            <img src="{{getImage($category->image)}}" alt="{{$category->name}}" loading="lazy">
                        </div>
                        <div class="taxonomy-title">{{$category->name}}</div>
                    </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if(count($taxonomies) > 0)
@foreach($taxonomies as $tax_item)
@php 
    $taxonomy = $tax_item['info'];
    $child_tabs = $tax_item['child_tabs'];
    $initial_products = $tax_item['initial_products'];
@endphp
<section class="taxonomy-product mt-5">
     <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">{{$taxonomy->name}}</h2>
        @if(count($child_tabs) > 0)
        <ul class="nav nav-pills taxonomy-home mb-3 text-center" id="pills-tab-{{$taxonomy->id}}" role="tablist">
          @foreach($child_tabs as $p => $parent)
          <li class="nav-item" role="presentation">
            <button class="nav-link @if($p == 0) active @endif" data-slug="{{$parent->slug}}" data-id="{{$parent->id}}" id="taxonomy-tab-{{$parent->id}}" data-bs-toggle="pill" data-bs-target="#taxonomy-{{$parent->id}}" type="button" role="tab" aria-controls="taxonomy-{{$parent->id}}" aria-selected="true">{{$parent->name}}</button>
          </li>
          @endforeach
        </ul>
        <div class="tab-content" id="pills-tabTaxonomy-{{$taxonomy->id}}">
            @foreach($child_tabs as $p => $parent)
            <div class="tab-pane fade @if($p == 0) show active @endif" id="taxonomy-{{$parent->id}}" role="tabpanel" aria-labelledby="taxonomy-tab-{{$parent->id}}" tabindex="{{$p}}">
                @if($p == 0 && count($initial_products) > 0)
                <div class="list-watch mt-3">
                    @foreach($initial_products as $product)
                    @include('Website::product.item',['product' => $product])
                    @endforeach
                </div>
                <div class="text-center mt-3">
                    <a href="{{getSlug($parent->slug)}}" class="btn-view-all">Xem tất cả</a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        @if(count($initial_products) > 0)
        <div class="list-watch mt-3">
            @foreach($initial_products as $product)
            @include('Website::product.item',['product' => $product])
            @endforeach
        </div>
        @endif
        <div class="text-center mt-3">
            <a href="{{getSlug($taxonomy->slug)}}" class="btn-view-all">Xem tất cả</a>
        </div>
        @endif
    </div>
</section>
@endforeach
@endif

@if(count($searchs) > 0)
<section class="product_home mt-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Tìm kiếm nhiều nhất</h2>
        <ul class="list-search text-center mt-3">
            @foreach($searchs as $search)
            <li><a href="/tim-kiem?s={{$search->name}}">{{$search->name}}</a></li>
            @endforeach
        </ul>
    </div>
</section>
@endif
@if(isset($watchs) && count($watchs) > 0)
<section class="product_home mt-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Các mẫu bạn đã xem</h2>
        <div class="list-watch mt-3">
            @foreach($watchs as $watch)
            @include('Website::product.item',['product' => $watch])
            @endforeach
        </div>
    </div>
</section>
@endif

@if(count($blogs) > 0)
<section class="blogs pt-5 pb-5">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Tin tức</h2>
        <ul class="nav nav-pills mb-3 text-center" id="pills-tab-blog" role="tablist">
          @foreach($blogs as $b => $blog)
          <li class="nav-item" role="presentation">
            <button class="nav-link @if($b == 0) active @endif" id="category-tab-{{$blog->id}}" data-bs-toggle="pill" data-bs-target="#category-{{$blog->id}}" type="button" role="tab" aria-controls="category-{{$blog->id}}" aria-selected="true">{{$blog->name}}</button>
          </li>
          @endforeach
        </ul>
        <div class="tab-content" id="pills-tabContent-blog">
            @foreach($blogs as $b => $blog)
            <div class="tab-pane fade @if($b == 0) show active @endif" id="category-{{$blog->id}}" role="tabpanel" aria-labelledby="category-tab-{{$blog->id}}" tabindex="0">
                @php $posts = App\Modules\Post\Models\Post::select('name','slug','image','user_id','created_at','description','cat_id')->where([['status','1'],['type','post']])->whereIn('cat_id',$blog->arrayCate($blog->id,'category'))->latest()->limit(3)->get(); @endphp
                @if($posts->count() > 0)
                <div class="row">
                    @foreach($posts as $post)
                    <div class="col-12 col-md-4">
                        <div class="item-blog">
                            <a href="{{getSlug($post->slug)}}" class="box-image">
                                 <img src="{{getImage($post->image)}}" alt="{{$post->name}}" loading="lazy">
                            </a>
                            <div class="ps-3 pe-3 ps-md-0 pe-md-0 mt-2">
                                <h3 class="post-title"><a href="{{getSlug($post->slug)}}">{{$post->name}}</a></h3>
                                <p class="blog-excerpt mt-2">{{$post->description}}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="text-center mt-3">
                    <a href="{{getSlug($blog->slug)}}" class="btn-view-all">Xem tất cả</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
@section('footer')
<script>
     $(document).ready(function() {
    $(".slider_home").owlCarousel({
        loop:true,
        items : 1,
        margin:10,
        singleItem:true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplaySpeed: 1000,
        nav:true,
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
    });
    $('.list-watch').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        autoplay: true,
        dots:false,
        loop: true,
        autoWidth:true,
        responsive: {
            0: {
                items: 2,
                nav: true
            },
            768: {
                items: 3,
                nav: true
            },
            1000: {
                items: 4,
                nav: true,

            }
        }
    });
    $('.list-flash').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        autoplay: true,
        dots:false,
        loop: true,
        autoWidth:true,
        responsive: {
            0: {
                items: 2,
                nav: true
            },
            768: {
                items: 3,
                nav: true
            },
            1000: {
                items: 4,
                nav: true,

            }
        }
    });
    $('.list-brand').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        autoplay: true,
        dots:false,
        autoWidth:true,
        responsive: {
            0: {
                items: 2,
                nav: true
            },
            768: {
                items: 3,
                nav: true
            },
            1000: {
                items: 5,
                nav: true,
                loop: true
            }
        }
    });
    
    $('.list-banner').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        autoplay: true,
        dots:false,
        autoWidth:true,
        responsive: {
            0: {
                items: 1,
                nav: true
            },
            768: {
                items: 2,
                nav: true
            },
            1000: {
                items: 3,
                nav: true,
                loop: true
            }
        }
    });
    $('.taxonomy-home').on('click','.nav-link', function(){
        var id = $(this).attr('data-id');
        var slug = $(this).attr('data-slug');
        var targetPane = $('#taxonomy-'+id);
        if(targetPane.html().trim() == '') {
            $.ajax({
                type: 'post',
                url: '/ajax/get-owl',
                data: {id:id,slug:slug},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    targetPane.html(res);
                    // Re-init carousel for new content
                    targetPane.find('.list-watch').owlCarousel({
                        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
                        responsiveclass: true,
                        autoplay: true,
                        dots:false,
                        loop: true,
                        autoWidth:true,
                        responsive: { 0: { items: 2, nav: true }, 768: { items: 3, nav: true }, 1000: { items: 4, nav: true } }
                    });
                },
                error: function(xhr, status, error){
                    alert('Có lỗi xảy ra, xin vui lòng thử lại');
                }
            })
        }
    });
})
</script>
<style>
    .carousel-four {
          width: 537px;
        }
</style>
@endsection
