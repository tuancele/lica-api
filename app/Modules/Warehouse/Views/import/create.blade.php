@extends('Layout::layout')
@section('title','Tạo phiếu nhập hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo phiếu nhập hàng',
])

<section class="content">
    <form id="import-create-form">
        @csrf
        <div class="row">
            <!-- Thông tin chung -->
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-file-text-o"></i> Thông tin phiếu nhập</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Mã đơn hàng (Nhà cung cấp) <span class="text-red">*</span></label>
                                    <input type="text" name="code" class="form-control" placeholder="Ví dụ: PO-001" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Số hóa đơn VAT</label>
                                    <input type="text" name="vat_invoice" class="form-control" placeholder="Số hóa đơn (nếu có)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tiêu đề nội dung nhập <span class="text-red">*</span></label>
                                    <input type="text" name="subject" class="form-control" placeholder="Nhập tiêu đề hoặc tên nhà cung cấp" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ghi chú thêm</label>
                                    <textarea name="content" class="form-control" rows="2" placeholder="Ghi chú thêm (nếu có)"></textarea>
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
                        <h3 class="box-title"><i class="fa fa-cubes"></i> Danh sách sản phẩm nhập</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-sm btn-primary" id="btnAddRow">
                                <i class="fa fa-plus"></i> Thêm dòng mới
                            </button>
                        </div>
                    </div>
                    <div class="box-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered" id="productTable">
                                <thead class="bg-gray">
                                    <tr>
                                        <th width="5%" class="text-center">STT</th>
                                        <th width="35%">Sản phẩm</th>
                                        <th width="20%">Phân loại (Biến thể)</th>
                                        <th width="15%">Giá nhập (đ)</th>
                                        <th width="10%">Số lượng</th>
                                        <th width="15%">Thành tiền</th>
                                        <th width="5%" class="text-center">#</th>
                                    </tr>
                                </thead>
                                <tbody id="product-rows-container">
                                    <!-- Rows injected here -->
                                </tbody>
                                <tfoot>
                                    <tr class="bg-warning">
                                        <td colspan="5" class="text-right"><strong>TỔNG CỘNG:</strong></td>
                                        <td colspan="2">
                                            <strong id="grand-total-text" class="text-red" style="font-size:18px;">0 đ</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nút thao tác -->
            <div class="col-md-12 text-center" style="margin-bottom: 30px;">
                <button type="submit" class="btn btn-success btn-lg" id="btnSubmit">
                    <i class="fa fa-save"></i> LƯU PHIẾU NHẬP HÀNG
                </button>
                <a href="/admin/import-goods" class="btn btn-default btn-lg">Hủy bỏ</a>
            </div>
        </div>
    </form>
</section>

<!-- Select2 & Helpers -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    var rowCount = 0;

    function addRow() {
        rowCount++;
        var html = `
            <tr class="product-row" id="row-${rowCount}" data-id="${rowCount}">
                <td class="text-center stt"><strong>${rowCount}</strong></td>
                <td>
                    <select class="form-control select-product" name="items[${rowCount}][product_id]" required></select>
                </td>
                <td>
                    <select class="form-control select-variant" name="items[${rowCount}][variant_id]" required disabled>
                        <option value="">-- Chọn SP trước --</option>
                    </select>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][price]" class="form-control input-price text-right" placeholder="0" required min="0">
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][quantity]" class="form-control input-qty text-right" placeholder="0" required min="1">
                </td>
                <td class="text-right">
                    <span class="row-total">0 đ</span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger btnRemoveRow"><i class="fa fa-times"></i></button>
                </td>
            </tr>
        `;
        $('#product-rows-container').append(html);
        
        var $newRow = $('#row-' + rowCount);
        initProductSelect($newRow.find('.select-product'));
    }

    function initProductSelect($el) {
        $el.select2({
            placeholder: 'Tìm kiếm sản phẩm...',
            ajax: {
                url: '/admin/api/v1/warehouse/products/search',
                dataType: 'json',
                delay: 250,
                data: (p) => ({ q: p.term, limit: 20 }),
                processResults: (res) => ({
                    results: res.data.map(i => ({ id: i.id, text: i.name }))
                })
            }
        }).on('change', function() {
            var pid = $(this).val();
            var $variantSelect = $(this).closest('tr').find('.select-variant');
            loadVariants(pid, $variantSelect);
        });
    }

    function loadVariants(productId, $el) {
        $el.prop('disabled', true).html('<option>Đang tải...</option>');
        fetch('/admin/api/v1/warehouse/products/' + productId + '/variants')
            .then(res => res.json())
            .then(res => {
                var opts = '<option value="">-- Chọn phân loại --</option>';
                res.data.forEach(v => {
                    opts += `<li value="${v.id}">${v.variant_sku || ''} - ${v.variant_option} (Tồn: ${v.physical_stock})</li>`;
                    // Fix: select2 needs <option>
                    opts = opts.replace('<li', '<option').replace('</li', '</option');
                });
                $el.prop('disabled', false).html(opts);
                $el.select2({ minimumResultsForSearch: Infinity });
            });
    }

    function calculateGrandTotal() {
        var total = 0;
        $('.product-row').each(function() {
            var p = parseFloat($(this).find('.input-price').val()) || 0;
            var q = parseFloat($(this).find('.input-qty').val()) || 0;
            var sub = p * q;
            $(this).find('.row-total').text(new Intl.NumberFormat('vi-VN').format(sub) + ' đ');
            total += sub;
        });
        $('#grand-total-text').text(new Intl.NumberFormat('vi-VN').format(total) + ' đ');
    }

    $('#btnAddRow').click(addRow);
    
    $(document).on('click', '.btnRemoveRow', function() {
        $(this).closest('tr').remove();
        updateSTT();
        calculateGrandTotal();
    });

    $(document).on('input', '.input-price, .input-qty', calculateGrandTotal);

    function updateSTT() {
        $('.stt strong').each((i, el) => $(el).text(i + 1));
    }

    // Submit
    $('#import-create-form').submit(function(e) {
        e.preventDefault();
        var $btn = $('#btnSubmit');
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang xử lý...');

        var items = [];
        $('.product-row').each(function() {
            var vid = $(this).find('.select-variant').val();
            var p = parseFloat($(this).find('.input-price').val());
            var q = parseInt($(this).find('.input-qty').val());
            if (vid && q > 0) {
                items.push({ variant_id: parseInt(vid), price: p, quantity: q });
            }
        });

        if (!items.length) {
            alert('Vui lòng thêm sản phẩm');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> LƯU PHIẾU NHẬP HÀNG');
            return;
        }

        var payload = {
            code: $('input[name="code"]').val(),
            subject: $('input[name="subject"]').val(),
            content: $('textarea[name="content"]').val(),
            vat_invoice: $('input[name="vat_invoice"]').val(),
            items: items
        };

        fetch('/admin/api/v1/warehouse/import-receipts', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                toastr.success('Tạo phiếu nhập thành công');
                window.location.href = res.data.view_url || '/admin/import-goods';
            } else {
                alert(res.message || 'Lỗi hệ thống');
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> LƯU PHIẾU NHẬP HÀNG');
            }
        })
        .catch(err => {
            alert('Lỗi kết nối');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> LƯU PHIẾU NHẬP HÀNG');
        });
    });

    // Start with 1 row
    addRow();
});
</script>

<style>
    .text-red { color: #d9534f; }
    .bg-gray { background-color: #f4f4f4; }
    .mt-10 { margin-top: 10px; }
    .select2-container--default .select2-selection--single { height: 34px; border-radius: 0; }
</style>
@endsection
