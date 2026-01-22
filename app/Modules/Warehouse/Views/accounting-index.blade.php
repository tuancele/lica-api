@extends('Layout::layout')
@section('title', 'Quản lý phiếu Nhập/Xuất')

@push('styles')
<link href="/public/admin/css/warehouse-accounting.css" rel="stylesheet" type="text/css">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap.min.css" rel="stylesheet">
@endpush

@section('content')
@include('Layout::breadcrumb', [
    'title' => 'Quản lý phiếu Nhập/Xuất',
])

<section class="content">
    <div class="box">
        <div class="box-header with-border">
            <h3 class="box-title">Danh sách phiếu Nhập/Xuất</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('warehouse.accounting.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Tạo phiếu mới
                </a>
            </div>
        </div>
        <div class="box-body">
            <!-- Filters -->
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-3">
                    <select id="filter-type" class="form-control">
                        <option value="">Tất cả loại phiếu</option>
                        <option value="import">Nhập kho</option>
                        <option value="export">Xuất kho</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" id="filter-date-from" class="form-control" placeholder="Từ ngày">
                </div>
                <div class="col-md-3">
                    <input type="date" id="filter-date-to" class="form-control" placeholder="Đến ngày">
                </div>
                <div class="col-md-3">
                    <input type="text" id="filter-keyword" class="form-control" placeholder="Tìm kiếm (Mã phiếu, Đối tác)">
                </div>
            </div>

            <!-- DataTable -->
            <table id="receipts-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Loại phiếu</th>
                        <th>Ngày tạo</th>
                        <th>Đối tác</th>
                        <th>Tổng tiền</th>
                        <th>Người tạo</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Print Modal (Hidden) -->
<div id="print-container" style="display: none;"></div>

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
    const table = $('#receipts-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("warehouse.accounting.list") }}',
            data: function(d) {
                d.type = $('#filter-type').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
                d.keyword = $('#filter-keyword').val();
                d.per_page = d.length;
                d.page = (d.start / d.length) + 1;
            }
        },
        columns: [
            { data: 'receipt_code', name: 'receipt_code' },
            { 
                data: 'type', 
                name: 'type',
                render: function(data) {
                    return data === 'import' ? '<span class="label label-success">Nhập kho</span>' : '<span class="label label-warning">Xuất kho</span>';
                }
            },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleString('vi-VN');
                }
            },
            { 
                data: null,
                name: 'partner',
                render: function(data) {
                    const name = data.supplier_name || data.customer_name || '-';
                    const phone = data.supplier_phone || data.customer_phone || '';
                    return name + (phone ? '<br><small>' + phone + '</small>' : '');
                }
            },
            { 
                data: 'total_value', 
                name: 'total_value',
                render: function(data) {
                    return new Intl.NumberFormat('vi-VN').format(data) + ' đ';
                }
            },
            { 
                data: 'creator', 
                name: 'creator.name',
                render: function(data) {
                    return data ? data.name : '-';
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    const statusMap = {
                        'draft': '<span class="label label-default">Nháp</span>',
                        'pending': '<span class="label label-info">Chờ duyệt</span>',
                        'approved': '<span class="label label-primary">Đã duyệt</span>',
                        'completed': '<span class="label label-success">Hoàn thành</span>',
                        'cancelled': '<span class="label label-danger">Đã hủy</span>'
                    };
                    return statusMap[data] || data;
                }
            },
            { 
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data) {
                    let actions = '';
                    // View
                    actions += '<a href="{{ route("warehouse.accounting.create") }}?id=' + data.id + '" class="btn btn-xs btn-info" title="Xem"><i class="fa fa-eye"></i></a> ';
                    // Print
                    actions += '<button onclick="quickPrint(' + data.id + ')" class="btn btn-xs btn-default" title="In nhanh"><i class="fa fa-print"></i></button> ';
                    // Void (only if completed)
                    if (data.status === 'completed') {
                        actions += '<button onclick="voidReceipt(' + data.id + ')" class="btn btn-xs btn-danger" title="Hủy phiếu"><i class="fa fa-ban"></i></button> ';
                    }
                    // Delete (only if draft)
                    if (data.status === 'draft') {
                        actions += '<button onclick="deleteReceipt(' + data.id + ')" class="btn btn-xs btn-danger" title="Xóa"><i class="fa fa-trash"></i></button>';
                    }
                    return actions || '-';
                }
            }
        ],
        order: [[2, 'desc']],
        pageLength: 20,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/vi.json'
        }
    });

    // Filter events
    $('#filter-type, #filter-date-from, #filter-date-to').on('change', function() {
        table.ajax.reload();
    });

    $('#filter-keyword').on('keyup', function() {
        table.ajax.reload();
    });
});

// Quick print function
function quickPrint(receiptId) {
    $.ajax({
        url: '{{ route("warehouse.accounting.print", ["id" => ":id"]) }}'.replace(':id', receiptId),
        method: 'GET',
        success: function(html) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.onload = function() {
                printWindow.print();
            };
        },
        error: function() {
            alert('Lỗi khi tải phiếu để in');
        }
    });
}

// Void receipt function
function voidReceipt(receiptId) {
    if (!confirm('Xác nhận hủy phiếu? Hệ thống sẽ tự động hoàn kho.')) {
        return;
    }

    $.ajax({
        url: '{{ route("warehouse.accounting.void", ["id" => ":id"]) }}'.replace(':id', receiptId),
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('Hủy phiếu thành công!');
                $('#receipts-table').DataTable().ajax.reload();
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

// Delete receipt function
function deleteReceipt(receiptId) {
    if (!confirm('Xác nhận xóa phiếu?')) {
        return;
    }

    $.ajax({
        url: '{{ route("warehouse.accounting") }}/' + receiptId,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                alert('Xóa phiếu thành công!');
                $('#receipts-table').DataTable().ajax.reload();
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
</script>
@endpush
@endsection

