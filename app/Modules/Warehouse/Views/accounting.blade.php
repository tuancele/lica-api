@extends('Layout::layout')
@section('title', 'Nhập/Xuất hàng')
@push('styles')
<link href="/public/admin/css/warehouse-accounting.css" rel="stylesheet" type="text/css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
@include('Layout::breadcrumb', [
    'title' => isset($receipt) && $receipt->id ? 'Xem phiếu ' . $receipt->receipt_code : 'Tạo phiếu Nhập/Xuất',
])

<section class="content" data-api-token="{{ $apiToken }}">
    <div style="margin-bottom: 15px;">
        <a href="{{ route('warehouse.accounting') }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Quay lại danh sách
        </a>
    </div>
    <div class="warehouse-accounting-container">
        <form id="warehouse-accounting-form" class="warehouse-accounting-form" @if(isset($receipt) && $receipt->status === 'completed') data-readonly="true" @endif>
            @csrf
            <input type="hidden" name="receipt_id" value="{{ $receipt->id ?? '' }}">

            <!-- Form Header theo mẫu 02-VT -->
            <div class="receipt-form-header">
                <div class="receipt-form-top">
                    <div class="receipt-form-left">
                        <div class="form-field">
                            <label>Đơn vị:</label>
                            <input type="text" name="company_name" id="company-name" class="form-input" 
                                   value="{{ config('app.name', 'Công ty TNHH Lica') }}" placeholder="Tên đơn vị">
                        </div>
                        <div class="form-field">
                            <label>Bộ phận:</label>
                            <select name="department" id="department" class="form-input" 
                                    @if(isset($receipt) && $receipt->status === 'completed') disabled @endif>
                                <option value="Mua hàng" {{ ($receipt->type ?? 'import') === 'import' ? 'selected' : '' }}>Mua hàng</option>
                                <option value="Bán hàng" {{ ($receipt->type ?? 'import') === 'export' ? 'selected' : '' }}>Bán hàng</option>
                            </select>
                        </div>
                    </div>
                    <div class="receipt-form-qr-center">
                        <!-- QR Code -->
                        <div class="qr-code-container">
                            @if(isset($receipt) && $receipt->receipt_code)
                                <img src="{{ route('warehouse.accounting.qrcode', ['receiptCode' => $receipt->receipt_code]) }}" 
                                     alt="QR Code: {{ $receipt->receipt_code }}" 
                                     class="qr-code-image" 
                                     id="qr-code-image">
                            @else
                                <img src="" alt="QR Code" class="qr-code-image" id="qr-code-image" style="display: none;">
                            @endif
                        </div>
                    </div>
                    <div class="receipt-form-right">
                        <div class="form-number">Mẫu số: 02 - VT</div>
                        <div class="form-circular">
                            (Kèm theo Thông tư số 99/2025/TT-BTC<br>
                            ngày 27 tháng 10 năm 2025 của Bộ trưởng Bộ Tài chính)
                        </div>
                    </div>
                </div>
                
                <div class="receipt-title-section">
                    <div class="receipt-title-main" id="receipt-title-main">
                        {{ ($receipt->type ?? 'import') === 'import' ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO' }}
                    </div>
                    <div class="receipt-date">
                        Ngày <input type="number" name="day" id="receipt-day" class="date-input" 
                                    value="{{ isset($receipt) ? $receipt->created_at->day : date('d') }}" 
                                    min="1" max="31" style="width: 40px;"> 
                        tháng <input type="number" name="month" id="receipt-month" class="date-input" 
                                     value="{{ isset($receipt) ? $receipt->created_at->month : date('m') }}" 
                                     min="1" max="12" style="width: 40px;"> 
                        năm <input type="number" name="year" id="receipt-year" class="date-input" 
                                   value="{{ isset($receipt) ? $receipt->created_at->year : date('Y') }}" 
                                   min="2020" max="2100" style="width: 60px;">
                    </div>
                </div>
                
                <div class="receipt-accounting-info">
                    <div class="accounting-left">
                        <div class="form-field">
                            <label>Họ và tên người nhận hàng:</label>
                            <input type="text" name="recipient_name" id="recipient-name" class="form-input" 
                                   value="{{ ($receipt->type ?? 'import') === 'import' ? ($receipt->supplier_name ?? '') : ($receipt->customer_name ?? '') }}" 
                                   placeholder="Họ và tên người nhận hàng">
                        </div>
                        <div class="form-field">
                            <label>Địa chỉ (bộ phận):</label>
                            <input type="text" name="recipient_address" id="recipient-address" class="form-input" 
                                   placeholder="Địa chỉ">
                        </div>
                        <div class="form-field">
                            <label>Lý do <span id="reason-label">{{ ($receipt->type ?? 'import') === 'import' ? 'nhập' : 'xuất' }}</span> kho:</label>
                            <input type="text" name="reason" id="reason" class="form-input" 
                                   value="{{ $receipt->subject ?? '' }}" placeholder="Lý do">
                        </div>
                        <div class="form-field">
                            <label><span id="warehouse-label">{{ ($receipt->type ?? 'import') === 'import' ? 'Nhập' : 'Xuất' }}</span> tại kho (ngăn lô):</label>
                            <input type="text" name="warehouse_location" id="warehouse-location" class="form-input" 
                                   placeholder="Kho/ngăn lô">
                            <label style="margin-left: 20px;">Địa điểm:</label>
                            <input type="text" name="location" id="location" class="form-input" 
                                   placeholder="Địa điểm" style="width: 200px;">
                        </div>
                    </div>
                    <div class="accounting-right">
                        <div class="form-field">
                            <label>Số:</label>
                            <input type="text" name="receipt_code" id="receipt-code" class="form-input receipt-code-display" 
                                   value="{{ $receipt->receipt_code ?? '' }}" readonly>
                        </div>
                        <div class="form-field">
                            <label>Nợ:</label>
                            <input type="text" name="debit" id="debit" class="form-input" placeholder="...">
                        </div>
                        <div class="form-field">
                            <label>Có:</label>
                            <input type="text" name="credit" id="credit" class="form-input" placeholder="...">
                        </div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="type" id="receipt-type" value="{{ $receipt->type ?? 'import' }}">


            <!-- Items Table -->
            <div class="items-table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="col-stt">STT<br><small>A</small></th>
                            <th class="col-product">Tên, nhãn hiệu, quy cách, phẩm chất vật tư, dụng cụ, sản phẩm, hàng hóa<br><small>B</small></th>
                            <th class="col-code">Mã số<br><small>C</small></th>
                            <th class="col-unit">Đơn vị tính<br><small>D</small></th>
                            <th class="col-quantity-header" colspan="2">Số lượng</th>
                            <th class="col-price">Đơn giá<br><small>3</small></th>
                            <th class="col-total">Thành tiền<br><small>4</small></th>
                            <th class="col-actions"></th>
                        </tr>
                        <tr class="sub-header">
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="col-quantity-request">Yêu cầu<br><small>1</small></th>
                            <th class="col-quantity-actual">Thực <span id="quantity-action">{{ ($receipt->type ?? 'import') === 'import' ? 'nhập' : 'xuất' }}</span><br><small>2</small></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody">
                        @if(isset($receipt) && $receipt->items)
                            @foreach($receipt->items as $index => $item)
                            <tr data-row-index="{{ $index }}">
                                <td class="col-stt">{{ $index + 1 }}</td>
                                <td class="col-product">
                                    <select name="items[{{ $index }}][product_id]" class="product-select2" style="width:100%">
                                        <option value="{{ $item->variant->product_id }}" selected>
                                            {{ $item->variant->product->name }}
                                        </option>
                                    </select>
                                    <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $item->variant_id }}">
                                </td>
                                <td class="col-code">{{ $item->variant->sku ?? '-' }}</td>
                                <td class="col-unit">Cái</td>
                                <td class="col-quantity-request">
                                    <input type="number" name="items[{{ $index }}][quantity_requested]" 
                                           class="quantity-request-input" value="{{ $item->quantity }}" min="1">
                                </td>
                                <td class="col-quantity-actual">
                                    <input type="number" name="items[{{ $index }}][quantity]" 
                                           class="quantity-input" value="{{ $item->quantity }}" min="1" required>
                                </td>
                                <td class="col-price">
                                    <input type="number" name="items[{{ $index }}][unit_price]" 
                                           class="price-input" value="{{ $item->unit_price }}" min="0" step="0.01" required>
                                </td>
                                <td class="col-total total-cell" data-total="{{ $index }}">
                                    {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }} đ
                                </td>
                                <td class="col-actions">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this)">X</button>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr data-row-index="0">
                                <td class="col-stt">1</td>
                                <td class="col-product">
                                    <select name="items[0][product_id]" class="product-select2" style="width:100%"></select>
                                    <input type="hidden" name="items[0][variant_id]" class="variant-id-input">
                                </td>
                                <td class="col-code">-</td>
                                <td class="col-unit">Cái</td>
                                <td class="col-quantity-request">
                                    <input type="number" name="items[0][quantity_requested]" class="quantity-request-input" min="1">
                                </td>
                                <td class="col-quantity-actual">
                                    <input type="number" name="items[0][quantity]" class="quantity-input" min="1" required>
                                </td>
                                <td class="col-price">
                                    <input type="number" name="items[0][unit_price]" class="price-input" min="0" step="0.01" required>
                                </td>
                                <td class="col-total total-cell" data-total="0">0 đ</td>
                                <td class="col-actions">
                                    <button type="button" class="btn-remove-row" onclick="removeRow(this)">X</button>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                <button type="button" id="btn-add-row" class="btn btn-default">+ Thêm dòng</button>
            </div>

            <!-- Totals and Summary -->
            <div class="receipt-summary">
                <div class="summary-row">
                    <div class="summary-label">- Tổng số tiền (viết bằng chữ):</div>
                    <div class="summary-value" id="total-in-words">{{ $totalInWords ?? '' }}</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">- Số chứng từ gốc kèm theo:</div>
                    <input type="text" name="vat_invoice" id="vat-invoice" class="form-input" 
                           value="{{ $receipt->vat_invoice ?? '' }}" placeholder="VAT số" style="flex: 1;">
                </div>
            </div>
            
            <div class="receipt-totals">
                <div class="total-row">
                    <div class="total-label">Tổng tiền:</div>
                    <div class="total-value" id="grand-total">0 đ</div>
                </div>
            </div>

            <!-- Footer Signatures -->
            <div class="receipt-footer">
                <div class="signature-date">
                    Ngày <input type="number" name="sign_day" class="date-input" 
                                value="{{ date('d') }}" min="1" max="31" style="width: 40px;"> 
                    tháng <input type="number" name="sign_month" class="date-input" 
                                 value="{{ date('m') }}" min="1" max="12" style="width: 40px;"> 
                    năm <input type="number" name="sign_year" class="date-input" 
                               value="{{ date('Y') }}" min="2020" max="2100" style="width: 60px;">
                </div>
                <div class="signature-sections">
                    <div class="signature-section">
                        <div class="signature-label">Người lập phiếu</div>
                        <div class="signature-name">(Ký, họ tên)</div>
                    </div>
                    <div class="signature-section">
                        <div class="signature-label">Người nhận hàng</div>
                        <div class="signature-name">(Ký, họ tên)</div>
                    </div>
                    <div class="signature-section">
                        <div class="signature-label">Thủ kho</div>
                        <div class="signature-name">(Ký, họ tên)</div>
                    </div>
                    <div class="signature-section">
                        <div class="signature-label">Kế toán trưởng<br>(Hoặc bộ phận có nhu cầu nhập)</div>
                        <div class="signature-name">(Ký, họ tên)</div>
                    </div>
                    <div class="signature-section">
                        <div class="signature-label">Giám đốc</div>
                        <div class="signature-name">(Ký, họ tên)</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="button" class="btn-print" onclick="window.print()">In Phiếu</button>
                @if(!isset($receipt) || $receipt->status !== 'completed')
                <button type="submit" class="btn-save">Lưu Phiếu</button>
                @endif
                @if(isset($receipt) && $receipt->status === 'completed')
                <div style="color: #d73925; margin-top: 10px;">
                    <i class="fa fa-lock"></i> Phiếu đã hoàn thành - Không thể chỉnh sửa. Chỉ có thể hủy phiếu từ danh sách.
                </div>
                @elseif(isset($receipt) && $receipt->status !== 'completed')
                <button type="button" class="btn-complete" onclick="completeReceipt({{ $receipt->id }})">Hoàn thành</button>
                @endif
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const apiToken = '{{ $apiToken }}';
const apiBase = '/admin/api/v1/warehouse';

