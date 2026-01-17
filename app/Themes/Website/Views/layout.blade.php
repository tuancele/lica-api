<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    @if(!getConfig('index'))
    <meta name="robots" content="noindex" />
    @else
    @if(isset($noindex) && $noindex == 1)
        <meta name="robots" content="noindex" />
    @else
        <meta name="robots" content="follow, index, max-snippet:-1, max-video-preview:-1, max-image-preview:large" />
    @endif
    @endif
    @if(isset($canonical) && $canonical != '')
    <link rel="canonical" href="{{asset($canonical)}}" />
    @endif
    <title>@yield('title')</title>
    <meta name="description" content="@yield('description')">
    <link rel="shortcut icon" type="image/x-icon" href="{{getConfig('favicon')}}" />
    <meta property="og:locale" content="vi_VN" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="@yield('title')" />
    <meta property="og:description" content="@yield('description')" />
    <meta property="og:url" content="{{url()->current()}}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="@yield('title')" />
    <meta name="twitter:description" content="@yield('description')" />
    <!-- CSS文件加载 -->
    <link rel="stylesheet" href="/public/website/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="/public/website/css/bootstrap.min.css">
    
    <!-- JavaScript优化：jQuery需要同步加载（其他脚本依赖），Bootstrap使用defer -->
    <script src="/public/website/js/jquery.min.js"></script>
    <script src="/public/website/js/bootstrap.bundle.min.js" defer></script>
    <!-- 异步加载优化脚本 -->
    <script src="/public/website/js/lazy-load.js" defer></script>
    <script src="/public/js/product-recommendation.js" defer></script>
    <script src="/public/js/product-home-optimizer.js" defer></script>
    <script src="/public/website/js/skeleton-optimizer.js" defer></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('header')
    <link rel="preload" href="/public/website/css/style.css" as="style">
    <link rel="stylesheet" href="/public/website/css/style.css">
    <link rel="stylesheet" href="/public/website/css/product-home-optimized.css">
    <style>
        /* Danh mục nổi bật - Horizontal scroll trên mobile */
        .list-taxonomy-wrapper {
            position: relative;
        }
        @media (max-width: 768px) {
            .list-taxonomy-wrapper {
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: thin;
                scrollbar-color: #ddd transparent;
                margin: 0 -15px;
                padding: 0 15px 10px;
                width: calc(100% + 30px);
            }
            .list-taxonomy-wrapper::-webkit-scrollbar {
                height: 4px;
            }
            .list-taxonomy-wrapper::-webkit-scrollbar-track {
                background: transparent;
            }
            .list-taxonomy-wrapper::-webkit-scrollbar-thumb {
                background: #ddd;
                border-radius: 2px;
            }
            .list-taxonomy-wrapper::-webkit-scrollbar-thumb:hover {
                background: #bbb;
            }
            .list-taxonomy {
                display: flex !important;
                flex-wrap: nowrap !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                padding-bottom: 0;
                width: max-content;
            }
            .list-taxonomy .col8 {
                flex: 0 0 auto !important;
                width: 140px !important;
                min-width: 140px;
                max-width: 140px;
                margin-left: 12px !important;
                margin-right: 0 !important;
            }
            .list-taxonomy .col8:first-child {
                margin-left: 0 !important;
            }
            .taxonomy-item {
                padding: 12px 16px 16px !important;
                border-radius: 8px;
            }
            .taxonomy-cover {
                margin-bottom: 6px;
            }
            .taxonomy-cover img {
                max-width: 100%;
                height: auto;
                object-fit: contain;
            }
            .taxonomy-title {
                font-size: 11px;
                line-height: 1.3;
                text-align: center;
                word-break: break-word;
            }
        }
        @media (max-width: 480px) {
            .list-taxonomy .col8 {
                width: 120px !important;
                min-width: 120px;
                max-width: 120px;
                margin-left: 10px !important;
            }
            .taxonomy-item {
                padding: 10px 12px 14px !important;
            }
            .taxonomy-title {
                font-size: 10px;
            }
        }
        .footer-blocks {
            background-color: #e9ecef;
        }
        .footer-block-item {
            padding: 10px 15px;
        }
        .footer-block-title {
            font-size: 13px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .footer-block-title strong {
            font-weight: 700;
        }
        .footer-links {
            font-size: 11px;
            line-height: 1.6;
            color: #666;
        }
        .footer-link {
            color: #666;
            text-decoration: none;
            font-size: 11px;
            transition: color 0.2s ease;
        }
        .footer-link:hover {
            color: #007bff;
            text-decoration: none;
        }
        .footer-link-separator {
            color: #adb5bd;
            margin: 0 3px;
            font-size: 11px;
        }
        /* Mobile collapse/expand */
        .footer-block-toggle {
            display: none;
        }
        @media (max-width: 768px) {
            .footer-blocks {
                padding: 0;
                margin: 0;
            }
            .footer-blocks .container-lg {
                padding: 6px 8px !important;
            }
            .footer-blocks .row {
                margin: 0;
            }
            .footer-block-item {
                padding: 4px 6px !important;
                margin-bottom: 2px !important;
                border-bottom: 1px solid #e9ecef;
                width: 100%;
                max-width: 100%;
                flex: 0 0 100%;
            }
            .footer-block-item:last-child {
                border-bottom: none;
                margin-bottom: 0 !important;
            }
            .footer-block-title {
                font-size: 9px;
                margin-bottom: 3px;
                line-height: 1.2;
                cursor: pointer;
                position: relative;
                padding-right: 18px;
                font-weight: 600;
            }
            .footer-block-title::after {
                content: '+';
                position: absolute;
                right: 0;
                font-size: 11px;
                font-weight: normal;
                transition: transform 0.3s ease;
                color: #666;
            }
            .footer-block-item.active .footer-block-title::after {
                content: '−';
                transform: rotate(0deg);
            }
            .footer-links {
                font-size: 8px;
                line-height: 1.3;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, margin-top 0.3s ease;
                margin-top: 0;
            }
            .footer-block-item.active .footer-links {
                max-height: 400px;
                margin-top: 3px;
            }
            .footer-link {
                font-size: 8px;
                display: inline-block;
                margin-right: 1px;
                margin-bottom: 1px;
                line-height: 1.3;
            }
            .footer-link-separator {
                font-size: 8px;
                margin: 0 1px;
            }
            @media (max-width: 480px) {
                .footer-blocks .container-lg {
                    padding: 4px 6px !important;
                }
                .footer-block-item {
                    padding: 3px 4px !important;
                    margin-bottom: 1px !important;
                }
            }
        }
        /* Footer chính - Mobile optimization */
        @media (max-width: 768px) {
            footer.pt-5.pb-5 {
                padding-top: 12px !important;
                padding-bottom: 12px !important;
            }
            footer .container-lg {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }
            footer .row {
                margin: 0;
            }
            /* Box footer - Logo và social */
            .box-footer {
                padding: 8px 0 !important;
                margin-bottom: 8px;
            }
            .logo_footer img {
                max-width: 120px;
                height: auto;
            }
            .list_social {
                margin: 6px 0 !important;
                padding: 0;
            }
            .list_social a img {
                width: 20px !important;
                height: 20px !important;
                margin-right: 8px;
            }
            .bocongthuong img {
                max-width: 90px !important;
                height: auto !important;
            }
            /* Info footer - Thông tin liên hệ */
            .info_footer {
                padding: 8px 0 !important;
                font-size: 9px;
                line-height: 1.4;
            }
            .info_footer p {
                margin-bottom: 4px;
                font-size: 9px;
                line-height: 1.4;
            }
            .info_footer strong {
                font-size: 9px;
            }
            /* Menu footer - Collapse/expand */
            .menu_footer {
                margin-bottom: 4px;
                border-bottom: 1px solid #e9ecef;
                padding-bottom: 4px;
            }
            .menu_footer:last-child {
                border-bottom: none;
            }
            .title_nav {
                font-size: 9px;
                font-weight: 600;
                margin-bottom: 4px;
                cursor: pointer;
                position: relative;
                padding-right: 18px;
                line-height: 1.2;
            }
            .title_nav::after {
                content: '+';
                position: absolute;
                right: 0;
                font-size: 11px;
                font-weight: normal;
                transition: transform 0.3s ease;
                color: #666;
            }
            .menu_footer.active .title_nav::after {
                content: '−';
            }
            .menu_footer ul {
                font-size: 8px;
                line-height: 1.3;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, margin-top 0.3s ease;
                margin-top: 0;
                padding-left: 12px;
                margin-bottom: 0;
            }
            .menu_footer.active ul {
                max-height: 300px;
                margin-top: 3px;
            }
            .menu_footer ul li {
                margin-bottom: 2px;
            }
            .menu_footer ul li a {
                font-size: 8px;
                color: #666;
                text-decoration: none;
            }
            .menu_footer ul li a:hover {
                color: #007bff;
            }
            /* Col adjustments */
            footer .col-md-5,
            footer .col-md-7 {
                padding: 0 4px;
            }
            footer .col-12 {
                padding: 0 4px;
            }
            /* First menu expanded by default */
            .menu_footer:first-child {
                margin-top: 0;
            }
            .menu_footer:first-child.active ul {
                max-height: 300px;
                margin-top: 3px;
            }
        }
        @media (max-width: 480px) {
            footer.pt-5.pb-5 {
                padding-top: 8px !important;
                padding-bottom: 8px !important;
            }
            footer .container-lg {
                padding-left: 6px !important;
                padding-right: 6px !important;
            }
            .box-footer {
                padding: 6px 0 !important;
            }
            .logo_footer img {
                max-width: 100px;
            }
            .list_social a img {
                width: 18px !important;
                height: 18px !important;
            }
            .bocongthuong img {
                max-width: 80px !important;
            }
            .info_footer {
                font-size: 8px;
            }
            .info_footer p {
                font-size: 8px;
                margin-bottom: 3px;
            }
            .title_nav {
                font-size: 8px;
            }
            .menu_footer ul {
                font-size: 7px;
            }
            .menu_footer ul li a {
                font-size: 7px;
            }
        }
    </style>
    {!!getConfig('code_header')!!}
</head>
<body class="home">
   <header class="header">
        <div class="header-top">
            <div class="container-lg">
                <div class="row">
                    <div class="col-12 col-md-12 text-center">
                        {{$header->title ?? ''}}
                    </div>
                </div>
            </div>
        </div>
      <div class="header-center">
         <div class="container-lg">
            <div class="d-flex header-center-content">
              <button class="btn-menu-mb">
                <i class="fa-bars-menu" aria-hidden="true"></i>
                <i class="fa-bars-menu" aria-hidden="true"></i>
                <i class="fa-bars-menu" aria-hidden="true"></i>
             </button>
                <a href="/" class="logo">
                    <div class="skeleton--img-logo js-skeleton">
                        <img src="{{getImage($header->logo ?? '')}}" width="" height="" alt="{{$header->alt ?? ''}}" class="js-skeleton-img">
                    </div>
                </a>
                <div class="head-right">
                    <div class="search-head d-none d-md-block">
                        <form action="/tim-kiem" class="search" method="get">
                            <div class="search-wrapper">
                                <input type="search" name="s" id="search-input" value="{{request()->s}}" placeholder="Nhập tên sản phẩm, thương hiệu muốn tìm" autocomplete="off">
                                <button type="submit"><span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
                            </div>
                            <div class="search-suggestions" id="search-suggestions" style="display: none;">
                                <div class="search-suggestions-content">
                                    <!-- 促销/Deal建议 -->
                                    <div class="suggestions-section deals-section" id="deals-section" style="display: none;">
                                        <div class="section-title">Khuyến mại</div>
                                        <div class="deals-list" id="deals-list"></div>
                                    </div>
                                    
                                    <!-- 搜索建议产品 -->
                                    <div class="suggestions-section products-section" id="products-section" style="display: none;">
                                        <div class="section-title">Sản phẩm gợi ý</div>
                                        <div class="products-list" id="products-list"></div>
                                    </div>
                                    
                                    <!-- 最近搜索 -->
                                    <div class="suggestions-section recent-section" id="recent-section" style="display: none;">
                                        <div class="section-title">Tìm kiếm gần đây</div>
                                        <div class="recent-searches-list" id="recent-searches-list"></div>
                                        <a href="javascript:;" class="view-more" id="view-more-recent" style="display: none;">Xem thêm <i class="fa fa-chevron-down"></i></a>
                                    </div>
                                    
                                    <!-- 产品类别快速链接 -->
                                    <div class="suggestions-section categories-section" id="categories-section" style="display: none;">
                                        <div class="section-title">Danh mục nhanh</div>
                                        <div class="categories-grid" id="categories-grid"></div>
                                    </div>
                                    
                                    <!-- 品牌logo -->
                                    <div class="suggestions-section brands-section" id="brands-section" style="display: none;">
                                        <div class="section-title">Thương hiệu</div>
                                        <div class="brands-grid" id="brands-grid"></div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="system-store d-none d-md-block">
                        <a href="#">
                            <span role="img" class="icon"><svg width="30" height="27" viewBox="0 0 30 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M30 9.70911C30 8.13337 28.1141 2.75357 27.0365 1.24149C26.4818 0.461581 25.5784 0 24.6117 0H5.38827C4.42155 0 3.51823 0.461581 2.96355 1.24149C1.8859 2.75357 0 8.13337 0 9.70911C0 10.8551 0.459588 11.9215 1.26783 12.7173C1.34707 12.7969 1.42631 12.8606 1.50555 12.9402V25.0527C1.50555 27.2173 3.24881 28.9682 5.40412 28.9682L14.9921 28.9841V27.8699H10.8558V20.3732C10.8558 19.4023 11.6482 18.6065 12.6149 18.6065H17.3534C18.3201 18.6065 19.1125 19.4023 19.1125 20.3732V29L24.5642 28.9841C26.7195 28.9841 28.4628 27.2333 28.4628 25.0686V12.9402C28.542 12.8765 28.6212 12.7969 28.7005 12.7173C29.5404 11.9215 30 10.8551 30 9.70911ZM22.7734 1.11416H24.6117C25.2139 1.11416 25.7845 1.40066 26.1331 1.89407C27.1632 3.32656 28.8906 8.4517 28.8906 9.70911C28.8906 10.5527 28.5578 11.3326 27.9556 11.9215C27.3534 12.5104 26.561 12.8128 25.7211 12.781C24.1046 12.7173 22.7734 11.3008 22.7734 9.62953V1.11416ZM21.664 9.70911C21.664 11.3963 20.2853 12.781 18.6054 12.781C16.9255 12.781 15.5468 11.3963 15.5468 9.70911V1.11416H21.664V9.62953C21.664 9.66136 21.664 9.67728 21.664 9.70911ZM8.33597 1.11416H14.4532V9.70911C14.4532 11.3963 13.0745 12.781 11.3946 12.781C9.71474 12.781 8.33597 11.3963 8.33597 9.70911C8.33597 9.67728 8.33597 9.64545 8.33597 9.62953V1.11416ZM1.10935 9.70911C1.10935 8.43579 2.83677 3.32656 3.86688 1.89407C4.21553 1.40066 4.78605 1.11416 5.38827 1.11416H7.22662V9.62953C7.22662 11.3167 5.91125 12.7333 4.27892 12.781C3.43899 12.8128 2.64659 12.5104 2.04437 11.9215C1.44216 11.3326 1.10935 10.5527 1.10935 9.70911ZM27.3693 25.0527C27.3693 26.5966 26.1173 27.854 24.58 27.854L20.2377 27.8699V20.3573C20.2377 18.7656 18.954 17.4764 17.3693 17.4764H12.6307C11.046 17.4764 9.76228 18.7656 9.76228 20.3573V27.854H5.41997C3.88273 27.854 2.63074 26.5966 2.63074 25.0527V13.5928C3.15372 13.7997 3.72425 13.9111 4.32647 13.8793C5.78447 13.8315 7.06815 12.972 7.76545 11.7464C8.47861 13.0198 9.84152 13.8793 11.3946 13.8793C12.9319 13.8793 14.2789 13.0357 15.0079 11.7783C15.7211 13.0357 17.084 13.8793 18.6212 13.8793C20.1743 13.8793 21.5372 13.0198 22.2504 11.7464C22.9477 12.972 24.2314 13.8156 25.6894 13.8793C25.7369 13.8793 25.7845 13.8793 25.8479 13.8793C26.3867 13.8793 26.8938 13.7838 27.3851 13.5928V25.0527H27.3693Z" fill="black"></path></svg></span>
                            <span>Hệ thống cửa hàng</span>
                        </a>
                        <a href="/blogs">
                            <span role="img" class="icon"><svg width="36" height="29" viewBox="0 0 36 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M35.2505 0.744259C34.2522 -0.24711 32.6305 -0.24711 31.6322 0.744259L27.6293 4.71961C26.6012 3.80729 25.2614 3.29349 23.8751 3.29349H5.64127C2.53045 3.29349 0 5.8065 0 8.89588V19.0269C0 22.0966 2.5006 24.5964 5.58489 24.626V27.3202C5.58489 27.9789 5.95633 28.5619 6.55661 28.8385C6.78876 28.9472 7.03418 28.9999 7.27628 28.9999C7.66098 28.9999 8.03906 28.8682 8.35081 28.608L13.1762 24.626H23.8751C26.9859 24.626 29.5163 22.113 29.5163 19.0269V10.0289L33.3236 6.24784C33.3303 6.24454 33.3336 6.24125 33.3402 6.23795C33.3468 6.23466 33.3468 6.22807 33.3502 6.22149L35.2472 4.33756C36.2487 3.34619 36.2487 1.73233 35.2505 0.744259ZM20.3232 17.7622C19.8953 18.1871 19.3979 18.5263 18.844 18.77L15.7166 20.1402L17.0963 17.0376C17.3417 16.4876 17.6833 15.9935 18.1111 15.5687L30.0735 3.68872L32.2856 5.88554L20.3232 17.7622ZM28.5214 19.0269C28.5214 21.5696 26.4354 23.638 23.8751 23.638H12.8181L7.71405 27.8472C7.5018 28.0217 7.22653 28.0579 6.9778 27.9427C6.72906 27.8274 6.57982 27.5935 6.57982 27.3169V23.638H5.64127C3.08098 23.638 0.994933 21.5696 0.994933 19.0269V8.89588C0.994933 6.34994 3.08098 4.28157 5.64127 4.28157H23.8751C24.996 4.28157 26.0838 4.68997 26.9262 5.41785L17.408 14.8704C16.894 15.3809 16.4827 15.9771 16.1876 16.6391L14.6454 20.1006C14.5028 20.4201 14.5725 20.7824 14.8212 21.0294C14.9804 21.1875 15.1993 21.2732 15.4181 21.2732C15.5342 21.2732 15.647 21.2501 15.7564 21.204L19.242 19.6758C19.9086 19.3826 20.5089 18.9742 21.0229 18.4637L28.5214 11.0169V19.0269ZM34.5474 3.63602L32.9887 5.18401L30.7766 2.99048L32.3353 1.4425C32.6404 1.13949 33.0417 0.987984 33.443 0.987984C33.8443 0.987984 34.2423 1.13949 34.5474 1.4425C35.1576 2.04852 35.1576 3.0333 34.5474 3.63602Z" fill="black"></path><path d="M20.2274 8.30981H4.0166V9.29789H20.2274V8.30981Z" fill="black"></path><path d="M15.3588 13.1282H4.0166V14.1162H15.3588V13.1282Z" fill="black"></path><path d="M11.8965 17.9436H4.0166V18.9317H11.8965V17.9436Z" fill="black"></path></svg></span>
                            <span>Tạp chí làm đẹp</span>
                        </a>
                    </div>
                    <button class="btn-search-mobile" type="button">
                        <span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
                    </button>
                    <div class="divider divider-vertical d-none d-md-inline-block" role="separator"></div>
                    <div class="header-content ">
                        <div class="menu-section">
                            @php $member = auth()->guard('member')->user(); @endphp
                            @if(isset($member) && !empty($member))
                            <button class="btn user-btn btn-login d-none d-md-inline-block show-menu-account" type="button">
                                <span role="img" class="icon"><svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5 0C6.50896 0 0 6.50896 0 14.5C0 22.491 6.50896 29 14.5 29C22.491 29 29 22.491 29 14.5C29 6.50896 22.5063 0 14.5 0ZM14.5 1.06955C21.9104 1.06955 27.9305 7.08957 27.9305 14.5C27.9305 17.7392 26.7845 20.7034 24.8746 23.0258C24.3093 21.1006 23.148 19.3588 21.4979 18.0448C20.2908 17.0669 18.8699 16.3641 17.3419 15.9821C19.2366 14.9737 20.52 12.9721 20.52 10.6802C20.52 7.36459 17.8156 4.66017 14.5 4.66017C11.1844 4.66017 8.47998 7.33404 8.47998 10.6649C8.47998 12.9568 9.76344 14.9584 11.6581 15.9668C10.1301 16.3488 8.70917 17.0516 7.50211 18.0295C5.86723 19.3435 4.69073 21.0854 4.12539 23.0105C2.21549 20.6881 1.06955 17.7239 1.06955 14.4847C1.08483 7.08957 7.10485 1.06955 14.5 1.06955ZM14.5 15.6154C11.765 15.6154 9.54952 13.3999 9.54952 10.6649C9.54952 7.92993 11.765 5.71444 14.5 5.71444C17.235 5.71444 19.4505 7.92993 19.4505 10.6649C19.4505 13.3999 17.235 15.6154 14.5 15.6154ZM14.5 27.9152C10.7871 27.9152 7.42571 26.4025 4.99631 23.9578C5.40885 21.9868 6.52423 20.1839 8.17439 18.8546C9.9315 17.4489 12.1776 16.6697 14.5 16.6697C16.8224 16.6697 19.0685 17.4489 20.8256 18.8546C22.4758 20.1839 23.5911 21.9868 24.0037 23.9578C21.5743 26.4025 18.2129 27.9152 14.5 27.9152Z" fill="black"></path></svg></span>
                                <span class="title-btn">
                                    {{$member['last_name']}}
                                </span>
                            </button>
                            <div class="menu_account">
                                <div class="name-user fs-16">
                                    <strong>Xin chào 🌞 {{$member['first_name']}} {{$member['last_name']}}!</strong>
                                </div>
                                <ul class="list-action menu-member">
                                    <li><a href="{{route('account.profile')}}">
                                        <div class="icon">
                                            <svg width="29" height="19" viewBox="0 0 29 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M25.7449 0H3.2551C1.45 0 0 1.48722 0 3.33866V15.6613C0 17.5128 1.45 19 3.2551 19H25.7449C27.55 19 29 17.5128 29 15.6613V3.33866C29 1.48722 27.55 0 25.7449 0ZM28.1122 15.631C28.1122 16.9665 27.0469 18.0591 25.7449 18.0591H3.2551C1.95306 18.0591 0.887755 16.9665 0.887755 15.631V3.33866C0.887755 2.0032 1.95306 0.910543 3.2551 0.910543H25.7449C27.0469 0.910543 28.1122 2.0032 28.1122 3.33866V15.631Z" fill="black"></path><path d="M10.4459 9.25719C11.1561 8.65016 11.5704 7.73962 11.6 6.79872C11.6296 5.00799 10.1796 3.42971 8.43367 3.39936C7.54592 3.36901 6.71735 3.70288 6.06633 4.34026C5.41531 4.97764 5.0898 5.82748 5.0898 6.73802C5.0898 7.70927 5.50408 8.65016 6.24388 9.28754C4.58673 10.1374 3.72857 11.8067 3.72857 14.2348H4.61633C4.61633 12.0192 5.35612 10.623 6.8949 9.98562C7.13163 9.89457 7.27959 9.65176 7.30918 9.40895C7.33878 9.16613 7.22041 8.92332 7.01327 8.77157C6.36225 8.31629 5.94796 7.55751 5.94796 6.76837C5.94796 6.10064 6.21429 5.49361 6.65816 5.03834C7.13163 4.58307 7.72347 4.34026 8.37449 4.34026C9.64694 4.37061 10.7122 5.52396 10.6827 6.82907C10.6531 7.61821 10.2684 8.34665 9.64694 8.80192C9.4398 8.95367 9.32143 9.19649 9.35102 9.4393C9.38061 9.68211 9.52857 9.92492 9.76531 10.016C11.3041 10.6534 12.0439 12.0495 12.0439 14.2652H12.9316C12.9316 11.7764 12.1031 10.1374 10.4459 9.25719Z" fill="black"></path><path d="M25.3306 4.58307H15.2102V5.49361H25.3306V4.58307Z" fill="black"></path><path d="M25.3306 9.04473H15.2102V9.95527H25.3306V9.04473Z" fill="black"></path><path d="M25.3306 13.476H15.2102V14.3866H25.3306V13.476Z" fill="black"></path></svg>
                                        </div>
                                        <div class="text-account">
                                            <div class="fw-bold">Thông tin tài khoản</div>
                                            <div class="size-10 desc-text">Tài khoản, Đơn hàng, Địa chỉ giao nhận, Đổi mật khẩu</div>
                                        </div>
                                    </a></li>
                                    <li><a href="{{route('account.orders')}}">
                                        <div class="icon">
                                            <svg width="29" height="21" viewBox="0 0 29 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M29 8.4062C29 3.78434 25.4769 0 21.1094 0C18.8966 0 16.8876 0.961594 15.4608 2.54357C15.0532 2.35746 14.5873 2.2644 14.1506 2.2644H3.40663C1.51406 2.2644 0 3.90842 0 5.89365V17.4018C0 19.387 1.51406 21 3.37751 21H14.1797C16.0432 21 17.5572 19.387 17.5572 17.4018V15.9129C18.6345 16.5022 19.8283 16.8124 21.1094 16.8124C22.4197 16.8124 23.6717 16.4712 24.749 15.8508L27.4859 20.7518L28.243 20.2866L25.506 15.3855C27.6315 13.8656 29 11.322 29 8.4062ZM10.8022 3.19498V9.52289C10.8022 9.89513 10.511 10.2053 10.1616 10.2053H7.39558C7.04619 10.2053 6.75502 9.89513 6.75502 9.52289V3.19498H10.8022ZM16.7129 17.4018C16.7129 18.8907 15.5773 20.0694 14.2088 20.0694H3.37751C1.97992 20.0694 0.873494 18.8597 0.873494 17.4018V5.89365C0.873494 4.40473 2.00904 3.226 3.37751 3.226H5.88153V9.55392C5.88153 10.4535 6.5512 11.1669 7.39558 11.1669H10.1616C11.006 11.1669 11.6757 10.4535 11.6757 9.55392V3.226H14.1797C14.4127 3.226 14.6456 3.25702 14.8785 3.35008C13.8594 4.77696 13.248 6.54505 13.248 8.43722C13.248 11.322 14.6165 13.8656 16.7129 15.3855V17.4018ZM14.1215 8.4062C14.1215 4.28065 17.2661 0.930576 21.1386 0.930576C25.011 0.930576 28.1556 4.28065 28.1556 8.4062C28.1556 12.5318 25.011 15.8818 21.1386 15.8818C17.2661 15.8818 14.1215 12.5318 14.1215 8.4062Z" fill="black"></path><path d="M24.6034 5.95569H17.6446V6.88626H24.6034V5.95569Z" fill="black"></path><path d="M24.6034 9.92615H17.6446V10.8567H24.6034V9.92615Z" fill="black"></path></svg>
                                        </div>
                                        <div class="text-account">
                                            <div class="fw-bold">Lịch sử đặt hàng</div>
                                            <div class="size-10 desc-text">Tra cứu đơn hàng đã đặt trước đó</div>
                                        </div>
                                    </a></li>
                                    <li><a href="{{route('account.logout')}}" class="logout">Đăng xuất</a></li>
                                </ul>
                            </div>
                            <button class="btn user-btn btn-wishlist" type="button">
                                <span role="img" class="icon"><svg width="24" height="24" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></span>
                                <span class="count-wishlist"> {{$wishlist}}</span>
                            </button>
                            @else
                            <button class="btn user-btn btn-login d-none d-md-inline-block" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">
                                <span role="img" class="icon"><svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5 0C6.50896 0 0 6.50896 0 14.5C0 22.491 6.50896 29 14.5 29C22.491 29 29 22.491 29 14.5C29 6.50896 22.5063 0 14.5 0ZM14.5 1.06955C21.9104 1.06955 27.9305 7.08957 27.9305 14.5C27.9305 17.7392 26.7845 20.7034 24.8746 23.0258C24.3093 21.1006 23.148 19.3588 21.4979 18.0448C20.2908 17.0669 18.8699 16.3641 17.3419 15.9821C19.2366 14.9737 20.52 12.9721 20.52 10.6802C20.52 7.36459 17.8156 4.66017 14.5 4.66017C11.1844 4.66017 8.47998 7.33404 8.47998 10.6649C8.47998 12.9568 9.76344 14.9584 11.6581 15.9668C10.1301 16.3488 8.70917 17.0516 7.50211 18.0295C5.86723 19.3435 4.69073 21.0854 4.12539 23.0105C2.21549 20.6881 1.06955 17.7239 1.06955 14.4847C1.08483 7.08957 7.10485 1.06955 14.5 1.06955ZM14.5 15.6154C11.765 15.6154 9.54952 13.3999 9.54952 10.6649C9.54952 7.92993 11.765 5.71444 14.5 5.71444C17.235 5.71444 19.4505 7.92993 19.4505 10.6649C19.4505 13.3999 17.235 15.6154 14.5 15.6154ZM14.5 27.9152C10.7871 27.9152 7.42571 26.4025 4.99631 23.9578C5.40885 21.9868 6.52423 20.1839 8.17439 18.8546C9.9315 17.4489 12.1776 16.6697 14.5 16.6697C16.8224 16.6697 19.0685 17.4489 20.8256 18.8546C22.4758 20.1839 23.5911 21.9868 24.0037 23.9578C21.5743 26.4025 18.2129 27.9152 14.5 27.9152Z" fill="black"></path></svg></span>
                                <span class="title-btn">
                                    Đăng nhập
                                </span>
                            </button>
                            <button class="btn user-btn" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">
                                <span role="img" class="icon"><svg width="24" height="24" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></span>
                            </button>
                            @endif
                            <button class="btn user-btn position-relative btn-cart">
                                <span role="img" class="icon"><svg width="27" height="27" viewBox="0 0 33 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.5923 27.9999H4.30586C3.28215 27.9999 2.38467 27.255 2.17432 26.2236L0.0427557 15.4655C-0.0834545 14.8065 0.0708127 14.1476 0.491513 13.6176C0.89819 13.1019 1.51522 12.801 2.17432 12.801H24.7239C25.383 12.801 25.986 13.1019 26.4067 13.6176C26.8133 14.1333 26.9816 14.8065 26.8554 15.4655L24.7239 26.2236C24.5135 27.255 23.616 27.9999 22.5923 27.9999ZM2.17432 13.8038C1.82373 13.8038 1.48717 13.9614 1.24878 14.2479C1.01038 14.5344 0.940254 14.8925 1.01037 15.2506L3.14193 26.0087C3.25412 26.5674 3.74492 26.9828 4.30586 26.9828H22.5923C23.1532 26.9828 23.6441 26.5674 23.7563 26.0087L25.8878 15.2506C25.9579 14.8925 25.8738 14.52 25.6494 14.2479C25.425 13.9757 25.0885 13.8038 24.7239 13.8038H2.17432Z" fill="black"></path><path d="M25.7905 17.5427H1.10938V18.5455H25.7905V17.5427Z" fill="black"></path><path d="M24.8924 22.27H1.99219V23.2728H24.8924V22.27Z" fill="black"></path><path d="M22.7062 11.4545H21.7245C21.7245 6.79889 18.0083 3.00275 13.4507 3.00275C8.89314 3.00275 5.17695 6.79889 5.17695 11.4545H4.19531C4.19531 6.24021 8.34623 2 13.4507 2C18.5553 2 22.7062 6.24021 22.7062 11.4545Z" fill="black"></path></svg></span>
                                <span class="count-cart"> {{$totalQty}}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="menu-top mb-0 mb-md-0">
                @include('Website::layout.menu',['menu' => $header->menu ?? []])
            </div>
            <div class="search-head search_mobile">
                <form action="/tim-kiem" class="search" method="get">
                    <div class="search-wrapper">
                        <input type="search" name="s" id="search-input-mobile" value="{{request()->s}}" placeholder="Nhập tên sản phẩm, thương hiệu muốn tìm" autocomplete="off">
                        <button type="submit"><span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
                    </div>
                    <div class="search-suggestions" id="search-suggestions-mobile" style="display: none;">
                        <button type="button" class="close-search-mobile" onclick="closeMobileSuggestions()">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div class="search-suggestions-content">
                            <!-- 促销/Deal建议 -->
                            <div class="suggestions-section deals-section" id="deals-section-mobile" style="display: none;">
                                <div class="section-title">Khuyến mại</div>
                                <div class="deals-list" id="deals-list-mobile"></div>
                            </div>
                            
                            <!-- 搜索建议产品 -->
                            <div class="suggestions-section products-section" id="products-section-mobile" style="display: none;">
                                <div class="section-title">Sản phẩm gợi ý</div>
                                <div class="products-list" id="products-list-mobile"></div>
                            </div>
                            
                            <!-- 最近搜索 -->
                            <div class="suggestions-section recent-section" id="recent-section-mobile" style="display: none;">
                                <div class="section-title">Tìm kiếm gần đây</div>
                                <div class="recent-searches-list" id="recent-searches-list-mobile"></div>
                                <a href="javascript:;" class="view-more" id="view-more-recent-mobile" style="display: none;">Xem thêm <i class="fa fa-chevron-down"></i></a>
                            </div>
                            
                            <!-- 产品类别快速链接 -->
                            <div class="suggestions-section categories-section" id="categories-section-mobile" style="display: none;">
                                <div class="section-title">Danh mục nhanh</div>
                                <div class="categories-grid" id="categories-grid-mobile"></div>
                            </div>
                            
                            <!-- 品牌logo -->
                            <div class="suggestions-section brands-section" id="brands-section-mobile" style="display: none;">
                                <div class="section-title">Thương hiệu</div>
                                <div class="brands-grid" id="brands-grid-mobile"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</header>
<main id="main" class="">
    @yield('content')
</main>
<section class="subcribe">
    <div class="container-lg">
        <div class="box_subcribe">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div><strong>NHẬN BẢN TIN LÀM ĐẸP</strong></div>
                    <div>Đừng bỏ lỡ hàng ngàn sản phẩm và chương trình siêu hấp dẫn</div>
                </div>
                <div class="col-12 col-md-6 text-end">
                    <form class="form-subcribe" method="post">
                        @csrf
                        <input type="email" placeholder="Nhập email của bạn" name="email" required="">
                        <span>
                            <button class="btn_send" type="submit">Theo dõi</button>
                        </span>
                    </form>
                    <div class="box-alert"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<footer class="pt-5 pb-5">
    <div class="container-lg">
        <div class="row">
            <div class="col-md-5">
                <div class="row">
                    @php $block4 = $footer && $footer->block_4 ? json_decode($footer->block_4) : null;@endphp
                    <div class="col-12 col-md-6">
                        <div class="box-footer">
                            @if($block4)
                            <a href="/" class="logo_footer">
                                <div class="skeleton--img-logo js-skeleton">
                                    <img src="{{getImage($block4->logo ?? '')}}" alt="{{$block4->alt ?? ''}}" class="js-skeleton-img">
                                </div>
                            </a>
                            <div class="copyright-text mt-3 mb-3">
                                <p class="mb-0">© 2009-{{date('Y')}} - All rights reserved</p>
                            </div>
                            <ul class="list_social">
                                <a href="{{$block4->facebook ?? '#'}}" target="_blank" rel="nofollow"><img src="/public/image/icon-facebook.webp" alt="Facebook" width="24" height="24"></a>
                                <a href="{{$block4->instagram ?? '#'}}" target="_blank" rel="nofollow"><img src="/public/image/icon-instagram.webp" alt="Facebook" width="24" height="24"></a>
                                <a href="{{$block4->tiktok ?? '#'}}" target="_blank" rel="nofollow"><img src="/public/image/icon-tiktok.webp" alt="Facebook" width="24" height="24"></a>
                            </ul>
                            <a href="{{$block4->link ?? '#'}}" class="bocongthuong">
                                <img src="/public/image/verified.png" alt="Bộ công thương" width="122" height="46">
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="info_footer">
                            {!!$footer->block_2 ?? ''!!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="row">
                    @include('Website::layout.footer',['menu' => $footer ? $footer->block_0 : []])
                </div>
            </div> 
        </div>
    </div>
</footer>
<?php 
// #region agent log
try {
    $logPath = base_path('.cursor/debug.log');
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $hasFooterBlocks = isset($footerBlocks);
    $footerBlocksCount = $hasFooterBlocks ? $footerBlocks->count() : 0;
    $footerBlocksType = $hasFooterBlocks ? get_class($footerBlocks) : 'not_set';
    @file_put_contents($logPath, json_encode(['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'I', 'location' => 'layout.blade.php:371', 'message' => 'Footer blocks check', 'data' => ['isset' => $hasFooterBlocks, 'count' => $footerBlocksCount, 'type' => $footerBlocksType, 'condition_met' => $hasFooterBlocks && $footerBlocksCount > 0], 'timestamp' => time() * 1000]) . "\n", FILE_APPEND);
} catch (\Exception $e) {}
// #endregion
?>
@if(isset($footerBlocks) && $footerBlocks->count() > 0)
<div class="footer-blocks">
    <div class="container-lg pt-4 pb-4">
        <div class="row">
            @foreach($footerBlocks as $block)
            <div class="col-md-3 col-sm-6 footer-block-item mb-4">
                @if($block->title)
                <div class="footer-block-title mb-2"><strong>{{$block->title}}</strong></div>
                @endif
                
                @if(count($block->links) > 0)
                <div class="footer-links">
                    @foreach($block->links as $link)
                    <a href="{{getSlug($link['url'])}}" class="footer-link">{{$link['text']}}</a>
                    @if(!$loop->last) <span class="footer-link-separator">|</span> @endif
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile collapse/expand functionality
    if (window.innerWidth <= 768) {
        const footerBlockItems = document.querySelectorAll('.footer-block-item');
        footerBlockItems.forEach(function(item) {
            const title = item.querySelector('.footer-block-title');
            if (title) {
                title.addEventListener('click', function() {
                    item.classList.toggle('active');
                });
                // First 2 items expanded by default on mobile
                if (item === footerBlockItems[0] || item === footerBlockItems[1]) {
                    item.classList.add('active');
                }
            }
        });
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const footerBlockItems = document.querySelectorAll('.footer-block-item');
            if (window.innerWidth > 768) {
                // Desktop: expand all
                footerBlockItems.forEach(function(item) {
                    item.classList.add('active');
                });
            } else {
                // Mobile: only first 2 expanded
                footerBlockItems.forEach(function(item, index) {
                    if (index < 2) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            }
        }, 250);
    });
});
</script>
@endif
@if(!isset($member) && empty($member))
{{--
<script src="https://accounts.google.com/gsi/client" async defer></script>
<div id="g_id_onload" data-client_id="{{env('GOOGLE_CLIENT_ID')}}" data-ux_mode="popup" data-callback="handleCredentialResponse" data-close_on_tap_outside="false"></div>
<!-- <div class="g_id_signin" data-type="standard" data-theme="filled_blue" data-size="large" data-text="continue_with" data-shape="rectangular" data-logo_alignment="center"></div> -->
<script>
    function handleCredentialResponse(response) {
        $.ajax({
            type: "post",
            url: "{{route('loginGoogle')}}",
            data: {
                access_token: response.credential,
                _token: "{{csrf_token()}}",
            },
            success: function(res) {
                if (res.status == 200) {
                    window.location.href = res.url;
                }
            },
        });
    }
</script>
--}}
@endif
    <nav id="menu-mobile" class="hidden-pc">
        <div class="mn-mb-header"> 
            <a href="/">
                <div class="skeleton--img-logo js-skeleton">
                    <img src="{{getImage($header->logo ?? '')}}" width="" height="" alt="{{$header->alt ?? ''}}" class="js-skeleton-img">
                </div>
            </a>
        </div>
        <button id="close-handle" class="close-handle" aria-label="Đóng" title="Đóng">
            <span class="mb-menu-cls" aria-hidden="true"><span class="bar animate"></span></span>Đóng
        </button>
        @include('Website::layout.mobile',['menu' => $header->menu ?? []])
        @if(isset($member) && !empty($member))
            <div class="name-user fs-16 pt-3 ps-2">
                <strong>Xin chào {{$member['first_name']}} {{$member['last_name']}}!</strong>
            </div>
            <ul class="list-action menu-member mt-2 mb-2">
                <li class="mt-3"><a href="{{route('account.profile')}}">Tài khoản</a></li>
                <li class="mt-3"><a href="{{route('account.orders')}}">Đơn hàng</a></li>
                <li class="mt-3"><a href="{{route('account.address')}}">Địa chỉ giao nhận</a></li>
                <li class="mt-3"><a href="{{route('account.promotion')}}">Ưu đãi của tôi</a></li>
                <li class="mt-3"><a href="{{route('account.password')}}">Đổi mật khẩu</a></li>
                <li class="mt-3"><a href="{{route('account.logout')}}">Đăng xuất</a></li>
            </ul>
        @else
            <button class="btn user-btn btn-login mt-3 ps-2" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">
                <span role="img" class="icon"><svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5 0C6.50896 0 0 6.50896 0 14.5C0 22.491 6.50896 29 14.5 29C22.491 29 29 22.491 29 14.5C29 6.50896 22.5063 0 14.5 0ZM14.5 1.06955C21.9104 1.06955 27.9305 7.08957 27.9305 14.5C27.9305 17.7392 26.7845 20.7034 24.8746 23.0258C24.3093 21.1006 23.148 19.3588 21.4979 18.0448C20.2908 17.0669 18.8699 16.3641 17.3419 15.9821C19.2366 14.9737 20.52 12.9721 20.52 10.6802C20.52 7.36459 17.8156 4.66017 14.5 4.66017C11.1844 4.66017 8.47998 7.33404 8.47998 10.6649C8.47998 12.9568 9.76344 14.9584 11.6581 15.9668C10.1301 16.3488 8.70917 17.0516 7.50211 18.0295C5.86723 19.3435 4.69073 21.0854 4.12539 23.0105C2.21549 20.6881 1.06955 17.7239 1.06955 14.4847C1.08483 7.08957 7.10485 1.06955 14.5 1.06955ZM14.5 15.6154C11.765 15.6154 9.54952 13.3999 9.54952 10.6649C9.54952 7.92993 11.765 5.71444 14.5 5.71444C17.235 5.71444 19.4505 7.92993 19.4505 10.6649C19.4505 13.3999 17.235 15.6154 14.5 15.6154ZM14.5 27.9152C10.7871 27.9152 7.42571 26.4025 4.99631 23.9578C5.40885 21.9868 6.52423 20.1839 8.17439 18.8546C9.9315 17.4489 12.1776 16.6697 14.5 16.6697C16.8224 16.6697 19.0685 17.4489 20.8256 18.8546C22.4758 20.1839 23.5911 21.9868 24.0037 23.9578C21.5743 26.4025 18.2129 27.9152 14.5 27.9152Z" fill="black"></path></svg></span>
                <span class="title-btn fs-14">
                    Đăng nhập
                </span>
            </button>
        @endif
    </nav>
    <div id="site-overlay" class="site-overlay active"></div>
    <div class="modal-backdrop fade"></div>
    <div class="modal fade" id="addCartModal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="false">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title " id="">
              <i class="fa fa-check-square-o" aria-hidden="true"></i> Thêm vào giỏ thành công
            </h4>
            <button type="button" class="close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="hidden col-sm-4 col-xs-5 imgCartItem">
              </div>
              <div class="col-sm-8 col-xs-7 imgCartDetail">
                <h3 class="itemCartTile"></h3>
                <div class="itemCartPrice"></div>
                <span class="itemCartQty"></span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="close continueShopping" data-bs-dismiss="modal">Tiếp tục mua sắm</button>
            <button type="button" class="close viewCartPage" onclick="window.location = '/cart/gio-hang'">Xem giỏ hàng</button>
          </div>
        </div>
      </div>
    </div>
    <div class="side-right">
        <div class="backdrop fade"></div>
        <div class="content-right"></div>
    </div>
    <script src="/public/js/jquery.validate.min.js"></script>
    @yield('footer')
<script>
    $(function () {
        var $allVideos = $("iframe[src*='//player.vimeo.com'], iframe[src*='//www.youtube.com'], object, embed"),
        $fluidEl = $(".box_video");

        $allVideos.each(function () {

            $(this)
              // jQuery .data does not work on object/embed elements
              .attr('data-aspectRatio', this.height / this.width)
              .removeAttr('height')
              .removeAttr('width');

        });

        $(window).resize(function () {

            var newWidth = $fluidEl.width();
            $allVideos.each(function () {

                var $el = $(this);
                $el
                    .width(newWidth)
                    .height(newWidth * $el.attr('data-aspectRatio'));

            });

        }).resize();

    });
</script>
<div class="modal" tabindex="-1" id="myLogin">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="fw-bold fs-26 text-uppercase text-center mb-3 mt-5">Đăng nhập</div>
        <form class="formLogin" method="post">
            @csrf
            <input type="hidden" name="returnUrl" value="{{\URL::current()}}">
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email đăng nhập" autocomplete="false">
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Mật khẩu đăng nhập" autocomplete="false">
            </div>
            <div class="mb-3">
                <label class="lab_remember"><input type="checkbox" name="remember" autocomplete="false"> Ghi nhớ đăng nhập</label>
                <a href="javascript:;" class="btn_forgot">Quên mật khẩu?</a>
            </div>
            <div class="text-center">
                <button class="btn btn-default w-100" type="submit">Đăng nhập</button>
            </div>
            <p class="text-center mt-4 mb-3">--- Hoặc đăng nhập với ---</p>
            <div class="align-center space-between">
                <a href="{{route('login.social',['provider' => 'facebook'])}}" class="btn-social"><i class="fa fa-facebook" aria-hidden="true"></i> Facebook</a>
                <a href="{{route('login.social',['provider' => 'google'])}}" class="btn-social"><i class="fa fa-google" aria-hidden="true"></i> Google</a>
            </div>
            <div class="box-alert text-center"></div>
            <div class="mt-3 text-center">
                <span>Bạn chưa có tài khoản? <a href="javascript:;" class="btn-register">Đăng ký ngay</a></span>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal" tabindex="-1" id="myForgot">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="fw-bold fs-26 text-uppercase text-center mb-3 mt-5">Quên mật khẩu</div>
        <form class="formForgot" method="post">
            @csrf
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email đăng nhập *" autocomplete="false">
            </div>
            <div class="text-center">
                <button class="btn btn-default w-100" type="submit">Gửi</button>
            </div>
            <div class="box-alert text-center"></div>
            <div class="mt-3 text-center">
                <span>Quay lại <a href="javascript:;" class="btn-login-forgot">Đăng nhập</a></span>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal" tabindex="-1" id="myRegister">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="fw-bold fs-26 text-uppercase text-center mb-3 mt-5">Đăng ký</div>
        <form class="formRegiter" method="post">
            @csrf
            <input type="hidden" name="returnUrl" value="{{\URL::current()}}">
            <div class="mb-3">
                <input type="text" class="form-control" name="first_name" placeholder="Họ *" autocomplete="false">
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="last_name" placeholder="Tên *" autocomplete="false">
            </div>
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email đăng nhập *" autocomplete="false">
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Mật khẩu đăng nhập *" autocomplete="false">
            </div>
            <div class="text-center">
                <button class="btn btn-default w-100" type="submit">Đăng ký</button>
            </div>
            <p class="text-center mt-4 mb-3">--- Hoặc đăng ký với ---</p>
            <div class="align-center space-between">
                <a href="{{route('login.social',['provider' => 'facebook'])}}" class="btn-social"><i class="fa fa-facebook" aria-hidden="true"></i> Facebook</a>
                <a href="{{route('login.social',['provider' => 'google'])}}" class="btn-social"><i class="fa fa-google" aria-hidden="true"></i> Google</a>
            </div>
            <div class="box-alert text-center"></div>
            <div class="mt-3 text-center">
                <span>Bạn đã có tài khoản? <a href="javascript:;" class="btn-login">Đăng nhập ngay</a></span>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
    $('.show-menu-account').click(function(){
        $('.menu_account').toggle();
    });
     $(".form-subcribe").on("submit", function (e) {
        e.preventDefault();
      $.ajax({
        type: 'post',
        url: '/ajax/post-subcriber',
        data:  $('.form-subcribe').serialize(),
        beforeSend: function () {
            $('.form-subcribe button').html('<span class="spinner-border"></span>');
            $('.form-subcribe button').prop('disabled',true);
        },
        success: function (res) {
          $('.form-subcribe button').html('GỬI');
          $('.form-subcribe button').prop('disabled',false);
          if(res.status == 'success'){
            $('.box_subcribe .box-alert').html('<div class="alert alert-success mt-2" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
             setTimeout(function () {
                $('.box_subcribe .box-alert').html('');
              },3000);
            $('.form-subcribe')[0].reset();
          }else{
              var errTxt = '';
              if(res.errors !== undefined) {
                  Object.keys(res.errors).forEach(key => {
                      errTxt += '<li>'+res.errors[key][0]+'</li>';
                  });
              } else {
                  errTxt = res.message;
              }
              $('.box_subcribe .box-alert').html('<div class="alert alert-danger mt-2" role="alert"><ul>'+errTxt+'</ul></div>');
          }
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
         }
      })
    });
    $(function() {
       var setInvisible = function(elem) {
         elem.css('visibility', 'hidden');
       };
       var setVisible = function(elem) {
         elem.css('visibility', 'visible');
       };
       var elem = $("#menutop");
       var items = elem.children();
       elem.prepend('<div id="right-button" style="visibility: hidden;"><svg width="1em" height="1em" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Arrow-Forward-iOS-1" transform="translate(5.000000, 8.000000) scale(-1, 1) translate(-5.000000, -8.000000) " fill="currentColor"><polygon id="Path" points="0.391846 14.5041 1.80785 15.9201 9.72785 8.00008 1.80785 0.0800781 0.391846 1.49608 6.89585 8.00008"></polygon></g></g></svg></div>');
       elem.append('  <div id="left-button"><svg width="1em" height="1em" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.391846 14.504L1.80785 15.92L9.72785 8L1.80785 0.0800047L0.391846 1.496L6.89585 8L0.391846 14.504Z" fill="currentColor"></path></svg></div>');
       items.wrapAll('<div id="inner" />');
       elem.find('#inner').wrap('<div id="outer"/>');

       var outer = $('#outer');

       var updateUI = function() {
         var maxWidth = outer.outerWidth(true);
         var actualWidth = 0;
         $.each($('#inner >'), function(i, item) {
           actualWidth += $(item).outerWidth(true);
         });

         if (actualWidth <= maxWidth) {
           setVisible($('#left-button'));
         }
       };
       updateUI();
       $('#right-button').click(function() {
         var leftPos = outer.scrollLeft();
         outer.animate({
           scrollLeft: leftPos - 200
         }, 800, function() {
           if ($('#outer').scrollLeft() <= 0) {
             setInvisible($('#right-button'));
           }
         });
       });

       $('#left-button').click(function() {
         setVisible($('#right-button'));
         var leftPos = outer.scrollLeft();
         outer.animate({
           scrollLeft: leftPos + 200
         }, 800);
       });

       $(window).resize(function() {
         updateUI();
       });
    });
    $('.btn-wishlist').click(function(){
        $.ajax({
            type: 'get',
            url: '{{route("wishlist.get")}}',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.side-right').addClass('active');
                $('body').css({'overflow':'hidden','touch-action':'none','width':'calc(100% - 6px)'});
                $('.side-right .content-right').html(res);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    function getCart(){
        $.ajax({
            type: 'get',
            url: '{{route("cart.get")}}',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.side-right').addClass('active');
                $('body').css({'overflow':'hidden','touch-action':'none','width':'calc(100% - 6px)'});
                $('.side-right .content-right').html(res);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    }
    $('body').on('click','.remove-cart',function(){
        var id = $(this).attr('data-id');
        $.ajax({
          type: "post",
          url: "{{route('cart.del')}}",
          data: { id: id },
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
            if(res.status === 'false'){
              $('body .list-cart').html('<div class="text-center pt-5 pb-5">Bạn chưa có sản phẩm nào trong giỏ hàng</div>');
            }else{
              $('body .item-cart-'+id+'').remove();
            }
            $('.count-cart').html(res.total);
            $('body .total-price').html(res.price+'đ');
            loadPromotion();
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
            }
      });
    })
    function loadPromotion(){
        $.ajax({
            type: 'get',
            url: '{{route("cart.loadPromotion")}}',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.list-promotion').html(res);
            }
        })
    }
    $(document).on('click','.quantity-quick .entry', function(){
        var _qty = parseInt($('.quantity-quick .quantity-input').val());
        if($(this).hasClass('btn_minus')){
          if(_qty > 1){
            $('.quantity-quick .quantity-input').val(_qty - 1);
          }
        }else{
          $('.quantity-quick .quantity-input').val(_qty + 1);
        }
    })
    function getFeeShip(){
        var ward, province, district;
        if ($('#province_name').length > 0) {
            ward = $('#ward_name').val();
            province = $('#province_name').val();
            district = $('#district_name').val();
        } else {
            ward = $('#ward option:selected').text();
            province = $('#province option:selected').text();
            district = $('#district option:selected').text();
        }
        var address = $('input[name="address"]').val();
        $.ajax({
            type: 'post',
            url: '{{route("cart.feeship")}}',
            data: {province:province,district:district,ward:ward,address:address},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                let fee = res.feeship;
                $('.item-ship').html(res.feeship+'đ');
                $('.total-order').html(res.amount+'đ');
                // Fix: Ensure fee is a string before replace
                let feeStr = (fee !== null && fee !== undefined) ? String(fee) : '0';
                $('input[name="feeShip"]').val(feeStr.replace(/,/g,""));
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                //window.location = window.location.href;
            }
        })
    }
    $('body').on('click','.qtyminus',function(e){
        e.preventDefault();
        var id = $(this).attr('data-id');
        var input = $('#quantity-'+id+'');
        var currentVal = parseInt(input.val());
        var qty = 1;
        if (!isNaN(currentVal) && currentVal > 1) {
            input.val(currentVal - 1);
            qty = currentVal - 1;
        }
        input.val(qty);
        $.ajax({
          type: "post",
          url: "{{route('cart.update')}}",
          data: { id: id, qty: qty },
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
            $('.count-cart').html(res.total);
            $('body .total-price').html(res.price+'đ');
            $('#page_checkout .subtotal-cart').html(res.price+'đ');
            $('#page_checkout .total-order').html(res.totalPrice+'đ');
            if(window.location.href == '{{asset("cart/thanh-toan")}}'){
                loadPromotion();
                getFeeShip();
            }
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
            }
        });
    });
    $('body').on('click','.qtyplus',function(e){
        e.preventDefault();
        var id = $(this).attr('data-id');
        var input = $('#quantity-'+id+'');
        var currentVal = parseInt(input.val());
        var qty = 1;
        if (!isNaN(currentVal)) {
          qty = currentVal + 1;
        }
        input.val(qty);
        $.ajax({
          type: "post",
          url: "{{route('cart.update')}}",
          data: { id: id, qty: qty },
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
            $('.count-cart').html(res.total);
            $('body .total-price').html(res.price+'đ');
            $('#page_checkout .subtotal-cart').html(res.price+'đ');
            $('#page_checkout .total-order').html(res.totalPrice+'đ');
            if(window.location.href == '{{asset("cart/thanh-toan")}}'){
                loadPromotion();
                getFeeShip();
            }
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
         }
      });
    });
    $('.btn-search-mobile').click(function(){
        $('.search_mobile').slideToggle();
    });
    $('.btn-cart').click(function(){
        getCart();
    });
    $('.side-right .backdrop').click(function(){
        $(this).parent().removeClass('active');
        $('body').css({'overflow':'inherit','touch-action':'inherit','width':'inherit'});
    })
    $('body').on('click','.close-cart',function(){
        $('.side-right').removeClass('active');
        $('body').css({'overflow':'inherit','touch-action':'inherit','width':'inherit'});
    });
    $('body').on('click','.btn-register',function(){
        var myLogin = bootstrap.Modal.getInstance(document.querySelector('#myLogin'));
        myLogin.hide();
        var myRegister = new bootstrap.Modal(document.getElementById('myRegister'))
        myRegister.show();
    })
    $('body').on('click','.btn_forgot',function(){
        var myLogin = bootstrap.Modal.getInstance(document.querySelector('#myLogin'));
        myLogin.hide();
        var myForgot = new bootstrap.Modal(document.getElementById('myForgot'))
        myForgot.show();
    })
    $('body').on('click','.btn-login-forgot',function(){
        var myForgot = bootstrap.Modal.getInstance(document.querySelector('#myForgot'));
        myForgot.hide();
        var myLogin = new bootstrap.Modal(document.getElementById('myLogin'))
        myLogin.show();
    })
    $('body').on('click','.btn-login',function(){
        var myRegister = bootstrap.Modal.getInstance(document.querySelector('#myRegister'));
        myRegister.hide();
        var myLogin = new bootstrap.Modal(document.getElementById('myLogin'))
        myLogin.show();
    })
    $('body').on('click','.btn_wishlist',function(){
        var id = $(this).attr('data-id');
        $.ajax({
            type: 'post',
            url: '{{route("wishlist.add")}}',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
              $('.count-wishlist').html(res);
              $('.group-wishlist-'+id+'').html('<button class="btn_remove_wishlist" type="button" data-id="'+id+'"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M 21.001 0 C 18.445 0 16.1584 1.24169 14.6403 3.19326 C 13.1198 1.24169 10.8355 0 8.27952 0 C 3.70634 0 0 3.97108 0 8.86991 C 0 15.1815 9.88903 23.0112 13.4126 25.5976 C 14.1436 26.1341 15.1369 26.1341 15.8679 25.5976 C 19.3915 23.0088 29.2805 15.1815 29.2805 8.86991 C 29.2782 3.97108 25.5718 0 21.001 0 Z" fill="black"></path></svg></button>');
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    })
    $('body').on('click','.btn_remove_wishlist',function(){
        var id = $(this).attr('data-id');
        $.ajax({
            type: 'post',
            url: '{{route("wishlist.remove")}}',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
              $('.count-wishlist').html(res);
              $('.group-wishlist-'+id+'').html('<button class="btn_wishlist" type="button" data-id="'+id+'"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></button>');
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    })
    $('body').on('click','.remove-wishlist',function(){
        var id = $(this).attr('data-id');
        $.ajax({
            type: 'post',
            url: '{{route("wishlist.remove")}}',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
              $('.count-wishlist').html(res);
              $('.group-wishlist-'+id+'').html('<button class="btn_wishlist" type="button" data-id="'+id+'"><svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path></svg></button>');
                $.ajax({
                    type: 'get',
                    url: '{{route("wishlist.get")}}',
                    data: {},
                    headers:
                    {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        $('.side-right .content-right').html(res);
                    },
                    error: function(xhr, status, error){
                        alert('Có lỗi xảy ra, xin vui lòng thử lại');
                        window.location = window.location.href;
                    }
                })
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    $('body').on('click','.remove-all-wishlist',function(){
        $.ajax({
            type: 'get',
            url: '{{route("wishlist.remove.all")}}',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.count-wishlist').html(0);
                $('.side-right .content-right').html(res);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    $('body').on('click','.addCart',function(){
        var id = $('body #variant_id').val();
        var qty = $('body input.quantity-input').val();
        $.ajax({
        type: 'post',
        url: '{{route("cart.add")}}',
        data: {id:id,qty:qty},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('body .addCart').prop('disabled',true);
            $('body .addCart .icon').html('<span class="spinner-border text-light"></span>')
        },
        success: function (res) {
            $('body .addCart').prop('disabled',false);
            $('body .addCart .icon').html('<svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg>');
          if(res.status == 'success'){
            $('body .count-cart').html(res.total);
            getCart();
          }else{
            alert("Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại");
          }
        },
        error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            //window.location = window.location.href;
        }
      })
    });
    $('body').on('click','.buyNow',function(){
        var id = $('body #variant_id').val();
        var qty = $('body input.quantity-input').val();
        $.ajax({
        type: 'post',
        url: '{{route("cart.add")}}',
        data: {id:id,qty:qty},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('body .buyNow').prop('disabled',true);
            $('body .buyNow').html('<span class="spinner-border text-light"></span> Mua ngay')
        },
        success: function (res) {
            $('body .buyNow').prop('disabled',false);
            $('body .buyNow').html('Mua ngay');
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
    $('.formRegiter').validate({
        rules: {
              first_name: {
                 required: true,
                 maxlength:60,
              },
              last_name: {
                 required: true,
                 maxlength:60,
              },
              email: {
                 required: true,
                 email: true,
              },
              password:{
                  required: true,
              },
        },
        messages: {
            first_name: {
               required: "Bạn chưa nhập họ",
               maxlength:"Số ký tự không vượt quá 60"
            },
            last_name: {
               required: "Bạn chưa nhập tên",
               maxlength:"Số ký tự không vượt quá 60"
            },
            email: {
                 required: "Bạn chưa nhập địa chỉ email",
                 email: "Địa chỉ email không đúng",
            },
            password:{
              required: "Bạn chưa nhập mật khẩu",
            },
        },
        submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("member.register")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.formRegiter button[type="submit"]').html('<span class="spinner-border"></span>');
                  $('.formRegiter button[type="submit"]').prop('disabled',true);
              },
              success: function (res) {
                if(res.status == 'success'){
                  $('.formRegiter .box-alert').html('<div class="alert alert-success mt-3" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
                    $('.formRegiter')[0].reset();
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.formRegiter .box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
                $('.formRegiter button[type="submit"]').html('ĐĂNG KÝ');
                $('.formRegiter button[type="submit"]').prop('disabled',false);
              }
              ,error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
             }
          });
          return false;
      }
    });
    $('.formLogin').validate({
      rules: {
          email: {
             required: true,
             email: true,
          },
          password:{
              required: true,
          },
      },
        messages: {
        email: {
             required: "Bạn chưa nhập địa chỉ email",
             email: "Địa chỉ email không đúng",
        },
        password:{
          required: "Bạn chưa nhập mật khẩu",
        },
        },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("member.login")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.formLogin button[type="submit"]').html('<span class="spinner-border"></span>');
                  $('.formLogin button[type="submit"]').prop('disabled',true);
              },
              success: function (res) {
                if(res.status == 'success'){
                    $('.formLogin')[0].reset();
                    window.location = res.url;
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.formLogin .box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
                $('.formLogin button[type="submit"]').html('ĐĂNG NHẬP');
                $('.formLogin button[type="submit"]').prop('disabled',false);
              },
              error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
          });
          return false;
      }
    });
    $('.formForgot').validate({
      rules: {
          email: {
             required: true,
             email: true,
          }
      },
        messages: {
        email: {
             required: "Bạn chưa nhập địa chỉ email",
             email: "Địa chỉ email không đúng",
        },
        },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("member.forgot")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.formForgot button[type="submit"]').html('<span class="spinner-border"></span>');
                  $('.formForgot button[type="submit"]').prop('disabled',true);
              },
              success: function (res) {
                if(res.status == 'success'){
                  $('.formForgot .box-alert').html('<div class="alert alert-success mt-3" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
                    $('.formForgot')[0].reset();
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.formForgot .box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
                $('.formForgot button[type="submit"]').html('GỬI');
                $('.formForgot button[type="submit"]').prop('disabled',false);
              },
              error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
          });
          return false;
      }
    });
    $('footer .map iframe').attr('width',$('footer .map').width()).attr('height','220px');
    $('.fb-comments').attr('data-width','500px');
    if($(window).width() <= 991){
        $(document).on('click','.menu-active #site-overlay,#close-handle',function(event){
            $("body").removeClass('menu-active')
        });
        $("body").on("click",".btn-menu-mb",function(){
            $("body").toggleClass('menu-active');
        })
        $('body').on('click','.cl-open',function(event){
            $(this).next().slideToggle('fast')
            $(this).toggleClass('minus-menu');
        });
    };
    // if($(window).width() <= 420){
    //     $('.g_id_signin').attr('data-width',$('#main .container-lg').width());
    // }
    
    // 搜索建议功能
    var searchTimeout;
    var isSearchSuggestionsVisible = false;
    
    function initSearchSuggestions(inputId, suggestionsId, prefix) {
        var $input = $('#' + inputId);
        var $suggestions = $('#' + suggestionsId);
        var isMobile = prefix === 'mobile';
        
        if ($input.length === 0 || $suggestions.length === 0) {
            console.warn('Search input or suggestions element not found:', inputId, suggestionsId);
            return;
        }
        
        // 移除旧的事件监听器，避免重复绑定
        $input.off('focus.searchSuggestions input.searchSuggestions click.searchSuggestions');
        
        // 当输入框获得焦点或点击时显示建议
        $input.on('focus.searchSuggestions click.searchSuggestions', function(e) {
            e.stopPropagation();
            if ($(this).val().length === 0) {
                loadSearchSuggestions('', prefix);
            }
            if (isMobile) {
                $('body').css('overflow', 'hidden');
            }
        });
        
        // 当输入时显示建议
        $input.on('input.searchSuggestions', function() {
            var keyword = $(this).val();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                loadSearchSuggestions(keyword, prefix);
            }, 300);
        });
        
        // 点击外部时隐藏建议（仅桌面端）
        if (!isMobile) {
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.search-wrapper, .search-suggestions').length) {
                    $suggestions.hide();
                    isSearchSuggestionsVisible = false;
                }
            });
        }
        
        // 移动端关闭按钮已在HTML中定义，通过onclick处理
    }
    
    function closeMobileSuggestions() {
        $('#search-suggestions-mobile').fadeOut(200);
        $('#search-input-mobile').blur();
        $('body').css('overflow', '');
    }
    
    // 全局函数，供onclick调用
    window.closeMobileSuggestions = closeMobileSuggestions;
    
    function loadSearchSuggestions(keyword, prefix) {
        var $suggestions = $('#' + (prefix ? 'search-suggestions-mobile' : 'search-suggestions'));
        var suffix = prefix ? '-mobile' : '';
        
        $.ajax({
            type: 'post',
            url: '/ajax-search-suggestions',
            data: {
                keyword: keyword,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                // 后端直接返回数据对象，没有status包装
                var data = res;
                
                // 兼容两种格式：直接数据对象或包装在data中的对象
                if (res.status === 'success' && res.data) {
                    data = res.data;
                }
                
                // 显示/隐藏各个部分
                if (data.deals && data.deals.length > 0) {
                    renderDeals(data.deals, suffix);
                    $('#deals-section' + suffix).show();
                } else {
                    $('#deals-section' + suffix).hide();
                }
                
                if (data.suggest_products && data.suggest_products.length > 0) {
                    renderProducts(data.suggest_products, suffix);
                    $('#products-section' + suffix).show();
                } else {
                    $('#products-section' + suffix).hide();
                }
                
                if (data.recent_searches && data.recent_searches.length > 0) {
                    renderRecentSearches(data.recent_searches, suffix);
                    $('#recent-section' + suffix).show();
                } else {
                    $('#recent-section' + suffix).hide();
                }
                
                if (data.categories && data.categories.length > 0) {
                    renderCategories(data.categories, suffix);
                    $('#categories-section' + suffix).show();
                } else {
                    $('#categories-section' + suffix).hide();
                }
                
                if (data.brands && data.brands.length > 0) {
                    renderBrands(data.brands, suffix);
                    $('#brands-section' + suffix).show();
                } else {
                    $('#brands-section' + suffix).hide();
                }
                
                $suggestions.show();
                isSearchSuggestionsVisible = true;
                
                // 移动端添加关闭按钮点击事件
                if (prefix === 'mobile') {
                    setTimeout(function() {
                        $('.search_mobile .search-suggestions').off('click.closeBtn').on('click.closeBtn', function(e) {
                            if ($(e.target).is('.search_mobile .search-suggestions')) {
                                closeMobileSuggestions();
                            }
                        });
                    }, 100);
                }
            },
            error: function() {
                console.log('Error loading search suggestions');
            }
        });
    }
    
    function renderDeals(deals, suffix) {
        var html = '';
        deals.forEach(function(deal) {
            html += '<div class="deal-item"><a href="' + (deal.link || '#') + '">' + (deal.title || deal.description || '') + '</a></div>';
        });
        $('#deals-list' + suffix).html(html);
    }
    
    function renderProducts(products, suffix) {
        var html = '';
        products.forEach(function(product) {
            var productUrl = product.slug ? '/' + product.slug : '#';
            html += '<div class="product-item"><a href="' + productUrl + '">';
            html += '<img src="' + (product.image || '/public/image/no-image.jpg') + '" alt="' + product.name + '" width="40" height="40">';
            html += '<span>' + product.name + '</span></a></div>';
        });
        $('#products-list' + suffix).html(html);
    }
    
    function renderRecentSearches(searches, suffix) {
        var html = '';
        // 显示所有搜索历史，使用标签形式
        searches.forEach(function(search) {
            html += '<span class="recent-tag">';
            html += '<a href="/tim-kiem?s=' + encodeURIComponent(search) + '" title="' + search + '">' + search + '</a>';
            html += '<button type="button" class="remove-recent-tag" data-search="' + encodeURIComponent(search) + '" title="Xóa"><i class="fa fa-times"></i></button>';
            html += '</span>';
        });
        
        $('#recent-searches-list' + suffix).html(html);
        // 隐藏"查看更多"按钮，因为标签形式已经可以显示所有内容
        $('#view-more-recent' + suffix).hide();
    }
    
    function renderCategories(categories, suffix) {
        var html = '';
        categories.forEach(function(cat) {
            var catUrl = cat.slug ? '/' + cat.slug : '#';
            html += '<div class="category-item">';
            html += '<a href="' + catUrl + '">';
            html += '<img src="' + (cat.image || '/public/image/no-image.jpg') + '" alt="' + cat.name + '">';
            html += '<span>' + cat.name + '</span>';
            html += '</a></div>';
        });
        $('#categories-grid' + suffix).html(html);
    }
    
    function renderBrands(brands, suffix) {
        var html = '';
        brands.forEach(function(brand) {
            var brandUrl = brand.slug ? '/thuong-hieu/' + brand.slug : '#';
            html += '<div class="brand-item">';
            html += '<a href="' + brandUrl + '">';
            html += '<img src="' + (brand.image || '/public/image/no-image.jpg') + '" alt="' + brand.name + '">';
            html += '</a></div>';
        });
        $('#brands-grid' + suffix).html(html);
    }
    
    // Placeholder打字机动画效果
    function initPlaceholderAnimation() {
        var placeholderTexts = [
            'Nhập tên sản phẩm, thương hiệu muốn tìm',
            'Tìm kiếm sản phẩm làm đẹp',
            'Tìm kem chống nắng, serum, toner...',
            'Tìm thương hiệu: CeraVe, La Roche-Posay...'
        ];
        
        var currentTextIndex = 0;
        var currentCharIndex = 0;
        var isDeleting = false;
        var typingSpeed = 100;
        var deletingSpeed = 50;
        var pauseTime = 2000;
        
        function typePlaceholder() {
            var $inputs = $('#search-input, #search-input-mobile');
            if ($inputs.length === 0) return;
            
            var $input = $inputs.first();
            var currentText = placeholderTexts[currentTextIndex];
            
            if (!isDeleting && currentCharIndex < currentText.length) {
                // 正在输入
                $input.attr('placeholder', currentText.substring(0, currentCharIndex + 1) + '|');
                currentCharIndex++;
                setTimeout(typePlaceholder, typingSpeed);
            } else if (isDeleting && currentCharIndex > 0) {
                // 正在删除
                $input.attr('placeholder', currentText.substring(0, currentCharIndex - 1) + '|');
                currentCharIndex--;
                setTimeout(typePlaceholder, deletingSpeed);
            } else if (!isDeleting && currentCharIndex === currentText.length) {
                // 输入完成，等待后开始删除
                setTimeout(function() {
                    isDeleting = true;
                    typePlaceholder();
                }, pauseTime);
            } else if (isDeleting && currentCharIndex === 0) {
                // 删除完成，切换到下一个文本
                isDeleting = false;
                currentTextIndex = (currentTextIndex + 1) % placeholderTexts.length;
                setTimeout(typePlaceholder, 500);
            }
        }
        
        // 只在输入框为空且未获得焦点时显示动画
        function checkAndStartAnimation() {
            var $inputs = $('#search-input, #search-input-mobile');
            $inputs.each(function() {
                var $input = $(this);
                if ($input.val() === '' && !$input.is(':focus')) {
                    if (!$input.data('typing-started')) {
                        $input.data('typing-started', true);
                        typePlaceholder();
                    }
                } else {
                    $input.data('typing-started', false);
                    $input.attr('placeholder', placeholderTexts[0]);
                }
            });
        }
        
        // 监听输入框焦点事件
        $(document).on('focus', '#search-input, #search-input-mobile', function() {
            var $input = $(this);
            $input.data('typing-started', false);
            $input.attr('placeholder', placeholderTexts[0]);
        });
        
        $(document).on('blur', '#search-input, #search-input-mobile', function() {
            var $input = $(this);
            if ($input.val() === '') {
                setTimeout(function() {
                    checkAndStartAnimation();
                }, 500);
            }
        });
        
        // 启动动画
        setTimeout(function() {
            checkAndStartAnimation();
        }, 1000);
    }
    
    // 初始化搜索建议 - 确保在DOM和jQuery都准备好后执行
    function initializeSearchFeatures() {
        if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
            setTimeout(initializeSearchFeatures, 100);
            return;
        }
        
        // 确保DOM元素存在
        if ($('#search-input').length > 0 && $('#search-suggestions').length > 0) {
            // 检查是否已经初始化过，避免重复绑定
            var $input = $('#search-input');
            var events = $._data ? $._data($input[0], 'events') : null;
            if (!events || !events.focus || events.focus.length === 0) {
                initSearchSuggestions('search-input', 'search-suggestions', '');
            }
        }
        
        if ($('#search-input-mobile').length > 0 && $('#search-suggestions-mobile').length > 0) {
            var $mobileInput = $('#search-input-mobile');
            var mobileEvents = $._data ? $._data($mobileInput[0], 'events') : null;
            if (!mobileEvents || !mobileEvents.focus || mobileEvents.focus.length === 0) {
                initSearchSuggestions('search-input-mobile', 'search-suggestions-mobile', 'mobile');
            }
        }
        
        // 启动placeholder动画
        if (typeof initPlaceholderAnimation !== 'undefined') {
            initPlaceholderAnimation();
        }
    }
    
    // 使用多种方式确保代码执行
    function runInitializeSearchFeatures() {
        if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
            setTimeout(runInitializeSearchFeatures, 100);
            return;
        }
        initializeSearchFeatures();
    }
    
    // 确保初始化代码在jQuery加载后执行
    // 使用jQuery ready作为主要方式（最可靠）
    (function initSearchOnReady() {
        // 如果jQuery已经加载，立即使用ready
        if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
            $(document).ready(function() {
                setTimeout(runInitializeSearchFeatures, 100);
            });
        } else {
            // 如果jQuery还没加载，等待后重试
            var checkCount = 0;
            var maxChecks = 100; // 最多检查10秒
            var checkInterval = setInterval(function() {
                checkCount++;
                if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
                    clearInterval(checkInterval);
                    $(document).ready(function() {
                        setTimeout(runInitializeSearchFeatures, 100);
                    });
                } else if (checkCount >= maxChecks) {
                    clearInterval(checkInterval);
                    console.warn('jQuery not loaded after 10 seconds, search suggestions may not work');
                }
            }, 100);
        }
        
    // DOMContentLoaded作为备用
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    if (typeof $ !== 'undefined') {
                        runInitializeSearchFeatures();
                    }
                }, 200);
            });
        } else {
            // DOM已经加载完成，延迟执行确保jQuery已加载
            setTimeout(function() {
                if (typeof $ !== 'undefined') {
                    runInitializeSearchFeatures();
                }
            }, 300);
        }
        
        // window.onload作为最后的安全网
        window.addEventListener('load', function() {
            setTimeout(runInitializeSearchFeatures, 300);
        });
    })();

    // ========== Skeleton image loading cho toàn site ==========
    // Hàm tự động nhận diện thiết bị di động và kích thước màn hình
    function detectMobileDevice() {
        var isMobile = false;
        var screenWidth = window.innerWidth || document.documentElement.clientWidth || screen.width;
        var screenHeight = window.innerHeight || document.documentElement.clientHeight || screen.height;
        
        // Phương pháp 1: Kiểm tra kích thước màn hình
        // Mobile thường có chiều rộng <= 768px hoặc chiều cao > chiều rộng (portrait mode)
        if (screenWidth <= 768 || (screenHeight > screenWidth && screenWidth <= 1024)) {
            isMobile = true;
        }
        
        // Phương pháp 2: Kiểm tra User-Agent
        var userAgent = navigator.userAgent || navigator.vendor || window.opera;
        var mobileRegex = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
        if (mobileRegex.test(userAgent)) {
            isMobile = true;
        }
        
        // Phương pháp 3: Kiểm tra touch events (thiết bị cảm ứng)
        if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
            isMobile = true;
        }
        
        // Phương pháp 4: Kiểm tra CSS media query
        if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
            isMobile = true;
        }
        
        return {
            isMobile: isMobile,
            screenWidth: screenWidth,
            screenHeight: screenHeight,
            isPortrait: screenHeight > screenWidth,
            isLandscape: screenWidth > screenHeight,
            deviceType: screenWidth <= 480 ? 'phone' : (screenWidth <= 768 ? 'tablet' : 'desktop')
        };
    }
    
    // ========== Skeleton Image System: Tự động phát hiện kích thước màn hình và điều chỉnh ==========
    
    // Hàm tự động điều chỉnh kích thước skeleton container dựa trên thiết bị và class
    function applySkeletonSize($wrap, deviceInfo) {
        if (!$wrap.length) return;
        
        var sizeConfig = {
            'skeleton--img-sm': {
                mobile: { width: '60px', height: '60px', minWidth: '40px', maxWidth: '15vw' },
                tablet: { width: '60px', height: '60px', minWidth: '40px', maxWidth: '100%' },
                desktop: { width: '60px', height: '60px', maxWidth: '100%' }
            },
            'skeleton--img-md': {
                mobile: { width: '100%', height: 'auto', aspectRatio: '1/1', maxWidth: '100%' },
                tablet: { width: '100%', height: 'auto', aspectRatio: '1/1', maxWidth: '100%' },
                desktop: { width: '212px', height: '212px', maxWidth: '100%' }
            },
            'skeleton--img-lg': {
                mobile: { width: '100%', height: 'auto', minHeight: '200px', maxWidth: '100%' },
                tablet: { width: '100%', height: 'auto', minHeight: '200px', maxWidth: '100%' },
                desktop: { width: '100%', height: 'auto', minHeight: '200px', maxWidth: '100%' }
            },
            'skeleton--img-banner': {
                mobile: { width: '100%', height: 'auto', aspectRatio: '4.4/1', maxWidth: '100%' },
                tablet: { width: '100%', height: 'auto', aspectRatio: '4.4/1', maxWidth: '100%' },
                desktop: { width: '100%', height: '265px', maxWidth: '100%' }
            },
            'skeleton--img-logo': {
                mobile: { width: 'auto', height: 'auto', maxWidth: '100%', maxHeight: '80px' },
                tablet: { width: 'auto', height: 'auto', maxWidth: '100%', maxHeight: '80px' },
                desktop: { width: 'auto', height: 'auto', maxWidth: '100%' }
            },
            'skeleton--img-square': {
                mobile: { width: '100%', height: 'auto', aspectRatio: '1/1', maxWidth: '100%' },
                tablet: { width: '100%', height: 'auto', aspectRatio: '1/1', maxWidth: '100%' },
                desktop: { width: '100%', height: 'auto', aspectRatio: '1/1', maxWidth: '100%' }
            }
        };
        
        // Xác định class size
        var sizeClass = null;
        for (var key in sizeConfig) {
            if ($wrap.hasClass(key)) {
                sizeClass = key;
                break;
            }
        }
        
        // Xác định device type
        var deviceType = deviceInfo.deviceType === 'phone' ? 'mobile' : 
                        (deviceInfo.deviceType === 'tablet' ? 'tablet' : 'desktop');
        
        // Áp dụng kích thước
        var baseStyles = {
            'max-width': '100%',
            'overflow': 'hidden',
            'box-sizing': 'border-box'
        };
        
        if (sizeClass && sizeConfig[sizeClass] && sizeConfig[sizeClass][deviceType]) {
            var config = sizeConfig[sizeClass][deviceType];
            Object.assign(baseStyles, config);
        } else if (!sizeClass) {
            // Không có class size, dùng mặc định
            if (deviceInfo.isMobile) {
                baseStyles.width = '100%';
                baseStyles.height = 'auto';
                baseStyles.aspectRatio = '1/1';
            } else {
                baseStyles.width = '212px';
                baseStyles.height = '212px';
            }
        }
        
        $wrap.css(baseStyles);
    }
    
    // Hàm chính: Khởi tạo skeleton images với tự động phát hiện kích thước
    function initSkeletonImages() {
        if (typeof $ === 'undefined') return;
        
        // Phát hiện thiết bị một lần
        var deviceInfo = detectMobileDevice();
        
        $('.js-skeleton-img').each(function () {
            var img = this;
            var $img = $(img);
            var $wrap = $img.closest('.js-skeleton');
            
            if (!$wrap.length) return;
            
            // Đảm bảo ảnh responsive
            $img.css({
                'max-width': '100%',
                'height': 'auto',
                'display': 'block'
            });
            
            // 初始化 skeleton 尺寸的函数
            var initSkeletonSize = function() {
                // 优先检查图片是否有明确的 width/height 属性
                var imgWidthAttr = $img.attr('width');
                var imgHeightAttr = $img.attr('height');
                
                // 尝试获取实际渲染尺寸（如果图片已加载）
                var actualWidth = img.offsetWidth || img.clientWidth || 0;
                var actualHeight = img.offsetHeight || img.clientHeight || 0;
                
                var displayWidth, displayHeight;
                
                // 如果能够获取实际渲染尺寸，优先使用（考虑 CSS 的影响）
                if (actualWidth > 0 && actualHeight > 0) {
                    displayWidth = actualWidth;
                    displayHeight = actualHeight;
                } else if (imgWidthAttr && imgHeightAttr) {
                    // 如果无法获取实际尺寸，使用 HTML 属性
                    displayWidth = parseInt(imgWidthAttr);
                    displayHeight = parseInt(imgHeightAttr);
                    
                    // 但需要考虑容器的宽度限制
                    var containerWidth = $wrap.parent().width() || $wrap.closest('.item-product').width() || window.innerWidth;
                    if (containerWidth > 0 && displayWidth > containerWidth) {
                        // 如果容器宽度小于图片宽度，按比例缩放高度
                        var ratio = containerWidth / displayWidth;
                        displayWidth = containerWidth;
                        displayHeight = Math.round(displayHeight * ratio);
                    }
                } else {
                    // 如果没有明确尺寸，使用设备相关的默认尺寸
                    applySkeletonSize($wrap, deviceInfo);
                    return;
                }
                
                // 设置 skeleton 容器大小
                if (displayWidth > 0 && displayHeight > 0) {
                    $wrap.css({
                        'width': displayWidth + 'px',
                        'height': displayHeight + 'px',
                        'min-width': displayWidth + 'px',
                        'max-width': displayWidth + 'px',
                        'min-height': displayHeight + 'px',
                        'max-height': displayHeight + 'px'
                    });
                }
            };
            
            // 立即尝试初始化
            initSkeletonSize();
            
            // 如果图片已加载，再次检查以确保尺寸准确
            if (img.complete && img.naturalWidth > 0) {
                // 等待一帧确保 DOM 已更新
                requestAnimationFrame(function() {
                    initSkeletonSize();
                });
            }
            
            function hideSkeleton() {
                // 获取图片的实际尺寸
                var naturalWidth = img.naturalWidth;
                var naturalHeight = img.naturalHeight;
                
                if (naturalWidth > 0 && naturalHeight > 0) {
                    // 获取图片的实际渲染尺寸（考虑 CSS 的影响）
                    var updateSize = function() {
                        // 优先使用实际渲染尺寸
                        var displayWidth = img.offsetWidth || img.clientWidth || 0;
                        var displayHeight = img.offsetHeight || img.clientHeight || 0;
                        
                        // 如果无法获取渲染尺寸，尝试使用 HTML 属性
                        if (displayWidth <= 0 || displayHeight <= 0) {
                            var imgWidthAttr = $img.attr('width');
                            var imgHeightAttr = $img.attr('height');
                            
                            if (imgWidthAttr && imgHeightAttr) {
                                displayWidth = parseInt(imgWidthAttr);
                                displayHeight = parseInt(imgHeightAttr);
                                
                                // 考虑容器的宽度限制
                                var containerWidth = $wrap.parent().width() || $wrap.closest('.item-product').width() || 0;
                                if (containerWidth > 0 && displayWidth > containerWidth) {
                                    var ratio = containerWidth / displayWidth;
                                    displayWidth = containerWidth;
                                    displayHeight = Math.round(displayHeight * ratio);
                                }
                            } else {
                                // 最后使用自然尺寸，但也要考虑容器限制
                                displayWidth = naturalWidth;
                                displayHeight = naturalHeight;
                                var containerWidth = $wrap.parent().width() || $wrap.closest('.item-product').width() || 0;
                                if (containerWidth > 0 && displayWidth > containerWidth) {
                                    var ratio = containerWidth / displayWidth;
                                    displayWidth = containerWidth;
                                    displayHeight = Math.round(displayHeight * ratio);
                                }
                            }
                        }
                        
                        // 确保尺寸有效且合理
                        if (displayWidth > 0 && displayHeight > 0) {
                            // 设置 skeleton 容器大小与图片完全匹配
                            $wrap.css({
                                'width': displayWidth + 'px',
                                'height': displayHeight + 'px',
                                'min-width': displayWidth + 'px',
                                'max-width': displayWidth + 'px',
                                'min-height': displayHeight + 'px',
                                'max-height': displayHeight + 'px'
                            });
                        }
                    };
                    
                    // 立即尝试获取尺寸
                    updateSize();
                    
                    // 如果尺寸为0或无效，等待下一帧再试（确保图片已完全渲染）
                    if (img.offsetWidth <= 0 || img.offsetHeight <= 0) {
                        requestAnimationFrame(function() {
                            updateSize();
                            // 再等待一帧确保尺寸稳定
                            requestAnimationFrame(function() {
                                updateSize();
                            });
                        });
                    } else {
                        // 即使尺寸有效，也再检查一次确保准确
                        requestAnimationFrame(function() {
                            updateSize();
                        });
                    }
                }
                
                $wrap.removeClass('skeleton skeleton-error');
                $img.css({
                    'opacity': 1,
                    'visibility': 'visible'
                });
            }
            
            function showSkeletonOnError() {
                if (!$wrap.hasClass('skeleton')) {
                    $wrap.addClass('skeleton skeleton-error');
                }
                $img.css({
                    'opacity': 0,
                    'visibility': 'hidden'
                }).attr('alt', 'Hình ảnh không tải được');
                
                // Đảm bảo kích thước phù hợp khi lỗi
                applySkeletonSize($wrap, deviceInfo);
            }
            
            // Kiểm tra trạng thái ảnh
            if (img.complete && img.naturalWidth > 0 && img.naturalHeight > 0) {
                hideSkeleton();
            } else {
                // Ảnh chưa load hoặc lỗi: hiển thị skeleton
                if (!$wrap.hasClass('skeleton')) {
                    $wrap.addClass('skeleton');
                }
                $img.css({
                    'opacity': 0,
                    'visibility': 'hidden'
                });
                
                // Đợi event load/error
                var loadHandler = function() {
                    if (img.naturalWidth > 0 && img.naturalHeight > 0) {
                        hideSkeleton();
                    } else {
                        showSkeletonOnError();
                    }
                    $img.off('load error', loadHandler);
                    $img.off('load error', errorHandler);
                };
                var errorHandler = function() {
                    showSkeletonOnError();
                    $img.off('load error', loadHandler);
                    $img.off('load error', errorHandler);
                };
                
                if (!img.complete) {
                    $img.on('load', loadHandler).on('error', errorHandler);
                } else {
                    showSkeletonOnError();
                }
            }
        });
        
        // Thêm resize handler để tự động điều chỉnh khi thay đổi kích thước màn hình
        if (!window.skeletonResizeHandler) {
            var resizeTimeout;
            var lastDeviceInfo = deviceInfo;
            
            window.skeletonResizeHandler = function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    var currentDeviceInfo = detectMobileDevice();
                    var deviceChanged = !lastDeviceInfo || 
                                       lastDeviceInfo.isMobile !== currentDeviceInfo.isMobile ||
                                       lastDeviceInfo.deviceType !== currentDeviceInfo.deviceType;
                    
                    if (deviceChanged) {
                        lastDeviceInfo = currentDeviceInfo;
                        // Điều chỉnh lại tất cả skeleton khi thiết bị thay đổi
                        $('.js-skeleton-img').each(function() {
                            var $wrap = $(this).closest('.js-skeleton');
                            if ($wrap.length) {
                                applySkeletonSize($wrap, currentDeviceInfo);
                            }
                        });
                    }
                }, 250);
            };
            
            $(window).on('resize orientationchange', window.skeletonResizeHandler);
            if (window.screen && window.screen.orientation) {
                window.screen.orientation.addEventListener('change', window.skeletonResizeHandler);
            }
        }
    }

    if (typeof $ !== 'undefined') {
        $(document).ready(function () {
            initSkeletonImages();
        });
    }
    // 移动端关闭按钮（使用伪元素，需要通过点击事件处理）
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            $(document).on('click', function(e) {
                // 检查是否点击在移动端建议面板的关闭区域
                if ($(e.target).closest('#search-suggestions-mobile').length === 0 && 
                    $('#search-suggestions-mobile').is(':visible') &&
                    !$(e.target).closest('.search-wrapper').length) {
                    closeMobileSuggestions();
                }
            });
        });
    }
    
    // 删除最近搜索（支持两种选择器：旧的.recent-item和新的标签形式）
    if (typeof $ !== 'undefined') {
        $(document).ready(function() {
            $(document).on('click', '.remove-recent, .remove-recent-tag', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var search = decodeURIComponent($(this).data('search'));
                var isMobile = $(this).closest('#search-suggestions-mobile').length > 0;
                $.ajax({
                    type: 'post',
                    url: '/ajax-remove-recent-search',
                    data: {
                        search: search,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        var keyword = isMobile ? $('#search-input-mobile').val() : $('#search-input').val();
                        loadSearchSuggestions(keyword, isMobile ? 'mobile' : '');
                    }
                });
            });
        });
    }
