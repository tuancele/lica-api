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
<!-- Desktop Slider - Loaded via API V1 -->
<section class="section slider-section d-none d-md-block" id="desktop-slider-section" style="display: none;">
    <div class="container-lg">
        <div class="slider_home" id="desktop-slider-container">
            <!-- Sliders will be loaded via API -->
        </div>
    </div>
</section>
<!-- Mobile Slider - Loaded via API V1 -->
<section class="section slider-section d-block d-md-none" id="mobile-slider-section" style="display: none;">
    <div class="container-lg">
        <div class="slider_home" id="mobile-slider-container">
            <!-- Sliders will be loaded via API -->
        </div>
    </div>
</section>
<!-- Flash Sale 区块 - 通过 API 加载 -->
<section class="flashsale" id="flash-sale-section" data-lazy-load="section">
    <div class="container-lg">
        <div class="box-flashsale">
            <div class="head-flashsale d-md-flex d-block">
                <div class="icon_flash"><img src="/public/image/flashsale.webp" alt=""></div>
                <div class="run-time">
                    <div class="title_time">Thời gian còn lại</div>
                    <div class="box-time">
                        <div>00<span>Ngày</span></div><div>00<span>GIỜ</span></div><div>00<span>PHÚT</span></div><div>00<span>GIÂY </span></div>
                    </div>
                </div>
                <a href="/flash-sale-hot" class="d-none d-md-inline-block view_flash">Xem tất cả</a>
            </div>
            <div class="lazy-placeholder" style="min-height: 300px; padding: 20px 0;">
                <div class="list-flash mt-3 product-recommendations-home recommendations-grid-3x6 skeleton-grid">
                    @for($i = 0; $i < 6; $i++)
                    @include('Website::product.skeleton-item')
                    @endfor
                </div>
            </div>
            <div class="lazy-hidden-content" style="display: none;">
                <div class="list-flash mt-3 flash-sale-carousel owl-carousel owl-theme" id="flash-sale-products">
                    <!-- Flash Sale 产品将通过 API 动态加载 -->
                </div>
            </div>
            <div class="text-center d-block d-md-none">
                <a href="/flash-sale-hot" class="view_flash">Xem tất cả</a>
            </div>
        </div>
    </div>
</section>
<section class="product_home mt-5" data-lazy-load="section" id="featured-categories-section">
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
                    <div class="list-taxonomy mt-3" id="categories-list">
                        <!-- 分类将通过 API 动态加载 -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Thương hiệu nổi bật - 通过 API V1 加载 -->
<section class="brand-shop mt-3" data-lazy-load="section" id="featured-brands-section">
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
                <div class="list-brand brand-grid-no-carousel brand-grid-2x7" id="brands-list">
                    <!-- 品牌将通过 API V1 动态加载 -->
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Top sản phẩm bán chạy - 通过 API 加载 -->
<section class="product_home mt-5" data-lazy-load="section" id="top-selling-section">
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
            <div class="list-watch mt-3 deals-grid-2x5 deals-no-carousel" id="top-selling-products">
                <!-- 产品将通过 API 动态加载 -->
            </div>
        </div>
    </div>
