@extends('Layout::layout')
@section('title','Nhập hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Nhập hàng',
])

<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/import-goods/create">
        @csrf
        <div class="row">
            <!-- Thông tin đơn hàng -->
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-file-text-o"></i> Thông tin đơn hàng</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mã đơn hàng <span class="text-red">*</span></label>
                                    <input type="text" name="code" class="form-control" placeholder="Nhập mã đơn hàng" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Số hóa đơn VAT</label>
                                    <input type="text" name="vat_invoice" class="form-control" placeholder="Nhập số hóa đơn VAT (nếu có)">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nội dung nhập <span class="text-red">*</span></label>
                                    <input type="text" name="subject" class="form-control" placeholder="Nhập nội dung" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ghi chú</label>
                                    <textarea name="content" class="form-control" rows="2" placeholder="Nhập ghi chú (nếu có)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-cubes"></i> Sản phẩm nhập</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-sm btn-primary" id="btnAddProduct">
                                <i class="fa fa-plus"></i> Thêm sản phẩm
                            </button>
                        </div>
                    </div>
                    <div class="box-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover" id="listProduct" number="1">
                                <thead>
                                    <tr class="bg-light-gray">
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="25%">Sản phẩm</th>
                                        <th width="20%">Phân loại</th>
                                        <th width="15%">Giá nhập (đ)</th>
                                        <th width="10%">Số lượng</th>
                                        <th width="15%">Thành tiền</th>
                                        <th width="10%" class="text-center">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="item_product item-1" item="1">
                                        <td class="text-center"><strong>1</strong></td>
                                        <td>
                                            <select class="form-control select_product select" name="product_id[]" data-placeholder="Nhập tên sản phẩm để tìm kiếm..." required>
                                                <option value="0">-- Chọn sản phẩm --</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control select_variant" name="variant_id[]" required>
                                                <option value="">-- Chọn phân loại --</option>
                                            </select>
                                            <small class="text-muted variant-info" style="display:none;"></small>
                                        </td>
                                        <td>
                                            <input type="text" name="price[]" class="form-control input-price" placeholder="0" required data-original-value="">
                                        </td>
                                        <td>
                                            <input type="text" name="qty[]" class="form-control input-qty" placeholder="0" required data-original-value="">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control text-total" readonly value="0 đ" style="background-color:#f5f5f5; font-weight:bold; color:#d9534f;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-danger btnDelete" title="Xóa">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light-blue">
                                        <td colspan="5" class="text-right"><strong>TỔNG CỘNG:</strong></td>
                                        <td colspan="2">
                                            <strong class="text-total-all" style="font-size:18px; color:#d9534f;">0 đ</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="col-md-12">
                <div class="box-footer">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-save"></i> Lưu đơn nhập hàng
                    </button>
                    <a href="/admin/import-goods" class="btn btn-default btn-lg">
                        <i class="fa fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>
        </div>
    </form>
</section>

