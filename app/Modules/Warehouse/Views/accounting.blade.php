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

            <!-- Header -->
            <div class="receipt-header">
                <div style="flex: 1;">
                    <select name="type" id="receipt-type" class="receipt-type-select" required @if(isset($receipt) && $receipt->status === 'completed') disabled @endif>
                        <option value="import" {{ ($receipt->type ?? 'import') === 'import' ? 'selected' : '' }}>Nhập kho</option>
                        <option value="export" {{ ($receipt->type ?? '') === 'export' ? 'selected' : '' }}>Xuất kho</option>
                    </select>
                    <div class="receipt-title" id="receipt-title">PHIẾU NHẬP KHO</div>
                </div>
                <div class="receipt-code-section">
                    <div class="receipt-code-label">Mã phiếu:</div>
                    <input type="text" name="receipt_code" id="receipt-code" class="receipt-code-input" 
                           value="{{ $receipt->receipt_code ?? '' }}" readonly>
                </div>
            </div>

            <!-- Partner Info -->
            <div class="partner-info">
                <div class="partner-info-row">
                    <div class="partner-info-label" id="partner-label">Nhà cung cấp:</div>
                    <input type="text" name="supplier_name" id="supplier-name" class="partner-info-input" 
                           value="{{ $receipt->supplier_name ?? '' }}" placeholder="Tên nhà cung cấp">
                </div>
                <div class="partner-info-row">
                    <div class="partner-info-label">Địa chỉ:</div>
                    <input type="text" name="supplier_address" id="supplier-address" class="partner-info-input" 
                           placeholder="Địa chỉ">
                </div>
                <div class="partner-info-row">
                    <div class="partner-info-label">SĐT:</div>
                    <input type="text" name="supplier_phone" id="supplier-phone" class="partner-info-input" 
                           placeholder="Số điện thoại">
                </div>
                <div class="partner-info-row">
                    <div class="partner-info-label">Mã số thuế:</div>
                    <input type="text" name="vat_invoice" id="vat-invoice" class="partner-info-input" 
                           value="{{ $receipt->vat_invoice ?? '' }}" placeholder="Mã số thuế">
                </div>
            </div>

            <!-- Items Table -->
            <div class="items-table-wrapper">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="col-stt">STT</th>
                            <th class="col-product">Sản phẩm</th>
                            <th class="col-variant">Phân loại</th>
                            <th class="col-quantity">Số lượng</th>
                            <th class="col-price">Đơn giá</th>
                            <th class="col-total">Thành tiền</th>
                            <th class="col-actions"></th>
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
                                <td class="col-variant">
                                    <select name="items[{{ $index }}][variant_id]" class="variant-select" style="width:100%">
                                        <option value="{{ $item->variant_id }}" selected>
                                            {{ $item->variant->option1_value ?? 'Mặc định' }}
                                        </option>
                                    </select>
                                </td>
                                <td class="col-quantity">
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
                                <td class="col-variant">
                                    <select name="items[0][variant_id]" class="variant-select" style="width:100%"></select>
                                </td>
                                <td class="col-quantity">
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

            <!-- Subject -->
            <div class="partner-info-row" style="margin-top: 20px;">
                <div class="partner-info-label">Nội dung:</div>
                <input type="text" name="subject" id="subject" class="partner-info-input" 
                       value="{{ $receipt->subject ?? '' }}" placeholder="Tiêu đề/Nội dung phiếu" required>
            </div>

            <!-- Totals -->
            <div class="receipt-totals">
                <div class="total-row">
                    <div class="total-label">Tổng tiền:</div>
                    <div class="total-value" id="grand-total">0 đ</div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-code-section">
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

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="signature-section">
                    <div class="signature-label">Người lập</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Người duyệt</div>
                </div>
                <div class="signature-section">
                    <div class="signature-label">Thủ kho</div>
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
    const $variantSelect = $row.find('.variant-select');
    $variantSelect.empty().append('<option>Đang tải...</option>');

    $.ajax({
        url: apiBase + '/products/' + productId + '/variants',
        success: function(response) {
            $variantSelect.empty();
            if (response.data && response.data.length > 0) {
                response.data.forEach(function(variant) {
                    const option = new Option(
                        variant.option1_value + ' (Tồn: ' + variant.current_stock + ')',
                        variant.id,
                        false,
                        false
                    );
                    $variantSelect.append(option);
                });
            } else {
                $variantSelect.append('<option value="">Không có phân loại</option>');
            }
            $variantSelect.trigger('change');
        },
        error: function() {
            $variantSelect.empty().append('<option value="">Lỗi tải phân loại</option>');
        }
    });

    $variantSelect.on('select2:select', function(e) {
        const variantId = e.params.data.id;
        $row.find('.variant-id-input').val(variantId);
        loadVariantPrice($row, variantId);
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
        '<td class="col-variant">' +
            '<select name="items[' + rowIndex + '][variant_id]" class="variant-select" style="width:100%"></select>' +
        '</td>' +
        '<td class="col-quantity">' +
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
    $newRow.find('.quantity-input, .price-input').on('input', function() {
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

// Receipt type change
$('#receipt-type').on('change', function() {
    const type = $(this).val();
    $('#receipt-title').text(type === 'import' ? 'PHIẾU NHẬP KHO' : 'PHIẾU XUẤT KHO');
    $('#partner-label').text(type === 'import' ? 'Nhà cung cấp:' : 'Khách hàng:');
    $('#supplier-name').attr('name', type === 'import' ? 'supplier_name' : 'customer_name');
    
    // Generate new code
    generateReceiptCode();
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
            // Create image if doesn't exist
            $('.qr-code-container').html('<img src="' + qrCodeUrl + '" alt="QR Code: ' + receiptCode + '" class="qr-code-image" id="qr-code-image">');
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

    const data = {
        type: $('#receipt-type').val(),
        receipt_code: $('#receipt-code').val(),
        subject: $('#subject').val(),
        vat_invoice: $('#vat-invoice').val(),
        supplier_name: $('#supplier-name').val(),
        customer_name: $('#supplier-name').val(), // Same field for both
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
    $('.quantity-input, .price-input').on('input', function() {
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

