@extends('Website::layout',['image' => $detail->image,'class' => 'home'])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<!-- 资源预连接优化 -->
<link rel="preconnect" href="{{url('/')}}" crossorigin>
<link rel="dns-prefetch" href="{{url('/')}}">
<!-- CSS样式表 -->
<link rel="stylesheet" href="/public/website/owl-carousel/owl.carousel-2.0.0.css">
<!-- JavaScript延迟加载 -->
<script src="/public/website/owl-carousel/owl.carousel-2.0.0.min.js" defer></script>
@endsection
@section('content')
@if(count($sliders) > 0)
<section class="section slider-section d-none d-md-block">
    <div class="container-lg">
        <div class="slider_home">
            @foreach($sliders as $slider)
            <a href="{{$slider->link}}">
                <div class="skeleton--img-lg js-skeleton" style="min-height: 400px;">
                    <img src="{{getImage($slider->image)}}" alt="{{$slider->name}}" width="100%" height="auto" class="js-skeleton-img">
                </div>
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
                <div class="skeleton--img-lg js-skeleton" style="min-height: 300px;">
                    <img src="{{getImage($sliderm->image)}}" alt="{{$sliderm->name}}" width="100%" height="auto" class="js-skeleton-img">
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@include('Website::product.flashsale')
@if(count($categories) > 0)
<section class="product_home mt-5" data-lazy-load="section">
    <div class="container-lg">
        <div class="box-category-shop">
            <h2 class="fs-25 fw-bold text-uppercase text-center">Danh mục nổi bật</h2>
            <div class="lazy-placeholder" style="min-height: 200px; padding: 20px 0;">
                <div class="list-taxonomy-wrapper">
                    <div class="list-taxonomy mt-3 skeleton-container" style="justify-content: center;">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col8 pt-2">
                            <div class="skeleton-category">
                                <div class="skeleton-category-image"></div>
                                <div class="skeleton-category-name"></div>
                            </div>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="lazy-hidden-content" style="display: none;">
                <div class="list-taxonomy-wrapper">
                    <div class="list-taxonomy mt-3">
                        @foreach($categories as $category)
                            <div class="col8 pt-2">
                                <a href="{{getSlug($category->slug)}}">
                                <div class="taxonomy-item">
                                    <div class="taxonomy-cover">
                                        <div class="skeleton--img-square js-skeleton">
                                            <img src="{{getImage($category->image)}}" alt="{{$category->name}}" class="js-skeleton-img" loading="lazy">
                                        </div>
                                    </div>
                                    <div class="taxonomy-title">{{$category->name}}</div>
                                </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@if(count($brands) > 0)
<section class="brand-shop mt-3" data-lazy-load="section">
    <div class="container-lg">
        <div class="box-brand-shop">
            <h2 class="fs-25 fw-bold text-uppercase text-center mb-4">Thương hiệu nổi bật</h2>
            <div class="lazy-placeholder" style="min-height: 120px; padding: 20px;">
                <div class="list-brand skeleton-container brand-grid-2x7" style="justify-content: center;">
                    @for($i = 0; $i < 8; $i++)
                    <div class="item-brand">
                        <div class="box-icon">
                            <div class="skeleton-brand-logo"></div>
                        </div>
                        <div class="brand-name">
                            <div class="skeleton--text skeleton--brand-name shimmer"></div>
                        </div>
                    </div>
                    @endfor
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
                <div class="list-brand brand-grid-no-carousel brand-grid-2x7">
                @foreach($brands->take(14) as $brand)
            <div class="item-brand">
                <a class="box-icon" href="{{route('home.brand',['url' => $brand->slug])}}">
                        <div class="skeleton--img-square js-skeleton brand-icon-square">
                            <img class="js-skeleton-img" src="{{getImage($brand->image)}}" alt="{{$brand->name}}" loading="lazy">
                        </div>
                    </a>
                    <div class="brand-name">
                        <a href="{{route('home.brand',['url' => $brand->slug])}}">{{$brand->name}}</a>
                    </div>
            </div>
            @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif
