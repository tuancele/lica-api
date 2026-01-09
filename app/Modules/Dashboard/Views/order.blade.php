@extends('Layout::layout')
@section('title','Tổng quan')
@section('content')
<section class="content-header">
    <h1>
    Tình trạng đơn hàng
    </h1>
    <ol class="breadcrumb">
        <li><a href="/admin/dashboard">Tổng quan</a></li>
        <li><a href="/admin/dashboard/orders">Tình trạng đơn hàng</a></li>
    </ol>
</section>
<div class="content-header">
  <ul class="list_breadcrumb">
    <li><a href="/admin/dashboard">Tổng quan</a></li>
    <li><a href="/admin/dashboard/orders" class="active">Trạng thái đơn hàng</a></li>
  </ul>
</div>

<section class="content">
  <div class="box" style="border-top:none">
  <div class="box-footer">
    <div class="list_box">
      <div class="item_box">
        <div class="description-block border-right" onclick="window.location = '/admin/order?stautus=1&payment=0'">
          <span class="description-percentage text-green fs-20"><i class="fa fa-credit-card" aria-hidden="true"></i></span>
          <p><span class="description-text text-green">CHƯA THANH TOÁN</span></p>
          <h5 class="description-header text-blue">{{$payment}}</h5>
        </div><!-- /.description-block -->
      </div><!-- /.col -->
      <div class="item_box">
        <div class="description-block border-right" onclick="window.location = '/admin/order?stautus=1&ship=0'">
          <span class="description-percentage text-orange fs-20"><i class="fa fa-truck" aria-hidden="true"></i></span>
          <p><span class="description-text text-orange">CHƯA GIAO HÀNG</span></p>
          <h5 class="description-header text-blue">{{$ship}}</h5>
        </div><!-- /.description-block -->
      </div><!-- /.col -->
      <div class="item_box">
        <div class="description-block border-right" onclick="window.location = '/admin/order?stautus=1&payment=1'"> 
          <span class="description-percentage text-blue fs-20"><i class="fa fa-truck" aria-hidden="true"></i></span>
          <p><span class="description-text text-blue">ĐANG GIAO HÀNG</span></p>
          <h5 class="description-header text-blue">{{$shipping}}</h5>
        </div><!-- /.description-block -->
      </div><!-- /.col -->
      <div class="item_box">
        <div class="description-block border-right" onclick="window.location = '/admin/order?stautus=2'">
          <span class="description-percentage text-red fs-20"><i class="fa fa-window-close" aria-hidden="true"></i></span>
          <p><span class="description-text text-red">ĐÃ HỦY</span></p>
          <h5 class="description-header text-blue">{{$cancel}}</h5>
        </div><!-- /.description-block -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </div>
  </div>
  <div class="row">
    <div class="col-md-4">
      <div class="box">
        <div class="box-header with-border title_h5 mb-0">
            <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Đơn Hàng Chưa Thanh Toán</h5>
            <div class="box-tools pull-right">
                <select class="form-control select-none chuathanhtoan">
                  <option value="1" style="text-align: right;">1 Ngày</option>
                  <option value="2">2 Ngày</option>
                  <option value="3">3 Ngày</option>
                  <option value="4">Trên 3 Ngày</option>
                </select>
            </div>
        </div>
        <div class="box-body load_chuathanhtoan">
            <ul class="products-list product-list-in-box">
              @if($payments->count()>0)
              @foreach($payments as $val_payment)
              <li class="item">
                  <div class="product-info ml-0">
                    <a href="/admin/order/view/{{$val_payment->code}}" class="product-title pull-left">{{$val_payment->code}}</a>
                    <div class="pull-right text-right">
                        <p class="mb-0"><strong>{{number_format($val_payment->total)}}đ</strong></p>
                        <span class="product-description">
                            {{date('d/m/Y H:i:s',strtotime($val_payment->created_at))}}
                        </span>
                    </div>
                  </div>
                </li>
                @endforeach
                @else
                <p>Hiện không có đơn hàng nào chưa được xử lý</p>
                @endif
            </ul>
           <div class="box_load hidden"><div class="ring"></div></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box">
        <div class="box-header with-border title_h5 mb-0">
            <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Đơn Hàng Chưa Giao</h5>
            <div class="box-tools pull-right">
                <select class="form-control select-none donchuagiao">
                  <option value="1">1 Ngày</option>
                  <option value="2">2 Ngày</option>
                  <option value="3">3 Ngày</option>
                  <option value="4">Trên 3 Ngày</option>
                </select>
            </div>
        </div>
        <div class="box-body load_donchuagiao">
            <ul class="products-list product-list-in-box">
              @if($ships->count()>0)
              @foreach($ships as $val_ship)
              <li class="item">
                  <div class="product-info ml-0">
                    <a href="/admin/order/view/{{$val_ship->code}}" class="product-title pull-left">{{$val_ship->code}}</a>
                    <div class="pull-right text-right">
                        <p class="mb-0"><strong>{{number_format($val_ship->total)}}đ</strong></p>
                        <span class="product-description">
                            {{date('d/m/Y H:i:s',strtotime($val_ship->created_at))}}
                        </span>
                    </div>
                  </div>
                </li>
                @endforeach
                @else
                <p>Hiện không có đơn hàng nào chưa được xử lý</p>
                @endif
            </ul>
            <div class="box_load hidden"><div class="ring"></div></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="box">
        <div class="box-header with-border title_h5 mb-0">
            <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Đơn Hàng Đang Giao</h5>
            <div class="box-tools pull-right">
                <select class="form-control select-none danggiao">
                  <option value="1" style="text-align: right;">1 Ngày</option>
                  <option value="2">2 Ngày</option>
                  <option value="3">3 Ngày</option>
                  <option value="4">Trên 3 Ngày</option>
                </select>
            </div>
        </div>
        <div class="box-body load_danggiao">
            <ul class="products-list product-list-in-box">
              @if($shippings->count()>0)
              @foreach($shippings as $val_shipping)
              <li class="item">
                  <div class="product-info ml-0">
                    <a href="/admin/order/view/{{$val_shipping->code}}" class="product-title pull-left">{{$val_shipping->code}}</a>
                    <div class="pull-right text-right">
                        <p class="mb-0"><strong>{{number_format($val_shipping->total)}}đ</strong></p>
                        <span class="product-description">
                            {{date('d/m/Y H:i:s',strtotime($val_shipping->created_at))}}
                        </span>
                    </div>
                  </div>
                </li>
                @endforeach
                @else
                <p>Hiện không có đơn hàng nào chưa được xử lý</p>
                @endif
            </ul>
            <div class="box_load hidden"><div class="ring"></div></div>
        </div>
      </div>
    </div>
  </div>
