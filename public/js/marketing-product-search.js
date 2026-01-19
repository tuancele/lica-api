/**
 * Marketing Product Search - Common JS for Flash Sale & Deal
 * Provides unified product search modal functionality
 */
(function($) {
    'use strict';
    
    var MarketingProductSearch = {
        /**
         * Initialize product search modal
         * @param {Object} options - Configuration options
         * @param {string} options.modalId - Modal ID selector (e.g., '#myModal')
         * @param {string} options.searchInputId - Search input ID (e.g., '#modalSearch')
         * @param {string} options.productListBodyId - Product list tbody ID (e.g., '#product-list-body')
         * @param {string} options.searchRoute - Route name for search (e.g., 'flashsale.search_product')
         * @param {string} options.choseRoute - Route name for chose product (e.g., 'flashsale.chose_product')
         * @param {string} options.mainProductBodyId - Main product table tbody ID (e.g., '#main-product-body')
         * @param {string} options.checkAllId - Check all checkbox ID (e.g., '#checkall')
         */
        init: function(options) {
            var self = this;
            var config = $.extend({
                modalId: '#myModal',
                searchInputId: '#modalSearch',
                productListBodyId: '#product-list-body',
                searchRoute: '',
                choseRoute: '',
                mainProductBodyId: '#main-product-body',
                checkAllId: '#checkall',
                debounceTime: 500
            }, options);
            
            // Store config for later use
            self.config = config;
            
            // Ajax Search for Modal with debounce
            var searchTimeout;
            $(config.searchInputId).on('keyup', function() {
                var keyword = $(this).val();
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    self.loadProducts(keyword, config);
                }, config.debounceTime);
            });
            
            // Load products when modal is shown
            $(config.modalId).on('shown.bs.modal', function () {
                if($(config.productListBodyId).children().length === 0) {
                    self.loadProducts('', config);
                }
            });
            
            // Select All Logic
            $(config.checkAllId).click(function(){
                var isChecked = $(this).is(':checked');
                $(config.productListBodyId + ' input[name="productid[]"]').prop('checked', isChecked);
            });
            
            // Handle form submission - use config.modalId to scope the form selector
            $(config.modalId + ' .choseProduct, ' + config.modalId + ' .choseProduct2').on("submit", function (e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleChoseProduct(config);
            });
        },
        
        /**
         * Load products via AJAX
         */
        loadProducts: function(keyword, config) {
            // Extract query params from route URL
            var url = config.searchRoute;
            var urlParts = url.split('?');
            var baseUrl = urlParts[0];
            var queryParams = {};
            
            if (urlParts.length > 1) {
                var params = urlParts[1].split('&');
                params.forEach(function(param) {
                    var parts = param.split('=');
                    if (parts.length === 2) {
                        queryParams[decodeURIComponent(parts[0])] = decodeURIComponent(parts[1]);
                    }
                });
            }
            
            // Add keyword to params
            queryParams.keyword = keyword;
            
            $.ajax({
                url: baseUrl,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    keyword: keyword,
                    type: queryParams.type || 'main',
                    deal_id: queryParams.deal_id || null
                },
                beforeSend: function() {
                    $(config.productListBodyId).html('<tr><td colspan="6" class="text-center">Đang tải...</td></tr>');
                },
                success: function(res) {
                    $(config.productListBodyId).html(res.html);
                },
                error: function() {
                    $(config.productListBodyId).html('<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>');
                }
            });
        },
        
        /**
         * Handle product selection
         */
        handleChoseProduct: function(config) {
            // 1. Get IDs already in the main table
            var existingIds = [];
            $(config.mainProductBodyId + ' tr').each(function() {
                var id = $(this).attr('class');
                if (id) {
                    // Extract ID from class like "item-123" or "item-123-variant-456"
                    var match = id.match(/item-(\d+)(?:-variant-(\d+))?/);
                    if (match) {
                        if (match[2]) {
                            existingIds.push(match[1] + '_v' + match[2]);
                        } else {
                            existingIds.push(match[1]);
                        }
                    }
                }
            });
            
            // 2. Get IDs selected in modal - use config.productListBodyId
            var selectedIds = [];
            $(config.productListBodyId + ' input[name="productid[]"]:checked').each(function() {
                var id = $(this).val();
                if (!existingIds.includes(id)) {
                    selectedIds.push(id);
                }
            });
            
            if(selectedIds.length === 0) {
                if($(config.productListBodyId + ' input[name="productid[]"]:checked').length > 0) {
                    alert('Các sản phẩm đã chọn đều đã có trong danh sách!');
                } else {
                    alert('Vui lòng chọn ít nhất 1 sản phẩm');
                }
                return false;
            }
            
            var self = this;
            var $submitButton = $(config.modalId + ' button[type="submit"]');
            $.ajax({
                type: 'post',
                url: config.choseRoute,
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    productid: selectedIds,
                },
                beforeSend: function () {
                    $submitButton.html('<img src="/public/image/load.gif" style="height:100%;">');
                    $submitButton.prop('disabled',true);
                },
                success: function (res) {
                    $submitButton.html('Xác nhận');
                    $submitButton.prop('disabled',false);
                    $(config.modalId).modal('hide');
                    
                    // Extract tbody content from response if it's a full HTML structure
                    var $response = $(res);
                    var tbodyContent = '';
                    
                    // Check if response contains tbody
                    if ($response.find('tbody').length > 0) {
                        tbodyContent = $response.find('tbody').html();
                        // Also check if there are scripts to execute
                        var scripts = $response.find('script');
                        scripts.each(function() {
                            eval($(this).html());
                        });
                    } else if ($response.find('tr').length > 0) {
                        // Response is already tbody rows
                        tbodyContent = $response.html();
                    } else {
                        // Response might be full HTML with wrapper
                        // Try to find tbody in the response
                        var tbodyMatch = res.match(/<tbody[^>]*>([\s\S]*?)<\/tbody>/i);
                        if (tbodyMatch) {
                            tbodyContent = tbodyMatch[1];
                        } else {
                            // Fallback: use entire response
                            tbodyContent = res;
                        }
                    }
                    
                    // Append to tbody instead of replacing container
                    if (config.appendToSelector) {
                        // Check if appendToSelector is tbody or container
                        var $target = $(config.appendToSelector);
                        if ($target.is('tbody')) {
                            // Append rows to tbody
                            $target.append(tbodyContent);
                        } else {
                            // Find tbody inside container and append
                            var $tbody = $target.find('tbody');
                            if ($tbody.length > 0) {
                                $tbody.append(tbodyContent);
                            } else {
                                // No tbody found, replace entire container
                                $target.html(res);
                            }
                        }
                    } else {
                        // Use mainProductBodyId (should be tbody)
                        $(config.mainProductBodyId).append(tbodyContent);
                    }
                    
                    // Re-initialize price formatting
                    if (typeof $ !== 'undefined' && $.fn.number) {
                        $('body .price').number(true, 0);
                    }
                    
                    // Update count - try both selectors
                    var $countChoose = $('.count_choose, .count_choose2');
                    if ($countChoose.length > 0) {
                        var count = $(config.mainProductBodyId).find('input[name="checklist[]"], input[name="checklist2[]"]').filter(':checked').length;
                        $countChoose.html(count);
                    }
                    
                    // Initialize validation for newly added rows (if function exists)
                    if (typeof initializeValidation === 'function') {
                        initializeValidation();
                    }
                },
                error: function(xhr, status, error){
                    alert('Có lỗi xảy ra, xin vui lòng thử lại');
                    $submitButton.html('Xác nhận');
                    $submitButton.prop('disabled',false);
                }
            });
            return false;
        }
    };
    
    // Export to global scope
    window.MarketingProductSearch = MarketingProductSearch;
    
})(jQuery);
