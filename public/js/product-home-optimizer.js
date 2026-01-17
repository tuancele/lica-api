/**
 * 产品显示智能优化脚本
 * 功能：
 * 1. 智能检测屏幕尺寸
 * 2. 自动调整产品网格布局
 * 3. 优化item边界和间距
 */
(function() {
    'use strict';

    const ProductHomeOptimizer = {
        /**
         * 初始化优化器
         */
        init: function() {
            this.optimizeAllSections();
            this.setupResizeHandler();
        },

        /**
         * 优化所有product_home section
         */
        optimizeAllSections: function() {
            const sections = document.querySelectorAll('section.product_home[data-lazy-load="section"]');
            
            sections.forEach(section => {
                // 等待lazy-load完成
                const lazyContent = section.querySelector('.lazy-hidden-content');
                if (lazyContent && lazyContent.style.display === 'none') {
                    const observer = new MutationObserver((mutations) => {
                        if (lazyContent.style.display !== 'none') {
                            this.optimizeSection(section);
                            observer.disconnect();
                        }
                    });
                    observer.observe(lazyContent, { attributes: true, attributeFilter: ['style'] });
                } else {
                    this.optimizeSection(section);
                }
            });
        },

        /**
         * 优化单个section
         */
        optimizeSection: function(section) {
            const lists = section.querySelectorAll('.list-watch:not(.owl-carousel), .list-flash:not(.owl-carousel)');
            
            lists.forEach(list => {
                // 跳过已经是grid布局的（如recommendations-grid-6x6）
                if (list.classList.contains('recommendations-grid-6x6')) {
                    return;
                }

                const screenWidth = window.innerWidth;
                let columns = this.calculateColumns(screenWidth);
                
                // 应用grid布局
                if (screenWidth >= 768) {
                    this.applyGridLayout(list, columns);
                }
            });
        },

        /**
         * 根据屏幕宽度计算列数
         */
        calculateColumns: function(width) {
            if (width >= 1920) return 8;
            if (width >= 1400) return 7;
            if (width >= 1200) return 6;
            if (width >= 1000) return 5;
            if (width >= 768) return 4;
            return 2; // 移动端使用轮播
        },

        /**
         * 应用grid布局
         */
        applyGridLayout: function(list, columns) {
            const items = list.querySelectorAll('.item-product');
            if (items.length === 0) return;

            // 计算每个item的最佳宽度
            const containerWidth = list.offsetWidth || list.parentElement.offsetWidth;
            const gap = 12;
            const itemWidth = Math.floor((containerWidth - (gap * (columns - 1))) / columns);

            list.style.display = 'grid';
            list.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
            list.style.gap = `${gap}px`;
            list.style.width = '100%';
            list.style.margin = '0';
            list.style.padding = '0';

            // 优化每个item
            items.forEach(item => {
                item.style.width = '100%';
                item.style.maxWidth = '100%';
                item.style.margin = '0';
            });
        },

        /**
         * 设置窗口大小改变监听
         */
        setupResizeHandler: function() {
            let resizeTimer;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    this.optimizeAllSections();
                }, 250);
            });
        }
    };

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ProductHomeOptimizer.init();
        });
    } else {
        ProductHomeOptimizer.init();
    }

    // 监听lazy-load事件
    document.addEventListener('lazyLoadComplete', () => {
        ProductHomeOptimizer.optimizeAllSections();
    });

    window.ProductHomeOptimizer = ProductHomeOptimizer;
})();
