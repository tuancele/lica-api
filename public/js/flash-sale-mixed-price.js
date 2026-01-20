/**
 * Flash Sale Mixed Price Handler
 * 
 * Xử lý hiển thị cảnh báo và breakdown giá khi mua vượt hạn mức Flash Sale
 */

(function() {
    'use strict';

    const FlashSaleMixedPrice = {
        /**
         * Tính giá với số lượng và hiển thị cảnh báo
         * @param {number} productId - Product ID
         * @param {number|null} variantId - Variant ID
         * @param {number} quantity - Số lượng
         * @param {string} priceElementSelector - Selector của element hiển thị giá
         * @param {string} warningContainerSelector - Selector của container hiển thị warning
         * @param {function} callback - Callback function sau khi tính giá thành công
         */
        calculatePriceWithQuantity: function(productId, variantId, quantity, priceElementSelector, warningContainerSelector, callback) {
            if (!productId || quantity <= 0) {
                return;
            }

            $.ajax({
                url: '/api/price/calculate',
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: JSON.stringify({
                    product_id: productId,
                    variant_id: variantId || null,
                    quantity: quantity
                }),
                success: (response) => {
                    if (response.success && response.data) {
                        const data = response.data;
                        
                        // QUAN TRỌNG: Kiểm tra tồn kho thực tế
                        if (data.is_available === false) {
                            // Sản phẩm vượt quá tồn kho thực tế
                            this.handleStockError(warningContainerSelector, data, priceElementSelector);
                            
                            // Gọi callback với thông tin lỗi
                            if (typeof callback === 'function') {
                                callback(data);
                            }
                            return;
                        }
                        
                        // Cập nhật giá hiển thị
                        if (priceElementSelector && data.price_breakdown && data.price_breakdown.length > 0) {
                            this.updatePriceDisplay(priceElementSelector, data);
                        }
                        
                        // Hiển thị cảnh báo nếu có (chỉ khi có Flash Sale warning)
                        if (data.warning && warningContainerSelector) {
                            this.showWarning(warningContainerSelector, data.warning, data.price_breakdown);
                        } else if (warningContainerSelector) {
                            this.hideWarning(warningContainerSelector);
                        }
                        
                        // Kích hoạt lại nút nếu đã bị disable trước đó
                        if (data.is_available !== false) {
                            this.enableCheckoutButtons();
                        }
                        
                        // Gọi callback nếu có (để cập nhật tổng tiền)
                        if (typeof callback === 'function') {
                            callback(data);
                        }
                    }
                },
                error: (xhr) => {
                    console.error('Error calculating price:', xhr);
                }
            });
        },

        /**
         * Cập nhật hiển thị giá với breakdown
         */
        updatePriceDisplay: function(selector, priceData) {
            const $element = $(selector);
            if (!$element.length) return;

            if (priceData.price_breakdown && priceData.price_breakdown.length > 1) {
                // Có giá hỗn hợp - hiển thị breakdown
                const breakdownText = priceData.price_breakdown.map(bd => {
                    const typeLabel = bd.type === 'flashsale' ? 'FS' : (bd.type === 'promotion' ? 'KM' : 'Thường');
                    return `${bd.quantity}x${this.formatNumber(bd.unit_price)}đ (${typeLabel})`;
                }).join(' + ');
                
                $element.html(`
                    <span class="price-breakdown" style="cursor: pointer; text-decoration: underline;" 
                          data-breakdown='${JSON.stringify(priceData.price_breakdown)}'
                          title="Click để xem chi tiết">
                        ${this.formatNumber(priceData.total_price)}đ
                    </span>
                `);
                
                // Thêm tooltip khi click
                $element.find('.price-breakdown').on('click', function() {
                    FlashSaleMixedPrice.showBreakdownModal($(this).data('breakdown'));
                });
            } else {
                // Giá đơn giản
                $element.html(`${this.formatNumber(priceData.total_price)}đ`);
            }
        },

        /**
         * Hiển thị cảnh báo
         */
        showWarning: function(containerSelector, warningMessage, priceBreakdown) {
            const $container = $(containerSelector);
            if (!$container.length) return;

            // Xóa warning cũ nếu có
            $container.find('.flash-sale-warning').remove();

            // Tạo warning element với tiêu đề mới (dùng class CSS thay vì inline style)
            const warningHtml = `
                <div class="flash-sale-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Vượt quá số lượng Flash Sale</strong>
                    ${priceBreakdown && priceBreakdown.length > 1 ? `
                        <div>
                            ${priceBreakdown.map(bd => {
                                const typeLabel = bd.type === 'flashsale' ? 'Flash Sale' : (bd.type === 'promotion' ? 'Khuyến mãi' : 'Giá thường');
                                return `${bd.quantity} sản phẩm × ${this.formatNumber(bd.unit_price)}đ (${typeLabel}) = ${this.formatNumber(bd.subtotal)}đ`;
                            }).join('<br>')}
                        </div>
                    ` : ''}
                </div>
            `;

            $container.append(warningHtml);
        },

        /**
         * Ẩn cảnh báo
         */
        hideWarning: function(containerSelector) {
            $(containerSelector).find('.flash-sale-warning').remove();
        },
        
        /**
         * Xử lý lỗi tồn kho (vượt quá tồn kho thực tế)
         */
        handleStockError: function(containerSelector, data, priceElementSelector) {
            const $container = $(containerSelector);
            if (!$container.length) return;
            
            // Xóa tất cả warning/error cũ
            $container.find('.flash-sale-warning, .stock-error').remove();
            
            // Tạo thông báo lỗi màu đỏ (dùng class CSS thay vì inline style)
            const errorHtml = `
                <div class="stock-error alert alert-danger checkout-warning">
                    <i class="fa fa-exclamation-circle"></i>
                    <strong>${data.stock_error || 'Số lượng vượt quá tồn kho'}</strong>
                </div>
            `;
            
            $container.html(errorHtml);
            
            // Tự động điều chỉnh số lượng về tồn kho thực tế
            if (data.total_physical_stock !== null && data.total_physical_stock !== undefined) {
                const maxStock = parseInt(data.total_physical_stock);
                
                // Tìm input quantity và cập nhật
                const $quantityInput = $('.quantity-input, .form-quatity, [id^="quantity-"]');
                if ($quantityInput.length) {
                    $quantityInput.each(function() {
                        const currentVal = parseInt($(this).val()) || 1;
                        if (currentVal > maxStock) {
                            $(this).val(maxStock);
                            // Trigger change event để tính lại giá
                            $(this).trigger('change');
                        }
                    });
                }
            }
            
            // Vô hiệu hóa nút thanh toán và thêm vào giỏ hàng
            this.disableCheckoutButtons();
            
            // Ẩn breakdown giá vì đơn hàng không hợp lệ
            if (priceElementSelector) {
                $(priceElementSelector).find('.price-breakdown').hide();
            }
        },
        
        /**
         * Vô hiệu hóa nút thanh toán và thêm vào giỏ hàng
         */
        disableCheckoutButtons: function() {
            // Disable nút thanh toán
            $('#place_order, .btn-checkout, button[type="submit"][id*="checkout"], button[type="submit"][id*="order"]').prop('disabled', true).addClass('disabled');
            
            // Disable nút thêm vào giỏ hàng
            $('.add-to-cart, .btn-add-cart, button[data-action="add-to-cart"]').prop('disabled', true).addClass('disabled');
            
            // Thêm class để style
            $('body').addClass('stock-error-active');
        },
        
        /**
         * Kích hoạt lại nút thanh toán và thêm vào giỏ hàng
         */
        enableCheckoutButtons: function() {
            // Enable nút thanh toán
            $('#place_order, .btn-checkout, button[type="submit"][id*="checkout"], button[type="submit"][id*="order"]').prop('disabled', false).removeClass('disabled');
            
            // Enable nút thêm vào giỏ hàng
            $('.add-to-cart, .btn-add-cart, button[data-action="add-to-cart"]').prop('disabled', false).removeClass('disabled');
            
            // Xóa class
            $('body').removeClass('stock-error-active');
        },

        /**
         * Hiển thị modal breakdown chi tiết
         */
        showBreakdownModal: function(breakdown) {
            let modalHtml = `
                <div class="modal fade" id="priceBreakdownModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chi tiết giá</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Loại giá</th>
                                            <th>Số lượng</th>
                                            <th>Đơn giá</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
            `;

            breakdown.forEach(bd => {
                const typeLabel = bd.type === 'flashsale' ? 'Flash Sale' : (bd.type === 'promotion' ? 'Khuyến mãi' : 'Giá thường');
                const typeColor = bd.type === 'flashsale' ? '#d9534f' : (bd.type === 'promotion' ? '#5bc0de' : '#777');
                modalHtml += `
                    <tr>
                        <td><span style="color: ${typeColor};">${typeLabel}</span></td>
                        <td>${bd.quantity}</td>
                        <td>${this.formatNumber(bd.unit_price)}đ</td>
                        <td><strong>${this.formatNumber(bd.subtotal)}đ</strong></td>
                    </tr>
                `;
            });

            const total = breakdown.reduce((sum, bd) => sum + bd.subtotal, 0);
            modalHtml += `
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Tổng cộng</th>
                                            <th>${this.formatNumber(total)}đ</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Xóa modal cũ nếu có
            $('#priceBreakdownModal').remove();
            
            // Thêm modal mới
            $('body').append(modalHtml);
            
            // Hiển thị modal
            const modal = new bootstrap.Modal(document.getElementById('priceBreakdownModal'));
            modal.show();
        },

        /**
         * Format số thành định dạng tiền Việt Nam
         */
        formatNumber: function(num) {
            return new Intl.NumberFormat('vi-VN').format(num);
        },
        
        /**
         * Cập nhật tổng giá trị đơn hàng
         * Tính tổng từ tất cả các item trong giỏ hàng/thanh toán
         */
        updateTotalOrderPrice: function() {
            let totalPrice = 0;
            
            // Lấy tất cả giá item từ các selector .item-total-{id} (Cart) hoặc .price-item-{id} (Checkout)
            $('[class*="item-total-"], [class*="price-item-"]').each(function() {
                const $item = $(this);
                const priceText = $item.text().replace(/[^\d]/g, '');
                const itemPrice = parseInt(priceText) || 0;
                
                // Nếu là .item-total-{id}, giá đã là subtotal (price × quantity)
                // Nếu là .price-item-{id}, cần nhân với quantity
                if ($item.attr('class').includes('price-item-')) {
                    const itemId = $item.attr('class').match(/price-item-(\d+)/);
                    if (itemId && itemId[1]) {
                        const variantId = itemId[1];
                        const $quantityInput = $('#quantity-' + variantId);
                        const quantity = parseInt($quantityInput.val()) || 1;
                        totalPrice += (itemPrice * quantity);
                    } else {
                        totalPrice += itemPrice;
                    }
                } else {
                    // .item-total-{id} đã là subtotal
                    totalPrice += itemPrice;
                }
            });
            
            // Cập nhật tổng tiền vào các selector tổng trong Cart
            $('.shop_table .order-total .total-price, .cart_totals .total-price').each(function() {
                $(this).text(FlashSaleMixedPrice.formatNumber(totalPrice) + 'đ');
            });
            
            return totalPrice;
        },
        
        /**
         * Khởi tạo Event Listeners cho Product Detail Page
         * Sử dụng Observer pattern để tránh bị overwrite
         */
        initProductDetailListeners: function() {
            // Sử dụng MutationObserver để đảm bảo hoạt động kể cả khi DOM thay đổi
            const observer = new MutationObserver((mutations) => {
                // Re-attach listeners nếu cần
                this.attachProductDetailListeners();
            });
            
            // Quan sát thay đổi trong quantity-selector
            const quantitySelector = document.querySelector('.quantity-selector');
            if (quantitySelector) {
                observer.observe(quantitySelector, { childList: true, subtree: true });
                this.attachProductDetailListeners();
            }
        },
        
        /**
         * Gắn Event Listeners cho Product Detail
         */
        attachProductDetailListeners: function() {
            // Remove old listeners để tránh duplicate
            $('.quantity-selector .btn_plus, .quantity-selector .btn_minus').off('click.flashsale');
            $('.quantity-input').off('change.flashsale input.flashsale');
            
            // Get product and variant IDs
            const productId = $('#detailProduct').attr('data-product-id');
            const variantId = $('input[name="variant_id"]').val();
            const $quantityInput = $('.quantity-selector .quantity-input');
            
            if (!productId || !$quantityInput.length) return;
            
            // Handle button clicks
            $('.quantity-selector .btn_plus').on('click.flashsale', function() {
                const current = parseInt($quantityInput.val()) || 1;
                $quantityInput.val(current + 1).trigger('change.flashsale');
            });
            
            $('.quantity-selector .btn_minus').on('click.flashsale', function() {
                const current = parseInt($quantityInput.val()) || 1;
                if (current > 1) {
                    $quantityInput.val(current - 1).trigger('change.flashsale');
                }
            });
            
            // Handle input change
            $quantityInput.on('change.flashsale input.flashsale', function() {
                const quantity = parseInt($(this).val()) || 1;
                
                // Debounce
                clearTimeout($(this).data('timeout'));
                $(this).data('timeout', setTimeout(() => {
                    FlashSaleMixedPrice.calculatePriceWithQuantity(
                        parseInt(productId),
                        variantId ? parseInt(variantId) : null,
                        quantity,
                        '.product-price-display',
                        '.flash-sale-warning-container'
                    );
                }, 300));
            });
        },
        
        /**
         * Khởi tạo Event Listeners cho Cart Page
         */
        initCartListeners: function() {
            // Remove old listeners
            $('body').off('click.flashsale', '.btn-plus, .btn-minus');
            $('body').off('change.flashsale input.flashsale', '.form-quatity');
            
            // Handle button clicks với namespace để tránh conflict
            $('body').on('click.flashsale', '.btn-plus, .btn-minus', function(e) {
                // Let existing handlers run first, then check Flash Sale
                setTimeout(() => {
                    const variantId = $(this).attr('data-id');
                    const $input = $('#quantity-cart-' + variantId);
                    if ($input.length) {
                        const quantity = parseInt($input.val()) || 1;
                        if (typeof checkFlashSalePrice === 'function') {
                            checkFlashSalePrice(variantId, quantity);
                        }
                    }
                }, 100);
            });
            
            // Handle manual input
            $('body').on('change.flashsale input.flashsale', '.form-quatity', function() {
                const inputId = $(this).attr('id');
                if (!inputId || !inputId.startsWith('quantity-cart-')) return;
                
                const variantId = inputId.replace('quantity-cart-', '');
                const quantity = parseInt($(this).val()) || 1;
                
                setTimeout(() => {
                    if (typeof checkFlashSalePrice === 'function') {
                        checkFlashSalePrice(variantId, quantity);
                    }
                }, 200);
            });
        },
        
        /**
         * Khởi tạo Event Listeners cho Checkout Page
         */
        initCheckoutListeners: function() {
            // Remove old listeners
            $('body').off('click.flashsale', '.qtyplus, .qtyminus');
            $('body').off('change.flashsale input.flashsale', '.form-quatity');
            
            // Handle button clicks
            $('body').on('click.flashsale', '.qtyplus, .qtyminus', function() {
                const variantId = $(this).attr('data-id');
                const $input = $('#quantity-' + variantId);
                if ($input.length) {
                    const quantity = parseInt($input.val()) || 1;
                    checkFlashSalePriceCheckout(variantId, quantity);
                }
            });
            
            // Handle manual input
            $('body').on('change.flashsale input.flashsale', '.form-quatity', function() {
                const inputId = $(this).attr('id');
                if (!inputId || !inputId.startsWith('quantity-')) return;
                
                const variantId = inputId.replace('quantity-', '');
                const quantity = parseInt($(this).val()) || 1;
                
                clearTimeout(window.checkoutPriceTimeout);
                window.checkoutPriceTimeout = setTimeout(() => {
                    if (typeof checkFlashSalePriceCheckout === 'function') {
                        checkFlashSalePriceCheckout(variantId, quantity);
                    }
                }, 500);
            });
        }
    };

    // Export to global scope
    window.FlashSaleMixedPrice = FlashSaleMixedPrice;
    
    // Auto-initialize khi DOM ready
    $(document).ready(function() {
        // Detect page type và khởi tạo listeners tương ứng
        if ($('#detailProduct').length) {
            // Product Detail Page
            FlashSaleMixedPrice.initProductDetailListeners();
        } else if ($('.shop_table.cart').length || $('.cart-wrapper').length) {
            // Cart Page
            FlashSaleMixedPrice.initCartListeners();
        } else if ($('#page_checkout').length || $('#checkoutForm').length) {
            // Checkout Page
            FlashSaleMixedPrice.initCheckoutListeners();
        }
    });

})();
