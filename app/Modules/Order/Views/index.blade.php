@extends('Layout::layout')
@section('title','Quản lý đơn hàng')
@section('content')
<style>
    .order-tabs {
        display: flex;
        background: #fff;
        margin-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .order-tabs a {
        padding: 15px 25px;
        color: #333;
        text-decoration: none;
        position: relative;
        font-weight: 500;
    }
    .order-tabs a.active {
        color: #ee4d2d;
    }
    .order-tabs a.active:after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: #ee4d2d;
    }
    .order-tabs a .count {
        font-size: 12px;
        background: #f5f5f5;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 5px;
    }
    .search-box {
        background: #fff;
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 4px;
    }
    .order-item {
        background: #fff;
        margin-bottom: 15px;
        border: 1px solid #f2f2f2;
        border-radius: 4px;
    }
    .order-item-header {
        padding: 12px 20px;
        border-bottom: 1px solid #f2f2f2;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
    }
    .order-item-body {
        padding: 20px;
        display: flex;
    }
    .product-info {
        flex: 1;
        display: flex;
    }
    .product-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border: 1px solid #eee;
        margin-right: 15px;
    }
    .product-detail {
        flex: 1;
    }
    .customer-info {
        width: 250px;
        padding: 0 20px;
        border-left: 1px solid #eee;
        border-right: 1px solid #eee;
    }
    .order-stats {
        width: 200px;
        padding: 0 20px;
        text-align: right;
    }
    .order-actions {
        width: 180px;
        padding: 0 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .btn-shopee {
        background: #ee4d2d;
        color: #fff;
        border: none;
        padding: 6px 12px;
        border-radius: 2px;
    }
    .btn-shopee:hover {
        background: #d73211;
        color: #fff;
    }
    .btn-shopee-outline {
        background: #fff;
        color: #555;
        border: 1px solid #ddd;
        padding: 6px 12px;
        border-radius: 2px;
    }
    .btn-shopee-outline:hover {
        background: #f8f8f8;
        border-color: #ccc;
    }
    .badge-status {
        padding: 4px 8px;
        border-radius: 2px;
        font-size: 12px;
        font-weight: normal;
    }
    .status-0 { background: #fff4e5; color: #ff8800; } /* Chờ xác nhận */
    .status-1 { background: #e5f9f6; color: #00bfa5; } /* Đã xác nhận */
    .status-2 { background: #fef1f1; color: #ff5722; } /* Đã hủy */
</style>

<div class="order-tabs">
    <?php 
        $st = request()->get('status'); 
        $sh = request()->get('ship');
    ?>
    <a href="/admin/order" class="{{ ($st == '' && $sh == '') ? 'active' : '' }}">Tất cả</a>
    <a href="/admin/order?status=0" class="{{ ($st == '0') ? 'active' : '' }}">Chờ xác nhận</a>
    <a href="/admin/order?status=1&ship=0" class="{{ ($st == '1' && $sh == '0') ? 'active' : '' }}">Chờ lấy hàng</a>
    <a href="/admin/order?ship=1" class="{{ ($sh == '1') ? 'active' : '' }}">Đang giao</a>
    <a href="/admin/order?ship=2" class="{{ ($sh == '2') ? 'active' : '' }}">Đã giao</a>
    <a href="/admin/order?status=2" class="{{ ($st == '2') ? 'active' : '' }}">Đã hủy</a>
    <a href="/admin/order?ship=3" class="{{ ($sh == '3') ? 'active' : '' }}">Trả hàng/Hoàn tiền</a>
</div>

<section class="content">
    <div class="search-box">
        <form method="get" action="/admin/order">
            <div class="row">
                <div class="col-md-3">
                    <input type="text" name="code" value="{{ request()->get('code') }}" class="form-control" placeholder="Mã đơn hàng">
                </div>
                <div class="col-md-4">
                    <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="form-control" placeholder="Tên khách hàng / Số điện thoại">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-shopee w-100">Tìm kiếm</button>
                </div>
                <div class="col-md-2">
                    <a href="/admin/order" class="btn btn-shopee-outline w-100">Đặt lại</a>
                </div>
            </div>
        </form>
    </div>

    <div class="order-list">
        @if(isset($orders) && count($orders) > 0)
            @foreach($orders as $order)
                <div class="order-item">
                    <div class="order-item-header">
                        <div>
                            <strong>{{$order->name}}</strong>
                            <span class="text-muted ms-3" style="margin-left:15px">ID Đơn hàng: {{$order->code}}</span>
                        </div>
                        <div class="text-right">
                            @if($order->status == 0)
                                <span class="badge-status status-0">CHỜ XÁC NHẬN</span>
                            @elseif($order->status == 1)
                                @if($order->ship == 0)
                                    <span class="badge-status status-1">CHỜ LẤY HÀNG</span>
                                @elseif($order->ship == 1)
                                    <span class="badge-status" style="background:#eaf2ff;color:#2673dd">ĐANG GIAO</span>
                                @elseif($order->ship == 2)
                                    <span class="badge-status" style="background:#e5f9f6;color:#00bfa5">ĐÃ GIAO</span>
                                @endif
                            @elseif($order->status == 2)
                                <span class="badge-status status-2">ĐÃ HỦY</span>
                            @endif
                        </div>
                    </div>
                    <div class="order-item-body">
                        <div class="product-info">
                            @php 
                                $first_item = $order->detail->first(); 
                                $total_items = $order->detail->count();
                            @endphp
                            @if($first_item)
                                <img src="{{ getImage($first_item->image) }}" class="product-img">
                                <div class="product-detail">
                                    <div class="fw-600">{{ $first_item->name }}</div>
                                    <div class="text-muted small">Phân loại: @if($first_item->color){{$first_item->color->name}}@endif @if($first_item->size), {{$first_item->size->name}}@endif</div>
                                    <div class="mt-1">x{{ $first_item->qty }}</div>
                                    @if($total_items > 1)
                                        <div class="mt-2"><a href="/admin/order/view/{{$order->code}}" class="text-info small">Xem thêm {{ $total_items - 1 }} sản phẩm</a></div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="customer-info">
                            <div><i class="fa fa-user-o"></i> {{$order->name}}</div>
                            <div class="text-muted"><i class="fa fa-phone"></i> {{$order->phone}}</div>
                            <div class="small text-muted mt-1" title="{{$order->address}}"><i class="fa fa-map-marker"></i> {{ \Illuminate\Support\Str::limit($order->address, 50) }}</div>
                        </div>
                        <div class="order-stats">
                            <div class="text-muted small">Tổng thanh toán</div>
                            <div class="fs-16 fw-bold text-danger">{{ number_format($order->total + $order->fee_ship - $order->sale) }}đ</div>
                            <div class="text-muted small mt-1">@if($order->payment == 1) Đã thanh toán @else COD @endif</div>
                        </div>
                        <div class="order-actions">
                            @if($order->status == 0)
                                <button class="btn btn-shopee quick-action" data-id="{{$order->code}}" data-action="confirm">Xác nhận</button>
                                <button class="btn btn-shopee-outline quick-action" data-id="{{$order->code}}" data-action="cancel">Hủy đơn</button>
                            @endif
                            
                            @if($order->status == 1 && $order->ship == 0)
                                @if(getConfig('ghtk_status'))
                                    @php $delivery = \App\Modules\Delivery\Models\Delivery::where('code', $order->code)->first(); @endphp
                                    @if($delivery)
                                        <a href="{{route('ghtk.print',['label' => $delivery->label_id])}}" target="_blank" class="btn btn-shopee">In vận đơn</a>
                                    @else
                                        <button class="btn btn-shopee quick-ghtk" data-id="{{$order->code}}">Đăng đơn GHTK</button>
                                    @endif
                                @endif
                            @endif

                            <a href="/admin/order/view/{{$order->code}}" class="btn btn-shopee-outline">Chi tiết</a>
                        </div>
                    </div>
                </div>
            @endforeach
            
            <div class="text-right">
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center bg-white p-5">
                <img src="/public/image/no-order.png" style="width:100px;opacity:0.5">
                <p class="mt-3 text-muted">Không tìm thấy đơn hàng nào</p>
            </div>
        @endif
    </div>
</section>

<script>
    $(document).ready(function() {
        // Xử lý xác nhận nhanh / Hủy đơn
        $('.quick-action').click(function() {
            var code = $(this).data('id');
            var action = $(this).data('action');
            var status = (action === 'confirm') ? 1 : 2;
            var confirmMsg = (action === 'confirm') ? 'Bạn muốn xác nhận đơn hàng này?' : 'Bạn chắc chắn muốn hủy đơn hàng này?';

            if(confirm(confirmMsg)) {
                $.ajax({
                    url: '/admin/order/edit',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        code: code,
                        status: status,
                        payment: (status == 1) ? 0 : 0, // Giữ nguyên thanh toán hoặc tùy chỉnh
                        ship: 0
                    },
                    success: function(res) {
                        if(res.status == 'success') {
                            toastr.success(res.alert);
                            location.reload();
                        } else {
                            toastr.error(res.errors.alert[0]);
                        }
                    }
                });
            }
        });

        // Đăng đơn GHTK nhanh
        $('.quick-ghtk').click(function() {
            var id = $(this).attr('data-id');
            $.ajax({
                type: 'post',
                url:  '{{route("ghtk.create")}}',
                data: {id:id},
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                beforeSend: function () {
                    $('.box_img_load_ajax').removeClass('hidden');
                },
                success: function (res) {
                    $('.box_img_load_ajax').addClass('hidden');
                    if(res.status == 'success'){
                        // Ở đây Shopee thường hiện Modal, mình có thể redirect hoặc hiện modal cũ
                        window.location = '/admin/order/view/' + id;
                    }else{
                        alert(res.message);
                    }
                }
            });
        });
    });
</script>
@endsection