// Initialize Select2 for products
function initProductSelect($select) {
    $select.select2({
        ajax: {
            url: apiBase + '/products/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    limit: 50
                };
            },
            processResults: function (data) {
                return {
                    results: data.data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name,
                            image: item.image
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Chọn sản phẩm...',
        templateResult: formatProduct,
        templateSelection: formatProductSelection
    });

    $select.on('select2:select', function(e) {
        const data = e.params.data;
        const $row = $(this).closest('tr');
        const productId = data.id;
        loadVariants($row, productId);
    });
}

function formatProduct(product) {
    if (!product.id) return product.text;
    return $('<span><img src="' + (product.image || '') + '" style="width:20px;height:20px;margin-right:5px;">' + product.text + '</span>');
}

function formatProductSelection(product) {
    return product.text || product.id;
}

// Load variants for a product
function loadVariants($row, productId) {
    $.ajax({
        url: apiBase + '/products/' + productId + '/variants',
        success: function(response) {
            if (response.data && response.data.length > 0) {
                // Auto-select first variant
                const firstVariant = response.data[0];
                const variantId = firstVariant.id;
                const sku = firstVariant.sku || '-';
                
                $row.find('.variant-id-input').val(variantId);
                $row.find('.col-code').text(sku);
                
                // Load price for selected variant
                loadVariantPrice($row, variantId);
            } else {
                $row.find('.variant-id-input').val('');
                $row.find('.col-code').text('-');
            }
        },
        error: function() {
            $row.find('.variant-id-input').val('');
            $row.find('.col-code').text('-');
        }
    });
}

// Load price for variant
function loadVariantPrice($row, variantId) {
    const receiptType = $('#receipt-type').val();
    $.ajax({
        url: apiBase + '/variants/' + variantId + '/price?type=' + receiptType,
        success: function(response) {
            if (response.data && response.data.suggested_price) {
                $row.find('.price-input').val(response.data.suggested_price);
                calculateRowTotal($row);
            }
        }
    });
}

// Calculate row total
function calculateRowTotal($row) {
    const quantity = parseFloat($row.find('.quantity-input').val()) || 0;
    const price = parseFloat($row.find('.price-input').val()) || 0;
    const total = quantity * price;
    const $totalCell = $row.find('.total-cell');
    $totalCell.text(new Intl.NumberFormat('vi-VN').format(total) + ' đ');
    $totalCell.attr('data-total-value', total);
    calculateGrandTotal();
}

// Calculate grand total
function calculateGrandTotal() {
    let grandTotal = 0;
    $('.total-cell').each(function() {
        const total = parseFloat($(this).attr('data-total-value')) || 0;
        grandTotal += total;
    });
    $('#grand-total').text(new Intl.NumberFormat('vi-VN').format(grandTotal) + ' đ');
    
    // Update total in words via API
    if (grandTotal > 0) {
        $.ajax({
            url: '{{ route("warehouse.accounting.number-to-text") }}',
            method: 'POST',
            data: { number: grandTotal },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $('#total-in-words').text(response.text);
                }
            }
        });
    } else {
        $('#total-in-words').text('');
    }
}

