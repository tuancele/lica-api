/**
 * 智能产品推荐系统
 * 自动跟踪用户行为并提供个性化推荐
 */
(function() {
    'use strict';

    const RecommendationEngine = {
        apiBase: '/api/recommendations',
        sessionId: null,
        trackingQueue: [],
        isTracking: false,

        /**
         * 初始化推荐引擎
         */
        init: function() {
            this.sessionId = this.getSessionId();
            this.setupAutoTracking();
            this.loadRecommendations();
        },

        /**
         * 获取会话ID
         */
        getSessionId: function() {
            let sessionId = this.getCookie('laravel_session') || 
                           localStorage.getItem('recommendation_session_id');
            
            if (!sessionId) {
                sessionId = this.generateSessionId();
                localStorage.setItem('recommendation_session_id', sessionId);
            }
            
            return sessionId;
        },

        /**
         * 生成会话ID
         */
        generateSessionId: function() {
            return 'rec_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * 获取Cookie
         */
        getCookie: function(name) {
            const value = "; " + document.cookie;
            const parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
            return null;
        },

        /**
         * 设置自动跟踪（增强版）
         */
        setupAutoTracking: function() {
            if (this.isProductPage()) {
                const productId = this.getProductId();
                if (productId) {
                    const pageTitle = document.title;
                    this.trackBehavior(productId, 'view', { 
                        page_title: pageTitle 
                    });
                    
                    let startTime = Date.now();
                    let scrollDepth = 0;
                    let viewedGallery = false;
                    let readDescription = false;
                    let clickedProduct = false;
                    
                    let maxScroll = 0;
                    window.addEventListener('scroll', () => {
                        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                        const currentScroll = Math.round((scrollTop / docHeight) * 100);
                        maxScroll = Math.max(maxScroll, currentScroll);
                        scrollDepth = maxScroll;
                    }, { passive: true });
                    
                    const galleryElements = document.querySelectorAll('.product-gallery, .gallery, [class*="gallery"]');
                    galleryElements.forEach(el => {
                        el.addEventListener('click', () => {
                            viewedGallery = true;
                        }, { once: true });
                    });
                    
                    const descriptionElement = document.querySelector('.product-description, .product-content, [class*="description"]');
                    if (descriptionElement) {
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => {
                                if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                                    readDescription = true;
                                }
                            });
                        }, { threshold: 0.5 });
                        observer.observe(descriptionElement);
                    }
                    
                    document.addEventListener('click', (e) => {
                        const productLink = e.target.closest('a[href*="/product/"], a[data-product-id]');
                        if (productLink && productLink.href !== window.location.href) {
                            clickedProduct = true;
                        }
                    });
                    
                    window.addEventListener('beforeunload', () => {
                        const duration = Math.floor((Date.now() - startTime) / 1000);
                        this.trackBehavior(productId, 'view', { 
                            duration: duration,
                            scroll_depth: scrollDepth,
                            viewed_gallery: viewedGallery,
                            read_description: readDescription,
                            clicked_product: clickedProduct,
                            page_title: pageTitle
                        });
                    });
                    
                    setInterval(() => {
                        const duration = Math.floor((Date.now() - startTime) / 1000);
                        if (duration >= 30) {
                            this.trackBehavior(productId, 'view', { 
                                duration: duration,
                                scroll_depth: scrollDepth,
                                viewed_gallery: viewedGallery,
                                read_description: readDescription,
                                clicked_product: clickedProduct
                            });
                        }
                    }, 30000);
                }
            }

            document.addEventListener('click', (e) => {
                const productLink = e.target.closest('a[href*="/product/"], a[data-product-id]');
                if (productLink) {
                    const productId = productLink.getAttribute('data-product-id') || 
                                    this.extractProductIdFromUrl(productLink.href);
                    if (productId) {
                        this.trackBehavior(productId, 'click');
                    }
                }
            });

            document.addEventListener('click', (e) => {
                const addToCartBtn = e.target.closest('.add-to-cart, [data-action="add-to-cart"]');
                if (addToCartBtn) {
                    const productId = addToCartBtn.getAttribute('data-product-id') || 
                                    this.getProductId();
                    if (productId) {
                        this.trackBehavior(productId, 'add_to_cart');
                    }
                }
            });
        },

        /**
         * 判断是否为产品页面
         */
        isProductPage: function() {
            return document.querySelector('[data-product-id]') !== null ||
                   window.location.pathname.includes('/product/') ||
                   document.body.classList.contains('product-detail');
        },

        /**
         * 获取产品ID
         */
        getProductId: function() {
            const productElement = document.querySelector('[data-product-id]');
            if (productElement) {
                return productElement.getAttribute('data-product-id');
            }

            const match = window.location.pathname.match(/\/product\/(\d+)/);
            if (match) {
                return match[1];
            }

            const metaProductId = document.querySelector('meta[property="product:id"]');
            if (metaProductId) {
                return metaProductId.getAttribute('content');
            }

            return null;
        },

        /**
         * 从URL提取产品ID
         */
        extractProductIdFromUrl: function(url) {
            const match = url.match(/\/product\/(\d+)/);
            return match ? match[1] : null;
        },

        /**
         * 跟踪用户行为
         */
        trackBehavior: function(productId, behaviorType, options = {}) {
            if (!productId) return;

            const data = {
                product_id: parseInt(productId),
                behavior_type: behaviorType,
                duration: options.duration || 0,
                scroll_depth: options.scroll_depth || 0,
                clicked_product: options.clicked_product || false,
                viewed_gallery: options.viewed_gallery || false,
                read_description: options.read_description || false,
                page_title: options.page_title || document.title,
            };

            this.trackingQueue.push(data);

            clearTimeout(this.trackingTimeout);
            this.trackingTimeout = setTimeout(() => {
                this.flushTrackingQueue();
            }, 1000);
        },

        /**
         * 发送跟踪队列
         */
        flushTrackingQueue: function() {
            if (this.isTracking || this.trackingQueue.length === 0) {
                return;
            }

            this.isTracking = true;
            const data = this.trackingQueue.shift();

            fetch(this.apiBase + '/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(result => {
            })
            .catch(error => {
                console.error('Failed to track behavior:', error);
            })
            .finally(() => {
                this.isTracking = false;
                if (this.trackingQueue.length > 0) {
                    setTimeout(() => this.flushTrackingQueue(), 500);
                }
            });
        },

        /**
         * 获取CSRF Token
         */
        getCsrfToken: function() {
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? token.getAttribute('content') : '';
        },

        /**
         * 加载推荐产品
         */
        loadRecommendations: function(containerSelector = '.product-recommendations', limit = 12) {
            const containers = document.querySelectorAll(containerSelector + ', .product-recommendations-home');
            if (containers.length === 0) return;

            containers.forEach(container => {
                const lazyContent = container.closest('.lazy-hidden-content');
                if (lazyContent && lazyContent.style.display === 'none') {
                    const observer = new MutationObserver((mutations) => {
                        if (lazyContent.style.display !== 'none') {
                            this.loadContainerRecommendations(container, limit);
                            observer.disconnect();
                        }
                    });
                    observer.observe(lazyContent, { attributes: true, attributeFilter: ['style'] });
                    return;
                }
                
                this.loadContainerRecommendations(container, limit);
            });
        },

        /**
         * 加载单个容器的推荐产品
         */
        loadContainerRecommendations: function(container, defaultLimit, append = false) {
            const excludeIds = container.getAttribute('data-exclude') || '';
            const customLimit = parseInt(container.getAttribute('data-limit')) || defaultLimit;
            const loaded = parseInt(container.getAttribute('data-loaded')) || 0;
            const offset = append ? loaded : 0;
            const limit = append ? (parseInt(container.getAttribute('data-per-load')) || 12) : customLimit;

            console.log('loadContainerRecommendations:', {
                append,
                loaded,
                offset,
                limit,
                excludeIds,
                apiBase: this.apiBase
            });

            const apiUrl = `${this.apiBase}?limit=${limit}&offset=${offset}&exclude=${excludeIds}`;
            console.log('Fetching from:', apiUrl);

            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => {
                console.log('Response status:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('API result:', result);
                if (result.success && result.data && result.data.length > 0) {
                    console.log(`Rendering ${result.data.length} products (append: ${append})`);
                    this.renderRecommendations(container, result.data, append);
                    const newLoaded = loaded + result.data.length;
                    container.setAttribute('data-loaded', newLoaded);
                    
                    // 显示/隐藏加载更多按钮
                    // 如果返回的数据少于请求的数量，说明没有更多数据了
                    const hasMore = result.data.length >= limit;
                    console.log('Has more products:', hasMore);
                    this.updateLoadMoreButton(container, !hasMore);
                } else {
                    console.log('No more products or API returned failure');
                    // 没有更多数据或API返回失败
                    if (!append) {
                        this.renderFallback(container);
                    }
                    // 隐藏加载更多按钮
                    this.updateLoadMoreButton(container, true);
                }
            })
            .catch(error => {
                console.error('Failed to load recommendations:', error);
                // 重置按钮状态
                if (append) {
                    console.log('Resetting button state after error');
                    this.updateLoadMoreButton(container, false, true);
                } else {
                    this.renderFallback(container);
                    this.updateLoadMoreButton(container, true);
                }
            });
        },

        /**
         * 更新加载更多按钮状态
         */
        updateLoadMoreButton: function(container, hideButton = false, resetButton = false) {
            const wrapper = container.closest('.lazy-hidden-content')?.querySelector('.recommendations-load-more-wrapper');
            if (wrapper) {
                if (hideButton) {
                    wrapper.style.display = 'none';
                } else {
                    wrapper.style.display = 'block';
                    const btn = wrapper.querySelector('.recommendations-load-more-btn');
                    if (btn) {
                        btn.disabled = false;
                        const btnText = btn.querySelector('.btn-text');
                        const btnLoading = btn.querySelector('.btn-loading');
                        if (btnText) {
                            btnText.style.display = 'inline';
                        }
                        if (btnLoading) {
                            btnLoading.style.display = 'none';
                        }
                    }
                }
                
                // 如果需要重置按钮状态（例如在错误后）
                if (resetButton) {
                    const btn = wrapper.querySelector('.recommendations-load-more-btn');
                    if (btn) {
                        btn.disabled = false;
                        const btnText = btn.querySelector('.btn-text');
                        const btnLoading = btn.querySelector('.btn-loading');
                        if (btnText) {
                            btnText.style.display = 'inline';
                        }
                        if (btnLoading) {
                            btnLoading.style.display = 'none';
                        }
                    }
                }
            }
        },

        /**
         * 渲染推荐产品
         */
        renderRecommendations: function(container, products, append = false) {
            if (!container) return;

            const isHomePage = container.classList.contains('product-recommendations-home');
            const isGrid3x6 = container.classList.contains('recommendations-grid-3x6');
            const isGrid6x6 = container.classList.contains('recommendations-grid-6x6');
            
            // 如果容器有 recommendations-grid-3x6 或 recommendations-grid-6x6 类，使用与首页相同的结构
            if (isHomePage || isGrid3x6 || isGrid6x6) {
                const html = products.map(product => {
                    const hasDiscount = product.original_price > product.price;
                    const discountPercent = hasDiscount ? Math.round((1 - product.price / product.original_price) * 100) : 0;
                    
                    // 计算平均评分
                    const averageRate = product.rating || 0;
                    const rateCount = product.review_count || 0;
                    const formattedRate = averageRate.toFixed(1);
                    
                    // 获取销售数量（如果 API 提供了）
                    const totalSold = product.total_sold || product.sold_count || 0;
                    const formattedSold = new Intl.NumberFormat('vi-VN').format(totalSold);
                    
                    return `
                        <div class="item-product text-center" data-product-id="${product.id}">
                            <div class="card-cover">
                                <a href="${product.url}">
                                    <div class="skeleton--img-md js-skeleton">
                                        <img src="${product.image}" alt="${this.escapeHtml(product.name)}" width="212" height="212" class="js-skeleton-img" loading="lazy">
                                    </div>
                                </a>
                                <div class="group-wishlist-${product.id}">
                                    <button class="btn_login_wishlist" type="button" data-bs-toggle="modal" data-bs-target="#myLogin">
                                        <svg width="20" height="20" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.7858 26C14.2619 26 13.738 25.8422 13.2869 25.5124C11.5696 24.2648 8.26609 21.7408 5.3846 18.7579C1.81912 15.0436 0 11.7739 0 9.03475C0 4.04413 3.85654 0 8.58626 0C10.9438 0 13.1704 1.00386 14.7858 2.79647C16.4158 1.00386 18.6424 0 20.9999 0C25.7297 0 29.5862 4.04413 29.5862 9.03475C29.5862 11.7739 27.7671 15.0436 24.1871 18.7579C21.3201 21.7408 18.002 24.2791 16.2848 25.5124C15.8482 25.8422 15.3097 26 14.7858 26ZM8.58626 1.00386C4.40955 1.00386 1.01871 4.60342 1.01871 9.03475C1.01871 14.9288 10.8711 22.5295 13.8981 24.7093C14.4366 25.0965 15.1497 25.0965 15.6881 24.7093C18.7151 22.5295 28.5675 14.9288 28.5675 9.03475C28.5675 4.60342 25.1767 1.00386 20.9999 1.00386C18.7588 1.00386 16.6341 2.05074 15.1933 3.88638L14.7858 4.38831L14.3783 3.88638C12.9522 2.05074 10.8274 1.00386 8.58626 1.00386Z" fill="black"></path>
                                        </svg>
                                    </button>
                                </div>
                                ${hasDiscount && discountPercent > 0 ? `
                                <div class="tag tag-discount"><span>-${discountPercent}%</span></div>
                                ` : ''}
                                ${product.stock === 0 ? `
                                <div class="out-stock">Hết hàng</div>
                                ` : ''}
                                ${product.price_label ? `
                                <div class="status-product">
                                    <div class="deal-hot mb-2">${product.price_label}</div>
                                </div>
                                ` : ''}
                            </div>
                            <div class="card-content mt-2">
                                <div class="price">
                                    <p>${this.formatPriceVND(product.price)}</p>
                                    ${hasDiscount ? `<del>${this.formatPriceVND(product.original_price)}</del>` : ''}
                                </div>
                                ${product.brand_name ? `
                                <div class="brand-btn">
                                    <a href="${product.brand_url || '#'}">${this.escapeHtml(product.brand_name)}</a>
                                </div>
                                ` : ''}
                                <div class="product-name">
                                    <a href="${product.url}">${this.escapeHtml(product.name)}</a>
                                </div>
                                ${product.deal_name ? `
                                <div class="deal-voucher">
                                    <div class="deal-discount-badge">${product.deal_discount_percent > 0 ? product.deal_discount_percent + '%' : ''}</div>
                                    <span class="deal-name">${this.escapeHtml(product.deal_name)}</span>
                                </div>
                                ` : ''}
                                <div class="rating-info">
                                    <div class="rating-score">
                                        <span class="rating-value">${formattedRate}</span>
                                        <span class="rating-count">(${rateCount})</span>
                                    </div>
                                    <div class="sales-count">
                                        <span class="sales-label">Đã bán ${formattedSold}/tháng</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');

                if (append) {
                    container.insertAdjacentHTML('beforeend', html);
                    // 在追加模式下，确保新添加的产品也应用网格布局样式
                    const newItems = container.querySelectorAll('.item-product');
                    if (newItems.length > 0) {
                        newItems.forEach(item => {
                            item.style.minWidth = 'auto';
                            item.style.width = '100%';
                            item.style.maxWidth = '100%';
                            item.style.marginRight = '0';
                            item.style.marginBottom = '0';
                        });
                    }
                    // 初始化新添加产品的 skeleton 优化器
                    if (window.initSmartSkeleton) {
                        window.initSmartSkeleton();
                    }
                } else {
                    container.innerHTML = html;
                    this.initCarousel(container);
                    // 设置加载更多按钮事件
                    this.setupLoadMoreButton(container);
                    // 初始化 skeleton 优化器
                    if (window.initSmartSkeleton) {
                        window.initSmartSkeleton();
                    }
                }
            } else {
                const html = products.map(product => `
                    <div class="recommended-product-item" data-product-id="${product.id}">
                        <a href="${product.url}" class="product-link">
                            <div class="product-image">
                                <img src="${product.image}" alt="${this.escapeHtml(product.name)}" loading="lazy">
                                ${product.price_label ? `<span class="price-badge">${product.price_label}</span>` : ''}
                            </div>
                            <div class="product-info">
                                <h3 class="product-name">${this.escapeHtml(product.name)}</h3>
                                <div class="product-price">
                                    ${product.original_price > product.price ? 
                                        `<span class="original-price">${this.formatPrice(product.original_price)}</span>` : ''}
                                    <span class="current-price">${this.formatPrice(product.price)}</span>
                                </div>
                            </div>
                        </a>
                    </div>
                `).join('');

                container.innerHTML = `
                    <div class="recommendations-grid">
                        ${html}
                    </div>
                `;
                this.injectStyles();
            }
        },

        /**
         * 渲染后备内容
         */
        renderFallback: function(container) {
            if (!container) return;
            container.innerHTML = '<div class="recommendations-loading">Đang tải sản phẩm đề xuất...</div>';
        },

        /**
         * 注入样式
         */
        injectStyles: function() {
            if (document.getElementById('recommendation-styles')) return;

            const style = document.createElement('style');
            style.id = 'recommendation-styles';
            style.textContent = `
                .recommendations-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 20px;
                    padding: 20px 0;
                }
                .recommended-product-item {
                    background: #fff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    transition: transform 0.3s, box-shadow 0.3s;
                }
                .recommended-product-item:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                .recommended-product-item .product-link {
                    display: block;
                    text-decoration: none;
                    color: inherit;
                }
                .recommended-product-item .product-image {
                    position: relative;
                    width: 100%;
                    padding-top: 100%;
                    overflow: hidden;
                    background: #f5f5f5;
                }
                .recommended-product-item .product-image img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .recommended-product-item .price-badge {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    background: var(--main-color, #b20a2c);
                    color: #fff;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .recommended-product-item .product-info {
                    padding: 15px;
                }
                .recommended-product-item .product-name {
                    font-size: 14px;
                    font-weight: 500;
                    margin: 0 0 10px 0;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                    line-height: 1.4;
                    min-height: 40px;
                }
                .recommended-product-item .product-price {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                .recommended-product-item .original-price {
                    color: #999;
                    text-decoration: line-through;
                    font-size: 13px;
                }
                .recommended-product-item .current-price {
                    color: var(--main-color, #b20a2c);
                    font-size: 16px;
                    font-weight: bold;
                }
                .recommendations-loading {
                    text-align: center;
                    padding: 40px;
                    color: #666;
                }
            `;
            document.head.appendChild(style);
        },

        /**
         * HTML转义
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        /**
         * 格式化价格
         */
        formatPrice: function(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(price);
        },

        /**
         * 格式化价格为VND格式
         */
        formatPriceVND: function(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
        },

        /**
         * 渲染评分星星
         */
        renderStars: function(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            
            let html = '<ul class="list-rate">';
            
            for (let i = 0; i < fullStars; i++) {
                html += '<li class="icon-star active"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3z"></path></svg></li>';
            }
            
            if (hasHalfStar) {
                html += '<li class="icon-star half"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3z"></path></svg></li>';
            }
            
            for (let i = 0; i < emptyStars; i++) {
                html += '<li class="icon-star"><svg viewBox="64 64 896 896" focusable="false" data-icon="star" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M908.1 353.1l-253.9-36.9L540.7 86.1c-3.1-6.3-8.2-11.4-14.5-14.5-15.8-7.8-35-1.3-42.9 14.5L369.8 316.2l-253.9 36.9c-7 1-13.4 4.3-18.3 9.3a32.05 32.05 0 00.6 45.3l183.7 179.1-43.4 252.9a31.95 31.95 0 0046.4 33.7L512 754l227.1 119.4c6.2 3.3 13.4 4.4 20.3 3.2 17.4-3 29.1-19.5 26.1-36.9l-43.4-252.9 183.7-179.1c5-4.9 8.3-11.3 9.3-18.3 2.7-17.5-9.5-33.7-27-36.3z"></path></svg></li>';
            }
            
            html += '</ul>';
            return html;
        },

        /**
         * 设置加载更多按钮
         */
        setupLoadMoreButton: function(container) {
            const lazyContent = container.closest('.lazy-hidden-content');
            if (!lazyContent) {
                console.warn('setupLoadMoreButton: lazy-hidden-content not found');
                return;
            }
            
            const wrapper = lazyContent.querySelector('.recommendations-load-more-wrapper');
            if (!wrapper) {
                console.warn('setupLoadMoreButton: recommendations-load-more-wrapper not found');
                return;
            }
            
            const btn = wrapper.querySelector('.recommendations-load-more-btn');
            if (!btn) {
                console.warn('setupLoadMoreButton: recommendations-load-more-btn not found');
                return;
            }
            
            // 移除旧的事件监听器（通过克隆节点）
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            
            // 添加新的事件监听器
            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // 更新按钮状态
                newBtn.disabled = true;
                const btnText = newBtn.querySelector('.btn-text');
                const btnLoading = newBtn.querySelector('.btn-loading');
                if (btnText) {
                    btnText.style.display = 'none';
                }
                if (btnLoading) {
                    btnLoading.style.display = 'inline';
                }
                
                // 加载更多产品
                console.log('Loading more recommendations...');
                this.loadContainerRecommendations(container, 0, true);
            });
        },

        /**
         * 初始化轮播（已禁用 - 使用CSS Grid布局）
         */
        initCarousel: function(container) {
            if (typeof jQuery !== 'undefined' && typeof jQuery.fn.owlCarousel !== 'undefined') {
                const $container = jQuery(container);
                const isNoCarousel = $container.hasClass('recommendations-no-carousel');
                const isGrid3x6 = $container.hasClass('recommendations-grid-3x6');
                const isGrid6x6 = $container.hasClass('recommendations-grid-6x6');
                
                // 如果标记为不使用carousel，直接使用CSS Grid布局
                if (isNoCarousel || isGrid3x6 || isGrid6x6) {
                    const windowWidth = window.innerWidth;
                    let gridColumns = 6;
                    
                    // 响应式列数
                    if (windowWidth < 480) {
                        gridColumns = 2;
                    } else if (windowWidth < 768) {
                        gridColumns = 3;
                    } else if (windowWidth < 1000) {
                        gridColumns = 4;
                    } else {
                        gridColumns = 6;
                    }
                    
                    if (isGrid3x6) {
                        // 使用CSS Grid显示响应式网格布局
                        $container.css({
                            'display': 'grid',
                            'grid-template-columns': `repeat(${gridColumns}, 1fr)`,
                            'grid-auto-rows': 'auto',
                            'gap': '20px',
                            'width': '100%',
                            'margin': '0 auto',
                            'justify-content': 'start',
                            'justify-items': 'start',
                            'box-sizing': 'border-box'
                        });
                    } else if (isGrid6x6) {
                        // 使用CSS Grid显示6行x6列
                        $container.css({
                            'display': 'grid',
                            'grid-template-columns': `repeat(${gridColumns}, 1fr)`,
                            'grid-auto-rows': 'auto',
                            'gap': '20px',
                            'width': '100%',
                            'margin': '0 auto',
                            'justify-content': 'start',
                            'justify-items': 'start',
                            'box-sizing': 'border-box'
                        });
                    }
                    $container.find('.item-product').css({
                        'min-width': 'auto',
                        'width': '100%',
                        'max-width': '100%',
                        'margin-right': '0',
                        'margin-bottom': '0'
                    });
                    
                    // 监听窗口大小变化
                    let resizeTimer;
                    $(window).on('resize', function() {
                        clearTimeout(resizeTimer);
                        resizeTimer = setTimeout(function() {
                            const newWidth = window.innerWidth;
                            let newGridColumns = 6;
                            
                            if (newWidth < 480) {
                                newGridColumns = 2;
                            } else if (newWidth < 768) {
                                newGridColumns = 3;
                            } else if (newWidth < 1000) {
                                newGridColumns = 4;
                            } else {
                                newGridColumns = 6;
                            }
                            
                            $container.css('grid-template-columns', `repeat(${newGridColumns}, 1fr)`);
                        }, 250);
                    });
                    
                    return;
                }
            }
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            RecommendationEngine.init();
        });
    } else {
        RecommendationEngine.init();
    }

    window.ProductRecommendation = RecommendationEngine;
})();
