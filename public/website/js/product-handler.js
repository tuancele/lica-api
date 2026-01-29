/**
 * Product Detail Handler
 * Handles variant selection, price calculation, stock updates, and cart actions
 * Uses event listeners instead of inline scripts
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        routes: {
            addToCart: window.cartAddRoute || '/cart/add',
            payment: window.cartPaymentRoute || '/cart/payment',
            cartIndex: window.cartIndexRoute || '/cart'
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };

    // State
    let currentVariantId = null;
    let currentStock = 0;
    let isProcessing = false;

    /**
     * Initialize Product Handler
     */
    function init() {
        // Wait for DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupEventListeners);
        } else {
            setupEventListeners();
        }
    }

    /**
     * Setup all event listeners
     */
    function setupEventListeners() {
        // Variant selection
        setupVariantSelection();
        
        // Quantity controls
        setupQuantityControls();
        
        // Cart actions
        setupCartActions();
        
        // Initialize variant state
        initializeVariantState();
    }

    /**
     * Setup variant selection handlers
     */
    function setupVariantSelection() {
        const variantList = document.getElementById('variant-option1-list');
        if (!variantList) return;

        variantList.addEventListener('click', function(e) {
            const variantItem = e.target.closest('.item-variant');
            if (!variantItem || variantItem.classList.contains('out-of-stock')) {
                return;
            }

            // Remove active class from all variants
            variantList.querySelectorAll('.item-variant').forEach(item => {
                item.classList.remove('active');
            });

            // Add active class to clicked variant
            variantItem.classList.add('active');

            // Update variant data
            updateVariantData(variantItem);
        });
    }

    /**
     * Update variant data (price, stock, SKU, image)
     */
    function updateVariantData(variantItem) {
        const variantId = variantItem.getAttribute('data-variant-id');
        const sku = variantItem.getAttribute('data-sku') || '';
        const priceHtmlRaw = variantItem.getAttribute('data-price-html') || '';
        const stock = parseInt(variantItem.getAttribute('data-stock') || '0', 10);
        const image = variantItem.getAttribute('data-image') || '';
        const optionText = variantItem.getAttribute('data-option1') || '';

        // Update hidden input
        const variantInput = document.querySelector('input[name="variant_id"]');
        if (variantInput) {
            variantInput.value = variantId;
        }

        // Update SKU display
        const skuDisplay = document.getElementById('variant-sku-display');
        if (skuDisplay) {
            skuDisplay.textContent = sku;
        }

        // Update option text
        const optionCurrent = document.getElementById('variant-option1-current');
        if (optionCurrent) {
            optionCurrent.textContent = optionText;
        }

        // Update price display
        if (priceHtmlRaw) {
            try {
                const priceHtml = decodeBase64Unicode(priceHtmlRaw);
                const priceDisplay = document.getElementById('variant-price-display');
                if (priceDisplay) {
                    priceDisplay.innerHTML = priceHtml;
                }
            } catch (e) {
                console.warn('Failed to decode price HTML:', e);
            }
        }

        // Update stock display
        updateStockDisplay(stock);

        // Update image if provided
        if (image) {
            const firstImg = document.querySelector('#slidesWrapper .slide img');
            if (firstImg) {
                firstImg.src = image;
            }
        }

        // Update button states
        updateButtonStates(stock);

        // Store current state
        currentVariantId = variantId;
        currentStock = stock;
    }

    /**
     * Decode base64 Unicode string
     */
    function decodeBase64Unicode(str) {
        try {
            if (typeof decodeUnicodeBase64 === 'function') {
                return decodeUnicodeBase64(str);
            }
            const binaryString = atob(str);
            const bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            return new TextDecoder('utf-8').decode(bytes);
        } catch (e) {
            try {
                return decodeURIComponent(escape(atob(str)));
            } catch (e2) {
                return '';
            }
        }
    }

    /**
     * Update stock display
     */
    function updateStockDisplay(stock) {
        const stockValue = document.getElementById('variant-stock-value');
        if (stockValue) {
            stockValue.textContent = stock > 0 
                ? stock.toLocaleString('vi-VN') 
                : 'Hết hàng';
            stockValue.setAttribute('data-server-stock', stock.toString());
        }

        const stockUnit = document.querySelector('.stock-unit');
        if (stockUnit) {
            stockUnit.textContent = stock > 0 ? 'sản phẩm' : '';
        }
    }

    /**
     * Update button states based on stock
     */
    function updateButtonStates(stock) {
        const disabled = stock <= 0;
        const buttons = document.querySelectorAll('.addCartDetail, .buyNowDetail, .btn_plus.entry, .btn_minus.entry, .quantity-input');
        
        buttons.forEach(btn => {
            btn.disabled = disabled;
        });

        // Update button text and styles
        const addCartBtn = document.querySelector('.addCartDetail');
        const buyNowBtn = document.querySelector('.buyNowDetail:not(.btnBuyDealSốc)');
        const buyDealBtn = document.querySelector('.buyNowDetail.btnBuyDealSốc');

        if (disabled) {
            if (addCartBtn) {
                const span = addCartBtn.querySelector('span:last-child');
                if (span) span.textContent = 'Hết hàng';
            }
            if (buyNowBtn) buyNowBtn.textContent = 'Hết hàng';
            if (buyDealBtn) buyDealBtn.textContent = 'Hết hàng';
        } else {
            if (addCartBtn) {
                const span = addCartBtn.querySelector('span:last-child');
                if (span) span.textContent = 'Thêm Vào Giỏ Hàng';
            }
            if (buyNowBtn) buyNowBtn.textContent = 'Mua ngay';
            if (buyDealBtn) buyDealBtn.textContent = 'MUA DEAL SỐC';
        }
    }

    /**
     * Setup quantity controls
     */
    function setupQuantityControls() {
        const minusBtn = document.querySelector('.btn_minus.entry');
        const plusBtn = document.querySelector('.btn_plus.entry');
        const quantityInput = document.querySelector('.quantity-input');

        if (minusBtn) {
            minusBtn.addEventListener('click', function() {
                if (this.disabled || isProcessing) return;
                const input = document.querySelector('.quantity-input');
                if (input && !input.disabled) {
                    const currentValue = parseInt(input.value || '1', 10);
                    if (currentValue > 1) {
                        input.value = currentValue - 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', function() {
                if (this.disabled || isProcessing) return;
                const input = document.querySelector('.quantity-input');
                if (input && !input.disabled) {
                    const currentValue = parseInt(input.value || '1', 10);
                    const maxStock = currentStock > 0 ? currentStock : 9999;
                    if (currentValue < maxStock) {
                        input.value = currentValue + 1;
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            });
        }

        if (quantityInput) {
            quantityInput.addEventListener('change', function() {
                const value = parseInt(this.value || '1', 10);
                const maxStock = currentStock > 0 ? currentStock : 9999;
                if (value < 1) {
                    this.value = 1;
                } else if (value > maxStock) {
                    this.value = maxStock;
                    alert(`Chỉ còn ${maxStock} sản phẩm trong kho`);
                }
            });
        }
    }

    /**
     * Setup cart action handlers
     */
    function setupCartActions() {
        // Add to cart
        document.addEventListener('click', function(e) {
            const addCartBtn = e.target.closest('.addCartDetail');
            if (addCartBtn && !addCartBtn.disabled && !isProcessing) {
                e.preventDefault();
                handleAddToCart();
            }
        });

        // Buy now
        document.addEventListener('click', function(e) {
            const buyNowBtn = e.target.closest('.buyNowDetail');
            if (buyNowBtn && !buyNowBtn.disabled && !isProcessing) {
                e.preventDefault();
                if (buyNowBtn.classList.contains('btnBuyDealSốc')) {
                    handleBuyDeal();
                } else {
                    handleBuyNow();
                }
            }
        });
    }

    /**
     * Handle add to cart action
     */
    function handleAddToCart() {
        if (isProcessing) return;

        // Check stock
        const stock = getCurrentStock();
        if (stock !== null && stock <= 0) {
            alert('Sản phẩm đã hết hàng');
            return;
        }

        const variantId = document.querySelector('input[name="variant_id"]')?.value;
        const quantity = document.querySelector('.quantity-input')?.value || '1';
        const combo = [];

        // Add main product
        combo.push({ id: variantId, qty: quantity, is_deal: 0 });

        // Add deal items
        document.querySelectorAll('.deal-checkbox-custom:checked').forEach(checkbox => {
            combo.push({ id: checkbox.value, qty: 1, is_deal: 1 });
        });

        isProcessing = true;
        showLoadingState('.addCartDetail', true);

        fetch(config.routes.addToCart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ combo: combo })
        })
        .then(response => response.json())
        .then(data => {
            isProcessing = false;
            showLoadingState('.addCartDetail', false);

            if (data.status === 'success') {
                updateCartCount(data.total);
                showSuccessMessage('Đã thêm vào giỏ hàng');
            } else {
                showErrorMessage(data.message || 'Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại');
            }
        })
        .catch(error => {
            isProcessing = false;
            showLoadingState('.addCartDetail', false);
            showErrorMessage('Có lỗi xảy ra, xin vui lòng thử lại');
            console.error('Add to cart error:', error);
        });
    }

    /**
     * Handle buy now action
     */
    function handleBuyNow() {
        if (isProcessing) return;

        // Check stock
        const stock = getCurrentStock();
        if (stock !== null && stock <= 0) {
            alert('Sản phẩm đã hết hàng');
            return;
        }

        const variantId = document.querySelector('input[name="variant_id"]')?.value;
        const quantity = document.querySelector('.quantity-input')?.value || '1';

        isProcessing = true;
        showLoadingState('.buyNowDetail:not(.btnBuyDealSốc)', true);

        fetch(config.routes.addToCart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ id: variantId, qty: quantity })
        })
        .then(response => response.json())
        .then(data => {
            isProcessing = false;
            showLoadingState('.buyNowDetail:not(.btnBuyDealSốc)', false);

            if (data.status === 'success') {
                window.location.href = config.routes.payment;
            } else {
                showErrorMessage(data.message || 'Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại');
            }
        })
        .catch(error => {
            isProcessing = false;
            showLoadingState('.buyNowDetail:not(.btnBuyDealSốc)', false);
            showErrorMessage('Có lỗi xảy ra, xin vui lòng thử lại');
            console.error('Buy now error:', error);
        });
    }

    /**
     * Handle buy deal action
     */
    function handleBuyDeal() {
        if (isProcessing) return;

        const variantId = document.querySelector('input[name="variant_id"]')?.value;
        const quantity = document.querySelector('.quantity-input')?.value || '1';
        const combo = [];

        combo.push({ id: variantId, qty: quantity, is_deal: 0 });
        document.querySelectorAll('.deal-checkbox-custom:checked').forEach(checkbox => {
            combo.push({ id: checkbox.value, qty: 1, is_deal: 1 });
        });

        if (combo.length < 2) {
            alert('Vui lòng chọn ít nhất 1 sản phẩm mua kèm');
            return;
        }

        isProcessing = true;
        const buyDealBtn = document.querySelector('.btnBuyDealSốc');
        if (buyDealBtn) {
            buyDealBtn.disabled = true;
            buyDealBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        }

        fetch(config.routes.addToCart, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ combo: combo })
        })
        .then(response => response.json())
        .then(data => {
            isProcessing = false;
            if (buyDealBtn) {
                buyDealBtn.disabled = false;
                buyDealBtn.textContent = 'MUA DEAL SỐC';
            }

            if (data.status === 'success') {
                window.location.href = config.routes.cartIndex;
            } else {
                showErrorMessage(data.message || 'Có lỗi xảy ra trong quá trình xử lý, xin vui lòng thử lại');
            }
        })
        .catch(error => {
            isProcessing = false;
            if (buyDealBtn) {
                buyDealBtn.disabled = false;
                buyDealBtn.textContent = 'MUA DEAL SỐC';
            }
            showErrorMessage('Có lỗi xảy ra, xin vui lòng thử lại');
            console.error('Buy deal error:', error);
        });
    }

    /**
     * Get current stock from active variant
     */
    function getCurrentStock() {
        const variantList = document.getElementById('variant-option1-list');
        if (variantList) {
            const activeVariant = variantList.querySelector('.item-variant.active');
            if (activeVariant) {
                return parseInt(activeVariant.getAttribute('data-stock') || '0', 10);
            }
        }
        return currentStock;
    }

    /**
     * Show loading state for buttons
     */
    function showLoadingState(selector, isLoading) {
        const buttons = document.querySelectorAll(selector);
        buttons.forEach(btn => {
            if (isLoading) {
                btn.disabled = true;
                const icon = btn.querySelector('.icon');
                if (icon) {
                    icon.innerHTML = '<span class="spinner-border spinner-border-sm text-light"></span>';
                } else {
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm text-light"></span> ' + (btn.textContent.includes('Thêm') ? 'Đang xử lý...' : 'Đang xử lý...');
                }
            } else {
                btn.disabled = false;
                // Restore original content (would need to store it initially)
                const icon = btn.querySelector('.icon');
                if (icon && icon.dataset.originalHtml) {
                    icon.innerHTML = icon.dataset.originalHtml;
                }
            }
        });
    }

    /**
     * Update cart count display
     */
    function updateCartCount(count) {
        const cartCount = document.querySelector('.count-cart');
        if (cartCount) {
            cartCount.textContent = count;
        }
    }

    /**
     * Show success message with toast notification
     */
    function showSuccessMessage(message) {
        showToast(message, 'success');
    }

    /**
     * Show error message with toast notification
     */
    function showErrorMessage(message) {
        showToast(message, 'error');
    }

    /**
     * Show toast notification with Design System colors
     */
    function showToast(message, type = 'success') {
        // Remove existing toast if any
        const existingToast = document.querySelector('.product-toast');
        if (existingToast) {
            existingToast.remove();
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = 'product-toast product-toast-' + type;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'polite');
        
        // Icon based on type
        let icon = '';
        if (type === 'success') {
            icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z" fill="currentColor"/></svg>';
        } else {
            icon = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z" fill="currentColor"/></svg>';
        }

        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${icon}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;

        // Append to body
        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }, 3000);
    }

    /**
     * Initialize variant state on page load
     */
    function initializeVariantState() {
        const variantList = document.getElementById('variant-option1-list');
        if (variantList) {
            const activeVariant = variantList.querySelector('.item-variant.active');
            if (activeVariant) {
                updateVariantData(activeVariant);
            }
        } else {
            // No variants, get stock from product data
            const productInfo = document.getElementById('product-detail-info');
            if (productInfo && productInfo.dataset.productData) {
                try {
                    const productData = JSON.parse(productInfo.dataset.productData);
                    const stock = productData.warehouse_stock !== undefined 
                        ? productData.warehouse_stock 
                        : productData.stock;
                    updateStockDisplay(stock || 0);
                    updateButtonStates(stock || 0);
                    currentStock = stock || 0;
                } catch (e) {
                    console.warn('Failed to parse product data:', e);
                }
            }
        }
    }

    // Initialize on load
    init();

    // Export for external use if needed
    window.ProductHandler = {
        updateVariantData: updateVariantData,
        getCurrentStock: getCurrentStock,
        updateButtonStates: updateButtonStates,
        syncVariantState: syncVariantStateFromDOM
    };

})();

