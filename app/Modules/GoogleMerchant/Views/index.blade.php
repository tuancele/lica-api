@extends('Layout::layout')
@section('title','Quản lý sản phẩm Google Merchant Center')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Quản lý sản phẩm Google Merchant Center',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <form method="get" action="{{route('google-merchant.index')}}"> 
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-4 pr-0">
                        <?php $statusFilter = request()->get('status', 'all'); ?>
                        <select class="form-control" name="status">
                            <option value="all" @if($statusFilter == 'all') selected="" @endif>Tất cả trạng thái</option>
                            <option value="approved" @if($statusFilter == 'approved') selected="" @endif>Đã duyệt</option>
                            <option value="pending" @if($statusFilter == 'pending') selected="" @endif>Chờ duyệt</option>
                            <option value="disapproved" @if($statusFilter == 'disapproved') selected="" @endif>Bị từ chối</option>
                            <option value="not_synced" @if($statusFilter == 'not_synced') selected="" @endif>Chưa đồng bộ</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <div class="PageContent">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">Hình ảnh</th>
                        <th width="20%">Tên sản phẩm</th>
                        <th width="10%">Biến thể</th>
                        <th width="10%">Offer ID</th>
                        <th width="10%">Trạng thái GMC</th>
                        <th width="25%">Chi tiết lỗi</th>
                        <th width="10%">Thao tác</th>
                    </tr>
                </thead>
                <tbody id="gmc-products-tbody">
                    @if(isset($items) && !empty($items))
                    @foreach($items as $index => $item)
                    @php 
                        $product = $item['product'];
                        $variant = $item['variant'];
                        $offerId = $item['offer_id'];
                    @endphp
                    <tr data-offer-id="{{$offerId}}" data-row-index="{{$index}}">
                        <td>
                            <img class="img-responsive" src="{{getImage($product->image)}}" style="max-width: 80px;">
                        </td>
                        <td>
                            <a href="{{asset($product->slug)}}" target="_blank">{{$product->name}}</a>
                            @if($variant)
                                <br><small style="color: #666;">{{$variant->option ?? 'N/A'}}</small>
                            @endif
                        </td>
                        <td>
                            @if($variant)
                                <span class="label label-info">Có biến thể</span>
                                <br><small>SKU: {{$variant->sku ?? 'N/A'}}</small>
                            @else
                                <span class="label label-default">Sản phẩm đơn</span>
                            @endif
                        </td>
                        <td>
                            <code>{{$offerId}}</code>
                        </td>
                        <td class="gmc-status-cell">
                            <span class="label label-default"><i class="fa fa-spinner fa-spin"></i> Đang tải...</span>
                        </td>
                        <td class="gmc-issues-cell">
                            <span class="text-muted">Đang tải...</span>
                        </td>
                        <td>
                            <button class="btn btn-primary btn-xs btn-sync-gmc" 
                                    data-product-id="{{$product->id}}" 
                                    data-variant-id="{{$variant ? $variant->id : ''}}"
                                    data-offer-id="{{$offerId}}">
                                <i class="fa fa-refresh" aria-hidden="true"></i> Đồng bộ ngay
                            </button>
                            <br><br>
                            <button class="btn btn-info btn-xs btn-refresh-status" 
                                    data-product-id="{{$product->id}}" 
                                    data-variant-id="{{$variant ? $variant->id : ''}}"
                                    data-offer-id="{{$offerId}}">
                                <i class="fa fa-info-circle" aria-hidden="true"></i> Làm mới trạng thái
                            </button>
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-12 text-right">
                {{$products->links()}}
            </div>
        </div>
    </div>
</div>
</section>

