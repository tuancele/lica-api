@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<link rel="stylesheet" href="/public/website/owl-carousel/owl.carousel-2.0.0.css">
<script src="/public/website/owl-carousel/owl.carousel-2.0.0.min.js"></script>
@endsection
@section('content')
<section class="mt-3" id="detailProduct">
    <div class="container-lg">
        <div class="row">
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
                                            <img src="{{$item['src']}}" alt="{{$detail->name}}">
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
                @endphp
                <div class="d-block overflow-hidden mb-2 list_attribute">
                    <div class="item_1 rate-section w40 fs-12 pointer" onclick="window.location='{{getSlug($detail->slug)}}#ratingProduct'">
                        <div class="rating mt-0 mb-0">
                            {!!getStar($rateSum,$rateCount)!!}
                            <div class="count-rate">({{$rateCount}})</div>
                        </div>
                    </div>
                    @php
                        $wishlistCollection = method_exists($detail, 'wishlists') ? ($detail->wishlists ?? collect()) : collect();
                        $wishlistCount = $wishlistCollection ? $wishlistCollection->count() : 0;
                    @endphp
                    <div class="item_2 fav-section w60 fs-12">
                        <span role="img" class="icon"><svg width="12" height="12" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.001 0C18.445 0 16.1584 1.24169 14.6403 3.19326C13.1198 1.24169 10.8355 0 8.27952 0C3.70634 0 0 3.97108 0 8.86991C0 15.1815 9.88903 23.0112 13.4126 25.5976C14.1436 26.1341 15.1369 26.1341 15.8679 25.5976C19.3915 23.0088 29.2805 15.1815 29.2805 8.86991C29.2782 3.97108 25.5718 0 21.001 0Z" fill="#C73130"></path></svg></span> <span class="total-wishlist ms-1">{{$wishlistCount}}</span>
                    </div>
                    @if($detail->origin)
                    <div class="item_3 origin-section w40 fs-12"><b>Xuất xứ:</b> {{$detail->origin->name}}</div>
                    @endif
                    @if($detail->cbmp != "")
                    <div class="item_4 origin-section w60 fs-12"><b>Số CBMP:</b> {{$detail->cbmp}}</div>
                    @endif
                    <div class="item_5 sku-section w40 fs-12"><b>SKU:</b> <span id="variant-sku-display">{{$first->sku}}</span></div>
                    @if($detail->verified == 1)
                    <div class="verified w60 fs-12 d-block d-md-none">
                        <strong>Đã xác thực bởi:</strong> {{getConfig('verified')}} 
                        <span class="show_verified"><svg viewBox="64 64 896 896" focusable="false" data-icon="question-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 708c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40zm62.9-219.5a48.3 48.3 0 00-30.9 44.8V620c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8v-21.5c0-23.1 6.7-45.9 19.9-64.9 12.9-18.6 30.9-32.8 52.1-40.9 34-13.1 56-41.6 56-72.7 0-44.1-43.1-80-96-80s-96 35.9-96 80v7.6c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V420c0-39.3 17.2-76 48.4-103.3C430.4 290.4 470 276 512 276s81.6 14.5 111.6 40.7C654.8 344 672 380.7 672 420c0 57.8-38.1 109.8-97.1 132.5z"></path></svg></span>
                    </div>
                    @endif
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
                        <div class="price" id="variant-price-display">
                            <p>{{number_format($first->price ?? 0)}}đ</p>
                        </div>
                    @else
                        <div class="price">{!!checkSale($detail->id)!!}</div>
                    @endif
                     @if($detail->verified == 1)
                    <div class="verified w60 fs-12 d-none d-md-block">
                        <strong>Đã xác thực bởi:</strong> {{getConfig('verified')}} 
                        <span class="show_verified"><svg viewBox="64 64 896 896" focusable="false" data-icon="question-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 708c-22.1 0-40-17.9-40-40s17.9-40 40-40 40 17.9 40 40-17.9 40-40 40zm62.9-219.5a48.3 48.3 0 00-30.9 44.8V620c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8v-21.5c0-23.1 6.7-45.9 19.9-64.9 12.9-18.6 30.9-32.8 52.1-40.9 34-13.1 56-41.6 56-72.7 0-44.1-43.1-80-96-80s-96 35.9-96 80v7.6c0 4.4-3.6 8-8 8h-48c-4.4 0-8-3.6-8-8V420c0-39.3 17.2-76 48.4-103.3C430.4 290.4 470 276 512 276s81.6 14.5 111.6 40.7C654.8 344 672 380.7 672 420c0 57.8-38.1 109.8-97.1 132.5z"></path></svg></span>
                    </div>
                    @endif
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
                                    <img src="{{getImage($product_deal->image)}}" alt="{{$product_deal->name}}">
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
                        @endphp
                        <div class="item-variant @if($k==0) active @endif"
                             data-variant-id="{{$v->id}}"
                             data-sku="{{$v->sku}}"
                             data-price="{{$v->price}}"
                             data-stock="{{(int)($v->stock ?? 0)}}"
                             data-image="{{getImage($v->image ?: $detail->image)}}"
                             data-option1="{{ $optLabel }}">
                            <p class="mb-0">{{ $optLabel }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @elseif($colors->count() > 0 && $colors[0]->color_id != 0)
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
                @if(checkFlash($detail->id))
                @php 
                    $date = strtotime(date('Y-m-d H:i:s'));
                    $flash = App\Modules\FlashSale\Models\FlashSale::where([['status','1'],['start','<=',$date],['end','>=',$date]])->first();
                @endphp
                <div class="div_flashsale d-flex">
                    <div class="title_flash">
                        <span role="img" class="anticon" style="font-size: 16px;"><svg width="16" height="26" viewBox="0 0 16 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.27059 0C7.54353 0 9.82118 0 12.0941 0C12.64 0.324982 12.7388 0.639809 12.48 1.26946C11.6141 3.39708 10.7435 5.51962 9.87765 7.64724C9.84471 7.72849 9.81647 7.80973 9.77882 7.91637C9.90118 7.91637 9.98588 7.91637 10.0706 7.91637C11.7412 7.91637 13.4118 7.92652 15.0824 7.91129C15.5153 7.90621 15.8259 8.04839 16 8.48508C16 8.62219 16 8.75421 16 8.89131C15.92 9.02334 15.8447 9.16044 15.7647 9.29246C12.7953 14.284 9.82118 19.2755 6.85177 24.267C6.57882 24.724 6.31529 25.181 6.03765 25.6279C5.75059 26.0849 5.20941 26.1255 4.89882 25.7244C4.71529 25.4857 4.71529 25.2166 4.78118 24.9322C5.45882 21.9922 6.13647 19.047 6.81412 16.1069C6.92235 15.6398 7.02588 15.1777 7.13412 14.6801C7.00706 14.6801 6.91294 14.6801 6.82353 14.6801C4.85647 14.6801 2.88941 14.6699 0.922353 14.6851C0.484706 14.6902 0.174118 14.5531 0 14.1164C0 13.9793 0 13.8473 0 13.7102C0.0329412 13.6391 0.0752941 13.568 0.103529 13.4918C1.63765 9.2163 3.17176 4.93567 4.70118 0.655042C4.81412 0.345294 4.97412 0.111713 5.27059 0Z" fill="white"></path></svg></span>
                        SIÊU DEAL <strong class="d-none d-md-inline-block">CHỚP NHOÁNG</strong>
                    </div>
                    <div class="timer_flash">
                        <div>00</div><span>:</span><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div>
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
                            return `<div>${days} ${days > 1 ? '' : ''}</div><span>:</span>`+'<div>'+`${hours}`+'</div><span>:</span><div>'+`${minutes} `+'</div><span>:</span><div>'+`${seconds}`+'</div>';
                        }
                        if (hours > 0) {
                            return `<div>00</div><span>:</span><div>${hours}`+'</div><span>:</span><div>'+`${minutes}`+'</div><span>:</span><div>'+`${seconds}`+'</div>';
                        }
                        if(days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0){
                            window.location = window.location.href;
                            return `<div>00</div><span>:</span><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div>`;
                        }
                        return `<div>00</div><span>:</span><div>00</div><span>:</span><div>${minutes}`+'</div><span>:</span><div>'+`${seconds}`+'</div>';
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
                        <div class="title_flash fs-12">
                            <span role="img" class="anticon" style="font-size: 16px;"><svg width="16" height="26" viewBox="0 0 16 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.27059 0C7.54353 0 9.82118 0 12.0941 0C12.64 0.324982 12.7388 0.639809 12.48 1.26946C11.6141 3.39708 10.7435 5.51962 9.87765 7.64724C9.84471 7.72849 9.81647 7.80973 9.77882 7.91637C9.90118 7.91637 9.98588 7.91637 10.0706 7.91637C11.7412 7.91637 13.4118 7.92652 15.0824 7.91129C15.5153 7.90621 15.8259 8.04839 16 8.48508C16 8.62219 16 8.75421 16 8.89131C15.92 9.02334 15.8447 9.16044 15.7647 9.29246C12.7953 14.284 9.82118 19.2755 6.85177 24.267C6.57882 24.724 6.31529 25.181 6.03765 25.6279C5.75059 26.0849 5.20941 26.1255 4.89882 25.7244C4.71529 25.4857 4.71529 25.2166 4.78118 24.9322C5.45882 21.9922 6.13647 19.047 6.81412 16.1069C6.92235 15.6398 7.02588 15.1777 7.13412 14.6801C7.00706 14.6801 6.91294 14.6801 6.82353 14.6801C4.85647 14.6801 2.88941 14.6699 0.922353 14.6851C0.484706 14.6902 0.174118 14.5531 0 14.1164C0 13.9793 0 13.8473 0 13.7102C0.0329412 13.6391 0.0752941 13.568 0.103529 13.4918C1.63765 9.2163 3.17176 4.93567 4.70118 0.655042C4.81412 0.345294 4.97412 0.111713 5.27059 0Z" fill="white"></path></svg></span>
                            SIÊU DEAL CHỚP NHOÁNG DIỄN RA VÀO
                        </div>
                        <div class="timer_flash">
                            <div>00</div><span>:</span><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div>
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
                                return `<div>${days} ${days > 1 ? '' : ''}</div><span>:</span>`+'<div>'+`${hours}`+'</div><span>:</span><div>'+`${minutes} `+'</div><span>:</span><div>'+`${seconds}`+'</div>';
                            }
                            if (hours > 0) {
                                return `<div>00</div><span>:</span><div>${hours}`+'</div><span>:</span><div>'+`${minutes}`+'</div><span>:</span><div>'+`${seconds}`+'</div>';
                            }
                            if(days <= 0 && hours <= 0 && minutes <= 0 && seconds <= 0){
                                window.location = window.location.href;
                                return `<div>00</div><span>:</span><div>00</div><span>:</span><div>00</div><span>:</span><div>00</div>`;
                            }
                            return `<div>00</div><span>:</span><div>00</div><span>:</span><div>${minutes}`+'</div><span>:</span><div>'+`${seconds}`+'</div>';
                        }
                        const deadline = new Date('{{date("Y/m/d H:i:s",$flash->start)}}');
                        let remainingTime = (deadline - new Date) / 1000;
                        setInterval(function () {
                            remainingTime--;
                            $('.timer_flash').html(formatTimer(remainingTime));
                        }, 1000);
                    </script>
                @endif
                <!--  So sánh cửa hàng -->
                @if($compares->count() > 0)
                <div class="div_compare">
                    <h3>Sản phẩm ở cửa hàng khác</h3>
                    <div class="list_compare">
                        @foreach($compares as $compare)
                        <div class="item_compare">
                <div class="logo_compare">
                    <img src="{{getImage($compare->store->logo??'')}}" alt="{{$compare->store->name??''}}">
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
                            <span role="img" class="icon"><svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg></span>
                            <span>Thêm vào giỏ hàng</span>
                        </button>
                    </div>
                    <div class="item-action">
                        @if(isset($saledeals) && $saledeals->count() > 0)
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail btnBuyDealSốc" style="background-color: #C73130; color: #fff; border-color: #C73130;" type="button">MUA DEAL SỐC</button>
                        @else
                        <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail" type="button">Mua ngay</button>
                        @endif
                    </div>
                    <div class="item-action btnWishlist group-wishlist-{{$detail->id}}">
                        {!!wishList($detail->id)!!}
                    </div>
                </div>
                <div class="product-policys row mt-3">
                    <div class="col-md-6 col-12">
                        <div class="align-center delivery-section mt-2">
                            <span role="img" aria-label="star" class="icon"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3zM664.8 561.6l36.1 210.3L512 672.7 323.1 772l36.1-210.3-152.8-149L417.6 382 512 190.7 606.4 382l211.2 30.7-152.8 148.9z"></path></svg></span>
                            <div class="ms-1">Nhận <b>Wal Point</b> cho mỗi lần mua</div>
                        </div>
                        <div class="align-center delivery-section mt-2">
                            <span role="img" aria-label="star" class="icon"><svg width="24" height="17" viewBox="0 0 24 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 5.63536L18.3572 1.40884H14.0222V0H0V1.40884H12.5691V12.7735H10.2684C9.95358 11.2003 8.47629 9.97928 6.73259 9.97928C4.9889 9.97928 3.53582 11.2003 3.19677 12.797H0V14.2058H3.19677C3.53582 15.8025 4.9889 17 6.73259 17C8.47629 17 9.92937 15.8025 10.2684 14.2058H14.555C14.894 15.8025 16.3471 17 18.0908 17C19.8345 17 21.3118 15.8025 21.6509 14.2058H24V5.63536ZM6.73259 15.5912C5.54591 15.5912 4.57719 14.6519 4.57719 13.5014C4.57719 12.3508 5.54591 11.4116 6.73259 11.4116C7.91927 11.4116 8.88799 12.3508 8.88799 13.5014C8.88799 14.6519 7.91927 15.5912 6.73259 15.5912ZM18.115 15.5912C16.9284 15.5912 15.9596 14.6519 15.9596 13.5014C15.9596 12.3508 16.9284 11.4116 18.115 11.4116C19.3017 11.4116 20.2704 12.3508 20.2704 13.5014C20.2704 14.6519 19.3017 15.5912 18.115 15.5912ZM22.5469 12.797H21.6509C21.3118 11.2003 19.8587 9.97928 18.115 9.97928C16.3713 9.97928 14.894 11.2003 14.555 12.797H14.0222V2.81768H17.8486L22.5469 6.33978V12.797Z" fill="#060404"></path><path d="M3.0999 4.34392H0V5.75276H3.0999V4.34392Z" fill="#060404"></path><path d="M3.0999 8.45304H0V9.86188H3.0999V8.45304Z" fill="#060404"></path></svg></span>
                            <div class="ms-1"><b>Miễn phí giao hàng</b>, tối đa 44k</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-12">
                        <div class="align-center delivery-section mt-2">
                            <span role="img" aria-label="star" class="icon"><svg width="24" height="17" viewBox="0 0 28 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M23.1742 6.23137H28V4.58431H23.1742V0H4.77419V4.58431H0V6.23137H4.77419V10.9255C4.77419 11.6118 4.85161 12.298 5.00645 12.9294H0V14.5765H5.57419C6.34839 16.3059 7.66451 17.7608 9.36774 18.6392L14.0129 21L18.6581 18.6392C20.3355 17.7882 21.6516 16.3333 22.4516 14.5765H28V12.9294H22.9419C23.0968 12.2706 23.1742 11.6118 23.1742 10.9255V6.23137ZM21.6258 10.898C21.6258 13.5333 20.1806 15.9765 17.9355 17.1294L13.9613 19.1608L9.9871 17.1294C7.74194 15.9765 6.29677 13.5333 6.29677 10.898V1.64706H21.6V10.898H21.6258Z" fill="black"></path><path d="M19.3548 6.61569L18.1677 5.5451L13.3935 11.4745L10.6581 8.75686L9.6 9.96471L13.5226 13.8627L19.3548 6.61569Z" fill="black"></path></svg></span>
                            <div class="ms-1">Cam kết <b>hàng chính hãng</b></div>
                        </div>
                        <div class="align-center delivery-section mt-2">
                            <span role="img" aria-label="star" class="icon"><svg width="24" height="17" viewBox="0 0 33 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M28.529 6.00281L27.4911 7.26508L29.5935 9.14446H24.404V18.317H8.59597V14.7546H6.99919V20H26.0008V10.8275H29.5935L27.4911 12.7349L28.529 13.9972L33 9.98597L28.529 6.00281Z" fill="black"></path><path d="M8.59597 10.8275V1.68303H12.8008V7.79804H20.1992V1.68303H24.404V5.4979H26.0008V0H6.99919V9.14446H3.40645L5.50887 7.26508L4.47096 5.97475L0 9.98597L4.47096 13.9972L5.50887 12.7069L3.40645 10.8275H8.59597ZM18.6024 1.68303V6.11501H14.3976V1.68303H18.6024Z" fill="black"></path></svg></span>
                            <div class="ms-1">Đổi/trả hàng trong <b>7 ngày</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($detail->content != "")
        <div class="divider-horizontal mt-5"></div>
        <div class="mt-5 row mb-5">
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
        @endif
        @if($detail->ingredient != "")
        <div class="mt-5 row mb-5">
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
        @endif
        <div class="divider-horizontal"></div>
                @php
                    // Chuẩn hoá lại $t_rates và $rates ở block đánh giá để tránh null
                    $tRateCol = isset($t_rates) && $t_rates ? $t_rates : collect();
                    $tRateCount = $tRateCol->count();
                    $tRateSum   = $tRateCol->sum('rate');
                    $rateCol = isset($rates) && $rates ? $rates : collect();
                @endphp
                <div class="rating-product row mt-5 mb-5" id="ratingProduct">
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
                                <img src="{{getImage($image)}}" alt="{{$rate->name}}">
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
        <div class="divider-horizontal"></div>
        <div class="product-related row mt-5 mb-5">
            <div class="col-12 col-md-4 mb-3 mb-md-0">
                <div class="fw-bold fs-24">Sản phẩm liên quan</div>
            </div>
            <div class="col-12 col-md-8">
                @if($products->count() > 0)
                <div class="list-product">
                    @foreach($products as $product)
                    @php $trate = App\Modules\Rate\Models\Rate::select('id','rate')->where([['status','1'],['product_id',$product->id]])->get() @endphp
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
                                    {!!getStar($trate->sum('rate'),$trate->count())!!}
                                    <div class="count-rate">({{$trate->count()??'0'}})</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @if(isset($watchs) && $watchs->count() > 0)
        <div class="divider-horizontal"></div>
        <div class="watched mt-5 mb-5">
            <h3 class="text-center text-uppercase fw-bold fs-25">Các mẫu bạn đã xem</h3>
            <div class="list-watch mt-3">
                @foreach($watchs as $watch)
                @include('Website::product.item',['product' => $watch])
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
@section('footer')
<div class="product-fix">
    <div class="container-lg">
        <div class="box-product d-flex align-center space-between">
            <div class="align-center product-info">
                <div class="thumb">
                    <img src="{{getImage($detail->image)}}" width="72" height="72" alt="{{$detail->name}}">
                </div>
                <div class="description ms-2">
                    <div class="fs-16 fw-bold">{{$detail->name}}</div>
                    @if($isShopeeVariant)
                        <div class="price-fix" id="variant-price-fix"><p>{{number_format($first->price ?? 0)}}đ</p></div>
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
                    <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail btnBuyDealSốc" style="background-color: #C73130; color: #fff; border-color: #C73130;" type="button">MUA DEAL SỐC</button>
                    @else
                    <button @if(($isShopeeVariant && $currentVariantStock <= 0) || (!$isShopeeVariant && $detail->stock == 0)) disabled @endif class="buyNowDetail" type="button">Mua ngay</button>
                    @endif
                </div>
                <div class="item-action btnWishlist group-wishlist-{{$detail->id}}">
                    {!!wishList($detail->id)!!}
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
                <img src="{{getImage($detail->image)}}" width="65" height="65" alt="{{$detail->name}}">
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
            <div class="divider-horizontal"></div>
            <div class="row mb-3">
                <div class="col-6">
                    <label>Biệt danh <span>*</span></label>
                    <input type="text" require class="form-control" name="name" value="@if(isset($member)){{$member['first_name']}} {{$member['last_name']}}@endif" placeholder="Nguyễn Văn An" autocomplete="false">
                </div>
                <div class="col-6">
                    <label>Email <span>*</span></label>
                    <input type="email" require class="form-control" name="email" value="@if(isset($member)){{$member['email']}}@endif" placeholder="admin@gmail.com" autocomplete="false">
                </div>
            </div>
            <div class="divider-horizontal mt-2"></div>
            <div class="row mb-3">
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
            var stock = parseInt($it.data('stock') || 0, 10);
            var img = $it.data('image') || '';
            var optionText = $it.data('option1') || '';

            $('#detailProduct input[name="variant_id"]').val(variantId);
            $('#variant-sku-display').text(sku);
            $('#variant-option1-current').text(optionText);
            $('#variant-price-display').html('<p>'+ (price || 0).toLocaleString('vi-VN') +'đ</p>');
            $('#variant-price-fix').html('<p>'+ (price || 0).toLocaleString('vi-VN') +'đ</p>');

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
        border-top: 1px solid #f1f1f1;
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
        min-width: 56px;
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
    }
    @media(max-width: 568px){
        .w-25p{
            width: 50%;
        }
    }
</style>
@endsection
