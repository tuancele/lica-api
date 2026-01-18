/**
 * 异步加载优化 - 简化版本，确保可靠工作
 * 参考正常显示的 block，修复 lazy loading
 */

(function() {
    'use strict';

    // 检查浏览器支持
    if (!('IntersectionObserver' in window)) {
        // 不支持IntersectionObserver的浏览器，直接显示所有内容
        document.querySelectorAll('.lazy-hidden-content').forEach(function(el) {
            el.style.display = '';
        });
        document.querySelectorAll('.lazy-placeholder').forEach(function(el) {
            el.style.display = 'none';
        });
        return;
    }

    // 配置选项 - 优化性能
    const observerConfig = {
        root: null,
        rootMargin: '500px', // 提前500px开始加载（减少初始观察范围）
        threshold: 0.01 // 单一阈值，减少计算
    };

    // 创建Intersection Observer实例
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const target = entry.target;
                
                if (target.dataset.loaded === 'true') {
                    observer.unobserve(target);
                    return;
                }
                
                loadSection(target);
            }
        });
    }, observerConfig);

    /**
     * 加载整个区块内容
     */
    function loadSection(element) {
        if (element.dataset.loaded === 'true') {
            return;
        }
        
        element.dataset.loaded = 'true';
        
        const placeholder = element.querySelector('.lazy-placeholder');
        const hiddenContent = element.querySelector('.lazy-hidden-content');
        
        if (!hiddenContent) {
            console.warn('No hidden content found for:', element.className);
            return;
        }
        
        // 隐藏占位符
        if (placeholder) {
            placeholder.style.display = 'none';
        }
        
        // 显示隐藏的内容
        hiddenContent.style.display = '';
        
        // 使用 requestAnimationFrame 优化初始化时机
        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                initCarousels(element);
                
                // 初始化新显示内容的 skeleton 优化器
                if (window.initSmartSkeleton) {
                    // 查找新显示内容中的所有 skeleton 元素
                    const newSkeletons = element.querySelectorAll('.js-skeleton:not([data-skeleton-processed])');
                    if (newSkeletons.length > 0) {
                        window.initSmartSkeleton();
                    }
                }
            });
        });
    }

    /**
     * 初始化轮播图
     */
    function initCarousel(element) {
        if (typeof $ === 'undefined' || typeof $.fn === 'undefined' || typeof $.fn.owlCarousel === 'undefined') {
            return;
        }
        
        const carouselType = element.dataset.carouselType || 'default';
        const configs = {
            'default': {
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                responsiveclass: true,
                autoplay: true,
                dots: false,
                loop: true,
                autoWidth: true,
                responsive: {
                    0: { items: 2, nav: true },
                    768: { items: 3, nav: true },
                    1000: { items: 4, nav: true }
                }
            },
            'slider': {
                loop: true,
                items: 1,
                margin: 10,
                singleItem: true,
                autoplay: true,
                autoplayTimeout: 5000,
                autoplaySpeed: 1000,
                nav: true,
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>']
            },
            'brand': {
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                responsiveclass: true,
                autoplay: true,
                dots: false,
                autoWidth: true,
                responsive: {
                    0: { items: 2, nav: true },
                    768: { items: 3, nav: true },
                    1000: { items: 5, nav: true, loop: true }
                }
            },
            'banner': {
                navText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>'],
                responsiveclass: true,
                autoplay: true,
                dots: false,
                autoWidth: true,
                responsive: {
                    0: { items: 1, nav: true },
                    768: { items: 2, nav: true },
                    1000: { items: 3, nav: true, loop: true }
                }
            }
        };
        
        const config = configs[carouselType] || configs['default'];
        
        if ($(element).data('owlCarousel')) {
            return;
        }
        
        $(element).owlCarousel(config);
    }

    /**
     * 初始化所有轮播图
     */
    function initCarousels(container) {
        if (typeof $ === 'undefined') return;
        
        $(container).find('.list-watch, .list-flash, .list-brand, .list-banner, .slider_home').each(function() {
            const $this = $(this);
            
            // 跳过skeleton placeholder中的元素
            if ($this.closest('.lazy-placeholder').length > 0) {
                return;
            }
            
            // 跳过标记为不初始化的元素
            if ($this.attr('data-owl-carousel-disabled') === 'true') {
                return;
            }
            
            // 跳过不使用carousel的品牌网格、推荐产品和Top sản phẩm bán chạy
            if ($this.hasClass('brand-grid-no-carousel') || $this.hasClass('recommendations-no-carousel') || $this.hasClass('deals-no-carousel')) {
                return;
            }
            
            if (!$(this).data('owlCarousel')) {
                
                let carouselType = 'default';
                
                if ($this.hasClass('slider_home')) {
                    carouselType = 'slider';
                } else if ($this.hasClass('list-brand')) {
                    carouselType = 'brand';
                } else if ($this.hasClass('list-banner')) {
                    carouselType = 'banner';
                }
                
                $this.attr('data-carousel-type', carouselType);
                initCarousel(this);
            }
        });
    }

    /**
     * 检查并加载可见元素（滚动备用方案）
     */
    function checkAndLoadVisible() {
        const lazyElements = document.querySelectorAll('[data-lazy-load]:not([data-loaded="true"])');
        
        lazyElements.forEach(function(element) {
            const rect = element.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const scrollY = window.scrollY || window.pageYOffset;
            const elementTop = rect.top + scrollY;
            const viewportBottom = scrollY + windowHeight;
            
            const inViewport = (
                elementTop < viewportBottom + 1000 && 
                elementTop > scrollY - 500
            );
            
            if (inViewport) {
                loadSection(element);
            }
        });
    }

    // 滚动事件监听
    let scrollTimer = null;
    
    function handleScroll() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            checkAndLoadVisible();
        }, 100);
    }
    
    // 绑定滚动事件
    function attachScrollListeners() {
        window.addEventListener('scroll', handleScroll, { passive: true });
        window.addEventListener('resize', handleScroll, { passive: true });
    }

    /**
     * 阻止skeleton placeholder中的元素被初始化carousel
     */
    function preventSkeletonCarouselInit() {
        if (typeof $ === 'undefined') return;
        
        // 查找所有skeleton placeholder中的.list-flash元素
        $('.lazy-placeholder .list-flash, .lazy-placeholder .list-watch').each(function() {
            const $this = $(this);
            // 如果已经有owlCarousel，销毁它
            if ($this.data('owlCarousel')) {
                $this.trigger('destroy.owl.carousel');
                $this.removeClass('owl-carousel owl-loaded owl-hidden');
                $this.find('.owl-stage-outer, .owl-stage, .owl-item, .owl-controls, .owl-nav, .owl-prev, .owl-next').remove();
            }
            // 标记为不初始化
            $this.attr('data-owl-carousel-disabled', 'true');
        });
    }

    /**
     * 初始化：观察所有需要延迟加载的元素
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // 使用 requestIdleCallback 优化初始化时机
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(init, { timeout: 2000 });
                } else {
                    setTimeout(init, 50);
                }
            });
            return;
        }

        // 立即阻止skeleton placeholder中的carousel初始化
        preventSkeletonCarouselInit();

        // 确保首屏的 skeleton 立即可见
        const lazyElements = document.querySelectorAll('[data-lazy-load]');
        
        // 先确保所有 lazy-placeholder 可见（如果它们的父 section 被隐藏）
        lazyElements.forEach(function(element) {
            // 如果 section 被隐藏，显示它以便 skeleton 可见
            if (element.style.display === 'none') {
                element.style.display = '';
            }
            
            // 确保 placeholder 可见
            const placeholder = element.querySelector('.lazy-placeholder');
            if (placeholder && placeholder.style.display === 'none') {
                placeholder.style.display = '';
            }
        });
        
        // 使用 requestIdleCallback 分批处理，避免阻塞主线程
        const processElements = function(index) {
            if (index >= lazyElements.length) {
                // 绑定滚动事件
                attachScrollListeners();
                return;
            }
            
            const element = lazyElements[index];
            if (element.dataset.loaded === 'true') {
                processElements(index + 1);
                return;
            }
            
            const rect = element.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const scrollY = window.scrollY || window.pageYOffset;
            const elementTop = rect.top + scrollY;
            const viewportBottom = scrollY + windowHeight;
            
            // 检查是否在首屏或接近首屏（立即加载）
            const inViewport = (
                elementTop < viewportBottom + 500 && 
                elementTop > scrollY - 200
            );
            
            if (inViewport) {
                // 首屏内容立即加载
                loadSection(element);
            } else {
                // 其他内容使用Intersection Observer观察
                observer.observe(element);
            }
            
            // 使用 requestIdleCallback 继续处理下一个元素
            if ('requestIdleCallback' in window && index < lazyElements.length - 1) {
                requestIdleCallback(function() {
                    processElements(index + 1);
                }, { timeout: 100 });
            } else {
                processElements(index + 1);
            }
        };
        
        // 开始处理
        if ('requestIdleCallback' in window) {
            requestIdleCallback(function() {
                processElements(0);
            }, { timeout: 2000 });
        } else {
            processElements(0);
        }
    }

    // 导出到全局
    window.LazyLoad = {
        observer: observer,
        loadSection: loadSection,
        initCarousels: initCarousels,
        checkAndLoadVisible: checkAndLoadVisible,
        init: init
    };

    // 执行初始化 - 优化启动时机
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            if ('requestIdleCallback' in window) {
                requestIdleCallback(init, { timeout: 1000 });
            } else {
                setTimeout(init, 50);
            }
        });
    } else {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(init, { timeout: 1000 });
        } else {
            setTimeout(init, 50);
        }
    }
})();
