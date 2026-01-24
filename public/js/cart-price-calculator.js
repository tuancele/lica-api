/**
 * Cart Price Calculator - Shared Module for Cart & Checkout
 * 
 * Tính toán giá tiền toàn diện cho giỏ hàng và thanh toán
 * Hỗ trợ: Tiered Pricing, Voucher (Ship/SP/Đơn hàng), Tổng thanh toán
 * 
 * @author AI Assistant
 * @date 2026-01-XX
 */

(function() {
    'use strict';

    /**
     * Cart Price Calculator Module
     */
    const CartPriceCalculator = {
        /**
         * Tính giá sản phẩm với Tiered Pricing (Lũy tiến)
         * 
         * @param {number} quantity - Số lượng sản phẩm
         * @param {number} limit - Hạn mức khuyến mãi (L)
         * @param {number} promoPrice - Giá khuyến mãi (P_km)
         * @param {number} rootPrice - Giá gốc (P_root)
         * @returns {Object} { totalPrice, breakdown }
         */
        calculateItemPrice: function(quantity, limit, promoPrice, rootPrice) {
            if (!quantity || quantity <= 0) {
                return {
                    totalPrice: 0,
                    breakdown: []
                };
            }

            // Validate inputs
            limit = Math.max(0, limit || 0);
            promoPrice = Math.max(0, promoPrice || 0);
            rootPrice = Math.max(0, rootPrice || 0);

            let totalPrice = 0;
            const breakdown = [];

            if (quantity <= limit) {
                // Trong hạn mức: Tất cả tính theo giá KM
                totalPrice = quantity * promoPrice;
                breakdown.push({
                    type: 'promo',
                    quantity: quantity,
                    unitPrice: promoPrice,
                    subtotal: totalPrice
                });
            } else {
                // Vượt hạn mức: Tính giá hỗn hợp
                // Phần trong hạn mức: L * P_km
                const promoSubtotal = limit * promoPrice;
                breakdown.push({
                    type: 'promo',
                    quantity: limit,
                    unitPrice: promoPrice,
                    subtotal: promoSubtotal
                });

                // Phần vượt hạn mức: (Q - L) * P_root
                const excessQuantity = quantity - limit;
                const rootSubtotal = excessQuantity * rootPrice;
                breakdown.push({
                    type: 'normal',
                    quantity: excessQuantity,
                    unitPrice: rootPrice,
                    subtotal: rootSubtotal
                });

                totalPrice = promoSubtotal + rootSubtotal;
            }

            return {
                totalPrice: Math.max(0, totalPrice),
                breakdown: breakdown
            };
        },

        /**
         * Tính giảm giá Voucher Ship
         * 
         * @param {number} shippingFee - Phí ship gốc
         * @param {number} shippingDiscount - Giảm giá ship
         * @returns {number} Phí ship thực tế (không âm)
         */
        calculateShippingVoucher: function(shippingFee, shippingDiscount) {
            shippingFee = Math.max(0, shippingFee || 0);
            shippingDiscount = Math.max(0, shippingDiscount || 0);
            
            // max(0, phí ship - giảm giá ship)
            return Math.max(0, shippingFee - shippingDiscount);
        },

        /**
         * Tính giảm giá Voucher Sản phẩm
         * Áp dụng trực tiếp vào 1 dòng sản phẩm cụ thể
         * 
         * @param {number} itemSubtotal - Tổng tiền dòng sản phẩm
         * @param {Object} voucher - Voucher object { type, value, maxDiscount?, targetProductId? }
         * @returns {number} Số tiền giảm giá
         */
        calculateItemVoucher: function(itemSubtotal, voucher) {
            if (!voucher || !voucher.value) {
                return 0;
            }

            itemSubtotal = Math.max(0, itemSubtotal || 0);
            let discount = 0;

            if (voucher.type === 'PERCENT') {
                // Giảm theo %
                discount = (itemSubtotal * voucher.value) / 100;
                
                // Áp dụng trần tối đa nếu có
                if (voucher.maxDiscount && discount > voucher.maxDiscount) {
                    discount = voucher.maxDiscount;
                }
            } else if (voucher.type === 'FIXED') {
                // Giảm cố định
                discount = Math.min(voucher.value, itemSubtotal);
            }

            return Math.max(0, discount);
        },

        /**
         * Tính giảm giá Voucher Đơn hàng
         * Trừ vào tổng tiền sau khi đã cộng phí ship thực tế
         * 
         * @param {number} subtotal - Tổng tiền hàng
         * @param {number} shippingFee - Phí ship thực tế (sau voucher ship)
         * @param {Object} voucher - Voucher object { type, value, maxDiscount?, minOrder? }
         * @returns {Object} { discount, isValid } - isValid = false nếu không đạt Min Spend
         */
        calculateOrderVoucher: function(subtotal, shippingFee, voucher) {
            if (!voucher || !voucher.value) {
                return { discount: 0, isValid: true };
            }

            subtotal = Math.max(0, subtotal || 0);
            shippingFee = Math.max(0, shippingFee || 0);
            
            // Kiểm tra điều kiện đơn hàng tối thiểu (Min Spend)
            const orderTotal = subtotal + shippingFee;
            if (voucher.minOrder && orderTotal < voucher.minOrder) {
                return {
                    discount: 0,
                    isValid: false,
                    reason: 'Đơn hàng chưa đạt mức tối thiểu ' + this.formatCurrency(voucher.minOrder)
                };
            }

            let discount = 0;

            if (voucher.type === 'PERCENT') {
                // Giảm theo % của tổng đơn hàng (subtotal + shipping)
                discount = (orderTotal * voucher.value) / 100;
                
                // Áp dụng trần tối đa nếu có
                if (voucher.maxDiscount && discount > voucher.maxDiscount) {
                    discount = voucher.maxDiscount;
                }
            } else if (voucher.type === 'FIXED') {
                // Giảm cố định
                discount = Math.min(voucher.value, orderTotal);
            }

            return {
                discount: Math.max(0, discount),
                isValid: true
            };
        },

        /**
         * Validate và áp dụng Voucher
         * Giới hạn: Tối đa 2 voucher (1 Ship + 1 SP hoặc 1 Đơn)
         * 
         * @param {Array} vouchers - Mảng các voucher đang áp dụng
         * @param {Object} newVoucher - Voucher mới muốn áp dụng
         * @returns {Object} { success, message, vouchers }
         */
        applyVoucher: function(vouchers, newVoucher) {
            vouchers = vouchers || [];
            
            // Kiểm tra giới hạn số lượng voucher
            if (vouchers.length >= 2) {
                return {
                    success: false,
                    message: 'Chỉ được áp dụng tối đa 2 voucher',
                    vouchers: vouchers
                };
            }

            // Phân loại voucher hiện tại
            const hasShippingVoucher = vouchers.some(v => v.scope === 'SHIPPING');
            const hasItemVoucher = vouchers.some(v => v.scope === 'ITEM');
            const hasOrderVoucher = vouchers.some(v => v.scope === 'GLOBAL' || v.scope === 'ORDER');

            // Kiểm tra loại voucher mới
            if (newVoucher.scope === 'SHIPPING') {
                if (hasShippingVoucher) {
                    return {
                        success: false,
                        message: 'Đã có voucher vận chuyển',
                        vouchers: vouchers
                    };
                }
                // Cho phép thêm voucher ship
            } else if (newVoucher.scope === 'ITEM') {
                // Voucher SP và Voucher Đơn loại trừ lẫn nhau
                if (hasItemVoucher) {
                    return {
                        success: false,
                        message: 'Đã có voucher sản phẩm',
                        vouchers: vouchers
                    };
                }
                if (hasOrderVoucher) {
                    return {
                        success: false,
                        message: 'Voucher sản phẩm không thể dùng cùng voucher đơn hàng',
                        vouchers: vouchers
                    };
                }
            } else if (newVoucher.scope === 'GLOBAL' || newVoucher.scope === 'ORDER') {
                // Voucher Đơn và Voucher SP loại trừ lẫn nhau
                if (hasOrderVoucher) {
                    return {
                        success: false,
                        message: 'Đã có voucher đơn hàng',
                        vouchers: vouchers
                    };
                }
                if (hasItemVoucher) {
                    return {
                        success: false,
                        message: 'Voucher đơn hàng không thể dùng cùng voucher sản phẩm',
                        vouchers: vouchers
                    };
                }
            }

            // Thêm voucher mới
            const updatedVouchers = [...vouchers, newVoucher];

            return {
                success: true,
                message: 'Áp dụng voucher thành công',
                vouchers: updatedVouchers
            };
        },

        /**
         * Xóa voucher
         * 
         * @param {Array} vouchers - Mảng các voucher
         * @param {string} voucherCode - Mã voucher cần xóa
         * @returns {Array} Mảng voucher sau khi xóa
         */
        removeVoucher: function(vouchers, voucherCode) {
            if (!vouchers || !Array.isArray(vouchers)) {
                return [];
            }

            return vouchers.filter(v => v.code !== voucherCode);
        },

        /**
         * Tính tổng thanh toán cuối cùng
         * 
         * @param {Object} params - {
         *   items: Array<{ subtotal, itemVoucher? }>,
         *   shippingFee: number,
         *   shippingVoucher?: Object,
         *   orderVoucher?: Object
         * }
         * @returns {Object} {
         *   subtotal,           // Tổng tiền hàng
         *   itemDiscount,      // Tổng giảm giá voucher SP
         *   shippingFee,       // Phí ship thực tế
         *   shippingDiscount,  // Giảm giá voucher ship
         *   orderDiscount,     // Giảm giá voucher đơn hàng
         *   total              // Tổng thanh toán (không âm)
         * }
         */
        calculateTotal: function(params) {
            params = params || {};
            const items = params.items || [];
            let shippingFee = Math.max(0, params.shippingFee || 0);
            const shippingVoucher = params.shippingVoucher;
            const orderVoucher = params.orderVoucher;

            console.log('[CartPriceCalculator] calculateTotal called with:', {
                itemsCount: items.length,
                items: items,
                shippingFee: shippingFee,
                shippingVoucher: shippingVoucher,
                orderVoucher: orderVoucher
            });

            // 1. Tính tổng tiền hàng (Subtotal)
            let subtotal = 0;
            let itemDiscount = 0;

            items.forEach(item => {
                const itemSubtotal = Math.max(0, item.subtotal || 0);
                subtotal += itemSubtotal;

                // Áp dụng voucher sản phẩm nếu có
                if (item.voucher) {
                    const discount = this.calculateItemVoucher(itemSubtotal, item.voucher);
                    itemDiscount += discount;
                }
            });

            console.log('[CartPriceCalculator] Step 1 - Subtotal calculation:', {
                subtotal: subtotal,
                itemDiscount: itemDiscount,
                subtotalAfterItemDiscount: subtotal - itemDiscount
            });

            // 2. Tính phí ship và giảm giá ship
            let shippingDiscount = 0;
            if (shippingVoucher) {
                shippingDiscount = this.calculateShippingVoucher(shippingFee, shippingVoucher.value || 0);
                shippingFee = Math.max(0, shippingFee - (shippingVoucher.value || 0));
            }

            console.log('[CartPriceCalculator] Step 2 - Shipping calculation:', {
                shippingFeeOriginal: params.shippingFee || 0,
                shippingDiscount: shippingDiscount,
                shippingFeeFinal: shippingFee
            });

            // 3. Tính giảm giá voucher đơn hàng
            // Áp dụng vào tổng sau khi đã cộng phí ship thực tế
            let orderDiscount = 0;
            let orderVoucherValid = true;
            if (orderVoucher) {
                const orderVoucherResult = this.calculateOrderVoucher(
                    subtotal - itemDiscount, // Subtotal sau khi trừ voucher SP
                    shippingFee,             // Phí ship thực tế
                    orderVoucher
                );
                orderDiscount = orderVoucherResult.discount;
                orderVoucherValid = orderVoucherResult.isValid;
            }

            console.log('[CartPriceCalculator] Step 3 - Order voucher calculation:', {
                subtotalAfterItemDiscount: subtotal - itemDiscount,
                shippingFee: shippingFee,
                orderVoucher: orderVoucher,
                orderDiscount: orderDiscount,
                orderVoucherValid: orderVoucherValid
            });

            // 4. Tính tổng thanh toán: (Tiền hàng - Voucher) + Phí ship thực tế
            // Luôn dùng max(0, Total) để tránh tiền âm
            const totalBeforeMax = (subtotal - itemDiscount - orderDiscount) + shippingFee;
            const total = Math.max(0, totalBeforeMax);

            const step4Data = {
                subtotal: subtotal,
                itemDiscount: itemDiscount,
                orderDiscount: orderDiscount,
                shippingFee: shippingFee,
                calculation: `(${subtotal} - ${itemDiscount} - ${orderDiscount}) + ${shippingFee} = ${totalBeforeMax}`,
                totalBeforeMax: totalBeforeMax,
                totalFinal: total
            };
            console.log('[CartPriceCalculator] Step 4 - Final total calculation:', step4Data);
            
            // Log to Laravel if available
            if (typeof window !== 'undefined' && typeof window.logToLaravel === 'function') {
                window.logToLaravel('info', 'CartPriceCalculator Step 4 - Final total calculation', step4Data);
            }

            const result = {
                subtotal: subtotal,
                itemDiscount: itemDiscount,
                shippingFee: shippingFee,
                shippingDiscount: shippingDiscount,
                orderDiscount: orderDiscount,
                total: total,
                orderVoucherValid: orderVoucherValid
            };

            console.log('[CartPriceCalculator] Final result:', result);
            
            // Log final result to Laravel
            if (typeof window !== 'undefined' && typeof window.logToLaravel === 'function') {
                window.logToLaravel('info', 'CartPriceCalculator Final result', result);
            }
            
            return result;
        },

        /**
         * Format số tiền thành chuỗi VND
         * 
         * @param {number} amount - Số tiền
         * @returns {string} Chuỗi đã format (ví dụ: "1.000.000đ")
         */
        formatCurrency: function(amount) {
            if (typeof amount !== 'number' || isNaN(amount)) {
                return '0đ';
            }
            return new Intl.NumberFormat('vi-VN').format(Math.round(amount)) + 'đ';
        },

        /**
         * Parse chuỗi tiền thành số
         * 
         * @param {string} currencyString - Chuỗi tiền (ví dụ: "1.000.000đ")
         * @returns {number} Số tiền
         */
        parseCurrency: function(currencyString) {
            if (!currencyString || typeof currencyString !== 'string') {
                return 0;
            }
            // Loại bỏ tất cả ký tự không phải số
            const numberString = currencyString.replace(/[^\d]/g, '');
            return parseInt(numberString, 10) || 0;
        },

        /**
         * API: Lấy dữ liệu giỏ hàng từ Backend
         * 
         * @param {Function} callback - Callback function (success, error)
         * @returns {Promise|void}
         */
        fetchCartData: function(callback) {
            const apiUrl = '/api/v1/cart';
            
            // Check if jQuery is available
            if (typeof $ !== 'undefined' && $.ajax) {
                return $.ajax({
                    url: apiUrl,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    xhrFields: {
                        withCredentials: true
                    }
                }).done(function(response) {
                    if (response.success && response.data) {
                        if (typeof callback === 'function') {
                            callback(null, response.data);
                        }
                    } else {
                        const error = new Error(response.message || 'Failed to fetch cart data');
                        if (typeof callback === 'function') {
                            callback(error, null);
                        }
                    }
                }).fail(function(xhr, status, error) {
                    const err = new Error(error || 'Request failed');
                    if (typeof callback === 'function') {
                        callback(err, null);
                    }
                });
            } else {
                // Fallback: Use fetch API
                return fetch(apiUrl, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).then(response => response.json())
                  .then(data => {
                      if (data.success && data.data) {
                          if (typeof callback === 'function') {
                              callback(null, data.data);
                          }
                      } else {
                          const error = new Error(data.message || 'Failed to fetch cart data');
                          if (typeof callback === 'function') {
                              callback(error, null);
                          }
                      }
                  })
                  .catch(error => {
                      if (typeof callback === 'function') {
                          callback(error, null);
                      }
                  });
            }
        },

        /**
         * API: Tính toán tổng từ dữ liệu Backend
         * Wrapper function để tích hợp dễ dàng với API response
         * 
         * @param {Object} cartData - Dữ liệu từ API /api/v1/cart
         * @param {Object} options - { shippingFee?, shippingVoucher?, orderVoucher? }
         * @returns {Object} Kết quả tính toán
         */
        calculateFromCartData: function(cartData, options) {
            options = options || {};
            
            if (!cartData || !cartData.items || !Array.isArray(cartData.items)) {
                return {
                    subtotal: 0,
                    itemDiscount: 0,
                    shippingFee: 0,
                    shippingDiscount: 0,
                    orderDiscount: 0,
                    total: 0,
                    orderVoucherValid: true
                };
            }

            // Convert cart items to format for calculateTotal
            const items = cartData.items.map(item => ({
                subtotal: parseFloat(item.subtotal || 0),
                voucher: item.voucher || null
            }));

            // Get shipping fee from options or cart data
            const shippingFee = options.shippingFee !== undefined 
                ? parseFloat(options.shippingFee) 
                : parseFloat(cartData.summary?.shipping_fee || 0);

            // Get vouchers from options
            const shippingVoucher = options.shippingVoucher;
            const orderVoucher = options.orderVoucher;

            // Calculate total
            return this.calculateTotal({
                items: items,
                shippingFee: shippingFee,
                shippingVoucher: shippingVoucher,
                orderVoucher: orderVoucher
            });
        },

        /**
         * Update UI với kết quả tính toán
         * 
         * @param {Object} result - Kết quả từ calculateTotal hoặc calculateFromCartData
         * @param {Object} selectors - { subtotal?, total?, shippingFee?, discount? }
         */
        updateUI: function(result, selectors) {
            selectors = selectors || {};
            
            const format = this.formatCurrency.bind(this);
            
            console.log('[CartPriceCalculator] 🎨 updateUI called with:', {
                result: result,
                selectors: selectors
            });

            // Update subtotal
            if (selectors.subtotal) {
                const formattedSubtotal = format(result.subtotal);
                console.log('[CartPriceCalculator] 🎨 Updating subtotal:', {
                    selector: selectors.subtotal,
                    value: result.subtotal,
                    formatted: formattedSubtotal
                });
                $(selectors.subtotal).text(formattedSubtotal);
            }

            // Update total
            if (selectors.total) {
                const formattedTotal = format(result.total);
                console.log('[CartPriceCalculator] 🎨 Updating total:', {
                    selector: selectors.total,
                    value: result.total,
                    formatted: formattedTotal
                });
                $(selectors.total).text(formattedTotal);
            }

            // Update shipping fee
            if (selectors.shippingFee) {
                const formattedShippingFee = format(result.shippingFee);
                console.log('[CartPriceCalculator] 🎨 Updating shipping fee:', {
                    selector: selectors.shippingFee,
                    value: result.shippingFee,
                    formatted: formattedShippingFee
                });
                $(selectors.shippingFee).text(formattedShippingFee);
            }

            // Update discount
            if (selectors.discount) {
                const discountTotal = result.itemDiscount + result.orderDiscount;
                const formattedDiscount = '-' + format(discountTotal);
                console.log('[CartPriceCalculator] 🎨 Updating discount:', {
                    selector: selectors.discount,
                    itemDiscount: result.itemDiscount,
                    orderDiscount: result.orderDiscount,
                    discountTotal: discountTotal,
                    formatted: formattedDiscount
                });
                $(selectors.discount).text(formattedDiscount);
            }

            // Update item discount
            if (selectors.itemDiscount) {
                $(selectors.itemDiscount).text('-' + format(result.itemDiscount));
            }

            // Update order discount
            if (selectors.orderDiscount) {
                $(selectors.orderDiscount).text('-' + format(result.orderDiscount));
            }
        },

        /**
         * Central function: Update cart totals từ API data
         * SINGLE SOURCE OF TRUTH - Chỉ dùng CartPriceCalculator
         * 
         * @param {Object} cartData - Dữ liệu từ API /api/v1/cart
         * @param {Object} options - { shippingFee?, shippingVoucher?, orderVoucher? }
         * @param {Object} selectors - { subtotal?, total?, shippingFee?, discount? }
         * @returns {Object} Kết quả tính toán
         */
        updateCartTotals: function(cartData, options, selectors) {
            options = options || {};
            selectors = selectors || {};

            // Validate input
            if (!cartData || !cartData.items) {
                console.error('[CartPriceCalculator] Invalid cartData:', cartData);
                return null;
            }

            // Calculate using CartPriceCalculator
            const calcResult = this.calculateFromCartData(cartData, options);

            // Update UI
            this.updateUI(calcResult, selectors);

            // Return result for further processing
            return calcResult;
        },

        /**
         * Central function: Update item price display
         * SINGLE SOURCE OF TRUTH - Chỉ dùng CartPriceCalculator để format
         * 
         * @param {number} variantId - Variant ID
         * @param {number} subtotal - Item subtotal
         * @param {string} selector - Selector cho price element (default: '.item-total-{variantId}')
         */
        updateItemPrice: function(variantId, subtotal, selector) {
            if (!variantId || subtotal === undefined || subtotal === null) {
                console.warn('[CartPriceCalculator] Invalid params for updateItemPrice:', { variantId, subtotal });
                return;
            }

            const priceSelector = selector || ('.item-total-' + variantId);
            const formattedPrice = this.formatCurrency(parseFloat(subtotal) || 0);
            
            $(priceSelector).text(formattedPrice);
        },

        /**
         * Tính toán từ price_breakdown (Flash Sale Mixed Price)
         * Sử dụng CartPriceCalculator để tính lại từ breakdown data
         * 
         * @param {Array} priceBreakdown - Array từ Backend API
         * @returns {Object} { totalPrice, breakdown, formattedBreakdown }
         */
        calculateFromBreakdown: function(priceBreakdown) {
            if (!priceBreakdown || !Array.isArray(priceBreakdown) || priceBreakdown.length === 0) {
                return {
                    totalPrice: 0,
                    breakdown: [],
                    formattedBreakdown: ''
                };
            }

            let totalPrice = 0;
            const breakdown = [];
            const formattedParts = [];

            priceBreakdown.forEach(bd => {
                const quantity = parseInt(bd.quantity) || 0;
                // Support multiple field names: unit_price, price, unitPrice
                const unitPrice = parseFloat(bd.unit_price || bd.price || bd.unitPrice || 0);
                // Support multiple field names: subtotal, total, amount
                const subtotal = parseFloat(bd.subtotal || bd.total || bd.amount || (quantity * unitPrice));
                const type = bd.type || 'normal';

                // Debug log
                console.log('[CartPriceCalculator] Parsing breakdown item:', {
                    raw: bd,
                    quantity: quantity,
                    unitPrice: unitPrice,
                    subtotal: subtotal,
                    type: type
                });

                // CRITICAL: If unitPrice is 0 but subtotal is not 0, calculate unitPrice from subtotal
                if (unitPrice === 0 && subtotal > 0 && quantity > 0) {
                    const calculatedUnitPrice = subtotal / quantity;
                    console.log('[CartPriceCalculator] unitPrice is 0, calculating from subtotal:', calculatedUnitPrice);
                    breakdown.push({
                        type: type,
                        quantity: quantity,
                        unitPrice: calculatedUnitPrice,
                        subtotal: subtotal
                    });
                } else if (unitPrice > 0 || subtotal > 0) {
                    // Only skip if both are truly 0
                    totalPrice += subtotal;

                    breakdown.push({
                        type: type,
                        quantity: quantity,
                        unitPrice: unitPrice,
                        subtotal: subtotal
                    });
                } else {
                    console.warn('[CartPriceCalculator] Skipping breakdown item with zero price:', bd);
                    return;
                }

                // Format cho hiển thị
                const typeLabel = type === 'flashsale' ? 'Flash Sale' : 
                                 (type === 'promotion' ? 'Khuyến mãi' : 'Giá thường');
                const displayUnitPrice = unitPrice > 0 ? unitPrice : (subtotal > 0 && quantity > 0 ? subtotal / quantity : 0);
                formattedParts.push(
                    `${quantity} sản phẩm × ${this.formatCurrency(displayUnitPrice)} (${typeLabel}) = ${this.formatCurrency(subtotal)}`
                );
            });

            return {
                totalPrice: Math.max(0, totalPrice),
                breakdown: breakdown,
                formattedBreakdown: formattedParts.join('<br>')
            };
        },

        /**
         * Validate và tính lại giá từ Flash Sale breakdown
         * So sánh với kết quả từ Backend để đảm bảo tính nhất quán
         * 
         * @param {Object} backendData - Dữ liệu từ API /api/price/calculate
         * @returns {Object} { isValid, calculated, backend, difference }
         */
        validateFlashSalePrice: function(backendData) {
            if (!backendData || !backendData.price_breakdown) {
                return {
                    isValid: false,
                    calculated: null,
                    backend: backendData,
                    difference: null
                };
            }

            // Tính toán lại từ breakdown
            const calculated = this.calculateFromBreakdown(backendData.price_breakdown);
            const backendTotal = parseFloat(backendData.total_price) || 0;

            // So sánh (cho phép sai số nhỏ do làm tròn)
            const difference = Math.abs(calculated.totalPrice - backendTotal);
            const isValid = difference < 1; // Cho phép sai số < 1đ

            return {
                isValid: isValid,
                calculated: calculated,
                backend: {
                    totalPrice: backendTotal,
                    breakdown: backendData.price_breakdown
                },
                difference: difference
            };
        }
    };

    // Export to window for global access
    window.CartPriceCalculator = CartPriceCalculator;

    // Log initialization
    if (typeof console !== 'undefined' && console.log) {
        console.log('[CartPriceCalculator] Module initialized with API support');
    }

})();