<script>
$(document).ready(function() {
    // Load GMC statuses via AJAX (batch load for performance)
    var offerIds = [];
    $('#gmc-products-tbody tr[data-offer-id]').each(function() {
        var offerId = $(this).data('offer-id');
        if (offerId) {
            offerIds.push(offerId);
        }
    });

    if (offerIds.length > 0) {
        // Load statuses in batches of 20 to avoid timeout
        var batchSize = 20;
        var batches = [];
        for (var i = 0; i < offerIds.length; i += batchSize) {
            batches.push(offerIds.slice(i, i + batchSize));
        }

        var currentBatch = 0;
        function loadNextBatch() {
            if (currentBatch >= batches.length) {
                return;
            }

            var batchOfferIds = batches[currentBatch];
            $.ajax({
                url: '{{route("google-merchant.batch-status")}}',
                method: 'POST',
                data: {
                    offer_ids: batchOfferIds
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $.each(response.data, function(offerId, statusData) {
                            updateRowStatus(offerId, statusData);
                        });
                    }
                    currentBatch++;
                    // Load next batch after a short delay
                    setTimeout(loadNextBatch, 100);
                },
                error: function(xhr) {
                    console.error('Failed to load batch status:', xhr);
                    // Mark all in this batch as error
                    batchOfferIds.forEach(function(offerId) {
                        updateRowStatus(offerId, {
                            status: 'error',
                            issues: [{description: 'Không thể tải trạng thái từ Google API'}]
                        });
                    });
                    currentBatch++;
                    setTimeout(loadNextBatch, 100);
                }
            });
        }

        // Start loading first batch
        loadNextBatch();
    }

    function updateRowStatus(offerId, statusData) {
        var $row = $('#gmc-products-tbody tr[data-offer-id="' + offerId + '"]');
        if ($row.length === 0) return;

        var gmcStatus = statusData ? (statusData.status || 'not_synced') : 'not_synced';
        var $statusCell = $row.find('.gmc-status-cell');
        var $issuesCell = $row.find('.gmc-issues-cell');

        // Update status badge
        var statusHtml = '';
        if (gmcStatus == 'approved') {
            statusHtml = '<span class="label label-success">Đã duyệt</span>';
        } else if (gmcStatus == 'pending') {
            statusHtml = '<span class="label label-warning">Chờ duyệt</span>';
        } else if (gmcStatus == 'disapproved') {
            statusHtml = '<span class="label label-danger">Bị từ chối</span>';
        } else if (gmcStatus == 'error') {
            statusHtml = '<span class="label label-danger">Lỗi</span>';
        } else {
            statusHtml = '<span class="label label-default">Chưa đồng bộ</span>';
        }
        $statusCell.html(statusHtml);

        // Update issues
        var issuesHtml = '';
        if (statusData && statusData.issues && statusData.issues.length > 0) {
            issuesHtml = '<div style="max-height: 150px; overflow-y: auto;">';
            statusData.issues.forEach(function(issue) {
                var severity = issue.severity || 'unknown';
                var alertClass = (severity == 'error' || severity == 'critical') ? 'danger' : 'warning';
                var message = issue.description || issue.detail || issue.value || 'Lỗi không xác định';
                
                issuesHtml += '<div class="alert alert-' + alertClass + ' alert-dismissible" style="padding: 5px; margin-bottom: 5px;">';
                issuesHtml += '<strong>' + severity.charAt(0).toUpperCase() + severity.slice(1) + ':</strong> ' + message;
                if (issue.attribute) {
                    issuesHtml += '<br><small>Thuộc tính: ' + issue.attribute + '</small>';
                }
                if (issue.location) {
                    issuesHtml += '<br><small>Vị trí: ' + issue.location + '</small>';
                }
                if (issue.code) {
                    issuesHtml += '<br><small>Mã lỗi: ' + issue.code + '</small>';
                }
                if (issue.resolution) {
                    issuesHtml += '<br><small><strong>Giải pháp:</strong> ' + issue.resolution + '</small>';
                }
                issuesHtml += '</div>';
            });
            issuesHtml += '</div>';
        } else if (gmcStatus == 'not_synced') {
            issuesHtml = '<span class="text-muted">Chưa được đồng bộ lên GMC</span>';
        } else {
            issuesHtml = '<span class="text-success">Không có lỗi</span>';
        }
        $issuesCell.html(issuesHtml);
    }

    // Sync to GMC
    $('.btn-sync-gmc').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var variantId = $btn.data('variant-id');
        var offerId = $btn.data('offer-id');
        
        if (!confirm('Bạn có chắc chắn muốn đồng bộ sản phẩm này lên Google Merchant Center?')) {
            return;
        }
        
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang xử lý...');
        
        var data = {};
        if (variantId) {
            data.variant_id = variantId;
        } else {
            data.product_id = productId;
        }
        
        $.ajax({
            url: '{{route("google-merchant.sync")}}',
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Đã thêm vào hàng đợi đồng bộ thành công!');
                    $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Đồng bộ ngay');
                    // Refresh status for this row after 5 seconds (wait for queue to process)
                    setTimeout(function() {
                        var offerId = $btn.data('offer-id');
                        var refreshData = {};
                        if (variantId) {
                            refreshData.variant_id = variantId;
                        } else {
                            refreshData.product_id = productId;
                        }
                        $.ajax({
                            url: '{{route("google-merchant.status")}}',
                            method: 'GET',
                            data: refreshData,
                            success: function(statusResponse) {
                                if (statusResponse.success && statusResponse.data && statusResponse.data.status) {
                                    updateRowStatus(offerId, statusResponse.data.status);
                                }
                            }
                        });
                    }, 5000);
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể đồng bộ'));
                    $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Đồng bộ ngay');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Lỗi không xác định';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert('Lỗi: ' + errorMsg);
                $btn.prop('disabled', false).html('<i class="fa fa-refresh"></i> Đồng bộ ngay');
            }
        });
    });
    
    // Refresh status
    $('.btn-refresh-status').on('click', function() {
        var $btn = $(this);
        var productId = $btn.data('product-id');
        var variantId = $btn.data('variant-id');
        var offerId = $btn.data('offer-id');
        
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang tải...');
        
        var data = {};
        if (variantId) {
            data.variant_id = variantId;
        } else {
            data.product_id = productId;
        }
        
        $.ajax({
            url: '{{route("google-merchant.status")}}',
            method: 'GET',
            data: data,
            success: function(response) {
                if (response.success && response.data) {
                    var offerId = $btn.data('offer-id');
                    updateRowStatus(offerId, response.data.status);
                    alert('Đã làm mới trạng thái thành công!');
                } else {
                    alert('Lỗi: ' + (response.message || 'Không thể làm mới trạng thái'));
                    $btn.prop('disabled', false).html('<i class="fa fa-info-circle"></i> Làm mới trạng thái');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Lỗi không xác định';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert('Lỗi: ' + errorMsg);
                $btn.prop('disabled', false).html('<i class="fa fa-info-circle"></i> Làm mới trạng thái');
            }
        });
    });
});
</script>
@endsection