</section><!-- /.content -->
<script type="text/javascript">
  $('select.donchuagiao').change(function(){
    var id = $(this).val();
    $.ajax({
        type: 'post',
        url: '/admin/dashboard/donchuagiao',
        data: {id: id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
          $('.load_donchuagiao .box_load').removeClass('hidden');
        },
        success: function (res) {
          $('.load_donchuagiao .box_load').addClass('hidden');
          $('.load_donchuagiao .products-list').html(res);
        }
    })
  });
  $('select.chuathanhtoan').change(function(){
    var id = $(this).val();
    $.ajax({
        type: 'post',
        url: '/admin/dashboard/chuathanhtoan',
        data: {id: id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
          $('.load_chuathanhtoan .box_load').removeClass('hidden');
        },
        success: function (res) {
          $('.load_chuathanhtoan .box_load').addClass('hidden');
          $('.load_chuathanhtoan .products-list').html(res);
        }
    })
  });
  $('select.danggiao').change(function(){
    var id = $(this).val();
    $.ajax({
        type: 'post',
        url: '/admin/dashboard/danggiao',
        data: {id: id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
          $('.load_danggiao .box_load').removeClass('hidden');
        },
        success: function (res) {
          $('.load_danggiao .box_load').addClass('hidden');
          $('.load_danggiao .products-list').html(res);
        }
    })
  });
</script>
@endsection