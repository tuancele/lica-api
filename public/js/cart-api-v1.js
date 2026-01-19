/**
 * Cart API V1 JavaScript Module
 * 
 * Handles all cart operations using API V1 endpoints
 * Replaces old AJAX calls with new RESTful API
 */

(function() {
    'use strict';

    const CartAPI = {
        baseUrl: '/api/v1/cart',
        
        /**
         * Get cart data
         */
        getCart: function() {
            return $.ajax({
                url: this.baseUrl,
                method: 'GET',
                timeout: 10000, // 10 seconds timeout
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false
            });
        },

        /**
         * Add item to cart
         * @param {number} variantId - Variant ID
         * @param {number} qty - Quantity
         * @param {boolean} isDeal - Is deal item
         */
        addItem: function(variantId, qty, isDeal = false) {
            // Validate inputs
            if (!variantId || variantId <= 0) {
                return $.Deferred().reject({
                    responseJSON: { message: 'Variant ID không hợp lệ' }
                });
            }
            if (!qty || qty <= 0) {
                return $.Deferred().reject({
                    responseJSON: { message: 'Số lượng phải lớn hơn 0' }
                });
            }
            
            return $.ajax({
                url: this.baseUrl + '/items',
                method: 'POST',
                timeout: 10000, // 10 seconds timeout
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false,
                data: JSON.stringify({
                    variant_id: variantId,
                    qty: qty,
                    is_deal: isDeal ? 1 : 0
                })
            });
        },

        /**
         * Add combo items to cart
         * @param {Array} combo - Array of items [{variant_id, qty, is_deal}]
         */
        addCombo: function(combo) {
            return $.ajax({
                url: this.baseUrl + '/items',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false,
                data: JSON.stringify({
                    combo: combo
                })
            });
        },

        /**
         * Update item quantity
         * @param {number} variantId - Variant ID
         * @param {number} qty - New quantity
         */
        updateItem: function(variantId, qty) {
            // Validate inputs
            if (!variantId || variantId <= 0) {
                return $.Deferred().reject({
                    responseJSON: { message: 'Variant ID không hợp lệ' }
                });
            }
            if (!qty || qty <= 0) {
                return $.Deferred().reject({
                    responseJSON: { message: 'Số lượng phải lớn hơn 0' }
                });
            }
            
            return $.ajax({
                url: this.baseUrl + '/items/' + variantId,
                method: 'PUT',
                timeout: 10000, // 10 seconds timeout
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false,
                data: JSON.stringify({
                    qty: qty
                })
            });
        },

        /**
         * Remove item from cart
         * @param {number} variantId - Variant ID
         */
        removeItem: function(variantId) {
            // Validate input
            if (!variantId || variantId <= 0) {
                console.error('[CartAPI] Invalid variantId:', variantId);
                return $.Deferred().reject({
                    responseJSON: { message: 'Variant ID không hợp lệ' }
                });
            }
            
            // Get CSRF token from meta tag or cookie
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (!csrfToken) {
                // Try to get from cookie
                csrfToken = this.getCookie('XSRF-TOKEN');
            }
            
            var url = this.baseUrl + '/items/' + variantId;
            
            // DEBUG: Log request details
            console.log('[CartAPI] removeItem request:', {
                url: url,
                method: 'DELETE',
                variantId: variantId,
                csrfToken: csrfToken ? 'Present (' + csrfToken.substring(0, 10) + '...)' : 'MISSING!',
                timestamp: new Date().toISOString()
            });
            
            var headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            
            // Add CSRF token if available
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }
            
            return $.ajax({
                url: url,
                method: 'DELETE',
                timeout: 10000, // 10 seconds timeout
                headers: headers,
                xhrFields: {
                    withCredentials: true // Important: Send cookies with cross-origin requests
                },
                crossDomain: false
            }).done(function(data, textStatus, xhr) {
                console.log('[CartAPI] removeItem success:', {
                    status: xhr.status,
                    data: data,
                    timestamp: new Date().toISOString()
                });
            }).fail(function(xhr, textStatus, errorThrown) {
                console.error('[CartAPI] removeItem failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    textStatus: textStatus,
                    errorThrown: errorThrown,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON,
                    timestamp: new Date().toISOString()
                });
            });
        },
        
        /**
         * Get cookie value by name
         * @param {string} name - Cookie name
         * @returns {string|null}
         */
        getCookie: function(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length === 2) {
                return parts.pop().split(";").shift();
            }
            return null;
        },

        /**
         * Apply coupon
         * @param {string} code - Coupon code
         */
        applyCoupon: function(code) {
            return $.ajax({
                url: this.baseUrl + '/coupon/apply',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false,
                data: JSON.stringify({
                    code: code
                })
            });
        },

        /**
         * Remove coupon
         */
        removeCoupon: function() {
            return $.ajax({
                url: this.baseUrl + '/coupon',
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                xhrFields: {
                    withCredentials: true // Important: Send cookies with requests
                },
                crossDomain: false
            });
        },

        /**
         * Calculate shipping fee
         * @param {Object} address - Address object {province_id, district_id, ward_id, address}
         */
        calculateShippingFee: function(address) {
            return $.ajax({
                url: this.baseUrl + '/shipping-fee',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || CartAPI.getCookie('XSRF-TOKEN')
                },
                data: JSON.stringify(address)
            });
        },

        /**
         * Format number to currency
         * @param {number} amount - Amount to format
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
        },

        /**
         * Show error message
         * @param {string} message - Error message
         */
        showError: function(message) {
            var errorMsg = message || 'Có lỗi xảy ra, vui lòng thử lại';
            
            // Try to use toast if available, otherwise use alert
            if (typeof toastr !== 'undefined') {
                toastr.error(errorMsg);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: errorMsg,
                    confirmButtonText: 'Đóng'
                });
            } else {
                alert(errorMsg);
            }
            
            // Log to console for debugging
            console.error('CartAPI Error:', errorMsg);
        },

        /**
         * Show success message
         * @param {string} message - Success message
         */
        showSuccess: function(message) {
            if (!message) return;
            
            // Try to use toast if available, otherwise use console
            if (typeof toastr !== 'undefined') {
                toastr.success(message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                console.log('Success:', message);
            }
        },

        /**
         * Update cart UI
         * @param {Object} cartData - Cart data from API
         */
        updateCartUI: function(cartData) {
            if (!cartData || !cartData.data) return;

            const summary = cartData.data.summary || {};
            // Debug total from backend summary (single source of truth)
            if (summary.total !== undefined || summary.subtotal !== undefined) {
                console.log('[CartAPI] cart_summary', {
                    total: summary.total,
                    subtotal: summary.subtotal,
                    total_qty: summary.total_qty
                });
            }
            // IMPORTANT: Sidebar totals must always use backend summary (no client-side math)
            if (summary.subtotal !== undefined) {
                $('.subtotal-price').text(this.formatCurrency(summary.subtotal));
            }
            if (summary.total !== undefined) {
                $('.total-price').text(this.formatCurrency(summary.total));
            } else if (summary.subtotal !== undefined) {
                $('.total-price').text(this.formatCurrency(summary.subtotal));
            }
            
            // Update total price: luôn tin vào số từ Backend (Single Source of Truth)
            // (already set above for both .subtotal-price and .total-price)

            // Update cart count (if exists)
            if (summary.total_qty !== undefined) {
                $('.count-cart').text(summary.total_qty);
            }

            // Update item prices & subtotals
            if (cartData.data.items) {
                cartData.data.items.forEach(function(item) {
                    // Subtotal
                    $('.item-total-' + item.variant_id).text(
                        CartAPI.formatCurrency(item.subtotal)
                    );

                    // Unit price (đơn giá sau khuyến mãi)
                    if (item.price !== undefined) {
                        $('.item-unit-' + item.variant_id).text(
                            CartAPI.formatCurrency(item.price)
                        );
                    }

                    // Optional: original price gạch bỏ nếu có giảm giá
                    if (item.original_price !== undefined && item.original_price > item.price) {
                        $('.item-original-' + item.variant_id)
                            .text(CartAPI.formatCurrency(item.original_price))
                            .show();
                    } else {
                        $('.item-original-' + item.variant_id).hide();
                    }
                });
            }
        }
    };

    // Export to window for global access
    window.CartAPI = CartAPI;

})();