@if(isset($deals) && count($deals) > 0)
<section class="product_home mt-5" data-lazy-load="section">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Top sản phẩm bán chạy</h2>
        <div class="lazy-placeholder" style="min-height: 400px; padding: 20px 0;">
            <div class="list-watch mt-3 deals-grid-2x5 skeleton-container skeleton-grid">
                @for($i = 0; $i < 6; $i++)
                @include('Website::product.skeleton-item')
                @endfor
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
            <div class="list-watch mt-3 deals-grid-2x5 deals-no-carousel">
                @foreach($deals->take(10) as $deal)
                @include('Website::product.item',['product' => $deal])
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
@if(count($banners) > 0)
<section class="banner-home mt-3" data-lazy-load="section">
    <div class="container-lg">
        <div class="lazy-placeholder" style="min-height: 200px; padding: 20px 0;">
            <div class="list-banner skeleton-container" style="justify-content: center;">
                @for($i = 0; $i < 3; $i++)
                <div class="skeleton-banner"></div>
                @endfor
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
            <div class="list-banner" data-carousel-type="banner">
                @foreach($banners as $banner)
                <a href="{{$banner->link}}">
                    <div class="skeleton--img-lg js-skeleton br-5" style="min-height: 200px;">
                        <img src="{{getImage($banner->image)}}" class="br-5 js-skeleton-img" alt="{{$banner->name}}" loading="lazy">
                    </div>
                </a>
                @endforeach
            </div>
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
    // 需要隐藏的分类名称列表
    $hidden_categories = ['Sữa rửa mặt', 'Sữa  rửa mặt', 'Kem dưỡng ẩm', 'Dầu gội', 'Tẩy tế bào chết', 'Kem chống nắng'];
    // 使用trim()来确保比较时忽略多余空格
    $taxonomy_name_trimmed = trim($taxonomy->name);
    $is_hidden = false;
    foreach($hidden_categories as $hidden) {
        if(trim($hidden) === $taxonomy_name_trimmed) {
            $is_hidden = true;
            break;
        }
    }
@endphp
@if(!$is_hidden)
<section class="taxonomy-product mt-5" data-lazy-load="section">
     <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">{{$taxonomy->name}}</h2>
        <div class="lazy-placeholder" style="min-height: 300px; padding: 20px 0;">
            <div class="list-watch mt-3 skeleton-container" style="justify-content: center;">
                @for($i = 0; $i < 4; $i++)
                @include('Website::product.skeleton-item')
                @endfor
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
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
    </div>
</section>
@endif
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

<!-- 智能推荐产品区块 - 4行 x 6项 = 24个产品骨架屏 -->
<section class="product_home mt-5" data-lazy-load="section">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Gợi ý cho bạn</h2>
            <div class="lazy-placeholder" style="min-height: 400px; padding: 20px 0;">
            <div class="list-flash mt-3 product-recommendations-home recommendations-grid-3x6 skeleton-grid recommendations-no-carousel" 
                 data-exclude=""
                 data-limit="18"
                 data-loaded="0"
                 data-per-load="12">
                @for($i = 0; $i < 6; $i++)
                @include('Website::product.skeleton-item')
                @endfor
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
            <div class="list-flash mt-3 product-recommendations-home recommendations-grid-3x6 recommendations-no-carousel" 
                 data-exclude=""
                 data-limit="18"
                 data-loaded="0"
                 data-per-load="12">
                <div class="recommendations-loading text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải sản phẩm đề xuất...</span>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4 recommendations-load-more-wrapper" style="display: none;">
                <button type="button" class="btn btn-primary recommendations-load-more-btn">
                    <span class="btn-text">Xem thêm</span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Đang tải...
                    </span>
                </button>
            </div>
        </div>
    </div>
</section>