// Add new row
let rowIndex = {{ isset($receipt) && $receipt->items ? count($receipt->items) : 1 }};
$('#btn-add-row').on('click', function() {
    const $newRow = $('<tr data-row-index="' + rowIndex + '">' +
        '<td class="col-stt">' + (rowIndex + 1) + '</td>' +
        '<td class="col-product">' +
            '<select name="items[' + rowIndex + '][product_id]" class="product-select2" style="width:100%"></select>' +
            '<input type="hidden" name="items[' + rowIndex + '][variant_id]" class="variant-id-input">' +
        '</td>' +
        '<td class="col-code">-</td>' +
        '<td class="col-unit">Cái</td>' +
        '<td class="col-quantity-request">' +
            '<input type="number" name="items[' + rowIndex + '][quantity_requested]" class="quantity-request-input" min="1">' +
        '</td>' +
        '<td class="col-quantity-actual">' +
            '<input type="number" name="items[' + rowIndex + '][quantity]" class="quantity-input" min="1" required>' +
        '</td>' +
        '<td class="col-price">' +
            '<input type="number" name="items[' + rowIndex + '][unit_price]" class="price-input" min="0" step="0.01" required>' +
        '</td>' +
        '<td class="col-total total-cell" data-total="' + rowIndex + '">0 đ</td>' +
        '<td class="col-actions">' +
            '<button type="button" class="btn-remove-row" onclick="removeRow(this)">X</button>' +
        '</td>' +
    '</tr>');
    
    $('#items-tbody').append($newRow);
    initProductSelect($newRow.find('.product-select2'));
    $newRow.find('.quantity-input, .price-input, .quantity-request-input').on('input', function() {
        calculateRowTotal($(this).closest('tr'));
    });
    rowIndex++;
    updateRowNumbers();
});

