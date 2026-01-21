@extends('Layout::layout')
@section('title','Nhập hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Nhập hàng',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <form id="import-filter-form">
                    <div class="row">  
                        <div class="col-md-6 pr-0">
                            <input type="text" id="import-keyword" class="form-control" placeholder="Từ khóa tìm kiếm (Mã phiếu / Nội dung)">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-2">
                <a class="button add btn btn-info pull-right" href="/admin/import-goods/create"><i class="fa fa-plus" aria-hidden="true"></i> Tạo phiếu nhập</a>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div class="PageContent">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                        <th width="12%">Mã phiếu</th>
                        <th width="20%">Tiêu đề</th>  
                        <th>Ghi chú</th>       
                        <th width="12%">Ngày nhập</th>
                        <th width="12%">Người nhập</th>
                        <th width="10%">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="import-table-body">
                    <tr><td colspan="7" class="text-center">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-4">
                <select class="form-control" id="bulk-action" style="width:50%;float:left;margin-right:5px;">
                    <option value="">---Chọn thao tác---</option>
                    <option value="delete">Xóa đã chọn</option>
                </select>
                <button class="btn btn-primary" id="btn-bulk-execute" type="button">Thực hiện</button>
            </div>
            <div class="col-md-8 text-right">
                <div id="import-pagination"></div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- Modal xem chi tiết -->
<div class="modal fade" id="showOrder" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Chi tiết phiếu nhập hàng</h4>
        </div>
        <div class="modal-body">
            <div id="receipt-detail-content"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
            <a href="#" id="btn-print-receipt" target="_blank" class="btn btn-primary"><i class="fa fa-print"></i> In phiếu</a>
        </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    var currentParams = { page: 1, limit: 10 };

    function loadImportReceipts(params) {
        currentParams = $.extend(currentParams, params);
        var $tbody = $('#import-table-body');
        $tbody.html('<tr><td colspan="7" class="text-center">Đang tải...</td></tr>');

        var query = $.param(currentParams);
        fetch('/admin/api/v1/warehouse/import-receipts?' + query, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                renderRows(res.data);
                renderPagination(res.pagination);
            } else {
                $tbody.html('<tr><td colspan="7" class="text-center text-danger">Lỗi: ' + res.message + '</td></tr>');
            }
        })
        .catch(err => {
            $tbody.html('<tr><td colspan="7" class="text-center text-danger">Không thể kết nối API</td></tr>');
        });
    }

    function renderRows(items) {
        var $tbody = $('#import-table-body');
        $tbody.empty();

        if (!items.length) {
            $tbody.append('<tr><td colspan="7" class="text-center">Không có dữ liệu</td></tr>');
            return;
        }

        items.forEach(item => {
            var date = item.created_at ? new Date(item.created_at).toLocaleDateString('vi-VN') : 'N/A';
            var tr = `
                <tr>
                    <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="${item.id}"></td>
                    <td><a href="javascript:void(0)" class="btnShow" data-id="${item.id}"><strong>${item.receipt_code}</strong></a></td>
                    <td>${item.subject || ''}</td>
                    <td><small class="text-muted">${item.content || ''}</small></td>
                    <td>${date}</td>
                    <td>${item.user ? item.user.name : 'N/A'}</td>
                    <td>
                        <button class="btn btn-info btn-xs btnShow" data-id="${item.id}" title="Xem"><i class="fa fa-eye"></i></button>
                        <a class="btn btn-primary btn-xs" href="/admin/import-goods/edit/${item.id}" title="Sửa"><i class="fa fa-pencil"></i></a>
                        <button class="btn btn-danger btn-xs btnDelete" data-id="${item.id}" title="Xóa"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });
    }

    function renderPagination(paging) {
        var $wrap = $('#import-pagination');
        $wrap.empty();
        if (!paging || paging.last_page <= 1) return;

        var html = '<ul class="pagination pagination-sm no-margin pull-right">';
        for (var i = 1; i <= paging.last_page; i++) {
            var active = i === paging.current_page ? 'active' : '';
            html += `<li class="${active}"><a href="javascript:void(0)" data-page="${i}">${i}</a></li>`;
        }
        html += '</ul>';
        $wrap.append(html);

        $wrap.find('a[data-page]').click(function() {
            loadImportReceipts({ page: $(this).data('page') });
        });
    }

    // Filter
    $('#import-filter-form').submit(function(e) {
        e.preventDefault();
        loadImportReceipts({ keyword: $('#import-keyword').val(), page: 1 });
    });

    // Show Detail
    $('body').on('click', '.btnShow', function() {
        var id = $(this).data('id');
        fetch('/admin/api/v1/warehouse/import-receipts/' + id)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                var item = res.data;
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Mã phiếu:</strong> ${item.receipt_code}</p>
                            <p><strong>Mã đơn:</strong> ${item.code || 'N/A'}</p>
                            <p><strong>Tiêu đề:</strong> ${item.subject}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ngày nhập:</strong> ${new Date(item.created_at).toLocaleString('vi-VN')}</p>
                            <p><strong>Người nhập:</strong> ${item.user ? item.user.name : 'N/A'}</p>
                            <p><strong>Số hóa đơn VAT:</strong> ${item.vat_invoice || 'N/A'}</p>
                        </div>
                    </div>
                    <table class="table table-bordered mt-10">
                        <thead>
                            <tr class="bg-gray">
                                <th>Sản phẩm</th>
                                <th class="text-right">Giá nhập</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-right">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${item.items.map(p => `
                                <tr>
                                    <td>${p.product_name}<br><small class="text-primary">${p.variant_option}</small></td>
                                    <td class="text-right">${new Intl.NumberFormat('vi-VN').format(p.price)}đ</td>
                                    <td class="text-center">${p.quantity}</td>
                                    <td class="text-right"><strong>${new Intl.NumberFormat('vi-VN').format(p.subtotal)}đ</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">TỔNG CỘNG:</th>
                                <th class="text-right text-red" style="font-size:16px;">${new Intl.NumberFormat('vi-VN').format(item.total_value)}đ</th>
                            </tr>
                        </tfoot>
                    </table>
                    <p class="text-italic mt-5">Bằng chữ: ${item.total_value_in_words}</p>
                `;
                $('#receipt-detail-content').html(html);
                $('#btn-print-receipt').attr('href', item.view_url);
                $('#showOrder').modal('show');
            }
        });
    });

    // Delete
    $('body').on('click', '.btnDelete', function() {
        var id = $(this).data('id');
        if (confirm('Bạn có chắc muốn xóa phiếu nhập này?')) {
            fetch('/admin/api/v1/warehouse/import-receipts/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    toastr.success('Xóa thành công');
                    loadImportReceipts();
                } else {
                    toastr.error(res.message);
                }
            });
        }
    });

    // Bulk action
    $('#btn-bulk-execute').click(function() {
        var action = $('#bulk-action').val();
        var ids = $('input[name="checklist[]"]:checked').map(function() { return $(this).val(); }).get();
        
        if (!action || !ids.length) {
            alert('Vui lòng chọn thao tác và phiếu cần xử lý');
            return;
        }

        if (action === 'delete') {
            if (confirm('Xóa ' + ids.length + ' phiếu đã chọn?')) {
                // Thực hiện xóa từng cái (hoặc build API bulk nếu cần)
                Promise.all(ids.map(id => 
                    fetch('/admin/api/v1/warehouse/import-receipts/' + id, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
                    })
                )).then(() => {
                    toastr.success('Đã thực hiện xóa');
                    loadImportReceipts();
                });
            }
        }
    });

    // Initial load
    loadImportReceipts();
});
</script>
@endsection