</script>
<style>
/* ========== 搜索区域样式 - 桌面端 ========== */
.search-head {
    position: relative;
    width: 100%;
    height: auto;
    background: transparent;
    padding: 0;
    border: none;
    margin: 0;
}

.search-head .search {
    position: relative;
    width: 100%;
}

.search-wrapper {
    position: relative;
    width: 100%;
    height: 40px;
    background: rgb(246, 246, 246);
    border-radius: 42px;
    border: 1px solid transparent;
    padding: 0;
    transition: all 0.3s ease;
}

.search-wrapper:hover {
    background: rgb(240, 240, 240);
    border-color: #e0e0e0;
}

.search-wrapper:focus-within {
    background: #fff;
    border-color: var(--main-color, #b20a2c);
    box-shadow: 0 0 0 3px rgba(178, 10, 44, 0.1);
}

.search-wrapper form {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    height: 100%;
    padding: 0;
}

.search-wrapper input[type="search"] {
    flex: 1;
    padding: 0 45px 0 15px;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #333;
    transition: all 0.3s ease;
    outline: none;
    text-overflow: ellipsis;
    min-width: 0;
    line-height: 40px;
    height: 40px;
    box-sizing: border-box;
    margin: 0;
}

.search-wrapper input[type="search"]::placeholder {
    color: #999;
}

.search-wrapper button[type="submit"] {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    padding: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    flex-shrink: 0;
    z-index: 1;
    height: auto;
    width: auto;
}

.search-wrapper button[type="submit"]:hover {
    opacity: 0.7;
}

.search-wrapper button[type="submit"] .icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ========== 搜索建议面板 ========== */
.search-suggestions {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 100%;
    min-width: 500px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.08);
    z-index: 1000;
    max-height: 650px;
    overflow-y: auto;
    animation: slideDown 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.search-suggestions-content {
    padding: 20px 20px 16px 20px;
}

/* 滚动条样式 */
.search-suggestions::-webkit-scrollbar {
    width: 6px;
}

.search-suggestions::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.search-suggestions::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}