<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<style>
    .box-primary { border-top-color: #3c8dbc !important; }
    .box-success { border-top-color: #00a65a !important; }
    .bg-light-gray { background-color: #f9f9f9 !important; }
    .bg-light-blue { background-color: #e3f2fd !important; }
    
    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }
    
    .input-price:focus, .input-qty:focus {
        border-color: #3c8dbc;
        box-shadow: 0 0 5px rgba(60, 141, 188, 0.3);
    }
    
    .variant-info {
        margin-top: 5px;
        font-size: 11px;
    }
    
    .text-red {
        color: #d9534f;
    }
    
    .select2-container {
        width: 100% !important;
    }
    
    .select2-result-product {
        padding: 8px 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .select2-result-product .product-name {
        font-size: 14px;
        line-height: 1.5;
    }
    
    .select2-results__option--highlighted .product-name {
        color: #fff;
    }
    
    /* Màu so le cho kết quả tìm kiếm */
    .select2-results__option:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    .select2-results__option:nth-child(odd) {
        background-color: #ffffff;
    }
    
    .select2-results__option--highlighted {
        background-color: #3c8dbc !important;
        color: #fff;
    }
    
    .select2-results__option[aria-selected="true"] {
        background-color: #e8f4f8 !important;
    }
    
    .select2-results__option[aria-selected="true"]:hover {
        background-color: #3c8dbc !important;
        color: #fff;
    }
    
    .table td {
        vertical-align: middle !important;
    }
    
    .btnDelete {
        padding: 5px 10px;
    }
    
    .input-price, .input-qty {
        text-align: right;
        font-weight: 500;
    }
    
    .input-price:focus, .input-qty:focus {
        background-color: #fffacd;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>

<script>
$(document).ready(function() {
    // Initialize Select2 with AJAX search (only search when typing)
    $('.select_product').select2({
        theme: 'bootstrap',
        width: '100%',
        placeholder: 'Nhập tên sản phẩm để tìm kiếm...',
        allowClear: true,
        minimumInputLength: 2, // Chỉ tìm kiếm khi nhập ít nhất 2 ký tự
        ajax: {
            url: '/admin/import-goods/searchProducts',
            dataType: 'json',
            delay: 300, // Delay 300ms sau khi người dùng ngừng gõ
            data: function (params) {
                return {
                    q: params.term, // Từ khóa tìm kiếm
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: false // Không phân trang, chỉ hiển thị 50 kết quả đầu
                    }
                };
            },
            cache: true // Cache kết quả để tăng tốc
        },
        language: {
            inputTooShort: function() {
                return "Vui lòng nhập ít nhất 2 ký tự để tìm kiếm";
            },
            noResults: function() {
                return "Không tìm thấy sản phẩm";
            },
            searching: function() {
                return "Đang tìm kiếm...";
            }
        },
        templateResult: function(product) {
            if (product.loading) {
                return "Đang tìm kiếm...";
            }
            if (!product.id) {
                return product.text;
            }
            var $result = $(
                '<div class="select2-result-product">' +
                '<div class="product-name"><strong>' + product.text + '</strong></div>' +
                '</div>'
            );
            return $result;
        },
        templateSelection: function(product) {
            return product.text || product.id;
        },
        escapeMarkup: function (markup) {
            return markup; // Cho phép HTML trong kết quả
        }
    });
    
    // Initialize variant select (simple)
    $('.select_variant').select2({
        theme: 'bootstrap',
        width: '100%',
        minimumResultsForSearch: Infinity
    });
    
    // Add new product row
    $('#btnAddProduct').click(function(){
        var id = $('#listProduct').attr('number');
        var next = parseInt(id) + 1;
        $('#listProduct').attr('number', next);
        
        $.ajax({
            type: 'get',
            url: '/admin/import-goods/loadAdd',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                var $newRow = $('<tr class="item-'+next+'" item="'+next+'">'+res+'</tr>');
                $newRow.find('td:first').html('<strong>'+next+'</strong>');
                $('#listProduct tbody').append($newRow);
                
                // Initialize Select2 for new row with AJAX search
                $('.item-'+next+' .select_product').select2({
                    theme: 'bootstrap',
                    width: '100%',
                    placeholder: 'Nhập tên sản phẩm để tìm kiếm...',
                    allowClear: true,
                    minimumInputLength: 2,
                    ajax: {
                        url: '/admin/import-goods/searchProducts',
                        dataType: 'json',
                        delay: 300,
                        data: function (params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.results,
                                pagination: {
                                    more: false
                                }
                            };
                        },
                        cache: true
                    },
                    language: {
                        inputTooShort: function() {
                            return "Vui lòng nhập ít nhất 2 ký tự để tìm kiếm";
                        },
                        noResults: function() {
                            return "Không tìm thấy sản phẩm";
                        },
                        searching: function() {
                            return "Đang tìm kiếm...";
                        }
                    },
                    templateResult: function(product) {
                        if (product.loading) {
                            return "Đang tìm kiếm...";
                        }
                        if (!product.id) {
                            return product.text;
                        }
                        var $result = $(
                            '<div class="select2-result-product">' +
                            '<div class="product-name"><strong>' + product.text + '</strong></div>' +
                            '</div>'
                        );
                        return $result;
                    },
                    templateSelection: function(product) {
                        return product.text || product.id;
                    },
                    escapeMarkup: function (markup) {
                        return markup;
                    }
                });
                
                $('.item-'+next+' .select_variant').select2({
                    theme: 'bootstrap',
                    width: '100%',
                    minimumResultsForSearch: Infinity
                });
                
                // Focus on product select
                setTimeout(function() {
                    $('.item-'+next+' .select_product').select2('open');
                }, 100);
            }
        });
    });
    
    // Load variants when product selected
    $('#listProduct').on('change', '.select_product', function(){
        var item = $(this).closest('tr').attr('item');
        var productId = $(this).val();
        var $row = $('.item-'+item);
        var $variantSelect = $row.find('.select_variant');
        var $variantInfo = $row.find('.variant-info');
        
        if(productId && productId != '0'){
            $.ajax({
                type: 'get',
                url: '/admin/import-goods/getVariants/'+productId,
                success: function (res) {
                    $variantSelect.html(res.variants);
                    $variantInfo.hide();
                    
                    // Auto focus on variant select
                    setTimeout(function() {
                        $variantSelect.focus();
                    }, 100);
                },
                error: function() {
                    $variantSelect.html('<option value="">-- Không có phân loại --</option>');
                    $variantInfo.hide();
                }
            });
        } else {
            $variantSelect.html('<option value="">-- Chọn phân loại --</option>');
            $variantInfo.hide();
        }
    });
    
    // Show variant info and stock when variant selected
    $('#listProduct').on('change', '.select_variant', function(){
        var item = $(this).closest('tr').attr('item');
        var variantId = $(this).val();
        var $row = $('.item-'+item);
        var $variantInfo = $row.find('.variant-info');
        
        if(variantId && variantId != ''){
            // Get variant stock info
            $.ajax({
                type: 'get',
                url: '/admin/import-goods/getVariantStock/'+variantId,
                success: function(res) {
                    if(res.stock !== undefined) {
                        $variantInfo.html('<i class="fa fa-cubes"></i> Tồn kho: <strong>'+res.stock+'</strong>').show();
                    }
                }
            });
            
            // Auto focus on price input
            setTimeout(function() {
                $row.find('.input-price').focus();
            }, 100);
        } else {
            $variantInfo.hide();
        }
    });
    
    // Format number with thousand separator
    function formatNumber(num) {
        if (num === null || num === undefined || num === '') return '';
        // Convert to string and remove all non-digit characters first
        var number = num.toString().replace(/[^\d]/g, '');
        if (number === '') return '';
        // Convert to number to remove leading zeros, then back to string
        number = parseInt(number, 10).toString();
        // Add thousand separators
        return number.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    // Parse number (remove thousand separators)
    function parseNumber(str) {
        if (!str || str === '') return 0;
        // Remove all non-digit characters and convert to integer
        var cleaned = str.toString().replace(/[^\d]/g, '');
        if (cleaned === '') return 0;
        return parseInt(cleaned, 10) || 0;
    }
    
    // Format input on input event
    $('#listProduct').on('input', '.input-price, .input-qty', function(){
        var $input = $(this);
        var cursorPos = $input[0].selectionStart;
        var oldValue = $input.val() || '';
        var newValue = $input.val();
        
        // Parse the current value to get raw number
        var rawNumber = parseNumber(newValue);
        
        // Format the number
        var formatted = formatNumber(rawNumber);
        
        // Only update if value changed to avoid cursor jumping
        if (formatted !== oldValue) {
            $input.val(formatted);
            
            // Restore cursor position (approximate)
            var oldLength = oldValue.length;
            var newLength = formatted.length;
            var diff = newLength - oldLength;
            var newCursorPos = Math.max(0, Math.min(cursorPos + diff, formatted.length));
            $input[0].setSelectionRange(newCursorPos, newCursorPos);
        }
        
        // Calculate row total
        var $row = $input.closest('tr');
        var price = parseNumber($row.find('.input-price').val());
        var qty = parseNumber($row.find('.input-qty').val());
        var total = price * qty;
        
        $row.find('.text-total').val(formatCurrency(total));
        calculateGrandTotal();
    });
    
    // Store original value on focus
    $('#listProduct').on('focus', '.input-price, .input-qty', function(){
        var $input = $(this);
        // Parse current value to get raw number
        var rawValue = parseNumber($input.val());
        $input.data('original-value', rawValue);
        // Show raw number when focusing (without formatting)
        if (rawValue > 0) {
            $input.val(rawValue);
        } else {
            $input.val('');
        }
    });
    
    // Format on blur
    $('#listProduct').on('blur', '.input-price, .input-qty', function(){
        var $input = $(this);
        var value = parseNumber($input.val());
        if (value > 0) {
            $input.val(formatNumber(value));
        } else {
            $input.val('');
        }
    });
    
    // Auto focus to quantity when price field loses focus (blur)
    $('#listProduct').on('blur', '.input-price', function(){
        var $row = $(this).closest('tr');
        var price = parseNumber($(this).val());
        // Only auto-focus if price is entered
        if(price > 0) {
            setTimeout(function() {
                $row.find('.input-qty').focus().select();
            }, 100);
        }
    });
    
    // Delete row
    $('#listProduct').on('click', '.btnDelete', function(){
        var $row = $(this).closest('tr');
        if(confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
            $row.fadeOut(300, function() {
                $(this).remove();
                updateRowNumbers();
                calculateGrandTotal();
            });
        }
    });
    
    // Keyboard navigation - Enter key
    $('#listProduct').on('keydown', '.input-price', function(e){
        if(e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            // Format before moving
            var value = parseNumber($(this).val());
            if (value > 0) {
                $(this).val(formatNumber(value));
            }
            $row.find('.input-qty').focus().select();
        }
    });
    
    $('#listProduct').on('keydown', '.input-qty', function(e){
        if(e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            var $row = $(this).closest('tr');
            var $nextRow = $row.next('.item_product');
            
            if($nextRow.length > 0) {
                $nextRow.find('.select_product').select2('open');
            } else {
                // Add new row if at last row
                $('#btnAddProduct').click();
            }
        }
    });
    
    // Tab navigation - better UX
    $('#listProduct').on('keydown', '.input-price', function(e){
        if(e.key === 'Tab' || e.keyCode === 9) {
            if(!e.shiftKey) {
                var $row = $(this).closest('tr');
                var price = parseFloat($(this).val()) || 0;
                if(price > 0) {
                    e.preventDefault();
                    $row.find('.input-qty').focus().select();
                }
            }
        }
    });
    
    // Update row numbers
    function updateRowNumbers() {
        $('#listProduct tbody tr.item_product').each(function(index) {
            $(this).find('td:first').html('<strong>'+(index+1)+'</strong>');
            $(this).attr('item', index+1);
        });
        $('#listProduct').attr('number', $('#listProduct tbody tr.item_product').length);
    }
    
    // Calculate grand total
    function calculateGrandTotal() {
        var grandTotal = 0;
        $('#listProduct tbody tr.item_product').each(function() {
            var totalText = $(this).find('.text-total').val();
            var total = parseNumber(totalText);
            grandTotal += total;
        });
        $('.text-total-all').text(formatCurrency(grandTotal));
    }
    
    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
    }
    
    // Format all number inputs before submit (remove thousand separators)
    $('#tblForm').on('submit', function(e) {
        // Remove formatting from all price and qty inputs before submit
        $('#listProduct tbody tr.item_product').each(function() {
            var $row = $(this);
            var price = parseNumber($row.find('.input-price').val());
            var qty = parseNumber($row.find('.input-qty').val());
            
            // Set raw number values
            $row.find('.input-price').val(price);
            $row.find('.input-qty').val(qty);
        });
        
        // Validate products
        var hasProducts = false;
        $('#listProduct tbody tr.item_product').each(function() {
            var productId = $(this).find('.select_product').val();
            var variantId = $(this).find('.select_variant').val();
            if(productId && productId != '0' && variantId && variantId != '') {
                hasProducts = true;
                return false;
            }
        });
        
        if(!hasProducts) {
            e.preventDefault();
            alert('Vui lòng thêm ít nhất một sản phẩm!');
            return false;
        }
    });
});
</script>
@endsection