</section>
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
<section class="taxonomy-product mt-5" data-lazy-load="section" data-taxonomy-id="{{$taxonomy->id}}">
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
            <div class="tab-pane fade @if($p == 0) show active @endif" id="taxonomy-{{$parent->id}}" role="tabpanel" aria-labelledby="taxonomy-tab-{{$parent->id}}" tabindex="{{$p}}" data-category-id="{{$parent->id}}" data-category-slug="{{$parent->slug}}">
                @if($p == 0)
                <div class="list-watch mt-3 taxonomy-products-{{$parent->id}}">
                    <!-- 产品将通过 API 动态加载 -->
                </div>
                <div class="text-center mt-3">
                    <a href="{{getSlug($parent->slug)}}" class="btn-view-all">Xem tất cả</a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @else
        <div class="list-watch mt-3 taxonomy-products-{{$taxonomy->id}}" data-category-id="{{$taxonomy->id}}" data-category-slug="{{$taxonomy->slug}}">
            <!-- 产品将通过 API 动态加载 -->
        </div>
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
    // 确保 skeleton 立即可见 - 初始化 skeleton 显示
    (function() {
        // 确保所有 lazy-placeholder 立即可见
        document.querySelectorAll('.lazy-placeholder').forEach(function(placeholder) {
            const section = placeholder.closest('[data-lazy-load]');
            if (section && section.style.display === 'none') {
                // 如果 section 被隐藏，显示它以便 skeleton 可见
                section.style.display = '';
            }
            // 确保 placeholder 可见
            if (placeholder.style.display === 'none') {
                placeholder.style.display = '';
            }
        });
        
        // 初始化 skeleton 优化器（如果已加载）
        if (window.initSmartSkeleton) {
            setTimeout(function() {
                window.initSmartSkeleton();
            }, 100);
        }
    })();
    
    // 通过 API 加载热门分类（与懒加载系统集成）
    function loadFeaturedCategories() {
        const categoriesSection = $('#featured-categories-section');
        const categoriesList = $('#categories-list');
        const hiddenContent = categoriesSection.find('.lazy-hidden-content');
        let isLoading = false;
        let hasLoaded = false;
        
        if (categoriesSection.length === 0 || categoriesList.length === 0) {
            console.warn('分类区块元素未找到');
            return;
        }
        
        // 加载分类数据的函数
        function loadCategoriesData() {
            // 防止重复加载
            if (isLoading || hasLoaded) {
                return;
            }
            
            // 如果隐藏内容未显示，等待懒加载系统
            if (hiddenContent.length > 0 && !hiddenContent.is(':visible')) {
                return;
            }
            
            // 如果已经有内容，不再加载
            if (categoriesList.children().length > 0) {
                hasLoaded = true;
                return;
            }
            
            isLoading = true;
            console.log('开始加载分类数据...');
            
            $.ajax({
                url: '/api/categories/featured',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    isLoading = false;
                    hasLoaded = true;
                    
                    console.log('分类数据加载成功:', response);
                    
                    // 兼容新旧两种响应格式
                    const isSuccess = (response.success === true || response.status === 'success');
                    const categories = response.data || [];
                    
                    if (isSuccess && categories && categories.length > 0) {
                        let html = '';
                        categories.forEach(function(category) {
                            html += '<div class="col8 pt-2">';
                            html += '<a href="' + (category.url || '/' + category.slug) + '">';
                            html += '<div class="taxonomy-item">';
                            html += '<div class="taxonomy-cover">';
                            html += '<div class="skeleton--img-square js-skeleton">';
                            html += '<img src="' + (category.image || '') + '" alt="' + (category.name || '') + '" class="js-skeleton-img" loading="lazy">';
                            html += '</div>';
                            html += '</div>';
                            html += '<div class="taxonomy-title">' + (category.name || '') + '</div>';
                            html += '</div>';
                            html += '</a>';
                            html += '</div>';
                        });
                        categoriesList.html(html);
                        
                        // 初始化图片懒加载和 skeleton 优化器
                        categoriesList.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            const imgSrc = img.attr('src');
                            
                            if (imgSrc && imgSrc !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                                
                                // 确保图片加载后初始化 skeleton
                                img.on('load', function() {
                                    if (window.initSmartSkeleton) {
                                        window.initSmartSkeleton();
                                    }
                                });
                                
                                // 图片加载失败时也初始化 skeleton（使用默认尺寸）
                                img.on('error', function() {
                                    if (window.initSmartSkeleton) {
                                        window.initSmartSkeleton();
                                    }
                                });
                            } else {
                                // 如果没有图片源，隐藏 skeleton 容器
                                img.closest('.js-skeleton').hide();
                            }
                        });
                        
                        // 初始化 skeleton 优化器
                        if (window.initSmartSkeleton) {
                            setTimeout(function() {
                                window.initSmartSkeleton();
                            }, 100);
                        }
                    } else {
                        console.warn('没有找到分类数据');
                        categoriesList.html('<div class="text-center py-4">暂无分类数据</div>');
                    }
                },
                error: function(xhr, status, error) {
                    isLoading = false;
                    console.error('加载分类数据失败:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusCode: xhr.status
                    });
                    categoriesList.html('<div class="text-center py-4 text-danger">加载分类数据失败，请刷新页面重试</div>');
                }
            });
        }
        
        // 使用 MutationObserver 监听 lazy-hidden-content 的显示状态
        if (hiddenContent.length > 0) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const isVisible = hiddenContent.is(':visible');
                        if (isVisible && !hasLoaded) {
                            loadCategoriesData();
                        }
                    }
                });
            });
            
            observer.observe(hiddenContent[0], {
                attributes: true,
                attributeFilter: ['style']
            });
            
            // 立即检查一次
            setTimeout(function() {
                if (hiddenContent.is(':visible')) {
                    loadCategoriesData();
                }
            }, 200);
            
            // 定期检查（作为备用方案，最多检查20次，每次间隔500ms）
            let checkCount = 0;
            const checkInterval = setInterval(function() {
                checkCount++;
                if (hiddenContent.is(':visible') && !hasLoaded) {
                    loadCategoriesData();
                }
                if (checkCount >= 20 || hasLoaded) {
                    clearInterval(checkInterval);
                }
            }, 500);
        } else {
            // 如果没有懒加载系统，直接加载
            loadCategoriesData();
        }
    }
    
    // 初始化分类加载
    loadFeaturedCategories();
    
    // ============================================================================
    // 加载 Thương hiệu nổi bật (Featured Brands) - API V1
    // ============================================================================
    function loadFeaturedBrands() {
        const brandsSection = $('#featured-brands-section');
        const brandsList = $('#brands-list');
        const hiddenContent = brandsSection.find('.lazy-hidden-content');
        let isLoading = false;
        let hasLoaded = false;
        
        if (brandsSection.length === 0 || brandsList.length === 0) {
            console.warn('品牌区块元素未找到');
            return;
        }
        
        // 加载品牌数据的函数
        function loadBrandsData() {
            // 防止重复加载
            if (isLoading || hasLoaded) {
                return;
            }
            
            // 如果隐藏内容未显示，等待懒加载系统
            if (hiddenContent.length > 0 && !hiddenContent.is(':visible')) {
                return;
            }
            
            // 如果已经有内容，不再加载
            if (brandsList.children().length > 0) {
                hasLoaded = true;
                return;
            }
            
            isLoading = true;
            console.log('开始加载品牌数据...');
            
            $.ajax({
                url: '/api/v1/brands/featured',
                method: 'GET',
                data: { limit: 14 },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    isLoading = false;
                    hasLoaded = true;
                    
                    console.log('品牌数据加载成功:', response);
                    
                    // 兼容新旧两种响应格式
                    const isSuccess = (response.success === true || response.status === 'success');
                    const brands = response.data || [];
                    
                    if (isSuccess && brands && brands.length > 0) {
                        let html = '';
                        brands.forEach(function(brand) {
                            const brandSlug = brand.slug || '';
                            const brandUrl = brandSlug ? '/thuong-hieu/' + brandSlug : '#';
                            const brandImage = brand.image || '/public/image/no_image.png';
                            const brandName = brand.name || '';
                            
                            html += '<div class="item-brand">';
                            html += '<a class="box-icon" href="' + brandUrl + '">';
                            html += '<div class="skeleton--img-square js-skeleton brand-icon-square">';
                            html += '<img class="js-skeleton-img" src="' + brandImage + '" alt="' + brandName + '" loading="lazy">';
                            html += '</div>';
                            html += '</a>';
                            html += '<div class="brand-name">';
                            html += '<a href="' + brandUrl + '">' + brandName + '</a>';
                            html += '</div>';
                            html += '</div>';
                        });
                        brandsList.html(html);
                        
                        // 初始化图片懒加载
                        brandsList.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            if (img.attr('src') && img.attr('src') !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                            }
                        });
                    } else {
                        console.warn('没有找到品牌数据');
                        brandsList.html('<div class="text-center py-4">暂无品牌数据</div>');
                    }
                },
                error: function(xhr, status, error) {
                    isLoading = false;
                    console.error('加载品牌数据失败:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusCode: xhr.status
                    });
                    brandsList.html('<div class="text-center py-4 text-danger">加载品牌数据失败，请刷新页面重试</div>');
                }
            });
        }
        
        // 使用 MutationObserver 监听 lazy-hidden-content 的显示状态
        if (hiddenContent.length > 0) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const isVisible = hiddenContent.is(':visible');
                        if (isVisible && !hasLoaded) {
                            loadBrandsData();
                        }
                    }
                });
            });
            
            observer.observe(hiddenContent[0], {
                attributes: true,
                attributeFilter: ['style']
            });
            
            // 立即检查一次
            setTimeout(function() {
                if (hiddenContent.is(':visible')) {
                    loadBrandsData();
                }
            }, 200);
            
            // 定期检查（作为备用方案，最多检查20次，每次间隔500ms）
            let checkCount = 0;
            const checkInterval = setInterval(function() {
                checkCount++;
                if (hiddenContent.is(':visible') && !hasLoaded) {
                    loadBrandsData();
                }
                if (checkCount >= 20 || hasLoaded) {
                    clearInterval(checkInterval);
                }
            }, 500);
        } else {
            // 如果没有懒加载系统，直接加载
            loadBrandsData();
        }
    }
    
    // 初始化品牌加载
    loadFeaturedBrands();
    
    // ============================================================================
    // 加载 Slider (Desktop & Mobile) - API V1
    // ============================================================================
    function loadSliders() {
        // Load desktop sliders
        function loadDesktopSliders() {
            const container = $('#desktop-slider-container');
            const section = $('#desktop-slider-section');
            
            if (container.length === 0) {
                return;
            }
            
            // Check if already loaded
            if (container.children().length > 0) {
                return;
            }
            
            $.ajax({
                url: '/api/v1/sliders',
                method: 'GET',
                data: { display: 'desktop' },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(slider) {
                            const link = slider.link || '#';
                            const image = slider.image || '/public/image/no_image.png';
                            const name = slider.name || '';
                            
                            html += '<a href="' + link + '">';
                            html += '<div class="skeleton--img-lg js-skeleton" style="min-height: 400px;">';
                            html += '<img src="' + image + '" alt="' + name + '" width="100%" height="auto" class="js-skeleton-img">';
                            html += '</div>';
                            html += '</a>';
                        });
                        
                        container.html(html);
                        section.show();
                        
                        // Initialize owl-carousel for desktop slider
                        if (typeof $.fn.owlCarousel !== 'undefined') {
                            // Destroy existing carousel if any
                            if (container.data('owlCarousel')) {
                                container.trigger('destroy.owl.carousel');
                            }
                            
                            container.owlCarousel({
                                loop: true,
                                items: 1,
                                margin: 10,
                                singleItem: true,
                                autoplay: true,
                                autoplayTimeout: 5000,
                                autoplaySpeed: 1000,
                                nav: true,
                                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                            });
                        }
                        
                        // Initialize image lazy loading
                        container.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            if (img.attr('src') && img.attr('src') !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                            }
                        });
                    } else {
                        section.hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('加载desktop slider失败:', error);
                    section.hide();
                }
            });
        }
        
        // Load mobile sliders
        function loadMobileSliders() {
            const container = $('#mobile-slider-container');
            const section = $('#mobile-slider-section');
            
            if (container.length === 0) {
                return;
            }
            
            // Check if already loaded
            if (container.children().length > 0) {
                return;
            }
            
            $.ajax({
                url: '/api/v1/sliders',
                method: 'GET',
                data: { display: 'mobile' },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    if (response.success && response.data && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(slider) {
                            const link = slider.link || '#';
                            const image = slider.image || '/public/image/no_image.png';
                            const name = slider.name || '';
                            
                            html += '<a href="' + link + '">';
                            html += '<div class="skeleton--img-lg js-skeleton" style="min-height: 300px;">';
                            html += '<img src="' + image + '" alt="' + name + '" width="100%" height="auto" class="js-skeleton-img">';
                            html += '</div>';
                            html += '</a>';
                        });
                        
                        container.html(html);
                        section.show();
                        
                        // Initialize owl-carousel for mobile slider
                        if (typeof $.fn.owlCarousel !== 'undefined') {
                            // Destroy existing carousel if any
                            if (container.data('owlCarousel')) {
                                container.trigger('destroy.owl.carousel');
                            }
                            
                            container.owlCarousel({
                                loop: true,
                                items: 1,
                                margin: 10,
                                singleItem: true,
                                autoplay: true,
                                autoplayTimeout: 5000,
                                autoplaySpeed: 1000,
                                nav: true,
                                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                            });
                        }
                        
                        // Initialize image lazy loading
                        container.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            if (img.attr('src') && img.attr('src') !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                            }
                        });
                    } else {
                        section.hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('加载mobile slider失败:', error);
                    section.hide();
                }
            });
        }
        
        // Load both sliders immediately
        loadDesktopSliders();
        loadMobileSliders();
    }
    
    // Initialize slider loading
    loadSliders();
    
    // ============================================================================
    // 产品卡片渲染函数
    // ============================================================================
    function renderProductCard(product, options = {}) {
        // options can contain: { excludeDeal: false }
        const excludeDeal = options.excludeDeal || false;
        const slug = product.slug || '';
        const productUrl = slug.startsWith('http') ? slug : (slug.startsWith('/') ? slug : '/' + slug);
        const image = product.image || '/public/image/no_image.png';
        const name = product.name || '';
        const price = parseFloat(product.price) || 0;
        const sale = parseFloat(product.sale) || 0;
        const priceSale = parseFloat(product.price_sale) || 0; // Flash Sale price
        const stock = parseInt(product.stock) || 0;
        const best = parseInt(product.best) || 0;
        const isNew = parseInt(product.is_new) || 0;
        const productId = product.id || 0;
        
        // 计算折扣百分比 - 使用 price_info từ API (Flash Sale > Marketing Campaign > Sale > Normal)
        let discountPercent = 0;
        let finalPrice = price;
        let hasDiscount = false;
        let isFlashSale = false;
        
        // Priority: price_info (from API) > price_sale (backward compatibility) > sale > Normal Price
        if (product.price_info) {
            // Use price_info from API (already calculated with correct priority: Flash Sale > Marketing Campaign > Sale > Normal)
            finalPrice = parseFloat(product.price_info.price) || price;
            discountPercent = product.price_info.discount_percent || 0;
            hasDiscount = discountPercent > 0;
            isFlashSale = product.price_info.type === 'flashsale';
        } else if (priceSale > 0 && priceSale < price) {
            // Flash Sale price (backward compatibility)
            discountPercent = Math.round((price - priceSale) / (price / 100));
            finalPrice = priceSale;
            hasDiscount = true;
            isFlashSale = true;
        } else if (sale > 0 && sale < price) {
            // Regular sale price
            discountPercent = Math.round((price - sale) / (price / 100));
            finalPrice = sale;
            hasDiscount = true;
        }
        
        // 格式化价格
        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        let priceHtml = '';
        if (hasDiscount) {
            priceHtml = '<p>' + formatPrice(finalPrice) + 'đ</p><del>' + formatPrice(price) + 'đ</del><div class="tag"><span>-' + discountPercent + '%</span></div>';
        } else {
            priceHtml = '<p>' + formatPrice(price) + 'đ</p>';
        }
        
        // 生成产品卡片 HTML
        let html = '<div class="item-product text-center">';
        html += '<div class="card-cover">';
        html += '<a href="' + productUrl + '">';
        html += '<div class="skeleton--img-md js-skeleton">';
        html += '<img src="' + image + '" alt="' + name + '" width="212" height="212" class="js-skeleton-img" loading="lazy">';
        html += '</div>';
        html += '</a>';
        html += '<div class="group-wishlist-' + productId + '">';
        // Wishlist 按钮将通过 AJAX 加载
        html += '<button class="btn_login_wishlist" type="button" data-bs-toggle="modal" data-bs-target="#myLogin"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></button>';
        html += '</div>';
        
        if (hasDiscount && discountPercent > 0) {
            // Flash Sale tag removed - only show discount percentage
            html += '<div class="tag tag-discount"><span>-' + discountPercent + '%</span></div>';
        }
        
        if (stock === 0) {
            html += '<div class="out-stock">Hết hàng</div>';
        }
        
        html += '<div class="status-product">';
        if (best) {
            html += '<div class="deal-hot mb-2">Deal<br/>Hot</div>';
        }
        if (isNew) {
            html += '<div class="is-new mb-2">Mới</div>';
        }
        html += '</div>';
        html += '</div>';
        
        html += '<div class="card-content mt-2">';
        html += '<div class="price">' + priceHtml + '</div>';
        html += '<div class="brand-btn">';
        // 检查品牌信息
        const brandName = product.brand_name;
        const brandSlug = product.brand_slug;
        const brandId = product.brand_id;
        
        if (brandName && brandName !== null && brandName !== '' && brandName !== 'null') {
            const brandUrl = brandSlug ? '/thuong-hieu/' + brandSlug : '#';
            html += '<a href="' + brandUrl + '">' + brandName + '</a>';
        } else if (brandId) {
            // 如果只有 brand_id 但没有品牌名称，记录警告
            console.warn('产品有 brand_id 但缺少品牌名称:', {
                product_id: productId,
                brand_id: brandId,
                product: product
            });
        }
        html += '</div>';
        html += '<div class="product-name">';
        html += '<a href="' + productUrl + '">' + name + '</a>';
        html += '</div>';
        
        // Deal voucher 信息 - 只在非 Flash Sale block 中显示
        if (product.deal && product.deal.name && !excludeDeal) {
            const dealDiscount = product.deal.discount_percent || 0;
            html += '<div class="deal-voucher">';
            if (dealDiscount > 0) {
                html += '<div class="deal-discount-badge">' + dealDiscount + '%</div>';
            }
            html += '<span class="deal-name">' + product.deal.name + '</span>';
            html += '</div>';
        }
        
        html += '<div class="rating-info">';
        html += '<div class="rating-score">';
        html += '<span class="rating-value">0.0</span>';
        html += '<span class="rating-count">(0)</span>';
        html += '</div>';
        html += '<div class="sales-count">';
        html += '<span class="sales-label">Đã bán 0/tháng</span>';
        html += '</div>';
        html += '</div>';
        
        // Add Flash Sale progress bar after rating-info
        if (product.flash_sale) {
            const remaining = product.flash_sale.remaining || 0;
            const total = product.flash_sale.number || 1;
            const percent = Math.ceil((remaining / total) * 100);
            html += '<div class="process-buy"><span style="width:' + percent + '%" class="process-status"></span><span class="process-title">Còn ' + remaining + ' sản phẩm</span></div>';
        }
        
        html += '</div>';
        html += '</div>';
        
        return html;
    }
    
    // ============================================================================
    // 加载 Top sản phẩm bán chạy
    // ============================================================================
    function loadTopSellingProducts() {
        const section = $('#top-selling-section');
        const container = $('#top-selling-products');
        const hiddenContent = section.find('.lazy-hidden-content');
        let isLoading = false;
        let hasLoaded = false;
        
        if (section.length === 0 || container.length === 0) {
            return;
        }
        
        function loadProducts() {
            if (isLoading || hasLoaded) {
                return;
            }
            
            if (hiddenContent.length > 0 && !hiddenContent.is(':visible')) {
                return;
            }
            
            if (container.children().length > 0) {
                hasLoaded = true;
                return;
            }
            
            isLoading = true;
            
            $.ajax({
                url: '/api/products/top-selling',
                method: 'GET',
                data: { limit: 10 },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    isLoading = false;
                    hasLoaded = true;
                    
                    if (response.success && response.data && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(product) {
                            // 调试：检查品牌信息
                            if (!product.brand_name && product.brand_id) {
                                console.warn('产品缺少品牌名称:', {
                                    id: product.id,
                                    name: product.name,
                                    brand_id: product.brand_id,
                                    product: product
                                });
                            }
                            html += renderProductCard(product);
                        });
                        container.html(html);
                        
                        // 初始化图片懒加载和 skeleton 优化器
                        container.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            const imgSrc = img.attr('src');
                            
                            if (imgSrc && imgSrc !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                                
                                // 确保图片加载后初始化 skeleton
                                img.on('load', function() {
                                    if (window.initSmartSkeleton) {
                                        window.initSmartSkeleton();
                                    }
                                });
                                
                                // 图片加载失败时也初始化 skeleton（使用默认尺寸）
                                img.on('error', function() {
                                    if (window.initSmartSkeleton) {
                                        window.initSmartSkeleton();
                                    }
                                });
                            } else {
                                // 如果没有图片源，隐藏 skeleton 容器
                                img.closest('.js-skeleton').hide();
                            }
                        });
                        
                        // 初始化 skeleton 优化器
                        if (window.initSmartSkeleton) {
                            setTimeout(function() {
                                window.initSmartSkeleton();
                            }, 100);
                        }
                    } else {
                        container.html('<div class="text-center py-4">暂无产品数据</div>');
                    }
                },
                error: function(xhr, status, error) {
                    isLoading = false;
                    console.error('加载热销产品失败:', error);
                    container.html('<div class="text-center py-4 text-danger">加载产品失败，请刷新页面重试</div>');
                }
            });
        }
        
        // 监听懒加载显示
        if (hiddenContent.length > 0) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        if (hiddenContent.is(':visible') && !hasLoaded) {
                            loadProducts();
                        }
                    }
                });
            });
            
            observer.observe(hiddenContent[0], {
                attributes: true,
                attributeFilter: ['style']
            });
            
            setTimeout(function() {
                if (hiddenContent.is(':visible')) {
                    loadProducts();
                }
            }, 200);
        } else {
            loadProducts();
        }
    }
    
    // ============================================================================
    // 加载 Flash Sale 产品
    // ============================================================================
    function loadFlashSaleProducts() {
        const section = $('#flash-sale-section');
        const container = $('#flash-sale-products');
        const hiddenContent = section.find('.lazy-hidden-content');
        let isLoading = false;
        let hasLoaded = false;
        
        if (section.length === 0 || container.length === 0) {
            return;
        }
        
        function loadProducts() {
            if (isLoading || hasLoaded) {
                return;
            }
            
            // Don't block on lazy loading - load data immediately
            // The section visibility will be controlled by API response
            isLoading = true;
            
            $.ajax({
                url: '/api/products/flash-sale',
                method: 'GET',
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    isLoading = false;
                    hasLoaded = true;
                    
                    // Debug logging
                    console.log('Flash Sale API Response:', response);
                    console.log('Has data:', response.data && response.data.length > 0);
                    console.log('Flash Sale info:', response.flash_sale);
                    
                    if (response.success && response.data && response.data.length > 0) {
                        console.log('Showing Flash Sale section with', response.data.length, 'products');
                        // Show Flash Sale section and hidden content
                        section.show();
                        if (hiddenContent.length > 0) {
                            hiddenContent.show();
                        }
                        
                        // Set countdown timer - use timestamp if available, otherwise parse date
                        if (response.flash_sale) {
                            let remainingTime = 0;
                            if (response.flash_sale.end_timestamp) {
                                // Use timestamp directly (more reliable)
                                const now = Math.floor(Date.now() / 1000);
                                remainingTime = Math.max(0, response.flash_sale.end_timestamp - now);
                            } else if (response.flash_sale.end_date) {
                                // Fallback to date parsing
                                const deadline = new Date(response.flash_sale.end_date);
                                remainingTime = Math.max(0, Math.floor((deadline - new Date()) / 1000));
                            }
                            
                            if (remainingTime > 0) {
                                // Update timer immediately
                                function updateTimer() {
                                    const days = Math.floor(remainingTime / 86400);
                                    const hours = String(Math.floor((remainingTime % 86400) / 3600)).padStart(2, '0');
                                    const minutes = String(Math.floor((remainingTime % 3600) / 60)).padStart(2, '0');
                                    const seconds = String(remainingTime % 60).padStart(2, '0');
                                    
                                    let timerHtml = '';
                                    if (days > 0) {
                                        timerHtml = '<div>' + days + '<span>NGÀY</span></div><div>' + hours + '<span>GIỜ</span></div><div>' + minutes + '<span>PHÚT</span></div><div>' + seconds + '<span>GIÂY</span></div>';
                                    } else {
                                        timerHtml = '<div>00<span>Ngày</span></div><div>' + hours + '<span>GIỜ</span></div><div>' + minutes + '<span>PHÚT</span></div><div>' + seconds + '<span>GIÂY</span></div>';
                                    }
                                    
                                    const boxTime = section.find('.box-time');
                                    if (boxTime.length > 0) {
                                        boxTime.html(timerHtml);
                                    }
                                }
                                
                                // Update immediately
                                updateTimer();
                                
                                // Then update every second
                                const timerInterval = setInterval(function() {
                                    remainingTime--;
                                    if (remainingTime <= 0) {
                                        clearInterval(timerInterval);
                                        section.hide();
                                        return;
                                    }
                                    updateTimer();
                                }, 1000);
                            }
                        }
                        
                        // 渲染产品 - Flash Sale block 中不显示 Deal voucher
                        let html = '';
                        response.data.forEach(function(product) {
                            let productHtml = renderProductCard(product, { excludeDeal: true });
                            html += productHtml;
                        });
                        container.html(html);
                        
                        // Hide skeleton placeholder
                        section.find('.lazy-placeholder').hide();
                        
                        // Initialize owl-carousel for Flash Sale products - 5.5 items per row
                        if (typeof $.fn.owlCarousel !== 'undefined') {
                            // Destroy existing carousel if any
                            if (container.data('owlCarousel')) {
                                container.trigger('destroy.owl.carousel').removeClass('owl-carousel owl-theme flash-sale-carousel');
                            }
                            
                            // Initialize new carousel for Flash Sale - 5.5 items per row
                            container.addClass('owl-carousel owl-theme flash-sale-carousel').owlCarousel({
                                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                                responsiveclass: true,
                                autoplay: false,
                                dots: false,
                                loop: false,
                                autoWidth: false,
                                margin: 16,
                                responsive: {
                                    0: {
                                        items: 2,
                                        nav: true,
                                        margin: 10
                                    },
                                    768: {
                                        items: 3,
                                        nav: true,
                                        margin: 14
                                    },
                                    1000: {
                                        items: 5.5,
                                        nav: true,
                                        margin: 16
                                    }
                                }
                            });
                            
                            // Initialize Flash Sale carousel CSS after carousel is ready
                            setTimeout(function() {
                                if (typeof initFlashSaleCarousel === 'function') {
                                    initFlashSaleCarousel();
                                }
                            }, 100);
                        }
                        
                        // 初始化图片懒加载
                        container.find('.js-skeleton-img').each(function() {
                            const img = $(this);
                            if (img.attr('src') && img.attr('src') !== '') {
                                img.css({
                                    'opacity': '1',
                                    'visibility': 'visible'
                                });
                            }
                        });
                    } else {
                        // No Flash Sale products - hide section
                        console.log('No Flash Sale products found, hiding section');
                        console.log('Response:', response);
                        section.hide();
                    }
                },
                error: function(xhr, status, error) {
                    isLoading = false;
                    hasLoaded = true;
                    section.hide();
                    console.error('Flash Sale load error:', {
                        status: status,
                        error: error,
                        statusCode: xhr.status,
                        responseText: xhr.responseText,
                        url: '/api/products/flash-sale'
                    });
                }
            });
        }
        
        // Load immediately - don't wait for lazy loading
        // The API will determine if Flash Sale should be shown
        loadProducts();
        
        // Also listen for lazy load trigger as fallback
        if (hiddenContent.length > 0) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        if (hiddenContent.is(':visible') && !hasLoaded && !isLoading) {
                            loadProducts();
                        }
                    }
                });
            });
            
            observer.observe(hiddenContent[0], {
                attributes: true,
                attributeFilter: ['style']
            });
        }
        
        // Fallback: retry after 1 second if not loaded
        setTimeout(function() {
            if (!hasLoaded && !isLoading) {
                loadProducts();
            }
        }, 1000);
    }
    
    // ============================================================================
    // 加载 Taxonomy 产品
    // ============================================================================
    function loadTaxonomyProducts() {
        $('.taxonomy-product').each(function() {
            const section = $(this);
            const hiddenContent = section.find('.lazy-hidden-content');
            let isLoading = false;
            let hasLoaded = false;
            
            function loadProducts(categoryId, container) {
                if (isLoading || hasLoaded) {
                    return;
                }
                
                if (container.children().length > 0) {
                    hasLoaded = true;
                    return;
                }
                
                isLoading = true;
                
                $.ajax({
                    url: '/api/products/by-category/' + categoryId,
                    method: 'GET',
                    data: { limit: 20 },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        isLoading = false;
                        hasLoaded = true;
                        
                        if (response.success && response.data && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(function(product) {
                                html += renderProductCard(product);
                            });
                            container.html(html);
                            
                            // 初始化图片懒加载
                            container.find('.js-skeleton-img').each(function() {
                                const img = $(this);
                                if (img.attr('src') && img.attr('src') !== '') {
                                    img.css({
                                        'opacity': '1',
                                        'visibility': 'visible'
                                    });
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        isLoading = false;
                        console.error('加载分类产品失败:', error);
                    }
                });
            }
            
            // 监听懒加载显示
            if (hiddenContent.length > 0) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            if (hiddenContent.is(':visible') && !hasLoaded) {
                                // 加载第一个 tab 的产品
                                const firstTab = section.find('.tab-pane.active');
                                if (firstTab.length > 0) {
                                    const categoryId = firstTab.attr('data-category-id');
                                    const container = firstTab.find('.list-watch, .taxonomy-products-' + categoryId);
                                    if (categoryId && container.length > 0) {
                                        loadProducts(categoryId, container);
                                    }
                                } else {
                                    // 没有 tabs，直接加载
                                    const categoryId = section.find('[data-category-id]').attr('data-category-id');
                                    const container = section.find('.list-watch, [data-category-id="' + categoryId + '"]');
                                    if (categoryId && container.length > 0) {
                                        loadProducts(categoryId, container);
                                    }
                                }
                            }
                        }
                    });
                });
                
                observer.observe(hiddenContent[0], {
                    attributes: true,
                    attributeFilter: ['style']
                });
                
                setTimeout(function() {
                    if (hiddenContent.is(':visible') && !hasLoaded) {
                        const firstTab = section.find('.tab-pane.active');
                        if (firstTab.length > 0) {
                            const categoryId = firstTab.attr('data-category-id');
                            const container = firstTab.find('.list-watch, .taxonomy-products-' + categoryId);
                            if (categoryId && container.length > 0) {
                                loadProducts(categoryId, container);
                            }
                        } else {
                            const categoryId = section.find('[data-category-id]').attr('data-category-id');
                            const container = section.find('.list-watch, [data-category-id="' + categoryId + '"]');
                            if (categoryId && container.length > 0) {
                                loadProducts(categoryId, container);
                            }
                        }
                    }
                }, 200);
            }
        });
        
        // 处理 Taxonomy tab 切换
        $('.taxonomy-home').on('click', '.nav-link', function() {
            const id = $(this).attr('data-id');
            const slug = $(this).attr('data-slug');
            const targetPane = $('#taxonomy-' + id);
            const container = targetPane.find('.list-watch, .taxonomy-products-' + id);
            
            if (container.length > 0 && container.children().length === 0) {
                $.ajax({
                    url: '/api/products/by-category/' + id,
                    method: 'GET',
                    data: { limit: 20 },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        if (response.success && response.data && response.data.length > 0) {
                            let html = '';
                            response.data.forEach(function(product) {
                                html += renderProductCard(product);
                            });
                            container.html(html);
                            
                            // 初始化图片懒加载
                            container.find('.js-skeleton-img').each(function() {
                                const img = $(this);
                                if (img.attr('src') && img.attr('src') !== '') {
                                    img.css({
                                        'opacity': '1',
                                        'visibility': 'visible'
                                    });
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('加载分类产品失败:', error);
                    }
                });
            }
        });
    }
    
    // 初始化产品加载
    loadTopSellingProducts();
    loadFlashSaleProducts();
    loadTaxonomyProducts();
    
    // Slider carousel initialization is now handled in loadSliders() function
    // Removed global initialization to avoid conflicts with API-loaded sliders
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
    // Taxonomy tab 切换已集成到 loadTaxonomyProducts() 函数中
})
</script>
<style>
    .carousel-four {
          width: 537px;
        }
    
    /* Flash Sale Carousel - 5.5 items per row */
    .flash-sale-carousel {
        display: block !important;
    }
    .flash-sale-carousel.owl-carousel .owl-stage {
        display: flex;
    }
    .flash-sale-carousel.owl-carousel .owl-item {
        flex: 0 0 calc(20% - 16px) !important;
        max-width: calc(20% - 16px) !important;
        margin-right: 16px !important;
    }
    .flash-sale-carousel.owl-carousel .owl-item:nth-child(6) {
        flex: 0 0 calc(10% - 8px) !important;
        max-width: calc(10% - 8px) !important;
    }
    .flash-sale-carousel .item-product {
        min-width: 100% !important;
        width: 100% !important;
        margin-right: 0 !important;
        margin-bottom: 0 !important;
        border-radius: 10px;
        padding: 0px;
        height: 380px;
    }
    .flash-sale-carousel .item-product img {
        height: 100%;
        width: initial;
        display: inline-block;
    }
    .flash-sale-carousel .card-cover {
        text-align: center;
        height: 168px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .flash-sale-carousel .card-cover a {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        overflow: hidden;
    }
    .flash-sale-carousel .card-cover .js-skeleton {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    .flash-sale-carousel .card-cover img {
        object-fit: contain;
        max-width: 100%;
        max-height: 100%;
        margin: 0 auto;
    }
    .flash-sale-carousel .item-product .status-product {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .flash-sale-carousel .item-product .btn_login_wishlist {
        position: absolute;
        top: 10px;
        left: 10px;
    }
    .flash-sale-carousel .item-product .card-content {
        padding: 10px;
    }
    @media (max-width: 1000px) {
        .flash-sale-carousel.owl-carousel .owl-item {
            flex: 0 0 calc(33.333% - 14px) !important;
            max-width: calc(33.333% - 14px) !important;
        }
        .flash-sale-carousel.owl-carousel .owl-item:nth-child(4) {
            flex: 0 0 calc(16.666% - 7px) !important;
            max-width: calc(16.666% - 7px) !important;
        }
    }
    @media (max-width: 768px) {
        .flash-sale-carousel.owl-carousel .owl-item {
            flex: 0 0 calc(50% - 10px) !important;
            max-width: calc(50% - 10px) !important;
        }
        .flash-sale-carousel.owl-carousel .owl-item:nth-child(3) {
            flex: 0 0 calc(25% - 5px) !important;
            max-width: calc(25% - 5px) !important;
        }
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
        
        // Flash Sale Carousel - 5.5 items per row
        function initFlashSaleCarousel() {
            if (window.innerWidth >= 1000) {
                $('.flash-sale-carousel.owl-carousel .owl-item').css({
                    'flex': '0 0 calc(20% - 16px)',
                    'max-width': 'calc(20% - 16px)',
                    'margin-right': '16px'
                });
                $('.flash-sale-carousel.owl-carousel .owl-item:nth-child(6)').css({
                    'flex': '0 0 calc(10% - 8px)',
                    'max-width': 'calc(10% - 8px)'
                });
            } else if (window.innerWidth >= 768) {
                $('.flash-sale-carousel.owl-carousel .owl-item').css({
                    'flex': '0 0 calc(33.333% - 14px)',
                    'max-width': 'calc(33.333% - 14px)',
                    'margin-right': '14px'
                });
                $('.flash-sale-carousel.owl-carousel .owl-item:nth-child(4)').css({
                    'flex': '0 0 calc(16.666% - 7px)',
                    'max-width': 'calc(16.666% - 7px)'
                });
            } else {
                $('.flash-sale-carousel.owl-carousel .owl-item').css({
                    'flex': '0 0 calc(50% - 10px)',
                    'max-width': 'calc(50% - 10px)',
                    'margin-right': '10px'
                });
                $('.flash-sale-carousel.owl-carousel .owl-item:nth-child(3)').css({
                    'flex': '0 0 calc(25% - 5px)',
                    'max-width': 'calc(25% - 5px)'
                });
            }
        }
        
        $(window).on('resize', function() {
            initRecommendationsGrid();
            initFlashSaleCarousel();
        });
    });
</script>
@endsection
