# 异步加载优化方案 - Lazy Loading & Intersection Observer

## 📋 当前问题分析

### 首页加载的内容（同步加载）
1. **Slider轮播图** - 首屏，应立即加载
2. **Flash Sale** - 首屏，应立即加载
3. **Brands品牌** - 可延迟加载
4. **Deals产品** - 可延迟加载
5. **Banners横幅** - 可延迟加载
6. **Categories分类** - 可延迟加载
7. **Taxonomies分类产品** - 可延迟加载
8. **Blogs博客** - 可延迟加载

### 问题
- 所有内容一次性加载，导致首屏加载慢
- 大量图片同时请求，阻塞渲染
- 用户可能不会滚动到底部，但所有内容都已加载

---

## 🚀 优化方案：Intersection Observer API

### 方案1：图片懒加载（已部分实现）
- ✅ 部分图片已使用 `loading="lazy"`
- ⚠️ 需要扩展到所有图片

### 方案2：内容区块延迟加载
使用 Intersection Observer API 实现：
- 当区块进入视口时才开始加载
- 显示加载占位符
- 异步加载内容

### 方案3：JavaScript按需加载
- Owl Carousel 只在需要时加载
- 其他非关键JS延迟加载

---

## 📝 实施步骤

### 步骤1：创建异步加载JavaScript文件

创建 `public/website/js/lazy-load.js`：

```javascript
/**
 * 异步加载优化 - Intersection Observer API
 * 实现：屏幕显示到哪里加载到哪里
 */

(function() {
    'use strict';

    // 检查浏览器支持
    if (!('IntersectionObserver' in window)) {
        // 不支持IntersectionObserver的浏览器，使用polyfill或直接加载
        console.warn('IntersectionObserver not supported, loading all content');
        return;
    }

    // 配置选项
    const config = {
        root: null, // 使用viewport作为root
        rootMargin: '50px', // 提前50px开始加载
        threshold: 0.01 // 只要1%可见就触发
    };

    // 创建Intersection Observer实例
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const target = entry.target;
                const loadType = target.dataset.lazyLoad;
                
                // 移除观察，避免重复加载
                observer.unobserve(target);
                
                // 根据类型加载内容
                switch(loadType) {
                    case 'section':
                        loadSection(target);
                        break;
                    case 'image':
                        loadImage(target);
                        break;
                    case 'carousel':
                        loadCarousel(target);
                        break;
                    case 'ajax':
                        loadAjaxContent(target);
                        break;
                }
            }
        });
    }, config);

    /**
     * 加载整个区块内容
     */
    function loadSection(element) {
        if (element.dataset.loaded === 'true') return;
        
        const url = element.dataset.url;
        const placeholder = element.querySelector('.lazy-placeholder');
        
        if (!url) {
            // 如果没有URL，只是显示隐藏的内容
            const hiddenContent = element.querySelector('.lazy-hidden-content');
            if (hiddenContent) {
                hiddenContent.style.display = '';
                if (placeholder) placeholder.style.display = 'none';
                element.dataset.loaded = 'true';
                initCarousels(element);
            }
            return;
        }
        
        // 显示加载状态
        if (placeholder) {
            placeholder.classList.add('loading');
        }
        
        // 异步加载内容
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            element.innerHTML = html;
            element.dataset.loaded = 'true';
            initCarousels(element);
        })
        .catch(error => {
            console.error('Error loading section:', error);
            if (placeholder) {
                placeholder.classList.remove('loading');
                placeholder.classList.add('error');
                placeholder.innerHTML = '<p>Không thể tải nội dung. Vui lòng thử lại.</p>';
            }
        });
    }

    /**
     * 加载图片
     */
    function loadImage(element) {
        const src = element.dataset.src;
        if (!src) return;
        
        const img = new Image();
        img.onload = function() {
            element.src = src;
            element.classList.add('loaded');
            element.removeAttribute('data-src');
        };
        img.onerror = function() {
            element.src = '/public/image/no_image.png';
            element.classList.add('error');
        };
        img.src = src;
    }

    /**
     * 加载轮播图
     */
    function loadCarousel(element) {
        if (element.dataset.loaded === 'true') return;
        
        // 加载Owl Carousel CSS和JS（如果还没加载）
        loadScript('/public/website/owl-carousel/owl.carousel-2.0.0.min.js', function() {
            initCarousel(element);
        });
    }

    /**
     * 通过AJAX加载内容
     */
    function loadAjaxContent(element) {
        if (element.dataset.loaded === 'true') return;
        
        const url = element.dataset.ajaxUrl;
        const params = element.dataset.ajaxParams ? JSON.parse(element.dataset.ajaxParams) : {};
        
        if (!url) return;
        
        const placeholder = element.querySelector('.lazy-placeholder');
        if (placeholder) {
            placeholder.classList.add('loading');
        }
        
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                ...params,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                element.innerHTML = response;
                element.dataset.loaded = 'true';
                initCarousels(element);
            },
            error: function() {
                if (placeholder) {
                    placeholder.classList.remove('loading');
                    placeholder.classList.add('error');
                }
            }
        });
    }

    /**
     * 初始化轮播图
     */
    function initCarousel(element) {
        if (typeof $.fn.owlCarousel === 'undefined') {
            console.warn('Owl Carousel not loaded');
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
        $(element).owlCarousel(config);
    }

    /**
     * 初始化所有轮播图
     */
    function initCarousels(container) {
        // 查找所有需要初始化的轮播图
        $(container).find('.list-watch, .list-flash, .list-brand, .list-banner, .slider_home').each(function() {
            if (!$(this).data('owlCarousel')) {
                const carouselType = $(this).hasClass('slider_home') ? 'slider' : 
                                   $(this).hasClass('list-brand') ? 'brand' :
                                   $(this).hasClass('list-banner') ? 'banner' : 'default';
                $(this).attr('data-carousel-type', carouselType);
                initCarousel(this);
            }
        });
    }

    /**
     * 动态加载JavaScript文件
     */
    function loadScript(src, callback) {
        // 检查是否已加载
        const existingScript = document.querySelector(`script[src="${src}"]`);
        if (existingScript) {
            if (callback) callback();
            return;
        }
        
        const script = document.createElement('script');
        script.src = src;
        script.onload = callback;
        script.onerror = function() {
            console.error('Failed to load script:', src);
        };
        document.head.appendChild(script);
    }

    /**
     * 初始化：观察所有需要延迟加载的元素
     */
    function init() {
        // 等待jQuery加载
        if (typeof $ === 'undefined') {
            setTimeout(init, 100);
            return;
        }

        // 观察所有标记为延迟加载的元素
        document.querySelectorAll('[data-lazy-load]').forEach(function(element) {
            observer.observe(element);
        });

        // 观察所有延迟加载的图片
        document.querySelectorAll('img[data-src]').forEach(function(img) {
            observer.observe(img);
        });
    }

    // DOM加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // 导出到全局
    window.LazyLoad = {
        observer: observer,
        loadSection: loadSection,
        loadImage: loadImage,
        initCarousels: initCarousels
    };
})();
```

