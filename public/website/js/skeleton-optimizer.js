/**
 * 智能 Skeleton 优化器
 * 自动调整 skeleton 容器尺寸以匹配实际图片尺寸
 */
(function() {
    'use strict';

    /**
     * 初始化智能 skeleton
     * 在图片加载后，将 skeleton 容器调整为与图片相同的尺寸
     * 支持所有类型的 skeleton: skeleton--img-md, skeleton--img-sm, skeleton--img-lg, skeleton--img-square, skeleton--img-logo
     */
    function initSmartSkeleton() {
        // 查找所有带有 js-skeleton 类的容器（包括所有类型）
        const skeletonContainers = document.querySelectorAll('.js-skeleton');
        
        skeletonContainers.forEach(function(container) {
            const img = container.querySelector('.js-skeleton-img');
            
            if (!img) return;
            
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
                    // 保持当前尺寸或使用默认值
                }, { once: true });
            }
        });
    }

    /**
     * 调整 skeleton 容器尺寸以匹配图片
     */
    function adjustSkeletonSize(container, img) {
        // 等待一帧，确保图片已完全渲染
        requestAnimationFrame(function() {
            // 获取图片的实际渲染尺寸（考虑 object-fit: contain 的影响）
            const imgRect = img.getBoundingClientRect();
            const imgWidth = imgRect.width;
            const imgHeight = imgRect.height;
            
            // 如果图片尺寸有效
            if (imgWidth > 0 && imgHeight > 0) {
                // 获取容器的父元素（通常是 card-cover 或 a 标签）
                const parent = container.parentElement;
                if (!parent) return;
                
                const parentRect = parent.getBoundingClientRect();
                const parentWidth = parentRect.width;
                const parentHeight = parentRect.height;
                
                // 计算图片的宽高比
                const imgAspectRatio = imgWidth / imgHeight;
                
                // 计算在父容器内，保持图片宽高比的最大尺寸
                let targetWidth = imgWidth;
                let targetHeight = imgHeight;
                
                // 如果图片尺寸超过父容器，按比例缩放
                if (targetWidth > parentWidth) {
                    targetWidth = parentWidth;
                    targetHeight = targetWidth / imgAspectRatio;
                }
                if (targetHeight > parentHeight) {
                    targetHeight = parentHeight;
                    targetWidth = targetHeight * imgAspectRatio;
                }
                
                // 确保尺寸不超过父容器
                targetWidth = Math.min(targetWidth, parentWidth);
                targetHeight = Math.min(targetHeight, parentHeight);
                
                // 应用精确尺寸到 skeleton 容器
                container.style.width = targetWidth + 'px';
                container.style.height = targetHeight + 'px';
                container.style.minWidth = targetWidth + 'px';
                container.style.minHeight = targetHeight + 'px';
                container.style.maxWidth = targetWidth + 'px';
                container.style.maxHeight = targetHeight + 'px';
                
                // 使用 aspect-ratio 保持比例（现代浏览器支持）
                if (CSS.supports('aspect-ratio', '1 / 1')) {
                    container.style.aspectRatio = imgAspectRatio + ' / 1';
                }
                
                // 移除可能导致尺寸不匹配的样式
                container.style.flex = '0 0 auto';
            }
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
                    const img = container.querySelector('.js-skeleton-img');
                    
                    if (img) {
                        if (img.complete && img.naturalWidth > 0) {
                            adjustSkeletonSize(container, img);
                        } else {
                            img.addEventListener('load', function() {
                                adjustSkeletonSize(container, img);
                            }, { once: true });
                        }
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

    // DOM 加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // 使用 requestIdleCallback 优化性能
            if (window.requestIdleCallback) {
                requestIdleCallback(function() {
                    initLazySkeletonOptimizer();
                }, { timeout: 2000 });
            } else {
                setTimeout(function() {
                    initLazySkeletonOptimizer();
                }, 100);
            }
        });
    } else {
        // DOM 已经加载完成
        if (window.requestIdleCallback) {
            requestIdleCallback(function() {
                initLazySkeletonOptimizer();
            }, { timeout: 2000 });
        } else {
            setTimeout(function() {
                initLazySkeletonOptimizer();
            }, 100);
        }
    }

    // 导出函数供其他脚本使用（例如动态加载的内容）
    window.initSmartSkeleton = initSmartSkeleton;
    window.adjustSkeletonSize = adjustSkeletonSize;

})();
