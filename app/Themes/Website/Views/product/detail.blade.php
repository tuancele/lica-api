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
                                    $hasVideo = !empty($detail->video);
                                    // Xây dựng danh sách media: 1 video (nếu có) + tối đa 9 hình
                                    $mediaItems = [];
                                    if ($hasVideo) {
                                        $mediaItems[] = [
                                            'type' => 'video',
                                            'src' => getImage($detail->video),
                                        ];
                                    }
                                    // Ảnh bìa luôn là ảnh đầu tiên
                                    $mainImage = getImage($detail->image);
                                    $mediaItems[] = [
                                        'type' => 'image',
                                        'src' => $mainImage,
                                    ];
                                    if(isset($gallerys) && !empty($gallerys)) {
                                        foreach($gallerys as $image) {
                                            $imgSrc = getImage($image);
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
                                                <img src="{{$item['src']}}" alt="{{$detail->name}}" class="js-skeleton-img">
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
                                        thumbnail.innerHTML = `<img src="${thumbSrc}" alt="Thumbnail ${index + 1}">`;
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
                        @if($detail->stock == 0)
                        <div class="is-stock mb-2">Hết hàng</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="breadcrumb">
                    <ol>
                        <li><a href="/">Trang chủ</a></li>
                        @if(isset($category) && !empty($category))
                        <li><a href="{{getSlug($category->slug)}}">{{$category->name}}</a></li>
                        @endif
                    </ol>
                </div>
                @if($detail->brand)
                <a href="/thuong-hieu/{{$detail->brand->slug}}" class="text-uppercase pointer brand-name">{{$detail->brand->name}}</a>
                @endif
                <h1 class="title-product">{{$detail->name}}</h1>
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
                    $currentVariantStock = (int)($first->stock ?? 0);
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
                        @endphp
                        <div class="item-variant @if($k==0) active @endif"
                             data-variant-id="{{$v->id}}"
                             data-sku="{{$v->sku}}"
                             data-price="{{$variantPriceInfo['final_price']}}"
                             data-original-price="{{$variantPriceInfo['original_price']}}"
                             data-price-html="{{base64_encode($variantPriceInfo['html'])}}"
                             data-stock="{{(int)($v->stock ?? 0)}}"
                             data-image="{{getImage($v->image ?: $detail->image)}}"
                             data-option1="{{ $optLabel }}">
                            <p class="mb-0">{{ $optLabel }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                <input type="hidden" name="variant_id"  value="{{$first->id}}">
                <div class="group-cart product-action align-center mt-3 space-between">
                    <div class="quantity align-center quantity-selector">
                        <button class="btn_minus entry" type="button" @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif>
                            <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                        </button>
                        <input @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif type="text" class="form-quatity quantity-input" value="1" min="1">
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="btn_plus entry" type="button">
                            <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                        </button>
                    </div>
                    <div class="item-action">
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif type="button" class="addCartDetail">
                            <span role="img" class="icon"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.5H14.5L10.5 0.5C10.3 0.2 10 0 9.7 0C9.4 0 9.1 0.2 8.9 0.5L4.9 6.5H0.5C0.2 6.5 0 6.7 0 7C0 7.1 0 7.2 0.1 7.3L2.3 16.3C2.5 17 3.1 17.5 3.8 17.5H16.2C16.9 17.5 17.5 17 17.7 16.3L19.9 7.3L20 7C20 6.7 19.8 6.5 19.5 6.5H19ZM9.7 2.5L12.5 6.5H6.9L9.7 2.5ZM16.2 16.5H3.8L1.8 8.5H18.2L16.2 16.5ZM9.7 10.5C8.9 10.5 8.2 11.2 8.2 12C8.2 12.8 8.9 13.5 9.7 13.5C10.5 13.5 11.2 12.8 11.2 12C11.2 11.2 10.5 10.5 9.7 10.5Z" stroke="#ee4d2d" stroke-width="1.5" fill="none"/><path d="M9.7 8.5V15.5M6.2 12H13.2" stroke="#ee4d2d" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                            <span>Thêm Vào Giỏ Hàng</span>
                        </button>
                    </div>
                    <div class="item-action">
                        @if(isset($saledeals) && $saledeals->count() > 0)
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail btnBuyDealSốc" type="button">MUA DEAL SỐC</button>
                        @else
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail" type="button">Mua ngay</button>
                        @endif
                    </div>
                </div>
                @if(isset($saledeals) && $saledeals->count() > 0)
                <div class="sc-67558998-0 buy-x-get-y-wrapper mb-4">
                    <div class="buy-x-get-y-header">
                        <div class="title">Mua kèm deal sốc</div>
                        <div class="sub-title">Mua để nhận ưu đãi (Tối đa {{$deal->limited}})</div>
                    </div>
                    <div class="buy-x-get-y-body">
                        @foreach($saledeals as $saledeal)
                        @php $product_deal = $saledeal->product; @endphp
                        <div class="item_deal_row">
                            <div class="item_deal_action me-3">
                                @if($deal->limited == 1)
                                    <input type="radio" name="deal_item" class="deal-checkbox-custom" id="deal_{{$saledeal->id}}" value="{{$product_deal->variant($product_deal->id)->id ?? ''}}">
                                @else
                                    <input type="checkbox" name="deal_item[]" class="deal-checkbox-custom" id="deal_{{$saledeal->id}}" value="{{$product_deal->variant($product_deal->id)->id ?? ''}}">
                                @endif
                                <label for="deal_{{$saledeal->id}}" class="deal-checkmark"></label>
                            </div>
                            <div class="item_deal_info">
                                <div class="thumb_deal">
                                    <div class="skeleton--img-sm js-skeleton">
                                        <img src="{{getImage($product_deal->image)}}" alt="{{$product_deal->name}}" class="js-skeleton-img">
                                    </div>
                                </div>
                                <div class="info_deal">
                                    <h5 class="deal-product-name">{{$product_deal->name}}</h5>
                                    <div class="price_deal">
                                        <span class="curr-price">{{number_format($saledeal->price)}}đ</span>
                                        <del class="old-price">{{number_format($product_deal->variant($product_deal->id)->price ?? 0)}}đ</del>
                                    </div>
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
                            $str = $detail->ingredient;
                            // Only apply dynamic linking if links are not already present (Legacy support)
                            if (strpos($str, 'item_ingredient') === false) {
                                $list =  App\Modules\Ingredient\Models\Ingredient::where('status','1')->get();
                                if(isset($list) && !empty($list)){
                                    foreach($list as $value){
                                        $str = str_replace($value->name,'<a href="javascript:;" class="item_ingredient" data-id="'.$value->slug.'">'.$value->name.'</a>',$str);
                                    }
                                }
                            }
                        @endphp
                        {!!$str!!}
                    </div>
                </div>
            </div>
        </div>
        @endif
                @php
                    // Chuẩn hoá lại $t_rates và $rates ở block đánh giá để tránh null
                    $tRateCol = isset($t_rates) && $t_rates ? $t_rates : collect();
                    $tRateCount = $tRateCol->count();
                    $tRateSum   = $tRateCol->sum('rate');
                    $rateCol = isset($rates) && $rates ? $rates : collect();
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
                    @php $images = json_decode($rate->images) @endphp
                    <div class="item-rate">
                        <p class="mb-2">{{$rate->name}}</p>
                        <div class="align-center mb-2">
                            <div class="rating me-3 mt-0 mb-0">{!!getStar($rate->rate,1)!!}</div>
                            <div class="text-gray">{{date('d/m/Y H:i',strtotime($rate->created_at))}}</div>
                        </div>
                        <div class="fw-bold mb-2">{{$rate->title}}</div>
                        <div>{{$rate->content}}</div>
                        @if(isset($images) && !empty($images))
                            <div class="list_gallery">
                            @foreach($images as $image)
                            <a href="{{getImage($image)}}" class="item_gallery image-link">
                                <div class="skeleton--img-sm js-skeleton">
                                    <img src="{{getImage($image)}}" alt="{{$rate->name}}" class="js-skeleton-img">
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
                                @if($product->stock == 0)
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
                                    $activeDeal = null;
                                    $dealDiscountPercent = 0;
                                    try {
                                        $now = strtotime(date('Y-m-d H:i:s'));
                                        $deal_id = App\Modules\Deal\Models\ProductDeal::where('product_id', $product->id)->where('status', 1)->pluck('deal_id')->toArray();
                                        if (!empty($deal_id)) {
                                            $activeDeal = App\Modules\Deal\Models\Deal::whereIn('id', $deal_id)
                                                ->where([['status', '1'], ['start', '<=', $now], ['end', '>=', $now]])
                                                ->first();
                                            
                                            // 计算 deal 折扣百分比
                                            $variant = App\Modules\Product\Models\Variant::select('price','sale')->where('product_id', $product->id)->first();
                                            if ($activeDeal && $variant) {
                                                $saleDeal = App\Modules\Deal\Models\SaleDeal::where([['deal_id', $activeDeal->id], ['product_id', $product->id], ['status', '1']])->first();
                                                if ($saleDeal && isset($saleDeal->price) && isset($variant->price) && $variant->price > 0) {
                                                    $dealDiscountPercent = round(($variant->price - $saleDeal->price) / ($variant->price / 100));
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        // 静默处理错误，不显示 deal voucher
                                        $activeDeal = null;
                                    }
                                @endphp
                                @if($activeDeal)
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
                        <img src="{{getImage($detail->image)}}" width="72" height="72" alt="{{$detail->name}}" class="js-skeleton-img">
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
                    <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif type="button" class="addCartDetail">
                        <span role="img" class="icon"><svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg></span>
                        <span>Thêm vào giỏ hàng</span>
                    </button>
                </div>
                <div class="item-action">
                    @if(isset($saledeals) && $saledeals->count() > 0)
                    <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail btnBuyDealSốc" type="button">MUA DEAL SỐC</button>
                    @else
                    <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail" type="button">Mua ngay</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal" tabindex="-1" id="showIngredient">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose closeIngredient" type="button">
                <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
            </button>
            <div class="modal-body">
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
                    <img src="{{getImage($detail->image)}}" width="65" height="65" alt="{{$detail->name}}" class="js-skeleton-img">
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
    
    $('body').on('click','.addCartDetail',function(){
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
            getCart();
            alert("Đã thêm vào giỏ hàng");
          }else{
            alert("Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại");
          }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
      })
    });

    $('body').on('click','.buyNowDetail',function(){
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
                    if(res.status == 'success'){
                        window.location = '{{route("cart.index")}}';
                    }
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
            alert("Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại");
          }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
      })
    });

    $('body').on('click','.item_ingredient',function(){
        var id = $(this).attr('data-id');
        $.ajax({
        type: 'get',
        url: '/ingredient/'+id,
        data: {},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('#showIngredient').modal('show');
            $('#showIngredient .modal-body').html(res);
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
        }
      })
    });
    var isShopeeVariant = @json($isShopeeVariant);

    if(isShopeeVariant){
        $('#detailProduct').on('click','#variant-option1-list .item-variant',function(){
            var $it = $(this);
            $('#variant-option1-list .item-variant').removeClass('active');
            $it.addClass('active');

            var variantId = $it.data('variant-id');
            var sku = $it.data('sku') || '';
            var price = parseFloat($it.data('price') || 0);
            // 获取 base64 编码的 HTML 字符串
            var priceHtmlRaw = $it.attr('data-price-html') || '';
            // 解码 base64 编码的 HTML（正确处理 UTF-8）
            var priceHtml = '';
            if (priceHtmlRaw) {
                try {
                    // 解码 base64 字符串并正确处理 UTF-8 编码
                    var binaryString = atob(priceHtmlRaw);
                    var bytes = new Uint8Array(binaryString.length);
                    for (var i = 0; i < binaryString.length; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    priceHtml = new TextDecoder('utf-8').decode(bytes);
                } catch(e) {
                    // 如果解码失败，尝试使用传统方法
                    try {
                        priceHtml = decodeURIComponent(escape(atob(priceHtmlRaw)));
                    } catch(e2) {
                        // 如果还是失败，使用默认价格
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

            // Update main slider first image (best-effort)
            if(img){
                var firstImg = document.querySelector('#slidesWrapper .slide img');
                if(firstImg) firstImg.src = img;
            }

            // Toggle buttons by stock
            var disabled = stock <= 0;
            $('.addCartDetail, .buyNowDetail, .btn_plus.entry, .btn_minus.entry, .quantity-input').prop('disabled', disabled);
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
</style>
@endsection