// Remove row
function removeRow(btn) {
    $(btn).closest('tr').remove();
    updateRowNumbers();
    calculateGrandTotal();
}

// Update row numbers
function updateRowNumbers() {
    $('#items-tbody tr').each(function(index) {
        $(this).find('.col-stt').text(index + 1);
    });
}

// Department change (select) - updates receipt type
$('#department').on('change', function() {
    const department = $(this).val();
    const isImport = department === 'Mua hàng';
    const type = isImport ? 'import' : 'export';
    
    // Update hidden receipt type
    $('#receipt-type').val(type);
    
    // Trigger receipt type change handler
    $('#receipt-type').trigger('change');
});

// Receipt type change
$('#receipt-type').on('change', function() {
    const type = $(this).val();
    const isImport = type === 'import';
    
    // Update title
    $('#receipt-title-main').text(isImport ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO');
    
    // Update department select (if not already set)
    if ($('#department').val() !== (isImport ? 'Mua hàng' : 'Bán hàng')) {
        $('#department').val(isImport ? 'Mua hàng' : 'Bán hàng');
    }
    
    // Update labels
    $('#reason-label').text(isImport ? 'nhập' : 'xuất');
    $('#warehouse-label').text(isImport ? 'Nhập' : 'Xuất');
    $('#quantity-action').text(isImport ? 'nhập' : 'xuất');
    
    // Generate new code if receipt is new (no receipt_id)
    // For existing receipts, only update prefix if it's different
    const receiptId = $('input[name="receipt_id"]').val();
    if (!receiptId || receiptId === '') {
        // New receipt - always generate new code
        generateReceiptCode();
    } else {
        // Existing receipt - update prefix only if different
        const currentCode = $('#receipt-code').val();
        if (currentCode && currentCode.length >= 2) {
            const currentPrefix = currentCode.substring(0, 2);
            const newPrefix = isImport ? 'PN' : 'PX';
            
            // Only update if prefix is different
            if (currentPrefix !== newPrefix) {
                const codeWithoutPrefix = currentCode.substring(2);
                const newCode = newPrefix + codeWithoutPrefix;
                $('#receipt-code').val(newCode);
                updateQRCodeImage(newCode);
            }
        } else {
            // Code is empty or invalid - generate new one
            generateReceiptCode();
        }
    }
});

// Generate receipt code
function generateReceiptCode() {
    const type = $('#receipt-type').val();
    const prefix = type === 'import' ? 'PN' : 'PX';
    const date = new Date();
    const yymmdd = String(date.getFullYear()).slice(-2) + 
                   String(date.getMonth() + 1).padStart(2, '0') + 
                   String(date.getDate()).padStart(2, '0');
    const hash = Math.random().toString(36).substring(2, 6).toUpperCase();
    const code = prefix + yymmdd + hash;
    $('#receipt-code').val(code);
    updateQRCodeImage(code);
}

// Update QR Code Image (Server-side generated)
function updateQRCodeImage(receiptCode) {
    if (receiptCode) {
        const qrCodeUrl = '{{ route("warehouse.accounting.qrcode", ["receiptCode" => ":code"]) }}'.replace(':code', receiptCode);
        const $qrImage = $('#qr-code-image');
        if ($qrImage.length) {
            $qrImage.attr('src', qrCodeUrl).show();
        } else {
            // Create image if doesn't exist in header QR container
            $('.receipt-form-qr-center .qr-code-container').html('<img src="' + qrCodeUrl + '" alt="QR Code: ' + receiptCode + '" class="qr-code-image" id="qr-code-image">');
        }
    }
}

// Form submit
$('#warehouse-accounting-form').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const items = [];
    
    $('#items-tbody tr').each(function() {
        const variantId = $(this).find('.variant-id-input').val() || $(this).find('.variant-select').val();
        if (variantId) {
            items.push({
                variant_id: parseInt(variantId),
                quantity: parseInt($(this).find('.quantity-input').val()),
                unit_price: parseFloat($(this).find('.price-input').val()),
                notes: ''
            });
        }
    });

    const receiptType = $('#receipt-type').val();
    const data = {
        type: receiptType,
        receipt_code: $('#receipt-code').val(),
        subject: $('#reason').val(),
        vat_invoice: $('#vat-invoice').val(),
        supplier_name: receiptType === 'import' ? $('#recipient-name').val() : null,
        customer_name: receiptType === 'export' ? $('#recipient-name').val() : null,
        status: 'draft',
        items: items
    };

    $.ajax({
        url: '{{ route("warehouse.accounting") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: data,
        success: function(response) {
            if (response.success) {
                alert('Lưu phiếu thành công!');
                if (response.data.view_url) {
                    window.location.href = response.data.view_url;
                }
            } else {
                alert('Lỗi: ' + response.message);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            alert('Lỗi: ' + (error?.message || 'Có lỗi xảy ra'));
        }
    });
});

