@extends('Layout::layout')
@section('title','Chi tiết đơn hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Chi tiết đơn hàng',
])
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="box">
                <div class="box-header with-border">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td width="30%">Mã đơn hàng</td>
                            <td width="70%"><strong>{{$order->code}}</strong></td>
                        </tr>
                         <tr>
                            <td width="30%">Họ tên</td>
                            <td width="70%">{{$order->name}}</td>
                        </tr>
                         <tr>
                            <td width="30%">Điện thoại</td>
                            <td width="70%">{{$order->phone}}</td>
                        </tr>
                        @if($order->email != "")
                        <tr>
                            <td width="30%">Địa chỉ email</td>
                            <td width="70%">{{$order->email}}</td>
                        </tr>
                        @endif
                         <tr>
                            <td width="30%">Địa chỉ</td>
                            <td width="70%">{{$order->address}},@if($order->ward) {{$order->ward->name}}, @endif @if($order->district) {{$order->district->name}}, @endif @if($order->province) {{$order->province->name}} @endif
                            </td>
                        </tr>
                         <tr>
                            <td width="30%">Ghi chú</td>
                            <td width="70%">{{$order->remark}}</td>
                        </tr>
                    </table>
                    <h5  class="fs-15">Thông tin sản phẩm</h5>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th colspan="2">Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>SL</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($list->count() > 0)
                            @foreach($list as $key => $product)
                            @php
                                $detail = App\Modules\Post\Models\Post::select('slug')->where('id',$product->product_id)->first();
                            @endphp
                                <tr>
                                    <td>{{$key + 1}}</td>
                                    <td><img src="{{$product->image}}" width="50px" alt="{{$product->name}}"></td>
                                    <td>
                                        @if(str_contains($product->name, '[DEAL SỐC]'))
                                            <span class="label label-danger">DEAL SỐC</span>
                                        @endif
                                        <a href="{{asset($detail->slug)}}" target="_blank">{{$product->name}}</a>
                                        <p style="margin-bottom:0px">@if($product->color)<span class="me-3" style="margin-right:15px">Màu sắc: {{$product->color->name}}</span>@endif @if($product->size)<span>Kích thước: {{$product->size->name}}{{$product->size->unit}}</span>@endif</p>
                                    </td>
                                    <td style="color:red">{{number_format($product->price)}}</td>
                                    <td>{{$product->qty}}</td>
                                    <td style="color:red;font-weight:600">{{number_format($product->qty * $product->price)}}</td>
                                </tr>
                            @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            @php $feeship = 0 @endphp
                            @if(getConfig('ghtk_status'))
                            @if(isset($delivery) && !empty($delivery))
                            @php $feeship = $status->ship_money; @endphp
                            <tr>
                               <td><i class="fa fa-truck" aria-hidden="true"></i></td>
                                <td colspan="4">
                                    <span>@if($delivery->type=='ghtk')<strong>GHTK đường bộ</strong>@else @endif</span>
                                    <p>{{$status->status_text}}</p>
                                </td>
                                <td>
                                    {{number_format($status->ship_money)}}đ
                                </td>
                            </tr>
                            @else
                            @php $feeship = $fee->fee; @endphp
                            <tr>
                                <td><i class="fa fa-truck" aria-hidden="true"></i></td>
                                <td colspan="4">
                                    <span><strong>GHTK đường bộ</strong></span>
                                </td>
                                <td>
                                    {{number_format($fee->fee)}}đ
                                </td>
                            </tr>
                            @endif
                            @endif
                            <tr>
                                <td colspan="5" style="text-align: right;">Tạm tính</td>
                                <td><span style="color: red;">{{number_format($order->total)}}đ</span></td>
                            </tr>
                            @if($order->sale !=0)
                            <tr>
                                <td colspan="5" style="text-align: right;">Khuyến mại</td>
                                <td><span style="color: red;">-{{number_format($order->sale)}}đ</span></td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="5" style="text-align: right;">Tổng tiền</td>
                                <td><span style="color: red;font-weight: 600;font-size: 16px;">{{number_format($order->total + $feeship - $order->sale)}}đ</span></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>
        </div>
        <div class="col-md-4">
            <form method="post" id="tblForm" ajax="/admin/order/edit">
                @csrf
                <input type="hidden" name="code" value="{{$order->code}}">
            <div class="box">
                <div class="box-header with-border">
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td>Trạng thái đơn hàng</td>
                            <td>
                                <select class="form-control" name="status">
                                    <option value="0" @if($order->status == 0) selected="" @endif>Chưa xác thực</option>
                                    <option value="1" @if($order->status == 1) selected="" @endif>Đã xác thực</option>
                                    <option value="2" @if($order->status == 2) selected="" @endif>Hủy đơn</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Thanh toán</td>
                            <td>
                                <select class="form-control" name="payment">
                                    <option value="0" @if($order->payment == 0) selected="" @endif>Chưa thanh toán</option>
                                    <option value="1" @if($order->payment == 1) selected="" @endif>Đã thanh toán</option>
                                    <option value="2" @if($order->payment == 2) selected="" @endif>Bị hoàn trả</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Vận chuyển</td>
                            <td>
                                <select class="form-control" name="ship">
                                    <option value="0" @if($order->ship == 0) selected="" @endif>Chưa chuyển</option>
                                    <option value="1" @if($order->ship == 1) selected="" @endif>Đã chuyển</option>
                                    <option value="2" @if($order->ship == 2) selected="" @endif>Đã nhận</option>
                                    <option value="3" @if($order->ship == 3) selected="" @endif>Bị hoàn trả</option>
                                    <option value="3" @if($order->ship == 4) selected="" @endif>Đã hủy</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="40%">Ngày đặt</td>
                            <td width="60%"><strong>{{date('H:i:s d-m-Y',strtotime($order->created_at))}}</strong></td>
                        </tr>
                        <tr>
                            <td width="40%">Người quản lý</td>
                            <td width="60%">@if($order->user) {{$order->user->name}} @endif</td>
                        </tr>
                        <tr>
                            <td width="40%">Cập nhật cuối</td>
                            <td width="60%">@if($order->updated_at != null){{date('H:i:s d-m-Y',strtotime($order->updated_at))}}@endif</td>
                        </tr>
                    </table>
                    <textarea class="form-control" name="content"  rows="4" placeholder="Ghi chú đơn hàng của quản trị viên" style="margin-top: 15px;">{{$order->content}}</textarea>
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px"><i class="fa fa-refresh" aria-hidden="true"></i> Cập nhật đơn hàng</button>
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h4 class="fs-15">Vận chuyển</h4>
                    <hr style="margin-bottom: 10px;margin-top:0px" />
                    @if(isset($delivery) && !empty($delivery))
                    @if(getConfig('ghtk_status'))
                    <div style="margin-bottom: 10px;"><strong>Đăng đơn GHTK</strong></div>
                    <table class="table table-bordered table-striped">
                        <tr>
                            <td width="40%">Tình trạng:</td>
                            <td>{{$status->status_text}}</td>
                        </tr>
                        <tr>
                            <td width="40%">ID trên GHTK:</td>
                            <td>{{$status->label_id}}</td>
                        </tr>
                        <tr>
                            <td width="40%">Ngày lấy hàng:</td>
                            <td>{{$status->pick_date}}</td>
                        </tr>
                        <tr>
                            <td width="40%">Ngày giao hàng:</td>
                            <td>{{$status->deliver_date}}</td>
                        </tr>
                    </table>
                    <a type="button" class="btn btn-primary mb-5" href="{{route('ghtk.print',['id' =>$status->label_id])}}" target="_blank">In hóa đơn GHTK</a>
                    <button type="button" class="btn btn-danger mb-5 btnCancel" data-label="{{$status->label_id}}">Hủy đơn</button>
                    @endif
                    @else
                        @if(getConfig('ghtk_status'))
                        @if($order->ship == 4)
                            <button type="button" class="btn btn-danger">Đơn hàng đã hủy không thể đăng lại</button>
                        @else
                            <button type="button" data-id="{{$order->code}}" class="btn btn-primary" id="createGHTK">Đăng đơn hàng lên GHTK</button>
                        @endif
                        
                        @endif
                    @endif
                    <div class="box-alert"></div>
                </div>
            </div>
            </form>
        </div>
    </div>

