/**
 * Cart Handler
 * Handles cart operations: quantity updates, item removal, cart loading
 * Uses event listeners and provides loading overlay
 */

(function() {
    'use strict';

    const config = {
        routes: {
            cartJson: window.cartJsonRoute || '/cart/v2/json',
            updateItem: window.cartUpdateRoute || '/cart/items',
            removeItem: window.cartRemoveRoute || '/cart/items'
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };

    let isLoading = false;

    /**
     * Initialize Cart Handler
     */
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupEventListeners);
        } else {
            setupEventListeners();
        }
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        // Quantity controls
        document.addEventListener('click', function(e) {
            const minusBtn = e.target.closest('.qty-minus');
            const plusBtn = e.target.closest('.qty-plus');
            
            if (minusBtn && !isLoading) {
                e.preventDefault();
                handleQuantityChange(minusBtn, -1);
            }
            
            if (plusBtn && !isLoading) {
                e.preventDefault();
                handleQuantityChange(plusBtn, 1);
            }
        });

        // Quantity input change
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('qty-input') && !isLoading) {
                const qty = parseInt(e.target.value || '1', 10);
                if (qty > 0) {
                    updateItem(e.target.dataset.variantId, qty);
                } else {
                    e.target.value = 1;
                }
            }
        });

        // Remove item
        document.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-item');
            if (removeBtn && !isLoading) {
                e.preventDefault();
                removeItem(removeBtn.dataset.variantId);
            }
        });
    }

    /**
     * Handle quantity change with +/- buttons
     */
    function handleQuantityChange(button, delta) {
        const input = button.closest('.quantity-controls')?.querySelector('.qty-input');
        if (!input) return;

        const currentQty = parseInt(input.value || '1', 10);
        const newQty = Math.max(1, currentQty + delta);
        
        input.value = newQty;
        updateItem(input.dataset.variantId, newQty);
    }

    /**
     * Update cart item quantity
     */
    function updateItem(variantId, qty) {
        if (isLoading) return;
        
        showLoadingOverlay();
        isLoading = true;

        fetch(`${config.routes.updateItem}/${variantId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ qty: qty })
        })
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();
            
            if (data.success) {
                loadCart();
                showToast('Đã cập nhật số lượng', 'success');
            } else {
                showToast(data.message || 'Cập nhật thất bại', 'error');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Error:', error);
            showToast('Có lỗi xảy ra', 'error');
        });
    }

    /**
     * Remove cart item
     */
    function removeItem(variantId) {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;
        if (isLoading) return;
        
        showLoadingOverlay();
        isLoading = true;

        fetch(`${config.routes.removeItem}/${variantId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();
            
            if (data.success) {
                loadCart();
                showToast('Đã xóa sản phẩm', 'success');
            } else {
                showToast(data.message || 'Xóa thất bại', 'error');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Error:', error);
            showToast('Có lỗi xảy ra', 'error');
        });
    }

    /**
     * Load cart data
     */
    function loadCart() {
        if (isLoading) return;
        
        showLoadingOverlay();
        isLoading = true;

        fetch(config.routes.cartJson)
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();
            
            if (data.success) {
                renderCart(data.data);
            } else {
                showError('Không thể tải giỏ hàng');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Error:', error);
            showError('Có lỗi xảy ra');
        });
    }

    /**
     * Render cart items
     */
    function renderCart(cartData) {
        const itemsContainer = document.getElementById('cart-items-container');
        const summaryContainer = document.getElementById('cart-summary-container');
        
        if (!itemsContainer || !summaryContainer) return;
        
        if (!cartData.items || cartData.items.length === 0) {
            itemsContainer.innerHTML = `
                <div class="cart-empty">
                    <div class="empty-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7ZM9 3V4H15V3H9ZM7 6V19H17V6H7Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h3>Giỏ hàng trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                    <a href="/" class="btn btn-primary">Tiếp tục mua sắm</a>
                </div>
            `;
            summaryContainer.innerHTML = '';
            return;
        }
        
        // Render cart items
        let itemsHtml = '<div class="cart-items-list">';
        
        cartData.items.forEach(item => {
            itemsHtml += `
                <div class="cart-item-card" data-variant-id="${item.variant_id}">
                    <div class="cart-item-image">
                        <img src="${item.image || '/website/images/placeholder.jpg'}" 
                             alt="${item.product_name}" 
                             loading="lazy"
                             onerror="this.src='/website/images/placeholder.jpg'">
                    </div>
                    <div class="cart-item-info">
                        <h4 class="cart-item-name">${escapeHtml(item.product_name)}</h4>
                        ${item.variant_name ? `<p class="cart-item-variant">${escapeHtml(item.variant_name)}</p>` : ''}
                        <div class="cart-item-price-mobile">
                            <span class="unit-price">${formatPrice(item.unit_price)}</span>
                            <span class="subtotal">${formatPrice(item.subtotal)}</span>
                        </div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-controls">
                            <button class="qty-minus" type="button" aria-label="Giảm số lượng">
                                <svg width="14" height="2" viewBox="0 0 14 2" fill="none">
                                    <path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="currentColor"/>
                                </svg>
                            </button>
                            <input type="number" 
                                   class="qty-input" 
                                   value="${item.quantity}" 
                                   min="1" 
                                   data-variant-id="${item.variant_id}"
                                   aria-label="Số lượng">
                            <button class="qty-plus" type="button" aria-label="Tăng số lượng">
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                                    <path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="currentColor"/>
                                    <path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="currentColor"/>
                                </svg>
                            </button>
                        </div>
                        <div class="cart-item-price">
                            <span class="unit-price">${formatPrice(item.unit_price)}</span>
                            <span class="subtotal">${formatPrice(item.subtotal)}</span>
                        </div>
                        <button class="remove-item" 
                                type="button" 
                                data-variant-id="${item.variant_id}"
                                aria-label="Xóa sản phẩm">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                                <path d="M9 0C4.03 0 0 4.03 0 9C0 13.97 4.03 18 9 18C13.97 18 18 13.97 18 9C18 4.03 13.97 0 9 0ZM13 12.59L11.59 14L9 11.41L6.41 14L5 12.59L7.59 10L5 7.41L6.41 6L9 8.59L11.59 6L13 7.41L10.41 10L13 12.59Z" fill="currentColor"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });
        
        itemsHtml += '</div>';
        itemsContainer.innerHTML = itemsHtml;
        
        // Render summary
        const summary = cartData.summary || {};
        summaryContainer.innerHTML = `
            <div class="cart-summary-card">
                <h3 class="summary-title">Tổng đơn hàng</h3>
                <div class="summary-details">
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span>${formatPrice(summary.subtotal || 0)}</span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá:</span>
                        <span class="text-success">-${formatPrice(summary.discount || 0)}</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span><strong>Tổng cộng:</strong></span>
                        <span class="total-amount"><strong>${formatPrice(summary.total || 0)}</strong></span>
                    </div>
                </div>
                <a href="${window.checkoutRoute || '/checkout/v2'}" class="btn btn-primary btn-checkout w-100">
                    Tiến hành thanh toán
                </a>
            </div>
        `;
    }

    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        let overlay = document.getElementById('cart-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'cart-loading-overlay';
            overlay.className = 'cart-loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Đang xử lý...</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.classList.add('show');
    }

    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        const overlay = document.getElementById('cart-loading-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `cart-toast cart-toast-${type}`;
        toast.setAttribute('role', 'alert');
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Show error message
     */
    function showError(message) {
        const container = document.getElementById('cart-items-container');
        if (container) {
            container.innerHTML = `<div class="alert alert-danger">${escapeHtml(message)}</div>`;
        }
    }

    /**
     * Format price
     */
    function formatPrice(price) {
        return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize
    init();

    // Export for external use
    window.CartHandler = {
        loadCart: loadCart,
        updateItem: updateItem,
        removeItem: removeItem
    };

})();