{{-- Tin tức 区块已删除 --}}

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
    $('.list-watch').each(function() {
        // 跳过在 lazy-hidden-content 中且隐藏的元素（由 lazy-load.js 处理）
        if ($(this).closest('.lazy-hidden-content').length > 0 && !$(this).closest('.lazy-hidden-content').is(':visible')) {
            return;
        }
        // 跳过不使用carousel的Top sản phẩm bán chạy
        if ($(this).hasClass('deals-no-carousel')) {
            return;
        }
        if (!$(this).data('owlCarousel')) {
            $(this).owlCarousel({
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
                items: 6,
                nav: true,

            }
        }
    });
    $('.list-brand').each(function() {
        // 跳过在 lazy-hidden-content 中且隐藏的元素（由 lazy-load.js 处理）
        if ($(this).closest('.lazy-hidden-content').length > 0 && !$(this).closest('.lazy-hidden-content').is(':visible')) {
            return;
        }
        if (!$(this).data('owlCarousel')) {
            $(this).owlCarousel({
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
        }
    });
    
    $('.list-banner').each(function() {
        // 跳过在 lazy-hidden-content 中且隐藏的元素（由 lazy-load.js 处理）
        if ($(this).closest('.lazy-hidden-content').length > 0 && !$(this).closest('.lazy-hidden-content').is(':visible')) {
            return;
        }
        if (!$(this).data('owlCarousel')) {
            $(this).owlCarousel({
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
    
    /* 推荐产品3行x6列网格布局 - 类似Flashsale */
    @media (min-width: 1000px) {
        .recommendations-grid-3x6 {
            display: grid !important;
            grid-template-columns: repeat(6, 1fr) !important;
            grid-auto-rows: auto !important;
            gap: 20px !important;
        }
        .recommendations-grid-3x6 .item-product {
            min-width: 190px !important;
            width: 190px !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            border-radius: 10px;
            padding: 0px;
            height: 380px;
        }
        .recommendations-grid-3x6 .item-product img {
            height: 100%;
            width: initial;
            display: inline-block;
        }
        .recommendations-grid-3x6 .card-cover {
            text-align: center;
            height: 168px;
        }
        .recommendations-grid-3x6 .card-cover a {
            display: block;
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
        .recommendations-grid-3x6 .item-product .status-product {
            left: 10px;
            top: 10px;
        }
        .recommendations-grid-3x6 .item-product .btn_login_wishlist {
            top: 10px;
            right: 10px;
        }
        .recommendations-grid-3x6 .item-product .card-content {
            padding-left: 10px;
            padding-right: 10px;
        }
        /* 6x6布局保持原样 */
        .recommendations-grid-6x6 {
            display: grid !important;
            grid-template-columns: repeat(6, 1fr) !important;
            grid-template-rows: repeat(6, auto) !important;
            gap: 20px !important;
        }
        .recommendations-grid-6x6 .item-product {
            min-width: 190px !important;
            width: 190px !important;
            margin-right: 0 !important;
            margin-bottom: 0 !important;
            border-radius: 10px;
            padding: 0px;
            height: 380px;
        }
        .recommendations-grid-6x6 .item-product img {
            height: 100%;
            width: initial;
            display: inline-block;
        }
        .recommendations-grid-6x6 .card-cover {
            text-align: center;
            height: 168px;
        }
        .recommendations-grid-6x6 .card-cover a {
            display: block;
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
        .recommendations-grid-6x6 .item-product .status-product {
            left: 10px;
            top: 10px;
        }
        .recommendations-grid-6x6 .item-product .btn_login_wishlist {
            top: 10px;
            right: 10px;
        }
        .recommendations-grid-6x6 .item-product .card-content {
            padding-left: 10px;
            padding-right: 10px;
        }
    }
    
    @media (max-width: 999px) {
        .recommendations-grid-3x6,
        .recommendations-grid-6x6 {
            display: block !important;
        }
        .recommendations-grid-3x6 .item-product,
        .recommendations-grid-6x6 .item-product {
            min-width: 160px !important;
            width: 160px !important;
            margin-right: 10px !important;
            height: auto !important;
        }
        .recommendations-grid-3x6 .item-product img,
        .recommendations-grid-6x6 .item-product img {
            height: 100%;
            width: initial;
            display: inline-block;
        }
        .recommendations-grid-3x6 .card-cover,
        .recommendations-grid-6x6 .card-cover {
            text-align: center;
            height: 168px;
        }
        .recommendations-grid-3x6 .card-cover a,
        .recommendations-grid-6x6 .card-cover a {
            display: block;
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
    }
</style>
<script>
    // 推荐产品网格布局初始化
    $(document).ready(function() {
        function initRecommendationsGrid() {
            if (window.innerWidth >= 1000) {
                // 3x6布局（自动扩展行数）
                $('.recommendations-grid-3x6').css({
                    'display': 'grid',
                    'grid-template-columns': 'repeat(6, 1fr)',
                    'grid-auto-rows': 'auto',
                    'gap': '20px'
                });
                $('.recommendations-grid-3x6 .item-product').css({
                    'min-width': '190px',
                    'width': '190px',
                    'margin-right': '0',
                    'margin-bottom': '0'
                });
                // 6x6布局
                $('.recommendations-grid-6x6').css({
                    'display': 'grid',
                    'grid-template-columns': 'repeat(6, 1fr)',
                    'grid-template-rows': 'repeat(6, auto)',
                    'gap': '20px'
                });
                $('.recommendations-grid-6x6 .item-product').css({
                    'min-width': '190px',
                    'width': '190px',
                    'margin-right': '0',
                    'margin-bottom': '0'
                });
            } else {
                $('.recommendations-grid-3x6 .item-product, .recommendations-grid-6x6 .item-product').css({
                    'min-width': '160px',
                    'width': '160px',
                    'margin-right': '10px'
                });
            }
        }
        
        initRecommendationsGrid();
        
        $(window).on('resize', function() {
            initRecommendationsGrid();
        });
    });
</script>
@endsection
