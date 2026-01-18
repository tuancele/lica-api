/**
 * 智能 Skeleton 优化器 - 增强版
 * 自动调整 skeleton 容器尺寸以匹配实际图片尺寸
 * 支持移动端、动态内容、图片加载失败等所有场景
 */
(function() {
    'use strict';

    // 设备检测缓存
    let deviceInfo = null;
    let resizeTimer = null;

    /**
     * 检测移动设备信息
     */
    function detectMobileDevice() {
        if (deviceInfo) {
            return deviceInfo;
        }

        const width = window.innerWidth || document.documentElement.clientWidth;
        const height = window.innerHeight || document.documentElement.clientHeight;
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        
        const isMobile = width <= 768 || 
                        /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent.toLowerCase());
        const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        deviceInfo = {
            isMobile: isMobile || isTouch,
            screenWidth: width,
            screenHeight: height,
            isPortrait: height > width,
            isLandscape: width > height,
            deviceType: width <= 480 ? 'phone' : (width <= 768 ? 'tablet' : 'desktop')
        };
        
        return deviceInfo;
    }

    /**
     * 初始化智能 skeleton
     * 在图片加载后，将 skeleton 容器调整为与图片相同的尺寸
     * 支持所有类型的 skeleton: skeleton--img-md, skeleton--img-sm, skeleton--img-lg, skeleton--img-square, skeleton--img-logo
     */
    function initSmartSkeleton() {
        // 查找所有带有 js-skeleton 类的容器（包括所有类型）
        const skeletonContainers = document.querySelectorAll('.js-skeleton:not([data-skeleton-processed])');
        
        skeletonContainers.forEach(function(container) {
            // 标记为已处理，避免重复处理
            container.setAttribute('data-skeleton-processed', 'true');
            
            const img = container.querySelector('.js-skeleton-img');
            
            if (!img) {
                // 如果没有图片，处理纯 skeleton 元素
                handleSkeletonWithoutImage(container);
                return;
            }
            
            // 如果图片已经加载完成
            if (img.complete && img.naturalWidth > 0) {
                adjustSkeletonSize(container, img);
            } else {
                // 等待图片加载完成
                img.addEventListener('load', function() {
                    adjustSkeletonSize(container, img);
                }, { once: true });
                
                // 如果图片加载失败，使用默认尺寸
                img.addEventListener('error', function() {
                    handleImageLoadError(container, img);
                }, { once: true });
            }
        });
    }

    /**
     * 处理没有图片的 skeleton 元素
     */
    function handleSkeletonWithoutImage(container) {
        const device = detectMobileDevice();
        
        // 根据设备类型设置默认尺寸
        if (device.isMobile) {
            if (container.classList.contains('skeleton--img-sm')) {
                container.style.width = '60px';
                container.style.height = '60px';
            } else if (container.classList.contains('skeleton--img-md')) {
                container.style.width = '100%';
                container.style.aspectRatio = '1 / 1';
            } else if (container.classList.contains('skeleton--img-lg')) {
                container.style.width = '100%';
                container.style.minHeight = '200px';
            }
        }
    }

    /**
     * 处理图片加载失败的情况
     */
    function handleImageLoadError(container, img) {
        const device = detectMobileDevice();
        const parent = container.parentElement;
        
        if (!parent) return;
        
        const parentRect = parent.getBoundingClientRect();
        const parentWidth = parentRect.width || 200;
        const parentHeight = parentRect.height || 200;
        
        // 根据 skeleton 类型设置默认尺寸
        if (container.classList.contains('skeleton--img-sm')) {
            const size = device.isMobile ? Math.min(60, parentWidth * 0.3) : 60;
            container.style.width = size + 'px';
            container.style.height = size + 'px';
        } else if (container.classList.contains('skeleton--img-md')) {
            container.style.width = '100%';
            container.style.height = Math.min(parentHeight, 212) + 'px';
            if (device.isMobile) {
                container.style.aspectRatio = '1 / 1';
            }
        } else if (container.classList.contains('skeleton--img-lg')) {
            container.style.width = '100%';
            container.style.height = Math.min(parentHeight, 265) + 'px';
        } else {
            // 默认使用父容器尺寸
            container.style.width = parentWidth + 'px';
            container.style.height = parentHeight + 'px';
        }
    }

    /**
     * 调整 skeleton 容器尺寸以匹配图片
     * 增强版：支持移动端、响应式、边缘情况处理
     */
    function adjustSkeletonSize(container, img) {
        // 等待一帧，确保图片已完全渲染
        requestAnimationFrame(function() {
            // 获取图片的实际渲染尺寸（考虑 object-fit: contain 的影响）
            const imgRect = img.getBoundingClientRect();
            const imgWidth = imgRect.width;
            const imgHeight = imgRect.height;
            
            // 如果图片尺寸无效，尝试使用 naturalWidth/Height
            let actualWidth = imgWidth;
            let actualHeight = imgHeight;
            
            if ((actualWidth <= 0 || actualHeight <= 0) && img.naturalWidth > 0 && img.naturalHeight > 0) {
                // 使用图片的原始尺寸
                actualWidth = img.naturalWidth;
                actualHeight = img.naturalHeight;
            }
            
            // 如果图片尺寸仍然无效，使用默认值
            if (actualWidth <= 0 || actualHeight <= 0) {
                handleImageLoadError(container, img);
                return;
            }
            
            // 获取容器的父元素（通常是 card-cover 或 a 标签）
            const parent = container.parentElement;
            if (!parent) return;
            
            const parentRect = parent.getBoundingClientRect();
            const parentWidth = parentRect.width || window.innerWidth;
            const parentHeight = parentRect.height || window.innerHeight;
            
            // 检测设备类型
            const device = detectMobileDevice();
            
            // 计算图片的宽高比
            const imgAspectRatio = actualWidth / actualHeight;
            
            // 根据 skeleton 类型和设备类型计算目标尺寸
            let targetWidth = actualWidth;
            let targetHeight = actualHeight;
            
            // 移动端特殊处理
            if (device.isMobile) {
                if (container.classList.contains('skeleton--img-sm')) {
                    // 小图片：在移动端使用响应式尺寸
                    targetWidth = Math.min(60, parentWidth * 0.15);
                    targetHeight = targetWidth;
                } else if (container.classList.contains('skeleton--img-md')) {
                    // 中等图片：100% 宽度，保持 1:1 比例
                    targetWidth = parentWidth;
                    targetHeight = targetWidth;
                } else if (container.classList.contains('skeleton--img-lg')) {
                    // 大图片：100% 宽度，最小高度 200px
                    targetWidth = parentWidth;
                    targetHeight = Math.max(200, targetWidth / 4.4);
                } else if (container.classList.contains('skeleton--img-square')) {
                    // 方形：100% 宽度，1:1 比例
                    targetWidth = parentWidth;
                    targetHeight = targetWidth;
                } else {
                    // 其他类型：保持图片宽高比，但限制在父容器内
                    if (targetWidth > parentWidth) {
                        targetWidth = parentWidth;
                        targetHeight = targetWidth / imgAspectRatio;
                    }
                    if (targetHeight > parentHeight) {
                        targetHeight = parentHeight;
                        targetWidth = targetHeight * imgAspectRatio;
                    }
                }
            } else {
                // 桌面端：保持图片宽高比，限制在父容器内
                if (targetWidth > parentWidth) {
                    targetWidth = parentWidth;
                    targetHeight = targetWidth / imgAspectRatio;
                }
                if (targetHeight > parentHeight) {
                    targetHeight = parentHeight;
                    targetWidth = targetHeight * imgAspectRatio;
                }
            }
            
            // 确保尺寸不超过父容器
            targetWidth = Math.min(targetWidth, parentWidth);
            targetHeight = Math.min(targetHeight, parentHeight);
            
            // 确保最小尺寸
            if (targetWidth < 10) targetWidth = 10;
            if (targetHeight < 10) targetHeight = 10;
            
            // 应用精确尺寸到 skeleton 容器
            container.style.width = targetWidth + 'px';
            container.style.height = targetHeight + 'px';
            container.style.minWidth = targetWidth + 'px';
            container.style.minHeight = targetHeight + 'px';
            container.style.maxWidth = '100%';
            container.style.maxHeight = '100%';
            
            // 使用 aspect-ratio 保持比例（现代浏览器支持）
            if (CSS.supports('aspect-ratio', '1 / 1')) {
                container.style.aspectRatio = imgAspectRatio + ' / 1';
            }
            
            // 确保不会溢出
            container.style.overflow = 'hidden';
            container.style.boxSizing = 'border-box';
            
            // 移除可能导致尺寸不匹配的样式
            container.style.flex = '0 0 auto';
        });
    }

    /**
     * 使用 Intersection Observer 优化性能
     * 只处理可见区域的 skeleton
     */
    function initLazySkeletonOptimizer() {
        if (!window.IntersectionObserver) {
            // 如果不支持 IntersectionObserver，直接初始化所有
            initSmartSkeleton();
            return;
        }

        const skeletonContainers = document.querySelectorAll('.js-skeleton');
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const container = entry.target;
                    
                    // 移除已处理标记，允许重新处理（用于窗口大小改变时）
                    if (container.hasAttribute('data-skeleton-processed')) {
                        container.removeAttribute('data-skeleton-processed');
                    }
                    
                    const img = container.querySelector('.js-skeleton-img');
                    
                    if (img) {
                        if (img.complete && img.naturalWidth > 0) {
                            adjustSkeletonSize(container, img);
                        } else {
                            img.addEventListener('load', function() {
                                adjustSkeletonSize(container, img);
                            }, { once: true });
                            
                            img.addEventListener('error', function() {
                                handleImageLoadError(container, img);
                            }, { once: true });
                        }
                    } else {
                        handleSkeletonWithoutImage(container);
                    }
                    
                    // 处理完成后停止观察
                    observer.unobserve(container);
                }
            });
        }, {
            rootMargin: '50px' // 提前 50px 开始处理
        });

        // 开始观察所有 skeleton 容器
        skeletonContainers.forEach(function(container) {
            observer.observe(container);
        });
    }

    /**
     * 处理窗口大小改变和方向改变
     */
    function handleResize() {
        // 清除设备信息缓存
        deviceInfo = null;
        
        // 重新检测设备
        detectMobileDevice();
        
        // 重新处理所有 skeleton（移除已处理标记）
        document.querySelectorAll('.js-skeleton[data-skeleton-processed]').forEach(function(container) {
            container.removeAttribute('data-skeleton-processed');
            const img = container.querySelector('.js-skeleton-img');
            if (img && img.complete && img.naturalWidth > 0) {
                adjustSkeletonSize(container, img);
            }
        });
    }

    /**
     * 初始化窗口大小改变监听
     */
    function initResizeListener() {
        // 使用防抖优化性能
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(handleResize, 250);
        }, { passive: true });
        
        // 监听方向改变（移动端）
        window.addEventListener('orientationchange', function() {
            // 方向改变后，等待布局完成再处理
            setTimeout(function() {
                deviceInfo = null; // 清除缓存
                handleResize();
            }, 100);
        }, { passive: true });
    }

    /**
     * 主初始化函数
     */
    function init() {
        // 初始化设备检测
        detectMobileDevice();
        
        // 初始化窗口大小改变监听
        initResizeListener();
        
        // 初始化 skeleton 优化器
        initLazySkeletonOptimizer();
        
        // 立即处理首屏可见的 skeleton
        initSmartSkeleton();
    }

    // DOM 加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // 使用 requestIdleCallback 优化性能
            if (window.requestIdleCallback) {
                requestIdleCallback(function() {
                    init();
                }, { timeout: 2000 });
            } else {
                setTimeout(function() {
                    init();
                }, 100);
            }
        });
    } else {
        // DOM 已经加载完成
        if (window.requestIdleCallback) {
            requestIdleCallback(function() {
                init();
            }, { timeout: 2000 });
        } else {
            setTimeout(function() {
                init();
            }, 100);
        }
    }

    // 监听动态内容加载（MutationObserver）
    if (window.MutationObserver) {
        const mutationObserver = new MutationObserver(function(mutations) {
            let shouldReinit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // 检查是否添加了新的 skeleton 元素
                            if (node.classList && node.classList.contains('js-skeleton')) {
                                shouldReinit = true;
                            } else if (node.querySelectorAll) {
                                const skeletons = node.querySelectorAll('.js-skeleton');
                                if (skeletons.length > 0) {
                                    shouldReinit = true;
                                }
                            }
                        }
                    });
                }
            });
            
            if (shouldReinit) {
                // 延迟处理，确保 DOM 完全更新
                setTimeout(function() {
                    initSmartSkeleton();
                }, 50);
            }
        });
        
        // 开始观察 DOM 变化
        if (document.body) {
            mutationObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                mutationObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        }
    }

    // 导出函数供其他脚本使用（例如动态加载的内容）
    window.initSmartSkeleton = initSmartSkeleton;
    window.adjustSkeletonSize = adjustSkeletonSize;
    window.detectMobileDevice = detectMobileDevice;

})();