</section>
<div class="modal fade" id="modalGHTK">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="createOrderGHTK">
                @csrf
                <input type="hidden" name="code" value="{{$order->code}}">
                <div class="modal-header">
                    <h4 class="modal-title">Đăng đơn lên GHTK</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                   
                </div>
                <div class="box-alert" style="padding:0px 15px;"></div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy đăng</button>
                    <button type="submit" class="btn btn-primary">Đăng ngay</button>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
    .mb-5{
        margin-bottom: 5px;
    }
    .table>thead:first-child>tr:first-child>th {
    border: 1px solid #d1d1d1;
}
    .table-bordered>thead>tr>th, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>tbody>tr>td, .table-bordered>tfoot>tr>td{
    border: 1px solid #d1d1d1;
}.modal-header .close{position: absolute;right: 15px;top: 20px;}
</style>
<script src="/public/js/jquery.validate.min.js"></script>
<script>
    $('body #createOrderGHTK').validate({
      rules: {
          thuho: {
             required: true,
             number:true,
          },
          giatridon:{
            required: true,
             number:true,
          }
      },
    messages: {
        thuho: {
           required: "Không được bỏ trống",
           number:"Giá trị nhập vào không đúng",
        },
        giatridon: {
           required: "Không được bỏ trống",
           number:"Giá trị nhập vào không đúng",
        },
    },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("ghtk.store")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                var string = JSON.parse(res);
                if(string.success== false){
                    $('body #createOrderGHTK .box-alert').html('<div class="alert alert-danger" role="alert">'+string.message+'</div>');
                }else{
                    alert(string.message);
                    window.location = window.location.href;
                    // if(string.status == true){

                    // }else{
                    //     $('body #createOrderGHTK .box-alert').html('<div class="alert alert-danger" role="alert">'+string.message+'</div>');
                    // }
                }
              }
          });
          return false;
      }
    });
    $('#createGHTK').click(function(){
        var id = $(this).attr('data-id');
        $.ajax({
              type: 'post',
              url:  '{{route("ghtk.create")}}',
              data: {id:id},
              headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                if(res.status == 'success'){
                    $("#modalGHTK").modal('show');
                    $("#modalGHTK .modal-body").html(res.view);
                }else{
                    alert(res.message);
                }
              },
              error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        });
    });
    $('.btnCancel').click(function(){
        var label = $(this).attr('data-id');
        $.ajax({
              type: 'post',
              url:  '{{route("ghtk.cancel")}}',
              data: {label:label},
              headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
              beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                if(res.status == 'success'){
                    alert(res.alert);
                    window.location = window.location.href;
                }else{
                    $('.box-alert').html('<div class="alert alert-danger" role="alert"><ul class="w-100 overflow-hidden">'+res.message+'</ul></div>');
                }
              },
               error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
                }
          });
    });
</script>
@endsection