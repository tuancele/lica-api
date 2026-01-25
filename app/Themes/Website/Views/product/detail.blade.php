@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<link rel="stylesheet" href="/public/website/owl-carousel/owl.carousel-2.0.0.css">
<script src="/public/website/owl-carousel/owl.carousel-2.0.0.min.js"></script>
@endsection
@section('content')
<section class="mt-3" id="detailProduct" data-product-id="{{$detail->id ?? ''}}">
    <div class="container-lg">
        <div class="row" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important;">
            <div class="col-12 col-md-6">
                <div class="position-relative pe-0 pe-md-5">
                    <style>
                        .product-slider-container {
                            width: 100%;
                            background: #fff;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                        }
                
                        /* Main Image Slider */
                        .main-slider {
                            position: relative;
                            width: 100%;
                            aspect-ratio: 1 / 1;
                            overflow: hidden;
                            background: #fafafa;
                        }
                
                        .slides-wrapper {
                            display: flex;
                            width: 100%;
                            height: 100%;
                            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                        }
                
                        .slide {
                            position: relative;
                            min-width: 100%;
                            height: 100%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 0;
                        }
                
                        .slide img,
                        .slide video {
                            max-width: 100%;
                            max-height: 100%;
                            object-fit: contain;
                            border-radius: 12px;
                        }
                
                        /* Navigation Arrows */
                        .nav-arrow {
                            position: absolute;
                            top: 50%;
                            transform: translateY(-50%);
                            width: 44px;
                            height: 44px;
                            background: rgba(255, 255, 255, 0.95);
                            border: none;
                            border-radius: 50%;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
                            transition: all 0.2s ease;
                            z-index: 10;
                        }
                
                        .nav-arrow:hover {
                            background: #fff;
                            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                            transform: translateY(-50%) scale(1.05);
                        }
                
                        .nav-arrow:active {
                            transform: translateY(-50%) scale(0.95);
                        }
                
                        .nav-arrow.prev {
                            left: 12px;
                        }
                
                        .nav-arrow.next {
                            right: 12px;
                        }
                
                        .nav-arrow svg {
                            width: 20px;
                            height: 20px;
                            stroke: #333;
                            stroke-width: 2.5;
                            fill: none;
                        }
                
                        /* Mobile: Page Indicator */
                        .page-indicator {
                            position: absolute;
                            bottom: 16px;
                            right: 16px;
                            background: rgba(0, 0, 0, 0.6);
                            color: #fff;
                            padding: 6px 14px;
                            border-radius: 20px;
                            font-size: 14px;
                            font-weight: 500;
                            letter-spacing: 0.5px;
                            backdrop-filter: blur(4px);
                            z-index: 10;
                        }
                
                        /* Desktop: Thumbnails */
                        .thumbnails-container {
                            display: none;
                            padding: 16px;
                            gap: 12px;
                            background: #fff;
                            border-top: 1px solid #eee;
                            overflow-x: auto;
                            scrollbar-width: thin;
                            scrollbar-color: #ccc #f0f0f0;
                        }
                
                        .thumbnails-container::-webkit-scrollbar {
                            height: 6px;
                        }
                
                        .thumbnails-container::-webkit-scrollbar-track {
                            background: #f0f0f0;
                            border-radius: 3px;
                        }
                
                        .thumbnails-container::-webkit-scrollbar-thumb {
                            background: #ccc;
                            border-radius: 3px;
                        }
                
                        .thumbnail {
                            min-width: 72px;
                            width: 72px;
                            height: 72px;
                            border: 2px solid transparent;
                            border-radius: 8px;
                            overflow: hidden;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            background: #fafafa;
                            padding: 4px;
                        }
                        .thumbnail.video {
                            position: relative;
                        }
                        .thumbnail.video::after {
                            content: '';
                            position: absolute;
                            left: 50%;
                            top: 50%;
                            transform: translate(-50%, -50%);
                            width: 24px;
                            height: 24px;
                            border-radius: 50%;
                            background: rgba(0,0,0,0.6);
                        }
                        .thumbnail.video::before {
                            content: '';
                            position: absolute;
                            left: 50%;
                            top: 50%;
                            transform: translate(-40%, -50%);
                            width: 0;
                            height: 0;
                            border-top: 6px solid transparent;
                            border-bottom: 6px solid transparent;
                            border-left: 10px solid #fff;
                            z-index: 2;
                        }
                
                        .thumbnail:hover {
                            border-color: #aaa;
                        }
                
                        .thumbnail.active {
                            border-color: #00a86b;
                            box-shadow: 0 0 0 2px rgba(0, 168, 107, 0.2);
                        }
                
                        .thumbnail img {
                            width: 100%;
                            height: 100%;
                            object-fit: cover;
                        }
                
                        /* Responsive: Desktop */
                        @media (min-width: 768px) {
                            .page-indicator {
                                display: none;
                            }
                
                            .thumbnails-container {
                                display: flex;
                            }
                
                            .nav-arrow {
                                width: 48px;
                                height: 48px;
                            }
                
                            .nav-arrow.prev {
                                left: 16px;
                            }
                
                            .nav-arrow.next {
                                right: 16px;
                            }
                        }
                
                        /* Touch/Swipe hint on mobile */
                        @media (max-width: 767px) {
                            .main-slider {
                                cursor: grab;
                            }
                
                            .main-slider:active {
                                cursor: grabbing;
                            }
                
                            .nav-arrow {
                                width: 40px;
                                height: 40px;
                            }
                
                            .nav-arrow.prev {
                                left: 8px;
                            }
                
                            .nav-arrow.next {
                                right: 8px;
                            }
                        }
                    </style>
                    <div class="product-slider-container">
                        <!-- Main Slider -->
                        <div class="main-slider" id="mainSlider">
                            <div class="slides-wrapper" id="slidesWrapper">
                                @php
                                    $cdnBase = config('filesystems.disks.r2.url') ?? '';
                                    $normalizeCdnUrl = function ($url) use ($cdnBase) {
                                        $final = getImage($url);
                                        if ($cdnBase) {
                                            $base = rtrim($cdnBase, '/');
                                            // Force CDN domain for image host
                                            return preg_replace('#^https?://[^/]+#', $base, $final);
                                        }
                                        return $final;
                                    };

                                    $hasVideo = !empty($detail->video);
                                    // Build media list: 1 video (if any) + main image + gallery images
                                    $mediaItems = [];
                                    if ($hasVideo) {
                                        $mediaItems[] = [
                                            'type' => 'video',
                                            'src' => $normalizeCdnUrl($detail->video),
                                        ];
                                    }
                                    // Main image is always first image
                                    $mainImage = $normalizeCdnUrl($detail->image);
                                    $mediaItems[] = [
                                        'type' => 'image',
                                        'src' => $mainImage,
                                    ];
                                    if (isset($gallerys) && !empty($gallerys)) {
                                        foreach ($gallerys as $image) {
                                            $imgSrc = $normalizeCdnUrl($image);
                                            if ($imgSrc != $mainImage) {
                                                $mediaItems[] = [
                                                    'type' => 'image',
                                                    'src' => $imgSrc,
                                                ];
                                            }
                                            if (count($mediaItems) >= ($hasVideo ? 10 : 9)) {
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @foreach($mediaItems as $item)
                                    <div class="slide" 
                                         data-type="{{$item['type']}}"
                                         data-thumb="{{ $item['type'] === 'video' ? $mainImage : $item['src'] }}">
                                        @if($item['type'] === 'video')
                                            <video src="{{$item['src']}}" controls playsinline muted></video>
                                        @else
                                            <div class="skeleton--img-square js-skeleton">
                                                <img src="{{$item['src']}}" alt="{{$detail->name}}" class="js-skeleton-img" loading="lazy">
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                
                            <!-- Navigation Arrows -->
                            <button class="nav-arrow prev" id="prevBtn" aria-label="Previous image">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                            <button class="nav-arrow next" id="nextBtn" aria-label="Next image">
                                <svg viewBox="0 0 24 24">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                
                            <!-- Page Indicator (Mobile) -->
                            <div class="page-indicator" id="pageIndicator">1/1</div>
                        </div>
                
                        <!-- Thumbnails (Desktop) -->
                        <div class="thumbnails-container" id="thumbnailsContainer">
                            <!-- Thumbnails will be generated by JS -->
                        </div>
                    </div>
                    <script>
                        class ProductSlider {
                            constructor() {
                                this.currentIndex = 0;
                                this.slidesWrapper = document.getElementById('slidesWrapper');
                                this.slides = document.querySelectorAll('.slide');
                                this.totalSlides = this.slides.length;
                                this.pageIndicator = document.getElementById('pageIndicator');
                                this.thumbnailsContainer = document.getElementById('thumbnailsContainer');
                                this.mainSlider = document.getElementById('mainSlider');
                
                                // Touch/Swipe variables
                                this.startX = 0;
                                this.currentX = 0;
                                this.isDragging = false;
                
                                this.init();
                            }
                
                            init() {
                                this.generateThumbnails();
                                this.bindEvents();
                                this.updateUI();

                                // Nếu slide đầu tiên là video thì tự động play
                                this.autoPlayVideoIfNeeded();
                            }
                
                            generateThumbnails() {
                                this.slides.forEach((slide, index) => {
                                    const img = slide.querySelector('img');
                                    const video = slide.querySelector('video');
                                    const thumbnail = document.createElement('div');
                                    const isVideo = !!video;
                                    thumbnail.className = `thumbnail ${isVideo ? 'video' : ''} ${index === 0 ? 'active' : ''}`;
                                    
                                    // Ưu tiên dùng data-thumb (được set ở Blade) để tránh dùng trực tiếp URL .mp4
                                    let thumbSrc = slide.getAttribute('data-thumb') || '';
                                    if (!thumbSrc) {
                                        if (video) {
                                            thumbSrc = video.currentSrc || video.src;
                                        } else if (img) {
                                            thumbSrc = img.src;
                                        }
                                    }

                                    if (thumbSrc) {
                                        thumbnail.innerHTML = `<img src="${thumbSrc}" alt="Thumbnail ${index + 1}" loading="lazy">`;
                                    }
                                    thumbnail.addEventListener('click', () => this.goToSlide(index));
                                    this.thumbnailsContainer.appendChild(thumbnail);
                                });
                            }
                
                            bindEvents() {
                                // Arrow buttons
                                const prevBtn = document.getElementById('prevBtn');
                                const nextBtn = document.getElementById('nextBtn');
                                
                                if(prevBtn) prevBtn.addEventListener('click', () => this.prev());
                                if(nextBtn) nextBtn.addEventListener('click', () => this.next());
                
                                // Keyboard navigation
                                document.addEventListener('keydown', (e) => {
                                    if (e.key === 'ArrowLeft') this.prev();
                                    if (e.key === 'ArrowRight') this.next();
                                });
                
                                // Touch events for swipe
                                if(this.mainSlider) {
                                    this.mainSlider.addEventListener('touchstart', (e) => this.handleTouchStart(e), { passive: true });
                                    this.mainSlider.addEventListener('touchmove', (e) => this.handleTouchMove(e), { passive: true });
                                    this.mainSlider.addEventListener('touchend', (e) => this.handleTouchEnd(e));
                    
                                    // Mouse events for drag (desktop)
                                    this.mainSlider.addEventListener('mousedown', (e) => this.handleMouseDown(e));
                                    this.mainSlider.addEventListener('mousemove', (e) => this.handleMouseMove(e));
                                    this.mainSlider.addEventListener('mouseup', (e) => this.handleMouseUp(e));
                                    this.mainSlider.addEventListener('mouseleave', (e) => this.handleMouseUp(e));
                                }
                            }
                
                            handleTouchStart(e) {
                                this.startX = e.touches[0].clientX;
                                this.isDragging = true;
                            }
                
                            handleTouchMove(e) {
                                if (!this.isDragging) return;
                                this.currentX = e.touches[0].clientX;
                            }
                
                            handleTouchEnd(e) {
                                if (!this.isDragging) return;
                                this.isDragging = false;
                
                                const diff = this.startX - this.currentX;
                                const threshold = 50;
                
                                if (Math.abs(diff) > threshold) {
                                    if (diff > 0) {
                                        this.next();
                                    } else {
                                        this.prev();
                                    }
                                }
                            }
                
                            handleMouseDown(e) {
                                this.startX = e.clientX;
                                this.isDragging = true;
                                this.mainSlider.style.cursor = 'grabbing';
                            }
                
                            handleMouseMove(e) {
                                if (!this.isDragging) return;
                                this.currentX = e.clientX;
                            }
                
                            handleMouseUp(e) {
                                if (!this.isDragging) return;
                                this.isDragging = false;
                                this.mainSlider.style.cursor = 'grab';
                
                                const diff = this.startX - this.currentX;
                                const threshold = 50;
                
                                if (Math.abs(diff) > threshold) {
                                    if (diff > 0) {
                                        this.next();
                                    } else {
                                        this.prev();
                                    }
                                }
                            }
                
                            prev() {
                                if (this.totalSlides <= 1) return;
                                this.currentIndex = (this.currentIndex - 1 + this.totalSlides) % this.totalSlides;
                                this.updateUI();
                            }
                
                            next() {
                                if (this.totalSlides <= 1) return;
                                this.currentIndex = (this.currentIndex + 1) % this.totalSlides;
                                this.updateUI();
                            }
                
                            goToSlide(index) {
                                this.currentIndex = index;
                                this.updateUI();
                            }
                
                            updateUI() {
                                // Update slide position
                                if(this.slidesWrapper) {
                                    this.slidesWrapper.style.transform = `translateX(-${this.currentIndex * 100}%)`;
                                }
                
                                // Update page indicator
                                if(this.pageIndicator) {
                                    this.pageIndicator.textContent = `${this.currentIndex + 1}/${this.totalSlides}`;
                                }
                
                                // Update thumbnails
                                const thumbnails = this.thumbnailsContainer.querySelectorAll('.thumbnail');
                                thumbnails.forEach((thumb, index) => {
                                    thumb.classList.toggle('active', index === this.currentIndex);
                                });
                
                                // Scroll active thumbnail into view
                                const activeThumbnail = thumbnails[this.currentIndex];
                                if (activeThumbnail) {
                                    activeThumbnail.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'nearest',
                                        inline: 'center'
                                    });
                                }

                                this.autoPlayVideoIfNeeded();
                            }

                            autoPlayVideoIfNeeded() {
                                // Tạm dừng tất cả video
                                this.slides.forEach((slide) => {
                                    const video = slide.querySelector('video');
                                    if (video) {
                                        video.pause();
                                    }
                                });

                                // Nếu slide hiện tại là video thì play
                                const currentSlide = this.slides[this.currentIndex];
                                if (!currentSlide) return;
                                const currentVideo = currentSlide.querySelector('video');
                                if (currentVideo) {
                                    // Tự động play, tắt tiếng để tránh block autoplay
                                    currentVideo.muted = true;
                                    const playPromise = currentVideo.play();
                                    if (playPromise && typeof playPromise.catch === 'function') {
                                        playPromise.catch(function(){});
                                    }
                                }
                            }
                        }
                
                        // Initialize slider when DOM is ready (avoid multiple init)
                        document.addEventListener('DOMContentLoaded', () => {
                            if (window.__ProductSliderInitialized) {
                                return;
                            }
                            window.__ProductSliderInitialized = true;
                            new ProductSlider();
                        });
                    </script>
                    <div class="status-product">
                        @if($detail->best)
                        <div class="deal-hot mb-2">Deal<br/>Hot</div>
                        @endif
                        @if($detail->is_new)
                        <div class="is-new mb-2">Mới</div>
                        @endif
                        @if(isset($isOutOfStock) && $isOutOfStock)
                        <div class="is-stock mb-2">Hết hàng</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <!-- Product Detail Info - Loaded via API -->
                <div id="product-detail-info" data-product-slug="{{$detail->slug ?? ''}}" data-product-id="{{$detail->id ?? ''}}">
                    <!-- Skeleton Placeholder (only show if no data available) -->
                    <div class="product-detail-skeleton" style="{{isset($detail) && $detail ? 'display: none;' : 'display: block;'}}">
                        <div class="breadcrumb">
                            <ol>
                                <li><a href="/">Trang chủ</a></li>
                                <li><span style="opacity: 0.5;">...</span></li>
                            </ol>
                        </div>
                        <div style="height: 20px; background: #f0f0f0; border-radius: 4px; margin: 10px 0; width: 150px;"></div>
                        <div style="height: 32px; background: #f0f0f0; border-radius: 4px; margin: 10px 0; width: 80%;"></div>
                        <div style="height: 24px; background: #f0f0f0; border-radius: 4px; margin: 10px 0; width: 200px;"></div>
                        <div style="height: 40px; background: #f0f0f0; border-radius: 4px; margin: 20px 0; width: 60%;"></div>
                    </div>
                    <!-- Content will be loaded here (for API-loaded content) -->
                    <div class="product-detail-content" style="display: none;"></div>
                </div>
                <!-- Blade Template Fallback (show when data is available from server) -->
                <div class="product-detail-blade-fallback" style="{{isset($detail) && $detail ? 'display: block;' : 'display: none;'}}">
                    <!-- Breadcrumb -->
                    <div class="breadcrumb">
                        <ol>
                            <li><a href="/">Trang chủ</a></li>
                            @if(isset($categories) && $categories && $categories->count() > 0)
                                @foreach($categories as $cat)
                                <li><a href="/{{$cat->slug}}">{{$cat->name}}</a></li>
                                @endforeach
                            @elseif(isset($detail->category) && $detail->category)
                            <li><a href="/{{$detail->category->slug}}">{{$detail->category->name}}</a></li>
                            @endif
                        </ol>
                    </div>
                    <!-- Brand and Product Name -->
                    @if(isset($detail->brand) && $detail->brand)
                    @if(isset($detail->brand->slug) && $detail->brand->slug)
                    <a href="/thuong-hieu/{{$detail->brand->slug}}" class="brand-name fs-14 fw-600 mb-2" style="color: #666; text-decoration: none;">{{$detail->brand->name ?? ''}}</a>
                    @else
                    <div class="brand-name fs-14 fw-600 mb-2" style="color: #666;">{{$detail->brand->name ?? ''}}</div>
                    @endif
                    @endif
                    <h1 class="title-product fs-24 fw-bold mb-3">{{$detail->name ?? ''}}</h1>
                @php
                    // Bảo vệ trong trường hợp $t_rates null hoặc không được truyền
                    $rateCollection = isset($t_rates) && $t_rates ? $t_rates : collect();
                    $rateCount = $rateCollection->count();
                    $rateSum   = $rateCollection->sum('rate');
                    $averageRate = $rateCount > 0 ? round($rateSum / $rateCount, 1) : 0;
                    
                    // 获取总销量
                    $totalSold = \Illuminate\Support\Facades\DB::table('orderdetail')
                        ->join('orders', 'orderdetail.order_id', '=', 'orders.id')
                        ->where('orderdetail.product_id', $detail->id)
                        ->where('orders.ship', 2)
                        ->where('orders.status', '!=', 2)
                        ->sum('orderdetail.qty') ?? 0;
                @endphp
                <div class="product-rating-sales">
                    <div class="rating-display">
                        <span class="rating-value">{{number_format($averageRate, 1)}}</span>
                        <div class="rating-stars">
                            {!!getStar($rateSum,$rateCount)!!}
                        </div>
                    </div>
                    <span class="separator">|</span>
                    <div class="review-count">
                        @if($rateCount >= 1000)
                            <span class="review-number">{{number_format($rateCount / 1000, 1)}}k</span>
                        @else
                            <span class="review-number">{{number_format($rateCount)}}</span>
                        @endif
                        <span class="review-text"> Đánh Giá</span>
                    </div>
                    <span class="separator">|</span>
                    <div class="sales-count">
                        <span class="sales-text">Đã Bán </span>
                        @if($totalSold >= 1000000)
                            <span class="sales-number">{{number_format($totalSold / 1000000, 1)}}tr+</span>
                        @elseif($totalSold >= 1000)
                            <span class="sales-number">{{number_format($totalSold / 1000, 1)}}k+</span>
                        @else
                            <span class="sales-number">{{number_format($totalSold)}}</span>
                        @endif
                    </div>
                </div>
                @php
                    // Shopee-style: mọi sản phẩm có biến thể đều dùng block phân loại mới
                    $hasAnyVariant = isset($variants) && $variants->count() > 0;
                    $isShopeeVariant = $hasAnyVariant;
                    // Use stock_display (with priority: Flash Sale > Deal > Available) from Warehouse only
                    // NO fallback to old $first->stock field - Warehouse is the single source of truth
                    $currentVariantStock = (int)($first->stock_display ?? $first->warehouse_stock ?? 0);
                    // Server-side calculated stock_display for locking
                    $serverStockDisplay = (int)($first->stock_display ?? $first->warehouse_stock ?? 0);
                @endphp
                <div class="price-detail">
                    <!-- VARIANT_DEBUG_A11: isShopeeVariant={{ $isShopeeVariant ? '1' : '0' }}, variants_count={{ isset($variants) ? $variants->count() : 0 }} -->
                    @if($isShopeeVariant)
                        @php
                            $firstPriceInfo = getVariantFinalPrice($first->id ?? 0, $detail->id);
                        @endphp
                        <div class="price" id="variant-price-display">
                            {!! $firstPriceInfo['html'] !!}
                        </div>
                    @else
                        <div class="price">{!!checkSale($detail->id)!!}</div>
                    @endif
                </div>
                <div class="d-block overflow-hidden mb-2 list_attribute">
                    {{-- 隐藏评分星星部分
                    <div class="item_1 rate-section w40 fs-12 pointer" onclick="window.location='{{getSlug($detail->slug)}}#ratingProduct'">
                        <div class="rating mt-0 mb-0">
                            {!!getStar($rateSum,$rateCount)!!}
                            <div class="count-rate">({{$rateCount}})</div>
                        </div>
                    </div>
                    --}}
                    {{-- 隐藏喜欢/收藏部分
                    @php
                        $wishlistCollection = method_exists($detail, 'wishlists') ? ($detail->wishlists ?? collect()) : collect();
                        $wishlistCount = $wishlistCollection ? $wishlistCollection->count() : 0;
                    @endphp
                    <div class="item_2 fav-section w60 fs-12">
                        <span role="img" class="icon"><svg width="12" height="12" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.001 0C18.445 0 16.1584 1.24169 14.6403 3.19326C13.1198 1.24169 10.8355 0 8.27952 0C3.70634 0 0 3.97108 0 8.86991C0 15.1815 9.88903 23.0112 13.4126 25.5976C14.1436 26.1341 15.1369 26.1341 15.8679 25.5976C19.3915 23.0088 29.2805 15.1815 29.2805 8.86991C29.2782 3.97108 25.5718 0 21.001 0Z" fill="#C73130"></path></svg></span> <span class="total-wishlist ms-1">{{$wishlistCount}}</span>
                    </div>
                    --}}
                    <div class="item_origin_cbmp fs-12">
                        @if($detail->cbmp != "")
                        <span><b>Số CBMP:</b> {{$detail->cbmp}}</span>
                        @endif
                        @if($detail->origin)
                        @if($detail->cbmp != "") <span class="separator">|</span> @endif
                        <span><b>Xuất xứ:</b> {{$detail->origin->name}}</span>
                        @endif
                    </div>
                @if(checkFlash($detail->id))
                @php 
                    $date = strtotime(date('Y-m-d H:i:s'));
                    $flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
                @endphp
                <div class="div_flashsale">
                    <div class="flash-sale-left">
                        <span class="flash-text">FL<span class="lightning-icon">⚡</span>SH SALE</span>
                    </div>
                    <div class="flash-sale-right">
                        <svg class="clock-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                            <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span class="ends-text">KẾT THÚC TRONG</span>
                        <div class="timer_flash">
                            <div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>
                        </div>
                    </div>
                </div>
                <script>
                    function toUnit(time, a, b) {
                        return String(Math.floor((time % a) / b)).padStart(2, '0');
                    }
                    function formatTimer(time) {
                        if (Number.isNaN(parseInt(time, 10))) {
                            return '';
                        }
                        const days = Math.floor(time / 86400);
                        const hours = toUnit(time, 86400, 3600);
                        const minutes = toUnit(time, 3600, 60);
                        const seconds = toUnit(time, 60, 1);

                        if (days > 0) {
                            return `<div class="timer-box">${hours}</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                        }
                        if (hours > 0) {
                            return `<div class="timer-box">${hours}</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                        }
                        if(days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0){
                            window.location = window.location.href;
                            return `<div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>`;
                        }
                        return `<div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                    }
                    const deadline = new Date('{{date("Y/m/d H:i:s",$flash->end)}}');
                    let remainingTime = (deadline - new Date) / 1000;
                    setInterval(function () {
                        remainingTime--;
                        $('.timer_flash').html(formatTimer(remainingTime));
                    }, 1000);
                </script>
                @elseif(checkStartFlash($detail->id))
                    @php 
                        $date = strtotime(date('Y-m-d H:i:s'));
                        $newdate = strtotime ('+24 hour' ,$date) ;
                        $flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$newdate],['end','>=',$date]])->first();
                    @endphp
                    <div class="div_flashsale">
                        <div class="flash-sale-left">
                            <span class="flash-text">FL<span class="lightning-icon">⚡</span>SH SALE</span>
                        </div>
                        <div class="flash-sale-right">
                            <svg class="clock-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="ends-text">KẾT THÚC TRONG</span>
                            <div class="timer_flash">
                                <div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>
                            </div>
                        </div>
                    </div>
                    <script>
                        function toUnit(time, a, b) {
                            return String(Math.floor((time % a) / b)).padStart(2, '0');
                        }
                        function formatTimer(time) {
                            if (Number.isNaN(parseInt(time, 10))) {
                                return '';
                            }
                            const days = Math.floor(time / 86400);
                            const hours = toUnit(time, 86400, 3600);
                            const minutes = toUnit(time, 3600, 60);
                            const seconds = toUnit(time, 60, 1);

                            if (days > 0) {
                                return `<div class="timer-box">${hours}</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                            }
                            if (hours > 0) {
                                return `<div class="timer-box">${hours}</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                            }
                            if(days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0){
                                window.location = window.location.href;
                                return `<div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>`;
                            }
                            return `<div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">${minutes}</div><span class="timer-separator">:</span><div class="timer-box">${seconds}</div>`;
                        }
                        const deadline = new Date('{{date("Y/m/d H:i:s",$flash->start)}}');
                        let remainingTime = (deadline - new Date) / 1000;
                        setInterval(function () {
                            remainingTime--;
                            $('.timer_flash').html(formatTimer(remainingTime));
                        }, 1000);
                    </script>
                @endif
                @if($isShopeeVariant)
                <div class="box-variant box-option1">
                    <div class="label">
                        <strong>{{ $detail->option1_name ?? 'Phân loại' }}:</strong>
                        <span id="variant-option1-current">{{ $first->option1_value ?? '' }}</span>
                    </div>
                    <div class="list-variant" id="variant-option1-list">
                        @foreach($variants as $k => $v)
                        @php
                            $optLabel = $v->option1_value;
                            if(!$optLabel){
                                $color = optional($v->color)->name;
                                $size  = optional($v->size)->name;
                                $optLabel = trim(($color ?: '') . (($color && $size) ? ' / ' : '') . ($size ?: ''));
                            }
                            if(!$optLabel) $optLabel = 'Mặc định';
                            
                            // 计算变体的最终价格（按优先级：闪购 -> 促销 -> 原价）
                            $variantPriceInfo = getVariantFinalPrice($v->id, $detail->id);
                            
                            // Calculate stock_display with priority: Flash Sale > Deal > Available from Warehouse only
                            // NO fallback to old $v->stock field - Warehouse is the single source of truth
                            $stockDisplay = (int) ($v->stock_display ?? $v->warehouse_stock ?? 0);
                            $isOutOfStock = $stockDisplay <= 0;
                            
                            // Store stock source info for JS
                            $stockSource = $v->stock_source ?? 'warehouse';
                            $hasFlashSale = $v->has_flash_sale ?? false;
                            $hasDeal = $v->has_deal ?? false;
                        @endphp
                        <div class="item-variant @if($k==0) active @endif @if($isOutOfStock) out-of-stock @endif"
                             data-variant-id="{{$v->id}}"
                             data-sku="{{$v->sku}}"
                             data-price="{{$variantPriceInfo['final_price']}}"
                             data-original-price="{{$variantPriceInfo['original_price']}}"
                             data-price-html="{{base64_encode($variantPriceInfo['html'])}}"
                             data-stock="{{$stockDisplay}}"
                             data-stock-source="{{$stockSource}}"
                             data-has-flash-sale="{{$hasFlashSale ? '1' : '0'}}"
                             data-has-deal="{{$hasDeal ? '1' : '0'}}"
                             data-image="{{getImage($v->image ?: $detail->image)}}"
                             data-option1="{{ $optLabel }}"
                             @if($isOutOfStock) style="opacity: 0.5; cursor: not-allowed; pointer-events: none;" @endif>
                            <p class="mb-0">{{ $optLabel }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                <input type="hidden" name="variant_id"  value="{{$first->id}}">
                <!-- Stock Container: Locked from Server - Moved below variant selector -->
                <div id="stock-container" style="margin-top: 10px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; font-size: 13px; color: #666;">
                    <span class="stock-label"><strong>Tồn kho:</strong></span>
                    <span id="variant-stock-value" 
                          data-is-locked="true" 
                          data-server-stock="{{ $serverStockDisplay }}"
                          style="font-weight: 600; color: #333; margin-left: 5px;">{{ $serverStockDisplay > 0 ? number_format($serverStockDisplay, 0, ',', '.') : 'Hết hàng' }}</span>
                    <span class="stock-unit" style="margin-left: 3px;">{{ $serverStockDisplay > 0 ? 'sản phẩm' : '' }}</span>
                </div>
                <div class="group-cart product-action align-center mt-3 space-between">
                    <div class="quantity align-center quantity-selector">
                        <button class="btn_minus entry" type="button" @if($currentVariantStock <= 0) disabled @endif>
                            <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                        </button>
                        <input @if($currentVariantStock <= 0) disabled @endif type="text" class="form-quatity quantity-input" value="1" min="1">
                        <button @if($currentVariantStock <= 0) disabled @endif class="btn_plus entry" type="button">
                            <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                        </button>
                    </div>
                    <div class="item-action">
                        <button @if($currentVariantStock <= 0) disabled @endif type="button" class="addCartDetail">
                            <span role="img" class="icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.5H14.5L10.5 0.5C10.3 0.2 10 0 9.7 0C9.4 0 9.1 0.2 8.9 0.5L4.9 6.5H0.5C0.2 6.5 0 6.7 0 7C0 7.1 0 7.2 0.1 7.3L2.3 16.3C2.5 17 3.1 17.5 3.8 17.5H16.2C16.9 17.5 17.5 17 17.7 16.3L19.9 7.3L20 7C20 6.7 19.8 6.5 19.5 6.5H19ZM9.7 2.5L12.5 6.5H6.9L9.7 2.5ZM16.2 16.5H3.8L1.8 8.5H18.2L16.2 16.5ZM9.7 10.5C8.9 10.5 8.2 11.2 8.2 12C8.2 12.8 8.9 13.5 9.7 13.5C10.5 13.5 11.2 12.8 11.2 12C11.2 11.2 10.5 10.5 9.7 10.5Z" stroke="#ee4d2d" stroke-width="1.5" fill="none"/><path d="M9.7 8.5V15.5M6.2 12H13.2" stroke="#ee4d2d" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                            <span>@if($currentVariantStock <= 0) Hết hàng @else Thêm Vào Giỏ Hàng @endif</span>
                        </button>
                    </div>
                    <div class="item-action">
                        @if(isset($saledeals) && $saledeals->count() > 0)
                        <button @if($currentVariantStock <= 0) disabled @endif class="buyNowDetail btnBuyDealSốc" type="button">@if($currentVariantStock <= 0) Hết hàng @else MUA DEAL SỐC @endif</button>
                        @else
                        <button @if($currentVariantStock <= 0) disabled @endif class="buyNowDetail" type="button">@if($currentVariantStock <= 0) Hết hàng @else Mua ngay @endif</button>
                        @endif
                    </div>
                </div>
                <!-- Flash Sale Warning Container -->
                <div class="flash-sale-warning-container" style="margin-top: 10px;"></div>
                @if(isset($saledeals) && $saledeals->count() > 0)
                <div class="sc-67558998-0 buy-x-get-y-wrapper mb-4">
                    <div class="buy-x-get-y-header">
                        <div class="title">Mua kèm deal sốc</div>
                        <div class="sub-title">Mua để nhận ưu đãi (Tối đa {{$deal->limited}})</div>
                    </div>
                    <div class="buy-x-get-y-body">
                        @foreach($saledeals as $saledeal)
                        @php 
                            $product_deal = $saledeal->product; 
                            $isAvailableDeal = $saledeal->available ?? true;
                        @endphp
                        <div class="item_deal_row @if(!$isAvailableDeal) text-muted @endif">
                            <div class="item_deal_action me-3">
                                @if($deal->limited == 1)
                                    <input type="radio" name="deal_item" class="deal-checkbox-custom" id="deal_{{$saledeal->id}}" value="{{$product_deal->variant($product_deal->id)->id ?? ''}}" @if(!$isAvailableDeal) disabled @endif>
                                @else
                                    <input type="checkbox" name="deal_item[]" class="deal-checkbox-custom" id="deal_{{$saledeal->id}}" value="{{$product_deal->variant($product_deal->id)->id ?? ''}}" @if(!$isAvailableDeal) disabled @endif>
                                @endif
                                <label for="deal_{{$saledeal->id}}" class="deal-checkmark"></label>
                            </div>
                            <div class="item_deal_info">
                                <div class="thumb_deal">
                                    <div class="skeleton--img-sm js-skeleton">
                                        <img src="{{getImage($product_deal->image)}}" alt="{{$product_deal->name}}" class="js-skeleton-img" loading="lazy">
                                    </div>
                                </div>
                                <div class="info_deal">
                                    <h5 class="deal-product-name">{{$product_deal->name}}</h5>
                                    <div class="price_deal">
                                        <span class="curr-price">{{number_format($saledeal->price)}}đ</span>
                                        <del class="old-price">{{number_format($product_deal->variant($product_deal->id)->price ?? 0)}}đ</del>
                                    </div>
                                    @if(!$isAvailableDeal)
                                        <div class="text-danger fs-12 mt-1">Deal đã hết quà hoặc hết kho</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <style>
                    .buy-x-get-y-wrapper {
                        border: 1px dashed #684d98;
                        border-radius: 10px;
                        padding: 15px;
                        background: rgba(104, 77, 152, 0.03);
                        margin-top: 20px;
                    }
                    .buy-x-get-y-header .title {
                        font-size: 16px;
                        font-weight: 700;
                        color: #000;
                        text-transform: uppercase;
                    }
                    .buy-x-get-y-header .sub-title {
                        font-size: 13px;
                        color: #666;
                        margin-bottom: 15px;
                    }
                    .item_deal_row {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 0;
                        border-bottom: 1px solid rgba(0,0,0,0.05);
                    }
                    .item_deal_row:last-child { border-bottom: none; }
                    .item_deal_info { display: flex; align-items: center; flex: 1; }
                    .thumb_deal { width: 50px; height: 50px; margin-right: 12px; }
                    .thumb_deal img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }
                    .deal-product-name {
                        font-size: 13px;
                        font-weight: 500;
                        margin: 0 0 4px;
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                        line-height: 1.4;
                    }
                    .curr-price { color: #ee4d2d; font-weight: 700; font-size: 14px; }
                    .old-price { font-size: 11px; color: #999; margin-left: 5px; }
                    
                    /* Custom Checkbox/Radio */
                    .item_deal_action { position: relative; width: 20px; height: 20px; flex-shrink: 0; }
                    .deal-checkbox-custom { display: none !important; }
                    .deal-checkmark {
                        position: absolute; top: 0; left: 0; width: 20px; height: 20px;
                        background: #fff; border: 2px solid #ddd; border-radius: 4px; cursor: pointer;
                        margin-bottom: 0 !important;
                    }
                    .deal-checkbox-custom:checked + .deal-checkmark {
                        background: #684d98; border-color: #684d98;
                    }
                    .deal-checkbox-custom:checked + .deal-checkmark:after {
                        content: ''; position: absolute; left: 6px; top: 2px;
                        width: 5px; height: 10px; border: solid white;
                        border-width: 0 2px 2px 0; transform: rotate(45deg);
                        display: block !important;
                    }
                    input[type="radio"].deal-checkbox-custom + .deal-checkmark { border-radius: 50%; }
                </style>
                <script>
                    $(document).ready(function(){
                        var limited = {{ $deal->limited }};
                        
                        $('.item_deal_row').click(function(e){
                            if(e.target.type !== 'checkbox' && e.target.type !== 'radio'){
                                $(this).find('.deal-checkbox-custom').prop('checked', !$(this).find('.deal-checkbox-custom').prop('checked')).trigger('change');
                            }
                        });

                        $('.deal-checkbox-custom').change(function(){
                            if(limited >= 1) {
                                var checkedCount = $('.deal-checkbox-custom:checked').length;
                                if(checkedCount > limited) {
                                    $(this).prop('checked', false);
                                    alert('Bạn chỉ được chọn tối đa ' + limited + ' sản phẩm mua kèm');
                                }
                            }
                        });
                    });
                </script>
                @endif
                </div>
                <!-- End Blade Template Fallback -->
                    {{-- 隐藏SKU
                    <div class="item_5 sku-section w40 fs-12"><b>SKU:</b> <span id="variant-sku-display">{{$first->sku}}</span></div>
                    --}}
                    {{-- 隐藏已验证部分（移动端）
                    @if($detail->verified == 1)
                    <div class="verified w60 fs-12 d-block d-md-none">
                        <strong>Đã xác thực bởi:</strong> {{getConfig('verified')}} 
                        <span class="show_verified"><svg viewBox="64 64 896 896" focusable="false" data-icon="question-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 708c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40zm62.9-219.5a48.3 48.3 0 00-30.9 44.8V620c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8v-21.5c0-23.1 6.7-45.9 19.9-64.9 12.9-18.6 30.9-32.8 52.1-40.9 34-13.1 56-41.6 56-72.7 0-44.1-43.1-80-96-80s-96 35.9-96 80v7.6c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V420c0-39.3 17.2-76 48.4-103.3C430.4 290.4 470 276 512 276s81.6 14.5 111.6 40.7C654.8 344 672 380.7 672 420c0 57.8-38.1 109.8-97.1 132.5z"></path></svg></span>
                    </div>
                    @endif
                    --}}
                </div>
                     {{-- 隐藏已验证部分（桌面端）
                     @if($detail->verified == 1)
                    <div class="verified w60 fs-12 d-none d-md-block">
                        <strong>Đã xác thực bởi:</strong> {{getConfig('verified')}} 
                        <span class="show_verified"><svg viewBox="64 64 896 896" focusable="false" data-icon="question-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 708c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40zm62.9-219.5a48.3 48.3 0 00-30.9 44.8V620c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8v-21.5c0-23.1 6.7-45.9 19.9-64.9 12.9-18.6 30.9-32.8 52.1-40.9 34-13.1 56-41.6 56-72.7 0-44.1-43.1-80-96-80s-96 35.9-96 80v7.6c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V420c0-39.3 17.2-76 48.4-103.3C430.4 290.4 470 276 512 276s81.6 14.5 111.6 40.7C654.8 344 672 380.7 672 420c0 57.8-38.1 109.8-97.1 132.5z"></path></svg></span>
                    </div>
                    @endif
                    --}}
                </div>
                @if(!$isShopeeVariant && $colors->count() > 0 && $colors[0]->color_id != 0)
                <div class="box-variant box-color" @if($colors[0]->color->id == '22') style="display:none" @endif>
                    <div class="label">
                        <strong>Màu sắc:</strong>
                        <span>{{$colors[0]->color->name??''}}</span>
                        <input type="hidden" name="color_id" value="{{$colors[0]->color->id??''}}">
                    </div>
                    <div class="list-variant">
                        @foreach($colors as $key => $color)
                        <div class="item-variant @if($key == 0) active @endif" data-id="{{$color->color->id??''}}" data-text="{{$color->color->name??''}}">
                            <span style="background-color:{{$color->color->color??''}}"></span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(!$isShopeeVariant)
                <div class="box-variant box-size">
                    {!!getSizes($detail->id,$colors[0]->color->id??'')!!}
                </div>
                @endif
                <!--  So sánh cửa hàng -->
                @if($compares->count() > 0)
                <div class="div_compare">
                    <h3>Sản phẩm ở cửa hàng khác</h3>
                    <div class="list_compare">
                        @foreach($compares as $compare)
                        <div class="item_compare">
                <div class="logo_compare">
                    <div class="skeleton--img-logo js-skeleton">
                        <img src="{{getImage($compare->store->logo??'')}}" alt="{{$compare->store->name??''}}" class="js-skeleton-img">
                    </div>
                </div>
                            <div class="caption_compare">
                                <span>{{number_format($compare->price)}}đ</span>
                                @if($compare->is_link == 1)
                                <a href="{{$compare->link}}" target="_bank" rel="nofollow">Mua</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <style>
                    .item_compare{background-color: #f9fafc;padding:10px;display: flex;justify-content: space-between;align-items: center;height:60px;margin-bottom: 20px;overflow: hidden;}
                    .item_compare .logo_compare{
                        width: 30%;
                    }
                    .div_compare h3{font-size: 16px;}
                    .item_compare .logo_compare img{max-width: 100%;height: 100%;}
                    .item_compare .caption_compare a{display: inline-block;background-color: #d0fae4;color:#0f4f37;padding:0px 10px;font-size:16px;font-weight: 600;margin-left: 10px;height:40px;line-height: 40px}
                    .item_compare .caption_compare span{color:#474f62;font-weight: 600;font-size: 18px;}
                    @media(max-width:468px){.item_compare .logo_compare{width: 40%;}}
                </style>
                @endif
            </div>
        </div>
    </div>
    <div class="container-lg">
        @if($detail->content != "")
        <div class="row mt-5 mb-5" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important; margin-bottom: 1rem !important;">
            <div class="row g-0" style="margin-left: 0; margin-right: 0;">
                <div class="col-12 col-md-4">
                    <div class="fw-bold fs-24">Giới thiệu</div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="product-content">
                        <div class="content">
                            {!!$detail->content!!}
                        </div>
                        <div class="bg-cover"></div>
                    </div>
                    <div class="text-center">
                        <button type="button" class="btn_viewmore" data-show="false">Xem thêm nội dung</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($detail->ingredient != "")
        <div class="row mt-5 mb-5" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important; margin-bottom: 1rem !important;">
            <div class="row g-0" style="margin-left: 0; margin-right: 0;">
                <div class="col-12 col-md-4">
                    <div class="fw-bold fs-24">Thành phần</div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="detail-content ingredient">
                        @php
                            // Normalize ingredient string and split by comma
                            $rawIng = strip_tags($detail->ingredient ?? '');
                            $parts = array_filter(array_map('trim', explode(',', $rawIng)), function ($v) {
                                return $v !== '';
                            });

                            // Prefer processedIngredients from controller, fallback to allIngredients if provided
                            $sourceIngredients = [];
                            if (isset($processedIngredients) && is_array($processedIngredients)) {
                                $sourceIngredients = $processedIngredients;
                            } elseif (isset($allIngredients) && is_array($allIngredients)) {
                                $sourceIngredients = $allIngredients;
                            }

                            // Build lookup map by lower-case name/title
                            $ingredientLookup = [];
                            foreach ($sourceIngredients as $ing) {
                                $name = '';
                                if (is_array($ing)) {
                                    $name = $ing['name'] ?? ($ing['title'] ?? '');
                                } else {
                                    $name = $ing->name ?? ($ing->title ?? '');
                                }
                                $key = trim(mb_strtolower((string) $name, 'UTF-8'));
                                if ($key !== '') {
                                    $ingredientLookup[$key] = $ing;
                                }
                            }
                        @endphp

                        @if(!empty($parts) && !empty($ingredientLookup))
                            <div class="ingredient-list ingredient-list-collapsed">
                            @foreach($parts as $token)
                                @php
                                    $tokenTrim = trim($token);
                                    $lookupKey = mb_strtolower($tokenTrim, 'UTF-8');
                                    $info = $ingredientLookup[$lookupKey] ?? null;

                                    $benefits = [];
                                    $rates = [];
                                    $slug = '';

                                    if ($info) {
                                        if (is_array($info)) {
                                            $benefits = $info['benefit_icons'] ?? ($info['benefits'] ?? []);
                                            $rates = $info['skin_types'] ?? ($info['rates'] ?? []);
                                            $slug = (string) ($info['slug'] ?? '');
                                        } else {
                                            $benefits = $info->benefit_icons ?? ($info->benefits ?? []);
                                            $rates = $info->skin_types ?? ($info->rates ?? []);
                                            $slug = (string) ($info->slug ?? '');
                                        }
                                    }

                                    // Build tooltip text from benefits and skin types
                                    $benefitNames = [];
                                    foreach ($benefits as $b) {
                                        if (is_array($b)) {
                                            $bn = (string) ($b['name'] ?? '');
                                        } else {
                                            $bn = (string) ($b->name ?? '');
                                        }
                                        if ($bn !== '') {
                                            $benefitNames[] = $bn;
                                        }
                                    }
                                    $benefitNames = array_values(array_unique($benefitNames));

                                    $rateNames = [];
                                    foreach ($rates as $r) {
                                        if (is_array($r)) {
                                            $rn = (string) ($r['name'] ?? '');
                                        } else {
                                            $rn = (string) ($r->name ?? '');
                                        }
                                        if ($rn !== '') {
                                            $rateNames[] = $rn;
                                        }
                                    }
                                    $rateNames = array_values(array_unique($rateNames));

                                    $tooltipParts = [];
                                    if (!empty($benefitNames)) {
                                        $tooltipParts[] = 'Cong dung: ' . implode(', ', $benefitNames);
                                    }
                                    if (!empty($rateNames)) {
                                        $tooltipParts[] = 'Phu hop: ' . implode(', ', $rateNames);
                                    }
                                    $tooltipText = !empty($tooltipParts) ? implode(' | ', $tooltipParts) : $tokenTrim;

                                    // Map benefits to simple icon classes (ASCII-safe)
                                    $iconHtml = '';
                                    $usedIcons = [];
                                    foreach ($benefitNames as $bn) {
                                        $lower = mb_strtolower($bn, 'UTF-8');
                                        $iconClass = '';
                                        if (strpos($lower, 'cap am') !== false || strpos($lower, 'hydrate') !== false || strpos($lower, 'moistur') !== false) {
                                            $iconClass = 'fa fa-tint'; // hydration
                                        } elseif (strpos($lower, 'sang') !== false || strpos($lower, 'brighten') !== false || strpos($lower, 'whiten') !== false) {
                                            $iconClass = 'fa fa-sun-o'; // brightening
                                        } elseif (strpos($lower, 'phuc hoi') !== false || strpos($lower, 'repair') !== false || strpos($lower, 'soothing') !== false) {
                                            $iconClass = 'fa fa-leaf'; // recovery
                                        }
                                        if ($iconClass && !in_array($iconClass, $usedIcons, true)) {
                                            $usedIcons[] = $iconClass;
                                            $iconHtml .= '<i class="benefit-icon ' . $iconClass . '" aria-hidden="true" style="margin-left:4px;font-size:11px;"></i>';
                                        }
                                    }
                                @endphp
                                <span class="ingredient-item item_ingredient"
                                      data-toggle="tooltip"
                                      title="{{ $tooltipText }}"
                                      @if($slug) data-id="{{ $slug }}" @endif
                                      style="margin:0 0 4px 0;">
                                    {{ $tokenTrim }}{!! $iconHtml !!}
                                </span>
                            @endforeach
                            </div>
                            <button type="button" class="btn btn-link btn-sm ingredient-toggle d-md-none" data-expanded="false" style="padding:0;margin-top:6px;">
                                Xem day du
                            </button>
                        @else
                            @php 
                                // Fallback to legacy HTML if we do not have processed ingredient data
                                $str = $detail->ingredient;
                                if (strpos($str, 'item_ingredient') === false) {
                                    // Use IngredientPaulas dictionary as fallback source
                                    $list =  App\Modules\Dictionary\Models\IngredientPaulas::where('status','1')->get();
                                    if(isset($list) && !empty($list)){
                                        foreach($list as $value){
                                            $linkText = '<a href="javascript:;" class="item_ingredient" data-id="'.$value->slug.'">'.$value->name.'</a>';
                                            $str = str_replace($value->name, $linkText, $str);
                                        }
                                    }
                                }
                            @endphp
                            {!! $str !!}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
                @php
                    // Chuẩn hoá lại $t_rates và $rates ở block đánh giá để tránh null
                    $tRateCol = collect($t_rates ?? []);
                    $tRateCount = $tRateCol->count();
                    $tRateSum   = $tRateCol->sum('rate');
                    $rateCol = collect($rates ?? []);
                @endphp
                <div class="row rating-product mt-5 mb-5" id="ratingProduct" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important; margin-bottom: 1rem !important;">
                    <div class="row g-0" style="margin-left: 0; margin-right: 0;">
            <div class="col-12 col-md-4 pe-3 pe-md-5">
                <div class="align-center space-between">
                    <div class="fs-24 fw-bold">{{$tRateCount}} đánh giá</div>
                    <button class="btn btn_write_review text-uppercase fs-14 fw-bold pe-0 ps-0" type="button" data-bs-toggle="modal" data-bs-target="#myRating">Viết đánh giá</button>
                </div>
                    <div class="list-star mt-3 mb-1 fs-26">
                        {!!getStar($tRateSum,$tRateCount)!!}
                    </div>
                @php $star5 = itemStar($detail->id,5)@endphp
                <div class="align-center space-between mb-1">
                    <div class="me-3 fs-18">5</div>
                    <div class="progress-outer @if($star5 > 0) active @endif"></div>
                    <div class="ms-3 fs-16">({{$star5}})</div>
                </div>
                @php $star4 = itemStar($detail->id,4)@endphp
                <div class="align-center space-between mb-1">
                    <div class="me-3 fs-18">4</div>
                    <div class="progress-outer @if($star4 > 0) active @endif"></div>
                    <div class="ms-3 fs-16">({{$star4}})</div>
                </div>
                @php $star3 = itemStar($detail->id,3)@endphp
                <div class="align-center space-between mb-1">
                    <div class="me-3 fs-18">3</div>
                    <div class="progress-outer @if($star3 > 0) active @endif"></div>
                    <div class="ms-3 fs-16">({{$star3}})</div>
                </div>
                @php $star2 = itemStar($detail->id,2)@endphp
                <div class="align-center space-between mb-1">
                    <div class="me-3 fs-18">2</div>
                    <div class="progress-outer @if($star2 > 0) active @endif"></div>
                    <div class="ms-3 fs-16">({{$star2}})</div>
                </div>
                @php $star1 = itemStar($detail->id,1)@endphp
                <div class="align-center space-between mb-1">
                    <div class="me-3 fs-18">1</div>
                    <div class="progress-outer @if($star1 > 0) active @endif"></div>
                    <div class="ms-3 fs-16">({{$star1}})</div>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="list-rate">
                @if($rateCol->count() > 0)
                    @foreach($rateCol as $rate)
                    @php
                        // Normalize rate item to object to support both array and model
                        $r = is_array($rate) ? (object) $rate : $rate;
                        $images = isset($r->images) ? json_decode($r->images) : [];
                        $rName = isset($r->name) ? $r->name : '';
                        $rTitle = isset($r->title) ? $r->title : '';
                        $rContent = isset($r->content) ? $r->content : '';
                        $rCreatedAt = isset($r->created_at) ? $r->created_at : null;
                        $rRateValue = property_exists($r, 'rate') ? $r->rate : 0;
                    @endphp
                    <div class="item-rate">
                        <p class="mb-2">{{$rName}}</p>
                        <div class="align-center mb-2">
                            <div class="rating me-3 mt-0 mb-0">{!!getStar($rRateValue,1)!!}</div>
                            <div class="text-gray">
                                @if($rCreatedAt)
                                    {{date('d/m/Y H:i',strtotime($rCreatedAt))}}
                                @endif
                            </div>
                        </div>
                        <div class="fw-bold mb-2">{{$rTitle}}</div>
                        <div>{{$rContent}}</div>
                        @if(isset($images) && !empty($images))
                            <div class="list_gallery">
                            @foreach($images as $image)
                            <a href="{{getImage($image)}}" class="item_gallery image-link">
                                <div class="skeleton--img-sm js-skeleton">
                                    <img src="{{getImage($image)}}" alt="{{$rName}}" class="js-skeleton-img" loading="lazy">
                                </div>
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                    @else
                    <div class="box-empty"><img src="/public/website/images/icon_nodata.PNG" alt="icon"><p>No Data</p></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="row product-related mt-5 mb-5" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important; margin-bottom: 1rem !important;">
            <div class="row g-0" style="margin-left: 0; margin-right: 0;">
                <div class="col-12 col-md-4 mb-3 mb-md-0">
                    <div class="fw-bold fs-24">Sản phẩm liên quan</div>
                </div>
                <div class="col-12 col-md-8">
                @if($products->count() > 0)
                <div class="list-product">
                    @foreach($products->take(6) as $product)
                    @php $trate = App\Modules\Rate\Models\Rate::select('id','rate')->where([['status','1'],['product_id',$product->id]])->get() @endphp
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
                                @if(isset($product->stock_display) && $product->stock_display <= 0)
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
                                        $rateCount = $trate->count() ?? 0;
                                        $rateSum = $trate->sum('rate') ?? 0;
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
                @endif
                </div>
            </div>
        </div>
        
        <!-- 智能推荐产品区域 -->
        <div class="row recommendations-section mt-5 mb-5" style="background: #fff; border-radius: 5px; padding: 15px; margin-top: 1rem !important; margin-bottom: 1rem !important;">
            <h3 class="fw-bold fs-25 mb-3" style="text-align: left;">Có thể bạn thích</h3>
            <div class="list-flash mt-3 product-recommendations recommendations-grid-3x6 recommendations-no-carousel" 
                 data-exclude="{{$detail->id ?? ''}}" 
                 data-limit="12">
                <div class="recommendations-loading text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải sản phẩm đề xuất...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>
@endsection
@section('footer')
<div class="product-fix">
    <div class="container-lg">
        <div class="box-product d-flex align-center space-between">
            <div class="align-center product-info">
                <div class="thumb">
                    <div class="skeleton--img-sm js-skeleton" style="width: 72px; height: 72px;">
                        <img src="{{getImage($detail->image)}}" width="72" height="72" alt="{{$detail->name}}" class="js-skeleton-img" loading="lazy">
                    </div>
                </div>
                <div class="description ms-2">
                    <div class="fs-16 fw-bold">{{$detail->name}}</div>
                    @if($isShopeeVariant)
                        @php
                            $firstPriceInfoFix = getVariantFinalPrice($first->id ?? 0, $detail->id);
                        @endphp
                        <div class="price-fix" id="variant-price-fix">{!! $firstPriceInfoFix['html'] !!}</div>
                    @else
                        <div class="price-fix">{!!checkSale($detail->id)!!}</div>
                    @endif
                </div>
            </div>
            <div class="product-action align-center">
                <div class="item-action">
                    <button @if($currentVariantStock <= 0) disabled @endif type="button" class="addCartDetail">
                        <span role="img" class="icon"><svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg></span>
                        <span>@if($currentVariantStock <= 0) Hết hàng @else Thêm vào giỏ hàng @endif</span>
                    </button>
                </div>
                <div class="item-action">
                    @if(isset($saledeals) && $saledeals->count() > 0)
                    <button @if($currentVariantStock <= 0) disabled @endif class="buyNowDetail btnBuyDealSốc" type="button">@if($currentVariantStock <= 0) Hết hàng @else MUA DEAL SỐC @endif</button>
                    @else
                    <button @if($currentVariantStock <= 0) disabled @endif class="buyNowDetail" type="button">@if($currentVariantStock <= 0) Hết hàng @else Mua ngay @endif</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@php $member = auth()->guard('member')->user(); @endphp
<div class="modal" tabindex="-1" id="myRating">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-body">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="fw-bold fs-24 text-center mb-3 mt-2">Viết đánh giá</div>
        <div class="align-center bg-gray br-10 pe-3 ps-3 pt-3 pb-3 mb-3">
            <div class="thumb-pro">
                <div class="skeleton--img-sm js-skeleton" style="width: 65px; height: 65px;">
                    <img src="{{getImage($detail->image)}}" width="65" height="65" alt="{{$detail->name}}" class="js-skeleton-img" loading="lazy">
                </div>
            </div>
            <div class="des-pro ps-3">
                @if($detail->brand)<div class="fs-14 fw-600">{{$detail->brand->name}}</div>@endif
                <a class="d-block fs-16" href="{{getSlug($detail->slug)}}">{{$detail->name}}</a>
            </div>
        </div>
        <form class="formRating mb-3" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="product_id" value="{{$detail->id}}">
            <div class="row mb-3">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <label>Bạn có sẵn sàng giới thiệu sản phẩm này không?</label>
                    <div class="form-check form-check-inline ps-0">
                      <input class="form-check-input" name="is_aff" type="checkbox" id="inlineCheckbox2" value="1">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label>Đánh giá chung <span>*</span></label>
                    <div id="rating"></div>
                </div>
            </div>
            <div class="row mb-3" style="margin-top: 1rem !important;">
                <div class="col-6">
                    <label>Biệt danh <span>*</span></label>
                    <input type="text" require class="form-control" name="name" value="@if(isset($member)){{$member['first_name']}} {{$member['last_name']}}@endif" placeholder="Nguyễn Văn An" autocomplete="false">
                </div>
                <div class="col-6">
                    <label>Email <span>*</span></label>
                    <input type="email" require class="form-control" name="email" value="@if(isset($member)){{$member['email']}}@endif" placeholder="admin@gmail.com" autocomplete="false">
                </div>
            </div>
            <div class="row mb-3" style="margin-top: 1rem !important;">
                <div class="col-12">
                    <label>Tóm tắt đánh giá <span>*</span></label>
                    <input type="text" class="form-control" require name="title" placeholder="Tóm tắt đánh giá của bạn" autocomplete="false">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <label>Hình ảnh</label>
                    <div class="input-field">
                        <div class="input-images" style="padding-top: .5rem;"></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <label>Đánh giá chi tiết</label>
                    <textarea name="content" rows="4" class="form-control height-auto" placeholder="Viết đánh giá chi tiết"></textarea>
                </div>
            </div>
            <div class="mt-2 opacity-05">Bạn có thể nói thêm về sản phẩm ở dưới đây, ví dụ như độ hoàn thiện, sự thoải mái</div>
            <div class="text-center mt-3">
                <button class="btn btn-default ps-5 pe-5" type="submit">Gửi cho chúng tôi</button>
            </div>
            <div class="box-alert"></div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal" tabindex="-1" id="myVerified">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-body">
         <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        @if(getConfig('link_verified') != "")
            <a href="{{getConfig('link_verified')}}" target="_bank"><img src="{{getConfig('verified_content')}}" class="w-100" alt="{{getConfig('verified')}}"></a>
        @else
            <img src="{{getConfig('verified_content')}}" class="w-100" alt="{{getConfig('verified')}}">
        @endif
      </div>
  </div>
</div>
</div>
<link type="text/css" rel="stylesheet" href="/public/website/upload/image-uploader.min.css">
<link rel="stylesheet" href="/public/website/magnific/magnific-popup.css">
<script type="text/javascript" src="/public/website/upload/image-uploader.min.js"></script>
<script type="text/javascript" src="/public/website/magnific/jquery.magnific-popup.min.js"></script>
<script src="/public/website/js/rating.js"></script>
<script>
    $('.show_verified').click(function(){
        var myVerified = new bootstrap.Modal(document.getElementById('myVerified'))
        myVerified.show();
    });
    $('.image-link').magnificPopup({
        type: 'image',
       mainClass: 'mfp-with-zoom',
       gallery:{
                enabled:true
            },
      zoom: {
        enabled: true, 
        duration: 300, // duration of the effect, in milliseconds
        easing: 'ease-in-out', // CSS transition easing function
        opener: function(openerElement) {
          return openerElement.is('img') ? openerElement : openerElement.find('img');
      }
    }
    })
    $('.input-images').imageUploader();
    (function ($) {
    $.switcher = function (filter) {
        var $haul = $('input[type=checkbox],input[type=radio]');
        if (filter !== undefined && filter.length) {
            $haul = $haul.filter(filter);
        }
        $haul.each(function () {
            var $checkbox = $(this).hide(),
                $switcher = $(document.createElement('div'))
                    .addClass('ui-switcher')
                    .attr('aria-checked', $checkbox.is(':checked'));

            if ('radio' === $checkbox.attr('type')) {
                $switcher.attr('data-name', $checkbox.attr('name'));
            }
            toggleSwitch = function (e) {
                if (e.target.type === undefined) {
                    $checkbox.trigger(e.type);
                }
                $switcher.attr('aria-checked', $checkbox.is(':checked'));
                if ('radio' === $checkbox.attr('type')) {
                    $('.ui-switcher[data-name=' + $checkbox.attr('name') + ']')
                        .not($switcher.get(0))
                        .attr('aria-checked', false);
                }
            };
            $switcher.on('click', toggleSwitch);
            $checkbox.on('click', toggleSwitch);
            $switcher.insertBefore($checkbox);
        });
    };
    })(jQuery);
  $.switcher('.formRating input[type=checkbox], .formRating input[type=radio]');
    $('body').on('change','#fileUpload',function(){
        var src = window.URL.createObjectURL(this.files[0])
        console.log(data);
    });
    $(document).ready(function() {
    rating.create({
        'selector': '#rating',
        'outOf': 5,
        'defaultRating': 5,
        });
    });
    $('.btn_viewmore').click(function(){
        var check =  $(this).attr('data-show');
        if(check == 'false'){
            $('.product-content .content').css('height','auto');
            $('.bg-cover').hide();
            $(this).html('ẨN BỚT NỘI DUNG').attr('data-show','true');
        }else{
            $('.product-content .content').css('height','200px');
            $('.bg-cover').show();
            $(this).html('XEM THÊM NỘI DUNG').attr('data-show','false');
        }
    })
    $('.selectors').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        margin: 10,
        autoplay: false,
        dots:false,
        loop:false,
        responsive: {
            0: {
                items: 4,
                nav: true
            },
            768: {
                items: 5,
                nav: true
            },
            1000: {
                items: 5,
                nav: true,
            }
        }
    });
    $(window).scroll(function() {
        var topPos = $(this).scrollTop();
        if (topPos > 500) {
           $('.product-fix').addClass("active");
        } else {
          $('.product-fix').removeClass("active");
        }
    });
    
    $('body').on('click','.addCartDetail',function(e){
        // Prevent click if button is disabled
        if ($(this).prop('disabled') || $(this).hasClass('disabled')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Check stock from active variant in #variant-option1-list
        // First try to get from active variant
        let stock = null;
        const variantList = $('#variant-option1-list');
        
        if (variantList.length > 0) {
            // Product has variants
            const activeVariant = variantList.find('.item-variant.active');
            if (activeVariant.length > 0) {
                stock = parseInt(activeVariant.data('stock') || activeVariant.attr('data-stock') || 0);
            } else {
                // Fallback: get from hidden input variant_id and find variant
                const variantId = $('input[name="variant_id"]').val();
                if (variantId) {
                    const variant = variantList.find(`.item-variant[data-variant-id="${variantId}"]`);
                    if (variant.length > 0) {
                        stock = parseInt(variant.data('stock') || variant.attr('data-stock') || 0);
                    }
                }
            }
        } else {
            // Product has no variants, check product stock from API data or fallback
            // Try to get from API-loaded data
            const productDetailInfo = document.getElementById('product-detail-info');
            if (productDetailInfo && productDetailInfo.dataset.productData) {
                try {
                    const productData = JSON.parse(productDetailInfo.dataset.productData);
                    stock = productData.warehouse_stock !== undefined ? productData.warehouse_stock : productData.stock;
                } catch(e) {
                    console.warn('Failed to parse product data:', e);
                }
            }
            // If still null, skip stock check (allow action to proceed)
        }
        
        // Only show alert if stock is explicitly 0 or less (not null/undefined)
        if (stock !== null && stock !== undefined && (isNaN(stock) || stock <= 0)) {
            e.preventDefault();
            e.stopPropagation();
            alert('Sản phẩm đã hết hàng');
            return false;
        }
        
        var main_id = $('input[name="variant_id"]').val();
        var main_qty = $('input.quantity-input').val();
        var combo = [];
        
        combo.push({id: main_id, qty: main_qty, is_deal: 0});
        $('.deal-checkbox-custom:checked').each(function(){
            combo.push({id: $(this).val(), qty: 1, is_deal: 1});
        });

        $.ajax({
        type: 'post',
        url: '{{route("cart.add")}}',
        data: {combo: combo},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.addCartDetail').prop('disabled',true);
            $('.addCartDetail .icon').html('<span class="spinner-border text-light"></span>')
        },
        success: function (res) {
            $('.addCartDetail').prop('disabled',false);
            $('.addCartDetail .icon').html('<svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg>');
          if(res.status == 'success'){
            $('.count-cart').html(res.total);
            alert("Đã thêm vào giỏ hàng");
          }else{
            var errorMsg = res.message || "Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại";
            alert(errorMsg);
          }
        },
        error: function(xhr, status, error){
            $('.addCartDetail').prop('disabled',false);
            $('.addCartDetail .icon').html('<svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg>');
            var errorMsg = 'Có lỗi xảy ra, xin vui lòng thử lại';
            if(xhr.responseJSON && xhr.responseJSON.message){
                errorMsg = xhr.responseJSON.message;
            }
            alert(errorMsg);
        }
      })
    });

    $('body').on('click','.buyNowDetail',function(e){
        // Prevent click if button is disabled
        if ($(this).prop('disabled') || $(this).hasClass('disabled')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Check stock from active variant in #variant-option1-list
        // First try to get from active variant
        let stock = null;
        const variantList = $('#variant-option1-list');
        
        if (variantList.length > 0) {
            // Product has variants
            const activeVariant = variantList.find('.item-variant.active');
            if (activeVariant.length > 0) {
                stock = parseInt(activeVariant.data('stock') || activeVariant.attr('data-stock') || 0);
            } else {
                // Fallback: get from hidden input variant_id and find variant
                const variantId = $('input[name="variant_id"]').val();
                if (variantId) {
                    const variant = variantList.find(`.item-variant[data-variant-id="${variantId}"]`);
                    if (variant.length > 0) {
                        stock = parseInt(variant.data('stock') || variant.attr('data-stock') || 0);
                    }
                }
            }
        } else {
            // Product has no variants, check product stock from API data or fallback
            // Try to get from API-loaded data
            const productDetailInfo = document.getElementById('product-detail-info');
            if (productDetailInfo && productDetailInfo.dataset.productData) {
                try {
                    const productData = JSON.parse(productDetailInfo.dataset.productData);
                    stock = productData.warehouse_stock !== undefined ? productData.warehouse_stock : productData.stock;
                } catch(e) {
                    console.warn('Failed to parse product data:', e);
                }
            }
            // If still null, skip stock check (allow action to proceed)
        }
        
        // Only show alert if stock is explicitly 0 or less (not null/undefined)
        if (stock !== null && stock !== undefined && (isNaN(stock) || stock <= 0)) {
            e.preventDefault();
            e.stopPropagation();
            alert('Sản phẩm đã hết hàng');
            return false;
        }
        
        if($(this).hasClass('btnBuyDealSốc')) {
            var main_id = $('input[name="variant_id"]').val();
            var main_qty = $('input.quantity-input').val();
            var combo = [];
            
            combo.push({id: main_id, qty: main_qty, is_deal: 0});
            $('.deal-checkbox-custom:checked').each(function(){
                combo.push({id: $(this).val(), qty: 1, is_deal: 1});
            });

            if(combo.length < 2) {
                alert('Vui lòng chọn ít nhất 1 sản phẩm mua kèm');
                return;
            }

            $.ajax({
                type: 'post',
                url: '{{route("cart.add")}}',
                data: {combo: combo},
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                beforeSend: function () {
                    $('.btnBuyDealSốc').prop('disabled',true).html('<span class="spinner-border spinner-border-sm"></span>');
                },
                success: function (res) {
                    $('.btnBuyDealSốc').prop('disabled',false).html('Mua ngay');
                    if(res.status == 'success'){
                        window.location = '{{route("cart.index")}}';
                    }else{
                        var errorMsg = res.message || "Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại";
                        alert(errorMsg);
                    }
                },
                error: function(xhr, status, error){
                    $('.btnBuyDealSốc').prop('disabled',false).html('Mua ngay');
                    var errorMsg = 'Có lỗi xảy ra, xin vui lòng thử lại';
                    if(xhr.responseJSON && xhr.responseJSON.message){
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert(errorMsg);
                }
            });
            return;
        }
        var id = $('input[name="variant_id"]').val();
        var qty = $('input.quantity-input').val();
        $.ajax({
        type: 'post',
        url: '{{route("cart.add")}}',
        data: {id:id,qty:qty},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.buyNowDetail').prop('disabled',true);
            $('.buyNowDetail').html('<span class="spinner-border text-light"></span> Mua ngay')
        },
        success: function (res) {
            $('.buyNowDetail').prop('disabled',false);
            $('.buyNowDetail').html('Mua ngay');
          if(res.status == 'success'){
            window.location = '{{route("cart.payment")}}';
          }else{
            var errorMsg = res.message || "Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại";
            alert(errorMsg);
          }
        },
        error: function(xhr, status, error){
            $('.buyNowDetail').prop('disabled',false);
            $('.buyNowDetail').html('Mua ngay');
            var errorMsg = 'Có lỗi xảy ra, xin vui lòng thử lại';
            if(xhr.responseJSON && xhr.responseJSON.message){
                errorMsg = xhr.responseJSON.message;
            }
            alert(errorMsg);
        }
      })
    });

    $('body').on('click','.item_ingredient',function(){
        var id = $(this).attr('data-id');
        if (!id) {
            return;
        }
        // Navigate to Paula's-style ingredient dictionary URL
        window.location.href = '/ingredient-dictionary/' + id;
    });
    var isShopeeVariant = @json($isShopeeVariant);

    // Only bind jQuery handler if content is NOT loaded from API
    // API-loaded content uses vanilla JS handlers in initializeVariantSelection
    if(isShopeeVariant){
        // Use event delegation but check if content is API-loaded
        // Note: API-loaded content uses vanilla JS handlers, so we skip jQuery handler for those
        $(document).on('click','#variant-option1-list .item-variant',function(e){
            // Check if this is API-loaded content (has data-variant-id attribute with proper format)
            const isApiLoaded = $(this).attr('data-variant-id') && $(this).attr('data-price-html');
            if (isApiLoaded) {
                // Let vanilla JS handler handle it (it's already bound in initializeVariantSelection)
                // Don't prevent default, just return early
                return true;
            }
            var $it = $(this);
            $('#variant-option1-list .item-variant').removeClass('active');
            $it.addClass('active');

            var variantId = $it.data('variant-id');
            var sku = $it.data('sku') || '';
            var price = parseFloat($it.data('price') || 0);
            // 获取 base64 编码的 HTML 字符串
            var priceHtmlRaw = $it.attr('data-price-html') || '';
            var priceHtml = '';
            if (priceHtmlRaw) {
                try {
                    // Use decodeUnicodeBase64 if available (for API-loaded content), otherwise use fallback
                    if (typeof decodeUnicodeBase64 === 'function') {
                        priceHtml = decodeUnicodeBase64(priceHtmlRaw);
                    } else {
                        // Fallback for server-rendered content: decode base64 with UTF-8 support
                        var binaryString = atob(priceHtmlRaw);
                        var bytes = new Uint8Array(binaryString.length);
                        for (var i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        priceHtml = new TextDecoder('utf-8').decode(bytes);
                    }
                } catch(e) {
                    // If decoding fails, try traditional method
                    try {
                        priceHtml = decodeURIComponent(escape(atob(priceHtmlRaw)));
                    } catch(e2) {
                        // If still fails, use default price
                        priceHtml = '<p>'+ (price || 0).toLocaleString('vi-VN') +'đ</p>';
                    }
                }
            } else {
                priceHtml = '<p>'+ (price || 0).toLocaleString('vi-VN') +'đ</p>';
            }
            var stock = parseInt($it.data('stock') || 0, 10);
            var img = $it.data('image') || '';
            var optionText = $it.data('option1') || '';

            $('#detailProduct input[name="variant_id"]').val(variantId);
            $('#variant-sku-display').text(sku);
            $('#variant-option1-current').text(optionText);
            // 使用预计算的HTML价格（包含闪购/促销/原价的完整显示）
            $('#variant-price-display').html(priceHtml);
            $('#variant-price-fix').html(priceHtml);
            console.log('Price Sync:', price);

            // Update main slider first image (best-effort)
            if(img){
                var firstImg = document.querySelector('#slidesWrapper .slide img');
                if(firstImg) firstImg.src = img;
            }

            // Toggle buttons by stock
            var disabled = stock <= 0;
            $('.addCartDetail, .buyNowDetail, .btn_plus.entry, .btn_minus.entry, .quantity-input').prop('disabled', disabled);
            
            // Update button styles and text
            if (disabled) {
                $('.addCartDetail, .buyNowDetail').css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed',
                    'pointer-events': 'none'
                });
                $('.addCartDetail span:last-child').text('Hết hàng');
                $('.buyNowDetail').not('.btnBuyDealSốc').text('Hết hàng');
                $('.buyNowDetail.btnBuyDealSốc').text('Hết hàng');
            } else {
                $('.addCartDetail, .buyNowDetail').css({
                    'opacity': '',
                    'cursor': '',
                    'pointer-events': ''
                });
                $('.addCartDetail span:last-child').text('Thêm Vào Giỏ Hàng');
                $('.buyNowDetail').not('.btnBuyDealSốc').text('Mua ngay');
                $('.buyNowDetail.btnBuyDealSốc').text('MUA DEAL SỐC');
            }
        });
    } else {
    $('#detailProduct .box-color').on('click','.item-variant',function(){
        var id = $(this).attr('data-id');
        var text = $(this).attr('data-text');
        var product = '{{$detail->id}}';
        $('#detailProduct .box-color .item-variant').removeClass('active');
        $('#detailProduct .box-color .label span').html(text);
        $('#detailProduct .box-color .label input[name="color_id"]').val(id);
        $(this).addClass('active');
        $.ajax({
            type: 'get',
            url: '/getSize?product='+product+'&color='+id,
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#detailProduct .price-detail').html(res.price);
                $('#detailProduct .sku-section span').html(res.sku);
                $('#detailProduct .box-size').html(res.html);
                $('#detailProduct input[name="variant_id"]').val(res.variant_id);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    })

    // Mobile ingredient "Xem day du" toggle
    $(document).on('click', '.ingredient-toggle', function () {
        var $btn = $(this);
        var expanded = $btn.attr('data-expanded') === 'true';
        var $list = $btn.closest('.detail-content').find('.ingredient-list').first();
        if ($list.length === 0) {
            return;
        }
        if (expanded) {
            $list.removeClass('ingredient-list-expanded').addClass('ingredient-list-collapsed');
            $btn.attr('data-expanded', 'false').text('Xem day du');
        } else {
            $list.addClass('ingredient-list-expanded').removeClass('ingredient-list-collapsed');
            $btn.attr('data-expanded', 'true').text('Thu gon');
        }
    });
    $('#detailProduct .box-size').on('click','.item-variant',function(){
        var id = $(this).attr('data-id');
        var product = '{{$detail->id}}';
        var text = $(this).attr('data-text');
        var color =  $('#detailProduct input[name="color_id"]').val();
        $('#detailProduct .box-size .item-variant').removeClass('active');
        $('#detailProduct .box-size .label span').html(text);
        $('#detailProduct .box-size .label input[name="size_id"]').val(id);
        $(this).addClass('active');
        $.ajax({
            type: 'get',
            url: '/getPrice?product='+product+'&color='+color+'&size='+id,
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#detailProduct .price-detail').html(res.price);
                $('#detailProduct .sku-section span').html(res.sku);
                $('#detailProduct input[name="variant_id"]').val(res.variant_id);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    }
    $(".formRating").submit(function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: "POST",
            url: "{{route('review.add')}}",
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            beforeSend: function () {
                $('.formRating button[type="submit"]').html('<span class="spinner-border text-light"></span>');
                $('.formRating button[type="submit"]').prop('disabled', true);
            },
            success: function (res) {
                $('.formRating button[type="submit"]').html('GỬI CHO CHÚNG TÔI');
                $('.formRating button[type="submit"]').prop('disabled', false);
                if(res.status == true){
                $('.formRating .box-alert').html('<div class="alert alert-success" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
                }else{
                  $('.formRating .box-alert').html('<div class="alert alert-danger" role="alert"><i class="fa fa-times" aria-hidden="true"></i> '+res.message+'</div>');
                }
                setTimeout(function () {
                  window.location = window.location.href;
                  var myRating = new bootstrap.Modal(document.getElementById('myRating'))
                    myRating.hide();
                }, 2000);
                $('.formRating')[0].reset();
            },
            error: function (data) {
                alert(data.responseJSON.errors.files[0]);
                console.log(data.responseJSON.errors);
            },
        });
    });
</script>
@if(isset($watchs) && $watchs->count() > 1)
<script>
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
</script>
@endif
<style> 
    .w-25p{
        width: calc(25% - 20px);
    }
    .ui-switcher {
      background-color: #fff;
        display: inline-block;
        height: 30px;
        width: 85px;
        border-radius: 15px;
        box-sizing: border-box;
        vertical-align: middle;
        position: relative;
        cursor: pointer;
        transition: border-color 0.25s;
        margin: -2px 4px 0 0;
        border: 1px solid #d1d1d1;
    }
    .ui-switcher:before {
      font-family: sans-serif;
      font-size: 10px;
      font-weight: 400;
      color: #ffffff;
      line-height: 1;
      display: inline-block;
      position: absolute;
      top: 6px;
      height: 12px;
      width: 20px;
      text-align: center;
    }
    .ui-switcher[aria-checked=false]:before {
      content: 'Không';
        right: 30px;
        color: #000;
        font-size: 14px;
        font-weight: 600;
        top: 7px;
    }
    .ui-switcher[aria-checked=true]:before {
      content: 'Có';
      left: 7px;
      color: #fff;
      font-size: 14px;
    }
    .ui-switcher[aria-checked=true] {
      background-color: #000;
    }
    .ui-switcher:after {
      background-color: #fff;
        content: '\0020';
        display: inline-block;
        position: absolute;
        top: 2px;
        height: 24px;
        width: 24px;
        border-radius: 50%;
        transition: left 0.25s;
    }
    .ui-switcher[aria-checked=false]:after {
      left: 2px;
      background-color: #000;
    }
    .ui-switcher[aria-checked=true]:after {
      left: 57px;
    }
    #myVerified .modal-content{
        border: none;
        background-color: initial;
    }
    #myVerified .btnClose{
        color:#fff;
    }
    /* Shopee-style variant selector (box-option1) */
    .box-variant.box-option1{
        margin-top: 16px;
        padding-top: 12px;
    }
    .box-variant.box-option1 .label{
        font-size: 13px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .box-variant.box-option1 .label strong{
        font-weight: 600;
        color: #222;
    }
    .box-variant.box-option1 .label span#variant-option1-current{
        padding: 2px 8px;
        border-radius: 999px;
        background: #f5f7fa;
        font-size: 12px;
        color: #444;
    }
    .box-variant.box-option1 .list-variant{
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .box-variant.box-option1 .list-variant .item-variant{
        min-width: auto;
        width: auto;
        padding: 6px 14px;
        border-radius: 6px;
        border: 1px solid #ddd;
        background: #f7f7f8;
        font-size: 13px;
        color: #333;
        cursor: pointer;
        text-align: center;
        transition: all .18s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        user-select: none;
        line-height: 1.2;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .box-variant.box-option1 .list-variant .item-variant:hover{
        border-color: #ee4d2d;
        color: #ee4d2d;
        background: #fff5f0;
    }
    .box-variant.box-option1 .list-variant .item-variant.active{
        border-color: #ee4d2d;
        background: #ee4d2d;
        color: #fff;
        box-shadow: 0 2px 6px rgba(238,77,45,0.28);
    }
    .box-variant.box-option1 .list-variant .item-variant.out-of-stock{
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
        position: relative;
    }
    .box-variant.box-option1 .list-variant .item-variant.out-of-stock::after{
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 5px,
            rgba(0,0,0,0.1) 5px,
            rgba(0,0,0,0.1) 10px
        );
        pointer-events: none;
    }
    .box-variant.box-option1 .list-variant .item-variant p{
        margin-bottom: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    @media(max-width: 568px){
        .w-25p{
            width: 50%;
        }
    }
    
    /* 产品评分和销量显示样式 */
    .product-rating-sales {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 12px;
        flex-wrap: wrap;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .product-rating-sales .rating-display {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .product-rating-sales .rating-value {
        color: #ee4d2d;
        font-weight: 700;
        font-size: 18px;
        line-height: 1;
        margin-right: 2px;
    }
    
    .product-rating-sales .rating-stars {
        display: inline-flex;
        align-items: center;
        line-height: 1;
    }
    
    .product-rating-sales .rating-stars .list-rate {
        display: inline-flex;
        align-items: center;
        list-style: none;
        padding: 0;
        margin: 0;
        gap: 2px;
    }
    
    .product-rating-sales .rating-stars .list-rate li {
        display: inline-flex;
        align-items: center;
        padding: 0;
        margin: 0;
    }
    
    .product-rating-sales .rating-stars .list-rate li.icon-star {
        color: #d5d5d5;
    }
    
    .product-rating-sales .rating-stars .list-rate li.icon-star.active {
        color: #ffc120;
    }
    
    .product-rating-sales .rating-stars .list-rate li svg {
        width: 14px;
        height: 14px;
        display: block;
    }
    
    .product-rating-sales .review-count {
        color: #767676;
        display: flex;
        align-items: center;
        gap: 2px;
    }
    
    .product-rating-sales .review-count .review-number {
        font-weight: 500;
    }
    
    .product-rating-sales .sales-count {
        color: #767676;
        display: flex;
        align-items: center;
        gap: 2px;
    }
    
    .product-rating-sales .sales-count .sales-number {
        font-weight: 600;
        color: #767676;
    }
    
    .product-rating-sales .separator {
        color: #d5d5d5;
        font-size: 14px;
        margin: 0 4px;
        user-select: none;
    }
    
    @media (max-width: 768px) {
        .product-rating-sales {
            gap: 12px;
            font-size: 13px;
        }
        
        .product-rating-sales .rating-value {
            font-size: 16px;
        }
        
        .product-rating-sales .rating-stars .list-rate li svg {
            width: 12px;
            height: 12px;
        }
    }
    
    /* Số CBMP和Xuất xứ合并显示样式 */
    .item_origin_cbmp {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .item_origin_cbmp .separator {
        color: #d5d5d5;
        margin: 0 4px;
    }
    
    /* 价格显示样式优化 */
    .price-detail {
        margin: 16px 0;
    }
    
    .price-detail .price {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .price-detail .price p {
        color: #ee4d2d;
        font-size: 30px;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }
    
    .price-detail .price del {
        color: #999;
        font-size: 16px;
        text-decoration: line-through;
        margin: 0;
        font-weight: 400;
    }
    
    .price-detail .price .tag {
        display: inline-flex;
        align-items: center;
        background: rgba(238, 77, 45, 0.1);
        border-radius: 2px;
        padding: 2px 6px;
        margin-left: 4px;
    }
    
    .price-detail .price .tag span {
        color: #ee4d2d;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
    }
    
    @media (max-width: 768px) {
        .price-detail .price p {
            font-size: 24px;
        }
        
        .price-detail .price del {
            font-size: 14px;
        }
        
        .price-detail .price .tag {
            padding: 1px 4px;
        }
        
        .price-detail .price .tag span {
            font-size: 12px;
        }
    }
    
    /* Flash Sale 横幅样式 */
    .div_flashsale {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
        padding: 0px 20px;
        border-radius: 0px;
        margin: 12px 0;
    }
    
    .flash-sale-left {
        display: flex;
        align-items: center;
    }
    
    .flash-text {
        color: white;
        font-size: 20px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .lightning-icon {
        color: #ffd700;
        font-size: 24px;
        display: inline-block;
        transform: rotate(-15deg);
        margin: 0 2px;
    }
    
    .flash-sale-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .clock-icon {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
    }
    
    .ends-text {
        color: white;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }
    
    .timer_flash {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .timer-box {
        background: #000;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 700;
        min-width: 28px;
        text-align: center;
        font-family: 'Courier New', monospace;
    }
    
    .timer-separator {
        color: white;
        font-size: 16px;
        font-weight: 700;
        margin: 0 2px;
    }
    
    @media (max-width: 768px) {
        .div_flashsale {
            padding: 10px 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .flash-text {
            font-size: 16px;
        }
        
        .lightning-icon {
            font-size: 20px;
        }
        
        .ends-text {
            font-size: 11px;
        }
        
        .timer-box {
            font-size: 12px;
            padding: 3px 6px;
            min-width: 24px;
        }
        
        .clock-icon {
            width: 16px;
            height: 16px;
        }
    }
    
    /* 购物车操作按钮组样式优化 */
    .group-cart.product-action {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        flex-wrap: wrap;
    }
    
    .group-cart.product-action .quantity-selector {
        display: flex;
        align-items: center;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
    }
    
    .group-cart.product-action .btn_minus,
    .group-cart.product-action .btn_plus {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: none;
        cursor: pointer;
        padding: 0;
        transition: background-color 0.2s;
    }
    
    .group-cart.product-action .btn_minus:hover,
    .group-cart.product-action .btn_plus:hover {
        background: #f5f5f5;
    }
    
    .group-cart.product-action .btn_minus:disabled,
    .group-cart.product-action .btn_plus:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .group-cart.product-action .quantity-input {
        width: 50px;
        height: 36px;
        border: none;
        border-left: 1px solid #e0e0e0;
        border-right: 1px solid #e0e0e0;
        text-align: center;
        font-size: 14px;
        padding: 0;
        outline: none;
    }
    
    .group-cart.product-action .item-action {
        flex: 1;
        min-width: 0;
    }
    
    .group-cart.product-action .addCartDetail,
    .group-cart.product-action .buyNowDetail {
        width: 100%;
        height: 44px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
        padding: 0 12px;
    }
    
    .group-cart.product-action .addCartDetail {
        background: #fff5f0;
        color: #ee4d2d;
        border: 1px solid #ee4d2d;
    }
    
    .group-cart.product-action .addCartDetail span {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .group-cart.product-action .addCartDetail .icon svg path {
        fill: #ee4d2d;
        stroke: #ee4d2d;
    }
    
    .group-cart.product-action .addCartDetail:hover {
        background: #ffe8e0;
    }
    
    .group-cart.product-action .addCartDetail:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .group-cart.product-action .buyNowDetail {
        background: #ee4d2d;
        color: #fff;
        border: 1px solid #ee4d2d;
    }
    
    .group-cart.product-action .buyNowDetail:hover {
        background: #d7321f;
        border-color: #d7321f;
    }
    
    .group-cart.product-action .buyNowDetail:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    @media (max-width: 768px) {
        .group-cart.product-action {
            gap: 8px;
        }
        
        .group-cart.product-action .quantity-selector {
            order: 1;
            width: 100%;
            justify-content: center;
        }
        
        .group-cart.product-action .item-action {
            order: 2;
            flex: 1;
            min-width: calc(50% - 4px);
        }
        
        .group-cart.product-action .addCartDetail,
        .group-cart.product-action .buyNowDetail {
            height: 40px;
            font-size: 13px;
        }
    }
    
    /* 确保所有内容区块宽度一致 - 现在这些区块都在container-lg内部，会自动限制宽度 */
    /* 移除row类的负margin，确保宽度正确 */
    section#detailProduct .container-lg:last-of-type .row.mt-5.mb-5,
    section#detailProduct .container-lg:last-of-type .row.rating-product,
    section#detailProduct .container-lg:last-of-type .row.product-related,
    section#detailProduct .container-lg:last-of-type .row.recommendations-section {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
        max-width: none !important;
        box-sizing: border-box !important;
    }
    
    section#detailProduct .product-related .list-product {
        width: calc(100% + 15px);
        max-width: calc(100% + 15px);
        margin-left: -15px;
        padding-left: 15px;
        padding-right: 15px;
        box-sizing: border-box;
    }
    
    section#detailProduct .product-related .col-12.col-md-8 {
        overflow: hidden;
    }
    
    /* 确保内部 row 不会再次应用负 margin */
    section#detailProduct .container-lg > .mt-5.mb-5 .row,
    section#detailProduct .container-lg > .mt-5 .row,
    section#detailProduct .container-lg > .mb-5 .row,
    section#detailProduct .container-lg > .product-related .row,
    section#detailProduct .container-lg > .rating-product .row {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    /* Ingredient list layout */
    .ingredient-list {
        line-height: 1.6;
        font-size: 13px;
    }

    .ingredient-item {
        display: inline-flex;
        align-items: center;
        padding: 2px 4px;
        border-radius: 3px;
        background: transparent;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .ingredient-item:not(:last-child)::after {
        content: ",";
        margin-left: 2px;
        color: #999;
    }

    .ingredient-item .benefit-icon {
        color: #5bc0de; /* pastel-like blue for hydration/benefits */
    }

    .ingredient-item:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    @media (max-width: 768px) {
        .ingredient-list {
            max-height: 150px;
            overflow: hidden;
            position: relative;
            -webkit-mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 60%, rgba(0,0,0,0) 100%);
            mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 60%, rgba(0,0,0,0) 100%);
        }
        .ingredient-list.ingredient-list-expanded {
            max-height: none;
            -webkit-mask-image: none;
            mask-image: none;
        }
    }
</style>
<script>
    // Load product detail via API
    (function() {
        function loadProductDetail() {
            const productDetailContainer = document.getElementById('product-detail-info');
            if (!productDetailContainer) {
                console.error('[API] Product detail container not found');
                return;
            }
            
            const productSlug = productDetailContainer.getAttribute('data-product-slug');
            if (!productSlug) {
                console.error('[API] Product slug not found');
                return;
            }
            
            console.log('[API] Loading product detail for slug:', productSlug);
            
            // Load product detail from API V1
            fetch(`/api/v1/products/${productSlug}`)
                .then(response => {
                    console.log('[API] Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('[API] Response data:', data);
                    if (data.success && data.data) {
                        // Update cart count from API response if available
                        if (data.data.cart && typeof data.data.cart.total_qty !== 'undefined') {
                            const cartCount = data.data.cart.total_qty || 0;
                            $('.count-cart').text(cartCount);
                            console.log('[API] Updated cart count from product detail API:', cartCount);
                        }
                        renderProductDetail(data.data);
                        // Load warehouse stock after product detail is rendered
                        loadWarehouseStock(data.data.id);
                    } else {
                        console.error('[API] Failed to load product detail:', data.message);
                        // Show Blade template fallback if API fails
                        const bladeFallback = document.querySelector('.product-detail-blade-fallback');
                        if (bladeFallback) {
                            bladeFallback.style.display = 'block';
                        }
                        const skeleton = productDetailContainer.querySelector('.product-detail-skeleton');
                        if (skeleton) {
                            skeleton.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('[API] Error loading product detail:', error);
                    // Show Blade template fallback if API fails
                    const bladeFallback = document.querySelector('.product-detail-blade-fallback');
                    if (bladeFallback) {
                        bladeFallback.style.display = 'block';
                    }
                    const skeleton = productDetailContainer.querySelector('.product-detail-skeleton');
                    if (skeleton) {
                        skeleton.style.display = 'none';
                    }
                });
        }
        
        /**
         * Load warehouse stock from Warehouse API V1
         * Updates variant stock display with real-time available_stock
         */
        function loadWarehouseStock(productId) {
            if (!productId) {
                console.warn('[Warehouse API] Product ID not available, skipping stock update');
                return;
            }
            
            console.log('[Warehouse API] Loading stock for product:', productId);
            
            // Call Warehouse API V1 to get real-time stock
            fetch(`/admin/api/v1/warehouse/inventory/by-product/${productId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.data && Array.isArray(data.data)) {
                        console.log('[Warehouse API] Stock data received:', data.data);
                        updateVariantStocks(data.data);
                    } else {
                        console.warn('[Warehouse API] Invalid response format:', data);
                    }
                })
                .catch(error => {
                    console.error('[Warehouse API] Error loading stock:', error);
                    // Don't show error to user, just log it
                });
        }
        
        /**
         * Update variant stock display with warehouse data
         */
        function updateVariantStocks(stockData) {
            // Create a map of variant_id -> stock info
            const stockMap = {};
            stockData.forEach(item => {
                stockMap[item.variant_id] = {
                    available_stock: item.available_stock || 0,
                    physical_stock: item.physical_stock || 0,
                    flash_sale_stock: item.flash_sale_stock || 0,
                    deal_stock: item.deal_stock || 0
                };
            });
            
            // Update variant items in the DOM
            const variantItems = document.querySelectorAll('#variant-option1-list .item-variant');
            variantItems.forEach(item => {
                const variantId = parseInt(item.getAttribute('data-variant-id'));
                if (variantId && stockMap[variantId]) {
                    const stockInfo = stockMap[variantId];
                    // SAFE-GUARD: Only use valid numbers, fallback to 0 if null/undefined
                    const availableStock = (stockInfo.available_stock !== null && stockInfo.available_stock !== undefined && !isNaN(stockInfo.available_stock)) 
                        ? stockInfo.available_stock 
                        : 0;
                    
                    // Update data-stock attribute
                    item.setAttribute('data-stock', availableStock);
                    
                    // Update out-of-stock class
                    if (availableStock <= 0) {
                        item.classList.add('out-of-stock');
                        item.style.opacity = '0.5';
                        item.style.cursor = 'not-allowed';
                        item.style.pointerEvents = 'none';
                    } else {
                        item.classList.remove('out-of-stock');
                        item.style.opacity = '';
                        item.style.cursor = '';
                        item.style.pointerEvents = '';
                    }
                }
            });
            
            // Update stock display if active variant exists
            const activeVariant = document.querySelector('#variant-option1-list .item-variant.active');
            if (activeVariant) {
                const variantId = parseInt(activeVariant.getAttribute('data-variant-id'));
                const stockDisplay = document.getElementById('variant-stock-value');
                
                // Check if stock is locked from server - NEVER override if locked
                if (stockDisplay && stockDisplay.getAttribute('data-is-locked') === 'true') {
                    // Use server-side stock value, do not override
                    const serverStock = parseInt(stockDisplay.getAttribute('data-server-stock') || '0');
                    // Always use server stock, even if 0 (to show "Hết hàng")
                    stockDisplay.textContent = serverStock > 0 ? serverStock.toLocaleString('vi-VN') : 'Hết hàng';
                    updateButtonStates(serverStock);
                    return; // Exit early, do not update from API
                }
                
                // Only update from API if not locked
                // SAFE-GUARD: Never override server stock if API returns null/undefined/error
                if (variantId && stockMap[variantId]) {
                    const stockInfo = stockMap[variantId];
                    // Only update if stockInfo.available_stock is a valid number (not null/undefined)
                    if (stockDisplay && stockInfo.available_stock !== null && stockInfo.available_stock !== undefined && !isNaN(stockInfo.available_stock)) {
                        stockDisplay.textContent = stockInfo.available_stock.toLocaleString('vi-VN');
                        // Update button states
                        updateButtonStates(stockInfo.available_stock);
                    } else {
                        // If API returns null/undefined, keep server-rendered value (do not override)
                        const serverStock = parseInt(stockDisplay.getAttribute('data-server-stock') || '0');
                        if (stockDisplay) {
                            stockDisplay.textContent = serverStock > 0 ? serverStock.toLocaleString('vi-VN') : 'Hết hàng';
                        }
                        updateButtonStates(serverStock);
                    }
                } else {
                    // If stockMap doesn't have data, keep server-rendered value
                    const serverStock = parseInt(stockDisplay.getAttribute('data-server-stock') || '0');
                    if (stockDisplay) {
                        stockDisplay.textContent = serverStock > 0 ? serverStock.toLocaleString('vi-VN') : 'Hết hàng';
                    }
                    updateButtonStates(serverStock);
                }
            }
            
            // For single product (no variants), update from first stock entry
            if (!document.getElementById('variant-option1-list') && stockData.length > 0) {
                const firstStock = stockData[0];
                updateButtonStates(firstStock.available_stock);
            }
        }
        
        /**
         * Update button states based on stock availability
         */
        function updateButtonStates(availableStock) {
            const buttons = document.querySelectorAll('.addCartDetail, .buyNowDetail, .btn_minus.entry, .btn_plus.entry');
            const quantityInput = document.querySelector('.quantity-input');
            const isOutOfStock = availableStock <= 0;
            
            if (isOutOfStock) {
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                    btn.style.pointerEvents = 'none';
                    if (btn.classList.contains('addCartDetail')) {
                        const span = btn.querySelector('span:last-child');
                        if (span) span.textContent = 'Hết hàng';
                    }
                    if (btn.classList.contains('buyNowDetail')) {
                        btn.textContent = 'Hết hàng';
                    }
                });
                if (quantityInput) {
                    quantityInput.disabled = true;
                    quantityInput.style.opacity = '0.5';
                    quantityInput.style.cursor = 'not-allowed';
                }
            } else {
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                    btn.style.pointerEvents = '';
                    if (btn.classList.contains('addCartDetail')) {
                        const span = btn.querySelector('span:last-child');
                        if (span) span.textContent = 'Thêm Vào Giỏ Hàng';
                    }
                    if (btn.classList.contains('buyNowDetail')) {
                        const isDeal = btn.classList.contains('btnBuyDealSốc');
                        btn.textContent = isDeal ? 'MUA DEAL SỐC' : 'Mua ngay';
                    }
                });
                if (quantityInput) {
                    quantityInput.disabled = false;
                    quantityInput.style.opacity = '';
                    quantityInput.style.cursor = '';
                }
            }
        }
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadProductDetail);
        } else {
            // DOM is already ready
            loadProductDetail();
        }
        
        // Helper functions (must be defined before renderProductDetail)
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Encode Unicode string to base64 (supports UTF-8)
         */
        function encodeUnicodeBase64(str) {
            try {
                // First, convert to UTF-8 bytes
                const utf8Bytes = new TextEncoder().encode(str);
                // Convert bytes to binary string
                let binary = '';
                for (let i = 0; i < utf8Bytes.length; i++) {
                    binary += String.fromCharCode(utf8Bytes[i]);
                }
                // Encode to base64
                return btoa(binary);
            } catch (e) {
                console.error('[API] Error encoding to base64:', e);
                // Fallback: use encodeURIComponent
                return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, (match, p1) => {
                    return String.fromCharCode('0x' + p1);
                }));
            }
        }
        
        /**
         * Decode base64 to Unicode string (supports UTF-8)
         */
        function decodeUnicodeBase64(str) {
            try {
                // Decode base64 to binary string
                const binary = atob(str);
                // Convert binary string to bytes
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }
                // Decode UTF-8 bytes to string
                return new TextDecoder('utf-8').decode(bytes);
            } catch (e) {
                console.error('[API] Error decoding from base64:', e);
                // Fallback: try traditional method
                try {
                    return decodeURIComponent(escape(atob(str)));
                } catch (e2) {
                    console.error('[API] Fallback decode also failed:', e2);
                    return '';
                }
            }
        }
        
        function formatNumber(num) {
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'k';
            }
            return num.toString();
        }
        
        function formatSales(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'tr+';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'k+';
            }
            return num.toString();
        }
        
        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        
        function generateStars(sum, count) {
            // Simple star generation - you may need to adjust based on your getStar() function
            const average = count > 0 ? sum / count : 0;
            const fullStars = Math.floor(average);
            const hasHalfStar = (average - fullStars) >= 0.5;
            let html = '';
            for (let i = 0; i < 5; i++) {
                if (i < fullStars) {
                    html += '<span class="star full">★</span>';
                } else if (i === fullStars && hasHalfStar) {
                    html += '<span class="star half">★</span>';
                } else {
                    html += '<span class="star empty">★</span>';
                }
            }
            return html;
        }
        
        function renderProductDetail(product) {
            const productDetailContainer = document.getElementById('product-detail-info');
            if (!productDetailContainer) {
                console.error('[API] Product detail container not found in renderProductDetail');
                return;
            }
            
            const container = productDetailContainer.querySelector('.product-detail-content');
            const skeleton = productDetailContainer.querySelector('.product-detail-skeleton');
            
            if (!container) {
                console.error('[API] Product detail content container not found');
                return;
            }
            
            console.log('[API] Rendering product detail:', {
                hasCategory: !!product.category,
                hasBrand: !!product.brand,
                hasName: !!product.name,
                productName: product.name,
                category: product.category,
                brand: product.brand
            });
            
            // Build breadcrumb - ALWAYS show at least "Trang chủ"
            // Note: Do NOT include product name in breadcrumb (removed per requirement)
            // Read categories from API: product.categories (array) or product.category (single)
            let breadcrumbHtml = '<div class="breadcrumb"><ol><li><a href="/">Trang chủ</a></li>';
            
            // Use categories array if available, otherwise fallback to single category
            if (product.categories && Array.isArray(product.categories) && product.categories.length > 0) {
                // Show all categories in breadcrumb
                product.categories.forEach(function(cat) {
                    if (cat && cat.slug && cat.name) {
                        breadcrumbHtml += `<li><a href="/${cat.slug}">${escapeHtml(cat.name)}</a></li>`;
                    }
                });
            } else if (product.category && product.category.slug && product.category.name) {
                // Fallback to single category
                breadcrumbHtml += `<li><a href="/${product.category.slug}">${escapeHtml(product.category.name)}</a></li>`;
            }
            breadcrumbHtml += '</ol></div>';
            
            // Build brand - ALWAYS show if brand exists
            // Read brand from API: product.brand.name
            let brandHtml = '';
            if (product.brand && product.brand.name) {
                // Use brand name directly from API, make it a link if slug exists
                if (product.brand.slug) {
                    brandHtml = `<a href="/thuong-hieu/${product.brand.slug}" class="fs-14 fw-600 mb-2 brand-name" style="color: #666;">${escapeHtml(product.brand.name)}</a>`;
                } else {
                    brandHtml = `<div class="brand-name fs-14 fw-600 mb-2" style="color: #666;">${escapeHtml(product.brand.name)}</div>`;
                }
            }
            
            // Build title - ALWAYS show product name
            const titleHtml = product.name ? `<h1 class="title-product">${escapeHtml(product.name)}</h1>` : '';
            
            // Build rating and sales
            const ratingHtml = `
                <div class="product-rating-sales">
                    <div class="rating-display">
                        <span class="rating-value">${product.rating.average.toFixed(1)}</span>
                        <div class="rating-stars">
                            ${generateStars(product.rating.sum, product.rating.count)}
                        </div>
                    </div>
                    <span class="separator">|</span>
                    <div class="review-count">
                        <span class="review-number">${formatNumber(product.rating.count)}</span>
                        <span class="review-text"> Đánh Giá</span>
                    </div>
                    <span class="separator">|</span>
                    <div class="sales-count">
                        <span class="sales-text">Đã Bán </span>
                        <span class="sales-number">${formatSales(product.total_sold)}</span>
                    </div>
                </div>
            `;
            
            // Build price
            let priceHtml = '';
            if (product.has_variants && product.variants_count > 0 && product.first_variant) {
                const firstVariant = product.variants.find(v => v.id === product.first_variant.id) || product.variants[0];
                if (firstVariant && firstVariant.price_info) {
                    priceHtml = `<div class="price-detail"><div class="price" id="variant-price-display">${firstVariant.price_info.html}</div></div>`;
                }
            } else {
                // Simple product - ALWAYS use PriceEngine price_info from API if present
                if (product.first_variant) {
                    if (product.first_variant.price_info && product.first_variant.price_info.html) {
                        console.log('Price Sync:', product.first_variant.price_info.final_price);
                        priceHtml = `<div class="price-detail"><div class="price" id="variant-price-display">${product.first_variant.price_info.html}</div></div>`;
                    } else {
                        const price = product.first_variant.price;
                        console.log('Price Sync:', price);
                        priceHtml = `<div class="price-detail"><div class="price"><p>${formatPrice(price)}đ</p></div></div>`;
                    }
                }
            }
            
            // Build attributes
            let attributesHtml = '<div class="d-block overflow-hidden mb-2 list_attribute">';
            if (product.cbmp) {
                attributesHtml += `<span><b>Số CBMP:</b> ${product.cbmp}</span>`;
            }
            if (product.origin) {
                if (product.cbmp) attributesHtml += '<span class="separator">|</span>';
                attributesHtml += `<span><b>Xuất xứ:</b> ${product.origin.name}</span>`;
            }
            attributesHtml += '</div>';
            
            // Build Flash Sale
            let flashSaleHtml = '';
            if (product.flash_sale) {
                const endDate = new Date(product.flash_sale.end_date);
                flashSaleHtml = `
                    <div class="div_flashsale">
                        <div class="flash-sale-left">
                            <span class="flash-text">FL<span class="lightning-icon">⚡</span>SH SALE</span>
                        </div>
                        <div class="flash-sale-right">
                            <svg class="clock-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/>
                                <path d="M12 6v6l4 2" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <span class="ends-text">KẾT THÚC TRONG</span>
                            <div class="timer_flash" data-end="${endDate.getTime()}">
                                <div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Build variants
            let variantsHtml = '';
            if (product.has_variants && product.variants_count > 0) {
                const firstVariant = product.variants[0];
                variantsHtml = `
                    <div class="box-variant box-option1">
                        <div class="label">
                            <strong>${product.option1_name || 'Phân loại'}:</strong>
                            <span id="variant-option1-current">${firstVariant.option_label || ''}</span>
                        </div>
                        <div class="list-variant" id="variant-option1-list">
                            ${product.variants.map((v, index) => {
                                // Use stock_display (with priority: Flash Sale > Deal > Available) if available
                                // Otherwise fallback to warehouse_stock or stock
                                // Will be updated by loadWarehouseStock() with real-time available_stock
                                const stockDisplay = v.stock_display !== undefined ? v.stock_display : 
                                    (v.warehouse_stock !== undefined ? v.warehouse_stock : v.stock);
                                const stockSource = v.stock_source || 'warehouse';
                                const hasFlashSale = v.has_flash_sale === true;
                                const hasDeal = v.has_deal === true;
                                const isOutOfStock = stockDisplay <= 0 || (v.is_out_of_stock !== undefined ? v.is_out_of_stock : false);
                                return `
                                <div class="item-variant ${index === 0 ? 'active' : ''} ${isOutOfStock ? 'out-of-stock' : ''}"
                                     data-variant-id="${v.id}"
                                     data-sku="${v.sku}"
                                     data-price="${v.price_info.final_price}"
                                     data-original-price="${v.price_info.original_price}"
                                     data-price-html="${encodeUnicodeBase64(v.price_info.html || '')}"
                                     data-stock="${stockDisplay}"
                                     data-stock-source="${stockSource}"
                                     data-has-flash-sale="${hasFlashSale ? '1' : '0'}"
                                     data-has-deal="${hasDeal ? '1' : '0'}"
                                     data-image="${v.image}"
                                     data-option1="${v.option_label}"
                                     ${isOutOfStock ? 'style="opacity: 0.5; cursor: not-allowed; pointer-events: none;"' : ''}>
                                    <p class="mb-0">${v.option_label}</p>
                                </div>
                            `;
                            }).join('')}
                        </div>
                        <div class="variant-stock-info" id="variant-stock-display" style="margin-top: 10px; padding: 8px 12px; background: #f8f9fa; border-radius: 4px; font-size: 13px; color: #666;">
                            <span class="stock-label"><strong>Tồn kho:</strong></span>
                            <span class="stock-value" id="variant-stock-value" style="font-weight: 600; color: #333; margin-left: 5px;">${(() => {
                                const stockVal = product.variants[0]?.stock_display ?? product.variants[0]?.warehouse_stock ?? 0;
                                return stockVal > 0 ? stockVal.toLocaleString('vi-VN') : 'Hết hàng';
                            })()}</span>
                            <span class="stock-unit" style="margin-left: 3px;">sản phẩm</span>
                        </div>
                    </div>
                `;
            }
            
            // Build action buttons - check stock_display (with priority: Flash Sale > Deal > Available)
            const hasStock = product.has_variants 
                ? (product.first_variant && (product.first_variant.stock_display !== undefined ? product.first_variant.stock_display : (product.first_variant.warehouse_stock !== undefined ? product.first_variant.warehouse_stock : product.first_variant.stock)) > 0 && !(product.first_variant.is_out_of_stock === true))
                : ((product.stock_display !== undefined ? product.stock_display : (product.warehouse_stock !== undefined ? product.warehouse_stock : product.stock)) > 0 && !(product.is_out_of_stock === true));
            
            const actionHtml = `
                <input type="hidden" name="variant_id" value="${product.first_variant ? product.first_variant.id : ''}">
                <div class="group-cart product-action align-center mt-3 space-between">
                    <div class="quantity align-center quantity-selector">
                        <button class="btn_minus entry" type="button" ${!hasStock ? 'disabled' : ''}>
                            <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                        </button>
                        <input ${!hasStock ? 'disabled' : ''} type="text" class="form-quatity quantity-input" value="1" min="1">
                        <button ${!hasStock ? 'disabled' : ''} class="btn_plus entry" type="button">
                            <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                        </button>
                    </div>
                    <div class="item-action">
                        <button ${!hasStock ? 'disabled style="opacity: 0.5; cursor: not-allowed; pointer-events: none;"' : ''} type="button" class="addCartDetail">
                            <span role="img" class="icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.5H14.5L10.5 0.5C10.3 0.2 10 0 9.7 0C9.4 0 9.1 0.2 8.9 0.5L4.9 6.5H0.5C0.2 6.5 0 6.7 0 7C0 7.1 0 7.2 0.1 7.3L2.3 16.3C2.5 17 3.1 17.5 3.8 17.5H16.2C16.9 17.5 17.5 17 17.7 16.3L19.9 7.3L20 7C20 6.7 19.8 6.5 19.5 6.5H19ZM9.7 2.5L12.5 6.5H6.9L9.7 2.5ZM16.2 16.5H3.8L1.8 8.5H18.2L16.2 16.5ZM9.7 10.5C8.9 10.5 8.2 11.2 8.2 12C8.2 12.8 8.9 13.5 9.7 13.5C10.5 13.5 11.2 12.8 11.2 12C11.2 11.2 10.5 10.5 9.7 10.5Z" stroke="#ee4d2d" stroke-width="1.5" fill="none"/><path d="M9.7 8.5V15.5M6.2 12H13.2" stroke="#ee4d2d" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                            <span>${!hasStock ? 'Hết hàng' : 'Thêm Vào Giỏ Hàng'}</span>
                        </button>
                    </div>
                    <div class="item-action">
                        ${product.deal && product.deal.sale_deals && product.deal.sale_deals.length > 0
                            ? `<button ${!hasStock ? 'disabled style="opacity: 0.5; cursor: not-allowed; pointer-events: none;"' : ''} class="buyNowDetail btnBuyDealSốc" type="button">${!hasStock ? 'Hết hàng' : 'MUA DEAL SỐC'}</button>`
                            : `<button ${!hasStock ? 'disabled style="opacity: 0.5; cursor: not-allowed; pointer-events: none;"' : ''} class="buyNowDetail" type="button">${!hasStock ? 'Hết hàng' : 'Mua ngay'}</button>`
                        }
                    </div>
                </div>
            `;
            
            // Build Deal section
            let dealHtml = '';
            if (product.deal && product.deal.sale_deals && product.deal.sale_deals.length > 0) {
                dealHtml = `
                    <div class="sc-67558998-0 buy-x-get-y-wrapper mb-4">
                        <div class="buy-x-get-y-header">
                            <div class="title">Mua kèm deal sốc</div>
                            <div class="sub-title">Mua để nhận ưu đãi (Tối đa ${product.deal.limited})</div>
                        </div>
                        <div class="buy-x-get-y-body">
                            ${product.deal.sale_deals.map(sd => `
                                <div class="item_deal_row ${sd.available === false ? 'text-muted' : ''}">
                                    <div class="item_deal_action me-3">
                                        ${product.deal.limited === 1 
                                            ? `<input type="radio" name="deal_item" class="deal-checkbox-custom" id="deal_${sd.id}" value="${sd.variant_id || ''}" ${sd.available === false ? 'disabled' : ''}>`
                                            : `<input type="checkbox" name="deal_item[]" class="deal-checkbox-custom" id="deal_${sd.id}" value="${sd.variant_id || ''}" ${sd.available === false ? 'disabled' : ''}>`
                                        }
                                        <label for="deal_${sd.id}" class="deal-checkmark"></label>
                                    </div>
                                    <div class="item_deal_info">
                                        <div class="thumb_deal">
                                            <div class="skeleton--img-sm js-skeleton">
                                                <img src="${sd.product_image}" alt="${sd.product_name}" class="js-skeleton-img">
                                            </div>
                                        </div>
                                        <div class="info_deal">
                                            <h5 class="deal-product-name">${sd.product_name}</h5>
                                            <div class="price_deal">
                                                <span class="curr-price">${formatPrice(sd.price)}đ</span>
                                                <del class="old-price">${formatPrice(sd.original_price)}đ</del>
                                            </div>
                                            ${sd.available === false ? '<div class="text-danger fs-12 mt-1">Deal đã hết quà hoặc hết kho</div>' : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Combine all HTML - Ensure breadcrumb, brand, and title are always shown first
            const finalHtml = breadcrumbHtml + 
                (brandHtml ? brandHtml : '') + 
                titleHtml + 
                ratingHtml + 
                priceHtml + 
                attributesHtml + 
                flashSaleHtml + 
                variantsHtml + 
                actionHtml + 
                dealHtml;
            
            // Set innerHTML
            container.innerHTML = finalHtml;
            
            console.log('[API] Final HTML length:', finalHtml.length);
            console.log('[API] Breadcrumb HTML:', breadcrumbHtml);
            console.log('[API] Brand HTML:', brandHtml);
            console.log('[API] Title HTML:', titleHtml);
            
            // Hide skeleton and show content
            if (skeleton) {
                skeleton.style.display = 'none';
                console.log('[API] Skeleton hidden');
            }
            container.style.display = 'block';
            console.log('[API] Content container displayed');
            
            // Hide Blade template fallback content
            const bladeFallback = document.querySelector('.product-detail-blade-fallback');
            if (bladeFallback) {
                bladeFallback.style.display = 'none';
                console.log('[API] Blade template fallback hidden');
            }
            
            // Verify elements are in DOM
            setTimeout(() => {
                const breadcrumbEl = container.querySelector('.breadcrumb');
                const brandEl = container.querySelector('.brand-name');
                const titleEl = container.querySelector('.title-product');
                console.log('[API] Elements in DOM:', {
                    breadcrumb: !!breadcrumbEl,
                    brand: !!brandEl,
                    title: !!titleEl,
                    containerVisible: container.style.display,
                    containerHTML: container.innerHTML.substring(0, 300)
                });
            }, 100);
            
            // Debug: Log to console to verify data is loaded correctly
            console.log('Product detail loaded:', {
                hasBreadcrumb: breadcrumbHtml.length > 0,
                hasBrand: brandHtml.length > 0,
                hasTitle: titleHtml.length > 0,
                category: product.category,
                brand: product.brand,
                name: product.name,
                breadcrumbHtml: breadcrumbHtml,
                brandHtml: brandHtml,
                titleHtml: titleHtml
            });
            
            // Debug: Log to console to verify data is loaded correctly
            console.log('Product detail loaded:', {
                hasBreadcrumb: breadcrumbHtml.length > 0,
                hasBrand: brandHtml.length > 0,
                hasTitle: titleHtml.length > 0,
                category: product.category,
                brand: product.brand,
                name: product.name
            });
            
            // Initialize flash sale timer if exists
            if (product.flash_sale) {
                initializeFlashSaleTimer();
            }
            
            // Initialize variant selection if exists
            if (product.has_variants && product.variants_count > 0) {
                // Use setTimeout to ensure DOM is fully rendered
                setTimeout(() => {
                    try {
                        initializeVariantSelection(product);
                    } catch (e) {
                        console.error('[API] Error initializing variant selection:', e);
                    }
                }, 200);
            }
            
            // Initialize quantity controls
            setTimeout(() => {
                try {
                    initializeQuantityControls();
                } catch (e) {
                    console.error('[API] Error initializing quantity controls:', e);
                }
            }, 200);
            
            // Initialize deal controls if exists
            if (product.deal && product.deal.sale_deals && product.deal.sale_deals.length > 0) {
                setTimeout(() => {
                    try {
                        initializeDealControls(product.deal);
                    } catch (e) {
                        console.error('[API] Error initializing deal controls:', e);
                    }
                }, 300);
            }
        }
        
        function initializeQuantityControls() {
            // Quantity increase/decrease buttons
            const btnPlus = document.querySelector('.btn_plus.entry');
            const btnMinus = document.querySelector('.btn_minus.entry');
            const quantityInput = document.querySelector('.quantity-input');
            
            // Get product and variant IDs
            const productId = document.getElementById('detailProduct')?.getAttribute('data-product-id');
            const variantIdInput = document.querySelector('input[name="variant_id"]');
            const variantId = variantIdInput ? variantIdInput.value : null;
            
            // Function to trigger price calculation
            const triggerPriceCalculation = function() {
                if (!productId || !quantityInput) return;
                
                const quantity = parseInt(quantityInput.value) || 1;
                
                // Check if FlashSaleMixedPrice is available
                if (typeof FlashSaleMixedPrice !== 'undefined') {
                    // Ensure warning container exists
                    let warningContainer = document.querySelector('.flash-sale-warning-container');
                    if (!warningContainer) {
                        warningContainer = document.createElement('div');
                        warningContainer.className = 'flash-sale-warning-container';
                        warningContainer.style.marginTop = '10px';
                        
                        // Insert after the product action buttons
                        const productAction = document.querySelector('.group-cart.product-action');
                        if (productAction && productAction.parentNode) {
                            productAction.parentNode.insertBefore(warningContainer, productAction.nextSibling);
                        }
                    }
                    
                    // Call FlashSaleMixedPrice to calculate price
                    FlashSaleMixedPrice.calculatePriceWithQuantity(
                        parseInt(productId),
                        variantId ? parseInt(variantId) : null,
                        quantity,
                        '.product-price-display', // Price display selector (if exists)
                        '.flash-sale-warning-container' // Warning container
                    );
                }
            };
            
            // Remove old event listeners to avoid duplicates
            if (btnPlus) {
                btnPlus.removeEventListener('click', triggerPriceCalculation);
                btnPlus.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    if (quantityInput && !quantityInput.disabled) {
                        const current = parseInt(quantityInput.value) || 1;
                        quantityInput.value = current + 1;
                        // Trigger price calculation immediately
                        triggerPriceCalculation();
                    }
                });
            }
            
            if (btnMinus) {
                btnMinus.removeEventListener('click', triggerPriceCalculation);
                btnMinus.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    if (quantityInput && !quantityInput.disabled) {
                        const current = parseInt(quantityInput.value) || 1;
                        if (current > 1) {
                            quantityInput.value = current - 1;
                            // Trigger price calculation immediately
                            triggerPriceCalculation();
                        }
                    }
                });
            }
            
            // Listen to direct input changes với debounce
            if (quantityInput) {
                quantityInput.removeEventListener('change', triggerPriceCalculation);
                quantityInput.removeEventListener('input', triggerPriceCalculation);
                
                quantityInput.addEventListener('change', function() {
                    triggerPriceCalculation();
                });
                
                quantityInput.addEventListener('input', function() {
                    // Debounce for better performance
                    clearTimeout(quantityInput.priceCalculationTimeout);
                    quantityInput.priceCalculationTimeout = setTimeout(function() {
                        triggerPriceCalculation();
                    }, 500);
                });
            }
        }
        
        function initializeFlashSaleTimer() {
            const timerElements = document.querySelectorAll('.timer_flash[data-end]');
            timerElements.forEach(timerEl => {
                const endTime = parseInt(timerEl.getAttribute('data-end'));
                if (!endTime) return;
                
                function updateTimer() {
                    const now = new Date().getTime();
                    const remaining = Math.max(0, Math.floor((endTime - now) / 1000));
                    
                    if (remaining <= 0) {
                        timerEl.innerHTML = '<div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div><span class="timer-separator">:</span><div class="timer-box">00</div>';
                        // Remove skeleton classes if any
                        $(timerEl).removeClass('skeleton js-skeleton');
                        return;
                    }
                    
                    const days = Math.floor(remaining / 86400);
                    const hours = Math.floor((remaining % 86400) / 3600);
                    const minutes = Math.floor((remaining % 3600) / 60);
                    const seconds = remaining % 60;
                    
                    timerEl.innerHTML = `
                        <div class="timer-box">${String(hours).padStart(2, '0')}</div>
                        <span class="timer-separator">:</span>
                        <div class="timer-box">${String(minutes).padStart(2, '0')}</div>
                        <span class="timer-separator">:</span>
                        <div class="timer-box">${String(seconds).padStart(2, '0')}</div>
                    `;
                    // Remove skeleton classes if any
                    $(timerEl).removeClass('skeleton js-skeleton');
                }
                
                updateTimer();
                setInterval(updateTimer, 1000);
            });
        }
        
        function initializeVariantSelection(product) {
            console.log('[API] Initializing variant selection');
            
            const variantList = document.getElementById('variant-option1-list');
            if (!variantList) {
                console.error('[API] Variant list not found');
                return;
            }
            
            const variantItems = variantList.querySelectorAll('.item-variant');
            console.log('[API] Found variant items:', variantItems.length);
            
            if (variantItems.length === 0) {
                console.warn('[API] No variant items found');
                return;
            }
            
            // Add click event listeners directly (jQuery handler will skip API-loaded content)
            variantItems.forEach((item) => {
                // Skip if out of stock
                const stock = parseInt(item.getAttribute('data-stock') || '0');
                const isOutOfStock = stock <= 0 || item.classList.contains('out-of-stock');
                if (isOutOfStock) {
                    item.style.opacity = '0.5';
                    item.style.cursor = 'not-allowed';
                    item.style.pointerEvents = 'none';
                    return; // Skip adding event listener
                }
                
                // Add click event listener with stopPropagation to prevent jQuery handler
                item.addEventListener('click', function(e) {
                    // Stop jQuery handler from processing
                    e.stopImmediatePropagation();
                    
                    console.log('[API] Variant clicked:', {
                        id: this.getAttribute('data-variant-id'),
                        sku: this.getAttribute('data-sku'),
                        price: this.getAttribute('data-price')
                    });
                    
                    // Remove active class from all items
                    const allItems = document.querySelectorAll('#variant-option1-list .item-variant');
                    allItems.forEach(v => v.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Update current variant display
                    const currentSpan = document.getElementById('variant-option1-current');
                    if (currentSpan) {
                        currentSpan.textContent = this.getAttribute('data-option1') || '';
                    }
                    
                    // Update price display
                    const priceDisplay = document.getElementById('variant-price-display');
                    if (priceDisplay) {
                        try {
                            const priceHtmlRaw = this.getAttribute('data-price-html');
                            if (priceHtmlRaw) {
                                // Decode base64 HTML (supports Unicode)
                                const priceHtml = decodeUnicodeBase64(priceHtmlRaw);
                                priceDisplay.innerHTML = priceHtml;
                            } else {
                                // Fallback: show price as number
                                const price = this.getAttribute('data-price') || '0';
                                priceDisplay.innerHTML = `<p>${parseInt(price).toLocaleString('vi-VN')}đ</p>`;
                            }
                        } catch (e) {
                            console.error('[API] Error decoding price HTML:', e);
                            const price = this.getAttribute('data-price') || '0';
                            priceDisplay.innerHTML = `<p>${parseInt(price).toLocaleString('vi-VN')}đ</p>`;
                        }
                    }
                    
                    // Update variant_id hidden input
                    const variantInput = document.querySelector('input[name="variant_id"]');
                    if (variantInput) {
                        variantInput.value = this.getAttribute('data-variant-id') || '';
                    }
                    
                    // Update SKU display if exists
                    const skuDisplay = document.getElementById('variant-sku-display');
                    if (skuDisplay) {
                        skuDisplay.textContent = this.getAttribute('data-sku') || '';
                    }
                    
                    // Update stock display (use data-stock which is updated by Warehouse API)
                    // BUT: Respect server-side locked value if exists
                    const stockDisplay = document.getElementById('variant-stock-value');
                    if (stockDisplay) {
                        // Check if stock is locked from server - NEVER override if locked
                        if (stockDisplay.getAttribute('data-is-locked') === 'true') {
                            // Use server-side stock value, do not override
                            const serverStock = parseInt(stockDisplay.getAttribute('data-server-stock') || '0');
                            // Always use server stock, even if 0 (to show "Hết hàng")
                            stockDisplay.textContent = serverStock > 0 ? serverStock.toLocaleString('vi-VN') : 'Hết hàng';
                                // Update button states with server stock
                                const buttons = document.querySelectorAll('.addCartDetail, .buyNowDetail, .btn_minus.entry, .btn_plus.entry');
                                const quantityInput = document.querySelector('.quantity-input');
                                const isOutOfStock = serverStock <= 0;
                                
                                if (isOutOfStock) {
                                    buttons.forEach(btn => {
                                        btn.disabled = true;
                                        btn.style.opacity = '0.5';
                                        btn.style.cursor = 'not-allowed';
                                        btn.style.pointerEvents = 'none';
                                        if (btn.classList.contains('addCartDetail')) {
                                            const span = btn.querySelector('span:last-child');
                                            if (span) span.textContent = 'Hết hàng';
                                        }
                                        if (btn.classList.contains('buyNowDetail')) {
                                            btn.textContent = 'Hết hàng';
                                        }
                                    });
                                    if (quantityInput) {
                                        quantityInput.disabled = true;
                                        quantityInput.style.opacity = '0.5';
                                        quantityInput.style.cursor = 'not-allowed';
                                    }
                                } else {
                                    buttons.forEach(btn => {
                                        btn.disabled = false;
                                        btn.style.opacity = '';
                                        btn.style.cursor = '';
                                        btn.style.pointerEvents = '';
                                        if (btn.classList.contains('addCartDetail')) {
                                            const span = btn.querySelector('span:last-child');
                                            if (span) span.textContent = 'Thêm Vào Giỏ Hàng';
                                        }
                                        if (btn.classList.contains('buyNowDetail')) {
                                            btn.textContent = btn.classList.contains('btnBuyDealSốc') ? 'MUA DEAL SỐC' : 'Mua ngay';
                                        }
                                    });
                                    if (quantityInput) {
                                        quantityInput.disabled = false;
                                        quantityInput.style.opacity = '';
                                        quantityInput.style.cursor = '';
                                    }
                                }
                                return; // Exit early, do not update from data-stock
                            }
                        }
                        
                        // If not locked, use data-stock from variant element
                        const stock = parseInt(this.getAttribute('data-stock') || '0');
                        stockDisplay.textContent = stock.toLocaleString('vi-VN');
                        
                        // Update stock status
                        const buttons = document.querySelectorAll('.addCartDetail, .buyNowDetail, .btn_minus.entry, .btn_plus.entry');
                        const quantityInput = document.querySelector('.quantity-input');
                        const isOutOfStock = stock <= 0;
                        
                        if (isOutOfStock) {
                            buttons.forEach(btn => {
                                btn.disabled = true;
                                btn.style.opacity = '0.5';
                                btn.style.cursor = 'not-allowed';
                                btn.style.pointerEvents = 'none';
                                // Update button text
                                if (btn.classList.contains('addCartDetail')) {
                                    const span = btn.querySelector('span:last-child');
                                    if (span) span.textContent = 'Hết hàng';
                                }
                                if (btn.classList.contains('buyNowDetail')) {
                                    btn.textContent = 'Hết hàng';
                                }
                            });
                            if (quantityInput) {
                                quantityInput.disabled = true;
                                quantityInput.style.opacity = '0.5';
                                quantityInput.style.cursor = 'not-allowed';
                            }
                        } else {
                            buttons.forEach(btn => {
                                btn.disabled = false;
                                btn.style.opacity = '';
                                btn.style.cursor = '';
                                btn.style.pointerEvents = '';
                                if (btn.classList.contains('addCartDetail')) {
                                    const span = btn.querySelector('span:last-child');
                                    if (span) span.textContent = 'Thêm Vào Giỏ Hàng';
                                }
                                if (btn.classList.contains('buyNowDetail')) {
                                    btn.textContent = btn.classList.contains('btnBuyDealSốc') ? 'MUA DEAL SỐC' : 'Mua ngay';
                                }
                            });
                            if (quantityInput) {
                                quantityInput.disabled = false;
                                quantityInput.style.opacity = '';
                                quantityInput.style.cursor = '';
                            }
                        }
                    }
                    
                    // Update stock status (only if stock was not locked)
                    const stockDisplayCheck = document.getElementById('variant-stock-value');
                    const isStockLocked = stockDisplayCheck && stockDisplayCheck.getAttribute('data-is-locked') === 'true';
                    
                    if (!isStockLocked) {
                        const buttons = document.querySelectorAll('.addCartDetail, .buyNowDetail, .btn_minus.entry, .btn_plus.entry');
                        const quantityInput = document.querySelector('.quantity-input');
                        const stockValue = parseInt(this.getAttribute('data-stock') || '0');
                        const isOutOfStock = stockValue <= 0;
                        
                        // Only refresh from Warehouse API if stock is not locked
                        const productDetailContainer = document.getElementById('product-detail-info');
                        if (productDetailContainer) {
                            const productId = productDetailContainer.getAttribute('data-product-id');
                            if (productId) {
                                // Refresh stock from Warehouse API when variant changes
                                loadWarehouseStock(parseInt(productId));
                            }
                        }
                        
                        if (isOutOfStock) {
                        buttons.forEach(btn => {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                            btn.style.cursor = 'not-allowed';
                            btn.style.pointerEvents = 'none';
                            // Update button text
                            if (btn.classList.contains('addCartDetail')) {
                                const span = btn.querySelector('span:last-child');
                                if (span) span.textContent = 'Hết hàng';
                            }
                            if (btn.classList.contains('buyNowDetail')) {
                                btn.textContent = 'Hết hàng';
                            }
                        });
                        if (quantityInput) {
                            quantityInput.disabled = true;
                            quantityInput.style.opacity = '0.5';
                            quantityInput.style.cursor = 'not-allowed';
                        }
                    } else {
                        buttons.forEach(btn => {
                            btn.disabled = false;
                            btn.style.opacity = '';
                            btn.style.cursor = '';
                            btn.style.pointerEvents = '';
                            // Restore button text
                            if (btn.classList.contains('addCartDetail')) {
                                const span = btn.querySelector('span:last-child');
                                if (span) span.textContent = 'Thêm Vào Giỏ Hàng';
                            }
                            if (btn.classList.contains('buyNowDetail') && !btn.classList.contains('btnBuyDealSốc')) {
                                btn.textContent = 'Mua ngay';
                            } else if (btn.classList.contains('btnBuyDealSốc')) {
                                btn.textContent = 'MUA DEAL SỐC';
                            }
                        });
                        if (quantityInput) {
                            quantityInput.disabled = false;
                            quantityInput.style.opacity = '';
                            quantityInput.style.cursor = '';
                        }
                    }
                    
                    // Update image in slider if needed
                    const variantImage = this.getAttribute('data-image');
                    if (variantImage) {
                        const firstImg = document.querySelector('#slidesWrapper .slide img');
                        if (firstImg) {
                            firstImg.src = variantImage;
                            firstImg.onerror = function() {
                                console.warn('[API] Failed to load variant image:', variantImage);
                            };
                        }
                    }
                    
                    console.log('[API] Variant selection updated');
                });
            });
            
            console.log('[API] Variant selection initialized for', variantItems.length, 'items');
        }
        
        function initializeDealControls(deal) {
            console.log('[API] Initializing deal controls, limited:', deal.limited);
            
            const dealRows = document.querySelectorAll('.item_deal_row');
            const dealCheckboxes = document.querySelectorAll('.deal-checkbox-custom');
            // Tìm nút bằng buyNowDetail vì khi tất cả deal hết, nút sẽ không còn class btnBuyDealSốc
            const buyDealButton = document.querySelector('.buyNowDetail');
            let toastShown = false;
            
            if (dealRows.length === 0) {
                console.warn('[API] No deal rows found');
                return;
            }
            
            // Nếu không tìm thấy nút, không thể khởi tạo deal controls
            if (!buyDealButton) {
                console.warn('[API] Buy button not found');
                return;
            }
            
            // Click on row to toggle checkbox
            dealRows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking directly on checkbox or label
                    if (e.target.type === 'checkbox' || e.target.type === 'radio' || e.target.tagName === 'LABEL') {
                        return;
                    }
                    
                    const checkbox = this.querySelector('.deal-checkbox-custom');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });
            
            // Handle checkbox/radio change
            dealCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Nếu backend trả available=false nhưng checkbox vẫn đang được check (do cache hoặc click trước đó), uncheck + toast
                    if (this.disabled && this.checked) {
                        this.checked = false;
                        if (!toastShown) {
                            showDealToast('Rất tiếc, quà tặng này vừa hết suất ưu đãi!');
                            toastShown = true;
                        }
                        updateBuyDealButton();
                        return;
                    }

                    const checkedCount = document.querySelectorAll('.deal-checkbox-custom:checked').length;
                    const limited = deal.limited || 1;
                    
                    // If limited = 1 (radio), uncheck others when one is checked
                    if (limited === 1 && this.checked && this.type === 'radio') {
                        dealCheckboxes.forEach(cb => {
                            if (cb !== this && cb.type === 'radio') {
                                cb.checked = false;
                            }
                        });
                    }
                    
                    // Enforce limit
                    if (checkedCount > limited) {
                        this.checked = false;
                        alert(`Bạn chỉ được chọn tối đa ${limited} sản phẩm mua kèm`);
                        return;
                    }
                    
                    // Update button state
                    updateBuyDealButton();
                });
            });
            
            // Ngay khi khởi tạo, uncheck các input đã bị disable (hết quà) và thông báo 1 lần nếu cần
            dealCheckboxes.forEach(cb => {
                if (cb.disabled && cb.checked) {
                    cb.checked = false;
                    if (!toastShown) {
                        showDealToast('Rất tiếc, quà tặng này vừa hết suất ưu đãi!');
                        toastShown = true;
                    }
                }
            });
            
            // Update button state function
            function updateBuyDealButton() {
                const checkedCount = document.querySelectorAll('.deal-checkbox-custom:checked').length;
                const allDealCheckboxes = document.querySelectorAll('.deal-checkbox-custom');
                const hasAvailableDeal = Array.from(allDealCheckboxes).some(cb => !cb.disabled && (parseInt(cb.closest('.item_deal_row')?.dataset.remainingQuota || '1', 10) > 0));
                const allDealsOutOfStock = allDealCheckboxes.length > 0 && Array.from(allDealCheckboxes).every(cb => cb.disabled);
                
                if (buyDealButton) {
                    // Nếu tất cả deal đều hết suất -> chuyển nút về mua hàng thường
                    if (allDealsOutOfStock || !hasAvailableDeal) {
                        // Loại bỏ class btnBuyDealSốc để nút quay về function mua hàng thường
                        buyDealButton.classList.remove('btnBuyDealSốc');
                        buyDealButton.disabled = false;
                        buyDealButton.textContent = 'Mua ngay';
                        console.log('[Deal] All deals out of stock, button switched to normal buy mode');
                    } else if (checkedCount === 0) {
                        // Còn deal available nhưng chưa chọn -> disable và yêu cầu chọn
                        buyDealButton.classList.add('btnBuyDealSốc');
                        buyDealButton.disabled = true;
                        buyDealButton.textContent = 'MUA DEAL SỐC';
                    } else {
                        // Đã chọn deal -> enable và cho phép mua deal
                        buyDealButton.classList.add('btnBuyDealSốc');
                        buyDealButton.disabled = false;
                        buyDealButton.textContent = 'MUA DEAL SỐC';
                    }
                }
            }
            
            // Initial button state
            updateBuyDealButton();
            
            console.log('[API] Deal controls initialized');
        }

        function showDealToast(message) {
            try {
                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.position = 'fixed';
                toast.style.bottom = '20px';
                toast.style.right = '20px';
                toast.style.background = 'rgba(0,0,0,0.8)';
                toast.style.color = '#fff';
                toast.style.padding = '10px 14px';
                toast.style.borderRadius = '6px';
                toast.style.zIndex = '9999';
                toast.style.fontSize = '14px';
                document.body.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.4s';
                    setTimeout(() => toast.remove(), 400);
                }, 2000);
            } catch (e) {
                console.warn('Toast display failed, fallback to alert', e);
                alert(message);
            }
        }
    })();
</script>
@endsection