---

### 步骤2：修改首页模板实现延迟加载

修改 `app/Themes/Website/Views/page/home.blade.php`：

```blade
{{-- Brands - 延迟加载 --}}
@if(count($brands) > 0)
<section class="brand-shop mt-3" data-lazy-load="section">
    <div class="container-lg">
        <div class="lazy-placeholder" style="min-height: 150px; display: flex; align-items: center; justify-content: center;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
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
    </div>
</section>
@endif

{{-- Deals - 延迟加载 --}}
@if(isset($deals) && count($deals) > 0)
<section class="product_home mt-5" data-lazy-load="section">
    <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">Top sản phẩm bán chạy</h2>
        <div class="lazy-placeholder" style="min-height: 300px; display: flex; align-items: center; justify-content: center;">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
        </div>
        <div class="lazy-hidden-content" style="display: none;">
            <div class="list-watch mt-3" data-carousel-type="default">
                @foreach($deals as $deal)
                @include('Website::product.item',['product' => $deal])
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
```

---

### 步骤3：添加加载占位符样式

在 `public/website/css/style.css` 添加：

```css
/* 延迟加载占位符样式 */
.lazy-placeholder {
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 8px;
}

.lazy-placeholder.loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

.lazy-placeholder.error {
    background: #fff3cd;
    color: #856404;
    padding: 20px;
    text-align: center;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* 图片懒加载 */
img[data-src] {
    opacity: 0;
    transition: opacity 0.3s;
}

img[data-src].loaded {
    opacity: 1;
}

/* 骨架屏效果 */
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    border-radius: 4px;
}
```

---

## 📊 预期效果

### 优化前
- 首屏加载时间：3-4秒
- 总加载时间：5-8秒
- 初始请求数：150+
- 首屏渲染阻塞：是

### 优化后（预期）
- 首屏加载时间：1-1.5秒 ⬇️ 60%
- 总加载时间：按需加载
- 初始请求数：30-50 ⬇️ 70%
- 首屏渲染阻塞：否

---

## 🎯 实施优先级

### 高优先级（立即实施）
1. ✅ 图片懒加载（扩展到所有图片）
2. ✅ Brands区块延迟加载
3. ✅ Deals产品区块延迟加载

### 中优先级（短期实施）
1. ✅ Banners延迟加载
2. ✅ Categories延迟加载
3. ✅ Taxonomies延迟加载

### 低优先级（长期优化）
1. ✅ Blogs延迟加载
2. ✅ Owl Carousel按需加载
3. ✅ 其他非关键内容延迟加载

---

## ⚠️ 注意事项

1. **SEO考虑**：确保搜索引擎能抓取到内容
2. **用户体验**：显示加载状态，避免空白
3. **降级方案**：不支持IntersectionObserver的浏览器直接加载
4. **性能监控**：监控加载时间和错误率

---

## Disable Cache for Data Integrity

Goal: ensure Admin and Public APIs always return real-time pricing, inventory, and Deal availability.

- Bypass server cache in API controllers/services that return pricing/inventory sensitive data.
- Add response headers for all API responses:
  - Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
  - Pragma: no-cache
  - Expires: Sat, 26 Jul 1997 05:00:00 GMT

Note: this may reduce performance but prevents stale data (price mismatch, sold-out Deal not locked).