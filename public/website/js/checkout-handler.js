/**
 * Checkout Handler
 * Handles checkout form: shipping fee calculation, coupon application, form submission
 * Uses event listeners and provides loading overlay
 */

(function() {
    'use strict';

    const config = {
        routes: {
            checkout: window.checkoutRoute || '/checkout/v2/checkout',
            shippingFee: window.shippingFeeRoute || '/checkout/v2/shipping-fee',
            applyCoupon: window.applyCouponRoute || '/checkout/v2/apply-coupon',
            removeCoupon: window.removeCouponRoute || '/checkout/v2/remove-coupon',
            searchLocation: window.searchLocationRoute || '/checkout/v2/search-location'
        },
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    };

    let isLoading = false;
    let shippingFeeTimeout = null;

    /**
     * Initialize Checkout Handler
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
        // Form submission
        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', handleCheckout);
        }

        // Coupon application
        const applyCouponBtn = document.getElementById('apply-coupon');
        const removeCouponBtn = document.getElementById('remove-coupon');
        const couponInput = document.getElementById('coupon-code');

        if (applyCouponBtn) {
            applyCouponBtn.addEventListener('click', applyCoupon);
        }

        if (removeCouponBtn) {
            removeCouponBtn.addEventListener('click', removeCoupon);
        }

        if (couponInput) {
            couponInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    applyCoupon();
                }
            });
        }

        // Address fields for shipping fee calculation
        setupAddressListeners();

        // Location search
        setupLocationSearch();
    }

    /**
     * Setup address field listeners
     */
    function setupAddressListeners() {
        const provinceId = document.getElementById('province_id');
        const districtId = document.getElementById('district_id');
        const wardId = document.getElementById('ward_id');
        const addressInput = document.querySelector('input[name="address"]');

        if (!provinceId || !districtId || !wardId) return;

        // Check if address is already filled
        const hasAddress = provinceId.value && districtId.value && wardId.value;
        if (hasAddress) {
            setTimeout(() => calculateShippingFee(), 500);
        }

        // Listen for changes
        [provinceId, districtId, wardId].forEach(field => {
            field.addEventListener('change', debounceShippingFee);
        });

        if (addressInput) {
            addressInput.addEventListener('blur', debounceShippingFee);
        }
    }

    /**
     * Debounce shipping fee calculation
     */
    function debounceShippingFee() {
        if (shippingFeeTimeout) {
            clearTimeout(shippingFeeTimeout);
        }

        // Show loading indicator immediately
        const shippingFeeEl = document.getElementById('shipping-fee');
        if (shippingFeeEl) {
            shippingFeeEl.textContent = 'Đang tính...';
            shippingFeeEl.style.opacity = '0.6';
        }

        shippingFeeTimeout = setTimeout(() => {
            const provinceId = document.getElementById('province_id')?.value;
            const districtId = document.getElementById('district_id')?.value;
            const wardId = document.getElementById('ward_id')?.value;

            if (provinceId && districtId && wardId) {
                calculateShippingFee();
            } else {
                // Reset if incomplete
                if (shippingFeeEl) {
                    shippingFeeEl.style.opacity = '1';
                }
            }
        }, 300); // Reduced from 500ms for faster response
    }

    /**
     * Calculate shipping fee
     */
    function calculateShippingFee() {
        if (isLoading) return;

        const provinceIdEl = document.getElementById('province_id');
        const districtIdEl = document.getElementById('district_id');
        const wardIdEl = document.getElementById('ward_id');
        const addressEl = document.querySelector('input[name="address"]');

        if (!provinceIdEl || !districtIdEl || !wardIdEl) return;

        const provinceId = parseInt(provinceIdEl.value);
        const districtId = parseInt(districtIdEl.value);
        const wardId = parseInt(wardIdEl.value);
        const address = addressEl ? addressEl.value.trim() : '';

        if (!provinceId || !districtId || !wardId || isNaN(provinceId) || isNaN(districtId) || isNaN(wardId)) {
            return;
        }

        showLoadingOverlay();
        isLoading = true;

        fetch(config.routes.shippingFee, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({
                province_id: provinceId,
                district_id: districtId,
                ward_id: wardId,
                address: address
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.message || `HTTP error! status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();

            if (data.success) {
                updateSummary(data.data.summary);
                // Visual feedback for shipping fee update
                const shippingFeeEl = document.getElementById('shipping-fee');
                if (shippingFeeEl) {
                    shippingFeeEl.style.opacity = '1';
                    shippingFeeEl.style.transition = 'all 200ms';
                    shippingFeeEl.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        shippingFeeEl.style.transform = 'scale(1)';
                    }, 200);
                }
            } else {
                showToast(data.message || 'Tính phí vận chuyển thất bại', 'error');
                const shippingFeeEl = document.getElementById('shipping-fee');
                if (shippingFeeEl) {
                    shippingFeeEl.style.opacity = '1';
                }
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Shipping fee error:', error);
            showToast('Có lỗi xảy ra khi tính phí vận chuyển', 'error');
        });
    }

    /**
     * Apply coupon code
     */
    function applyCoupon() {
        if (isLoading) return;

        const codeInput = document.getElementById('coupon-code');
        const code = codeInput ? codeInput.value.trim() : '';

        if (!code) {
            showToast('Vui lòng nhập mã giảm giá', 'error');
            return;
        }

        showLoadingOverlay();
        isLoading = true;

        fetch(config.routes.applyCoupon, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();

            if (data.success) {
                updateSummary(data.data.summary);
                const removeBtn = document.getElementById('remove-coupon');
                if (removeBtn) removeBtn.style.display = 'block';
                showToast('Áp dụng mã thành công', 'success');
            } else {
                showToast(data.message || 'Áp dụng mã thất bại', 'error');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Apply coupon error:', error);
            showToast('Có lỗi xảy ra', 'error');
        });
    }

    /**
     * Remove coupon code
     */
    function removeCoupon() {
        if (isLoading) return;

        showLoadingOverlay();
        isLoading = true;

        fetch(config.routes.removeCoupon, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();

            if (data.success) {
                updateSummary(data.data.summary);
                const removeBtn = document.getElementById('remove-coupon');
                const codeInput = document.getElementById('coupon-code');
                if (removeBtn) removeBtn.style.display = 'none';
                if (codeInput) codeInput.value = '';
                showToast('Đã hủy mã giảm giá', 'success');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Remove coupon error:', error);
        });
    }

    /**
     * Handle checkout form submission
     */
    function handleCheckout(e) {
        e.preventDefault();
        if (isLoading) return;

        const form = document.getElementById('checkoutForm');
        if (!form) return;

        // Validate form
        if (!validateForm(form)) {
            return;
        }

        showLoadingOverlay();
        isLoading = true;

        const formData = new FormData(form);

        fetch(config.routes.checkout, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': config.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            hideLoadingOverlay();

            if (data.success) {
                window.location.href = data.data.redirect_url || 
                    (window.checkoutResultRoute || '/checkout/v2/result') + '?code=' + data.data.order_code;
            } else {
                showToast(data.message || 'Đặt hàng thất bại', 'error');
            }
        })
        .catch(error => {
            isLoading = false;
            hideLoadingOverlay();
            console.error('Checkout error:', error);
            showToast('Có lỗi xảy ra', 'error');
        });
    }

    /**
     * Validate checkout form
     */
    function validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
                field.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                }, { once: true });
            }
        });

        if (!isValid) {
            showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
        }

        return isValid;
    }

    /**
     * Update order summary with smooth animation
     */
    function updateSummary(summary) {
        if (!summary) return;

        const subtotalEl = document.getElementById('subtotal');
        const discountEl = document.getElementById('discount');
        const shippingFeeEl = document.getElementById('shipping-fee');
        const totalEl = document.getElementById('total');

        // Animate value changes for visual feedback
        const animateValueChange = (element, newValue) => {
            if (!element) return;
            element.style.transition = 'all 200ms';
            element.style.transform = 'scale(1.05)';
            element.textContent = newValue;
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
        };

        if (subtotalEl) animateValueChange(subtotalEl, formatPrice(summary.subtotal || 0));
        if (discountEl) animateValueChange(discountEl, '-' + formatPrice(summary.discount || 0));
        if (shippingFeeEl) {
            shippingFeeEl.style.opacity = '1';
            animateValueChange(shippingFeeEl, formatPrice(summary.shipping_fee || 0));
        }
        if (totalEl) animateValueChange(totalEl, formatPrice(summary.total || 0));
    }

    /**
     * Setup location search
     */
    function setupLocationSearch() {
        const input = document.getElementById('search_location_input');
        if (!input) return;

        let searchTimeout = null;

        input.addEventListener('input', function() {
            const keyword = this.value.trim();

            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            if (keyword.length < 2) {
                const results = document.getElementById('search_location_results');
                if (results) results.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(config.routes.searchLocation + '?q=' + encodeURIComponent(keyword))
                    .then(response => response.json())
                    .then(data => {
                        const results = document.getElementById('search_location_results');
                        if (!results) return;

                        if (data.results && data.results.length > 0) {
                            results.innerHTML = data.results.map(item =>
                                `<div class="location-item" 
                                      data-ward="${item.ward_id}" 
                                      data-district="${item.district_id}" 
                                      data-province="${item.province_id}">
                                    ${escapeHtml(item.text)}
                                </div>`
                            ).join('');

                            results.querySelectorAll('.location-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    const provinceEl = document.getElementById('province_id');
                                    const districtEl = document.getElementById('district_id');
                                    const wardEl = document.getElementById('ward_id');

                                    if (provinceEl) provinceEl.value = this.dataset.province;
                                    if (districtEl) districtEl.value = this.dataset.district;
                                    if (wardEl) wardEl.value = this.dataset.ward;

                                    input.value = this.textContent;
                                    results.innerHTML = '';
                                    calculateShippingFee();
                                });
                            });
                        } else {
                            results.innerHTML = '';
                        }
                    })
                    .catch(error => {
                        console.error('Location search error:', error);
                    });
            }, 300);
        });
    }

    /**
     * Show loading overlay
     */
    function showLoadingOverlay() {
        let overlay = document.getElementById('checkout-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'checkout-loading-overlay';
            overlay.className = 'checkout-loading-overlay';
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
        const overlay = document.getElementById('checkout-loading-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `checkout-toast checkout-toast-${type}`;
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
    window.CheckoutHandler = {
        calculateShippingFee: calculateShippingFee,
        applyCoupon: applyCoupon,
        removeCoupon: removeCoupon
    };

})();

