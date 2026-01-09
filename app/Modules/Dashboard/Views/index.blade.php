@extends('Layout::layout')
@section('title','Tổng quan')
@section('content')
<section class="content-header">
    <h1>
    Tổng quan
    </h1>
    <ol class="breadcrumb">
        <li><a href="/admin/dashboard"><i class="fa fa-dashboard"></i> Tổng quan</a></li>
    </ol>
</section>
<div class="content-header">
  <ul class="list_breadcrumb">
    <li><a href="/admin/dashboard" class="active">Tổng quan</a></li>
    <li><a href="/admin/dashboard/orders" >Trạng thái đơn hàng</a></li>
  </ul>
</div>
<section class="content">
    <!-- Main row -->
    <div class="row">
            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-aqua">
                <div class="inner">
                  <h3>{{$order->count()}}</h3>
                  <p>Đơn hàng</p>
                </div>
                <div class="icon">
                  <i class="ion ion-bag"></i>
                </div>
                <a href="{{asset('admin/order')}}" class="small-box-footer">Chi tiết <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div><!-- ./col -->
            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-green">
                <div class="inner">
                  <h3>{{$contact}}</h3>
                  <p>Liên hệ</p>
                </div>
                <div class="icon">
                  <i class="fa fa-envelope-o" aria-hidden="true"></i>
                </div>
                <a href="{{asset('admin/contact')}}" class="small-box-footer">Chi tiết <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div><!-- ./col -->
            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-yellow">
                <div class="inner">
                  <h3>{{$product}}</h3>
                  <p>Sản phẩm</p>
                </div>
                <div class="icon">
                  <i class="fa fa-star" aria-hidden="true"></i>
                </div>
                <a href="{{asset('admin/product')}}" class="small-box-footer">Chi tiết <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div><!-- ./col -->
            <div class="col-lg-3 col-xs-6">
              <!-- small box -->
              <div class="small-box bg-red">
                <div class="inner">
                  <h3>{{$post}}</h3>
                  <p>Bài viết</p>
                </div>
                <div class="icon">
                  <i class="fa fa-file-text-o" aria-hidden="true"></i>
                </div>
                <a href="{{asset('admin/post')}}" class="small-box-footer">Chi tiết <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div><!-- ./col -->
          </div>
          <div class="row">

            <div class="col-md-12">
              <!-- LINE CHART -->
              <div class="box" style="border-top:none;padding-top: 10px;">
                <div class="box-body chart-responsive">
                  <div class="row">
                    <div class="col-md-4 text-center" style="box-shadow: inset -1px 0 0 rgb(38 50 56 / 10%);">
                      <label style="color:#575962">TỔNG DOANH THU</label>
                      <p style="color: rgb(10, 171, 117);font-size: 18px;font-weight: 600;margin-bottom: 0px;">{{number_format(array_sum(array_column($order->toArray(), 'total')) + array_sum(array_column($order->toArray(), 'fee_ship')) - array_sum(array_column($order->toArray(), 'sale')))}}đ</p>
                    </div>
                    <div class="col-md-4 text-center" style="box-shadow: inset -1px 0 0 rgb(38 50 56 / 10%);">
                      <label style="color:#575962">TỔNG ĐƠN HÀNG</label>
                      <p style="color: rgb(10, 171, 117);font-size: 18px;font-weight: 600;margin-bottom: 0px;">{{$order->count()}}</p>
                    </div>
                    <div class="col-md-4 text-center">
                      <label style="color:#575962">LƯỢNG HÀNG ĐÃ BÁN</label>
                      <p style="color: #575962;font-size: 18px;font-weight: 600;margin-bottom: 0px;">{{array_sum(array_column($products->toArray(), 'qty'))}}</p>
                    </div>
                  </div>  
                  <hr/>
                  <div class="row loadchart">
                    {!!$block_chart!!}  
                  </div>
                </div><!-- /.box-body -->
              </div><!-- /.box -->
            </div>
          </div>
</section><!-- /.content -->
@endsection