// Complete receipt
function completeReceipt(receiptId) {
    if (!confirm('Xác nhận hoàn thành phiếu? Tồn kho sẽ được cập nhật.')) {
        return;
    }

    $.ajax({
        url: '/admin/warehouse/accounting/' + receiptId + '/complete',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('Hoàn thành phiếu thành công!');
                location.reload();
            } else {
                alert('Lỗi: ' + response.message);
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON;
            alert('Lỗi: ' + (error?.message || 'Có lỗi xảy ra'));
        }
    });
}

// Initialize
$(document).ready(function() {
    // Initialize all product selects
    $('.product-select2').each(function() {
        initProductSelect($(this));
    });

    // Initialize quantity and price inputs
    $('.quantity-input, .price-input, .quantity-request-input').on('input', function() {
        calculateRowTotal($(this).closest('tr'));
    });

    // Initialize QR code
    const existingCode = $('#receipt-code').val();
    if (existingCode) {
        // If receipt code already exists (edit mode), update QR image
        updateQRCodeImage(existingCode);
    } else {
        // If new receipt, generate new code
        generateReceiptCode();
    }
    
    // Update QR when receipt code changes manually
    $('#receipt-code').on('input', function() {
        const code = $(this).val();
        if (code) {
            updateQRCodeImage(code);
        }
    });
});
</script>
@endpush
@endsection