.search-suggestions::-webkit-scrollbar-thumb:hover {
    background: #999;
}

/* ========== 建议部分 ========== */
.suggestions-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.suggestions-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.section-title {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 10px;
    color: #333;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-family: 'SVN-Mont-SemiBold', sans-serif;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ========== 促销/Deal列表 ========== */
.deals-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.deals-list .deal-item {
    padding: 12px 15px !important;
    background: linear-gradient(135deg, #b20a2c 0%, #d32f2f 100%) !important;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(178, 10, 44, 0.2) !important;
    border-left: 4px solid rgba(255, 255, 255, 0.3) !important;
}

.deals-list .deal-item:hover {
    background: linear-gradient(135deg, #d32f2f 0%, #b20a2c 100%) !important;
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(178, 10, 44, 0.3) !important;
}

.deals-list .deal-item a {
    color: #fff !important;
    text-decoration: none;
    font-size: 13px;
    line-height: 1.5;
    display: block;
    font-weight: 500;
}

.deals-list .deal-item a:hover {
    color: #fff !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* ========== 产品建议列表 ========== */
.products-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.products-list .product-item {
    padding: 10px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.products-list .product-item:hover {
    background: #f8f9fa;
}

.products-list .product-item a {
    display: flex;
    align-items: center;
    color: #333;
    text-decoration: none;
    gap: 12px;
}

.products-list .product-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    flex-shrink: 0;
}

.products-list .product-item span {
    font-size: 13px;
    line-height: 1.4;
    flex: 1;
}

.products-list .product-item:hover span {
    color: var(--main-color, #b20a2c);
}

/* ========== 最近搜索列表 ========== */
/* ========== 搜索历史标签形式 - 优化空间占用 ========== */
.recent-searches-list {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: flex-start;
    line-height: 1.2;
    margin-top: -2px;
}

.recent-searches-list .recent-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #f5f5f5;
    border-radius: 16px;
    transition: all 0.2s ease;
    border: 1px solid #e8e8e8;
    font-size: 11px;
    line-height: 1.3;
    max-width: 100%;
    height: auto;
    min-height: 24px;
}

.recent-searches-list .recent-tag:hover {
    background: #eeeeee;
    border-color: #d0d0d0;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
}

.recent-searches-list .recent-tag a {
    color: #555;
    text-decoration: none;
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
    display: inline-block;
    transition: color 0.2s ease;
    line-height: 1.3;
}

.recent-searches-list .recent-tag a:hover {
    color: var(--main-color, #b20a2c);
}

.recent-searches-list .remove-recent-tag {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 14px;
    height: 14px;
    min-width: 14px;
    min-height: 14px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 9px;
    flex-shrink: 0;
    line-height: 1;
    margin-left: 2px;
}

.recent-searches-list .remove-recent-tag:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #333;
}

/* 兼容旧的.recent-item样式（列表形式） */
.recent-searches-list .recent-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.recent-searches-list .recent-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.recent-searches-list .recent-item:hover {
    background: #f8f9fa;
}

.recent-searches-list .recent-item a {
    color: #333;
    text-decoration: none;
    font-size: 13px;
    flex: 1;
    display: flex;
    align-items: center;
}

.recent-searches-list .recent-item a:hover {
    color: var(--main-color, #b20a2c);
}

.recent-searches-list .remove-recent {
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    padding: 4px 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.recent-searches-list .remove-recent:hover {
    background: #f0f0f0;
    color: #333;
}

/* 搜索历史部分优化 - 减少间距，最小化占用空间 */
.recent-section {
    margin-bottom: 14px !important;
    padding-bottom: 14px !important;
}

.recent-section .section-title {
    margin-bottom: 8px !important;
}

.view-more {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    padding: 10px;
    color: var(--main-color, #b20a2c);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.view-more:hover {
    background: #f8f9fa;
    color: var(--main-color, #b20a2c);
}

.view-more i {
    font-size: 11px;
    transition: transform 0.3s ease;
}

.view-more:hover i {
    transform: translateY(2px);
}

/* ========== 产品类别网格 ========== */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.category-item {
    text-align: center;
}

.category-item a {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #333;
    padding: 12px;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.category-item a:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.category-item img {
    width: 100%;
    max-width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 8px;
    border: 1px solid #e0e0e0;
}

.category-item span {
    display: block;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.3;
    text-align: center;
}

.category-item a:hover span {
    color: var(--main-color, #b20a2c);
}

/* ========== 品牌网格 ========== */
.brands-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.brand-item {
    text-align: center;
}

.brand-item a {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    border-radius: 10px;
    background: #f8f9fa;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.brand-item a:hover {
    background: #fff;
    border-color: #e0e0e0;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.brand-item img {
    width: 100%;
    max-width: 70px;
    height: auto;
    object-fit: contain;
    opacity: 0.8;
    transition: all 0.3s ease;
    filter: grayscale(20%);
}

.brand-item a:hover img {
    opacity: 1;
    filter: grayscale(0%);
}

/* ========== Skeleton Loading System: Tự động phát hiện kích thước màn hình ========== */

/* Base skeleton styles */
.js-skeleton {
    position: relative;
    max-width: 100%;
    box-sizing: border-box;
    contain: layout style paint;
}

/* Khi KHÔNG có class .skeleton: không hiển thị effect */
.js-skeleton:not(.skeleton) {
    background-color: transparent;
}
.js-skeleton:not(.skeleton)::after {
    display: none !important;
}

/* Khi CÓ class .skeleton: hiển thị skeleton effect */
.js-skeleton.skeleton {
    overflow: hidden;
    background-color: #f2f2f2;
    border-radius: 4px;
    display: block;
}
.js-skeleton.skeleton::after {
    content: "";
    position: absolute;
    inset: 0;
    background-image: linear-gradient(
        90deg,
        rgba(255,255,255,0) 0%,
        rgba(255,255,255,0.6) 50%,
        rgba(255,255,255,0) 100%
    );
    transform: translateX(-100%);
    animation: skeleton-shimmer 1.4s ease-in-out infinite;
    z-index: 1;
    pointer-events: none;
}

/* Skeleton thay thế cho ảnh lỗi */
.js-skeleton.skeleton.skeleton-error {
    background-color: #e8e8e8;
    border: 1px dashed #ccc;
}
.js-skeleton.skeleton.skeleton-error::before {
    content: "📷";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 24px;
    opacity: 0.3;
    z-index: 0;
    pointer-events: none;
}

@keyframes skeleton-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Size classes - Desktop default */
.skeleton--img-sm {
    width: 60px;
    height: 60px;
    display: inline-block;
    flex-shrink: 0;
}
.skeleton--img-md {
    width: 212px;
    height: 212px;
    display: inline-block;
}
.skeleton--img-lg {
    width: 100%;
    height: auto;
    min-height: 200px;
    display: block;
}
.skeleton--img-banner {
    width: 100%;
    height: 265px;
    display: block;
}
.skeleton--img-logo {
    width: auto;
    height: auto;
    min-width: 100px;
    min-height: 50px;
    display: inline-block;
}
.skeleton--img-square {
    width: 100%;
    height: 0;
    padding-bottom: 100%;
    display: block;
    position: relative;
    aspect-ratio: 1 / 1;
}
.skeleton--img-square img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Image styles */
.js-skeleton img.js-skeleton-img {
    max-width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}

/* Card cover specific */
.card-cover {
    position: relative;
    text-align: center;
}
.card-cover .js-skeleton-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Responsive: Mobile & Tablet - Tự động điều chỉnh */
@media (max-width: 768px) {
    .js-skeleton {
        max-width: 100% !important;
        overflow: hidden !important;
    }
    
    .skeleton--img-sm {
        width: 60px !important;
        height: 60px !important;
        min-width: 40px !important;
        min-height: 40px !important;
        max-width: 15vw !important;
    }
    
    .skeleton--img-md {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 1 / 1;
    }
    
    .skeleton--img-lg {
        width: 100% !important;
        height: auto !important;
        min-height: 200px;
    }
    
    .skeleton--img-banner {
        width: 100% !important;
        height: auto !important;
        aspect-ratio: 4.4 / 1;
    }
    
    .skeleton--img-logo {
        max-width: 100% !important;
        max-height: 80px !important;
    }
    
    .skeleton--img-square {
        width: 100% !important;
        aspect-ratio: 1 / 1;
    }
}

/* Responsive: Small phones */
@media (max-width: 480px) {
    .skeleton--img-sm {
        width: 50px !important;
        height: 50px !important;
        max-width: 12vw !important;
    }
}

/* ========== 移动端样式 ========== */
@media (max-width: 991px) {
    .search-head.search_mobile {
        width: 100%;
        padding: 0 15px;
        margin: 10px 0;
    }
    
    .search_mobile .search-wrapper {
        width: 100%;
    }
    
    .search_mobile .search-wrapper input[type="search"] {
        padding: 14px 50px 14px 20px;
        font-size: 16px; /* 防止iOS自动缩放 */
        border-radius: 30px;
    }
    
    .search_mobile .search-suggestions {
        position: fixed;
        top: 0 !important;
        left: 0;
        right: 0;
        bottom: 0;
        max-height: 100vh;
        border-radius: 0;
        border: none;
        box-shadow: none;
        z-index: 9999;
        background: #fff;
    }
    
    .search_mobile .search-suggestions-content {
        padding: 20px 15px;
        height: 100%;
        overflow-y: auto;
        padding-top: 60px; /* 为关闭按钮留空间 */
    }
    
    /* 移动端关闭按钮 */
    .search_mobile .close-search-mobile {
        position: fixed;
        top: 15px;
        right: 15px;
        width: 44px;
        height: 44px;
        background: #f0f0f0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        cursor: pointer;
        z-index: 10000;
        transition: all 0.3s ease;
        padding: 0;
    }
    
    .search_mobile .close-search-mobile:hover {
        background: #e0e0e0;
        transform: scale(1.1);
    }
    
    .search_mobile .close-search-mobile svg {
        width: 20px;
        height: 20px;
        color: #333;
    }
    
    .search_mobile .categories-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .search_mobile .category-item img {
        max-width: 60px;
        height: 60px;
    }
    
    .search_mobile .category-item span {
        font-size: 11px;
    }
    
    .search_mobile .brands-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }
    
    .search_mobile .brand-item img {
        max-width: 50px;
    }
    
    .search_mobile .section-title {
        font-size: 12px;
    }
    
    .search_mobile .deals-list .deal-item a,
    .search_mobile .products-list .product-item span,
    .search_mobile .recent-searches-list .recent-item a {
        font-size: 14px;
    }
    
    /* 移动端搜索历史标签 */
    .search_mobile .recent-searches-list .recent-tag {
        padding: 5px 10px;
        font-size: 11px;
    }
    
    .search_mobile .recent-searches-list .recent-tag a {
        font-size: 11px;
        max-width: 120px;
    }
}

/* ========== 桌面端搜索框优化 ========== */
@media (min-width: 992px) {
    .search-head {
        width: 300px;
        max-width: 400px;
        margin-left: 20px;
        margin-right: 40px;
    }
    
    .search-head .search-suggestions {
        width: 500px;
        min-width: 500px;
        max-width: 600px;
        left: 0;
        right: auto;
        transform: none;
    }
    
    .search-wrapper input[type="search"] {
        font-size: 14px;
    }
}

/* ========== 空状态样式 ========== */
.search-suggestions-content:empty::before {
    content: 'Nhập từ khóa để tìm kiếm...';
    display: block;
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 14px;
}

/* ========== 加载状态 ========== */
.search-suggestions.loading .search-suggestions-content::after {
    content: '';
    display: block;
    width: 40px;
    height: 40px;
    margin: 20px auto;
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--main-color, #b20a2c);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v13.0&appId={{getConfig('facebook_api')}}&autoLogAppEvents=1" nonce="w7xgUtuR"></script>
</body>
</html>
