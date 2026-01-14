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

    // 配置选项 - 使用更大的rootMargin提前加载
    const observerConfig = {
        root: null,
        rootMargin: '1000px', // 提前1000px开始加载
        threshold: [0, 0.01] // 多个阈值确保触发
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
        
        // 延迟初始化轮播图，确保DOM已更新
        setTimeout(function() {
            initCarousels(element);
        }, 300);
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
            if (!$(this).data('owlCarousel')) {
                const $this = $(this);
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
     * 初始化：观察所有需要延迟加载的元素
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(init, 100);
            });
            return;
        }

        const lazyElements = document.querySelectorAll('[data-lazy-load]');
        console.log('Initializing lazy load, found', lazyElements.length, 'elements');
        
        // 先检查首屏元素并立即加载
        lazyElements.forEach(function(element, index) {
            if (element.dataset.loaded === 'true') {
                return;
            }
            
            const rect = element.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const scrollY = window.scrollY || window.pageYOffset;
            
            // 计算元素距离顶部的绝对位置
            const elementTop = rect.top + scrollY;
            const viewportBottom = scrollY + windowHeight;
            
            // 检查是否在首屏或接近首屏（立即加载）
            const inViewport = (
                elementTop < viewportBottom + 1000 && 
                elementTop > scrollY - 500
            );
            
            if (inViewport) {
                // 首屏内容立即加载
                console.log('Loading immediately (in viewport):', element.className, 'elementTop:', elementTop);
                loadSection(element);
            } else {
                // 其他内容使用Intersection Observer观察
                console.log('Observing:', element.className, 'elementTop:', elementTop);
                observer.observe(element);
            }
        });

        // 绑定滚动事件
        attachScrollListeners();
        
        // 初始检查一次（延迟更久确保DOM完全渲染）
        setTimeout(function() {
            checkAndLoadVisible();
        }, 800);
    }

    // 导出到全局
    window.LazyLoad = {
        observer: observer,
        loadSection: loadSection,
        initCarousels: initCarousels,
        checkAndLoadVisible: checkAndLoadVisible,
        init: init
    };

    // 执行初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(init, 200);
        });
    } else {
        setTimeout(init, 200);
    }

    window.addEventListener('load', function() {
        setTimeout(init, 100);
    });
})();
