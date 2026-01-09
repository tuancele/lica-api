@extends('Layout::layout')
@section('title','Thông tin thành viên')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thông tin thành viên',
])
<script type="text/javascript" src="/public/admin/jquery.validate.min.js"></script>
<section class="content">
        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-body">
                      <div class="title_h5">
                        <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left"><i class="fa fa-user-circle" aria-hidden="true"></i> {{$member->first_name}} {{$member->last_name}}</h5>
                      </div>
                        @if(isset($order) && !empty($order))
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <p>Đơn hàng gần nhất</p>
                                <p><strong><a href="/admin/order/view/{{$order->code}}" target="_blank">{{$order->code}}</a></strong></p>
                                <p>{{date('H:i d/m/Y', strtotime($order->created_at))}}</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <p>Doanh thu tích lũy</p>
                                <p><strong>
                                  @php $arr_income = $income->toArray(); 
                                    $total = array_sum(array_column($arr_income, 'total')) + array_sum(array_column($arr_income, 'fee_ship')) - array_sum(array_column($arr_income, 'sale'));
                                  @endphp
                                  {{number_format($total)}}đ</strong></p>
                                <p>{{$orders->count()}} đơn hàng</p>
                            </div>
                            <div class="col-md-4 text-center">
                                <p>Giá trị trung bình đơn hàng</p>
                                <p><strong>@if($total != 0) {{number_format($total/$income->count())}}đ @else 0đ @endif</strong></p>
                            </div>
                        </div>
                        @else
                        <p>Khách hàng này hiện không có đơn hàng.</p>
                        @endif
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="title_h5">
                          <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Địa chỉ nhận hàng</h5>
                        </div>
                        <table class="table table-bordered table-striped">
                          <thead>
                            <tr>
                              <th>Họ tên</th>
                              <th>Điện thoại</th>
                              <th>Địa chỉ</th>
                              <th>Thao tác</th>
                            </tr>
                          </thead>
                          <tbody class="list_address">
                             @if($addresss->count() > 0) 
                             @foreach($addresss as $address)
                             <tr>
                               <td width="20%">{{$address->first_name}} {{$address->last_name}}</td>
                               <td width="15%">{{$address->phone}}</td>
                               <td width="50%">
                                  <p>{{$address->address}}</p>
                                  <p>@if(isset($address->ward)){{$address->ward->name}}@endif @if(isset($address->district)), {{$address->district->name}}@endif @if(isset($address->province)), {{$address->province->name}}@endif</p>
                               </td>
                               <td width="10%">
                                 <a class="btn btn-primary btn-xs btn_edit" title="Sửa" data-id="{{$address->id}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                <a class="btn btn-danger btn-xs del_address" title="Xóa" data-id="{{$address->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                               </td>
                             </tr>
                             @endforeach
                             @else
                             @endif
                          </tbody>
                      </table>
                  </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                      <div class="title_h5">
                        <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Đơn hàng</h5>
                      </div>
                        <div class="table-responsive">
                          @if($orders->count() > 0)
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>Mã đơn hàng</th>
                          <th>Số lượng</th>
                          <th>Tổng tiền</th>
                          <th>Ngày đặt</th>
                        </tr>
                      </thead>
                      <tbody >
                        @foreach($orders as $order)
                        <tr>
                          <td><a href="/admin/order/view/{{$order->code}}" target="_blank">#{{$order->code}}</a></td>
                          <td>{{$order->detail->count()}}</td>
                          <td>{{number_format($order->total + $order->fee_ship - $order->sale)}}$</td>
                          <td>{{date('H:i:s d/m/Y',strtotime($order->created_at))}}</td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
                    @else
                      Khách hàng này hiện không có đơn hàng.
                    @endif
                  </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
              <div class="panel panel-default">
                    <div class="panel-body">
                      <div class="title_h5">
                        <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Thông tin tài khoản</h5>
                        <a href="javascript:;" data-toggle="modal" data-target="#setPassword" class="pull-right">Thiết lập lại mật khẩu</a>
                      </div>
                       <form role="form" id="formEdit" method="post">
                        @csrf
                          <div class="form-group">
                            <div class="row">
                              <div class="col-md-6">
                                  <label class="fw-700">
                                  Họ
                                  </label>
                                  <input type="text" value="{{$member->first_name}}" name="first_name" class="form-control">
                                  <input type="hidden" name="id" value="{{$member->id}}">
                              </div>
                              <div class="col-md-6">
                                  <label class="fw-700">
                                  Tên
                                  </label>
                                  <input type="text" value="{{$member->last_name}}" name="last_name" class="form-control">
                              </div>
                            </div>
                              
                          </div>
                          <div class="form-group">
                              <label class="fw-700">
                                  Email
                              </label>
                              <input type="text" value="{{$member->email}}" name="email" class="form-control">
                          </div>
                          <div class="form-group">
                              <label class="fw-700">
                                  Số điện thoại
                              </label>
                              <input type="tel" value="{{$member->phone}}" name="phone" class="form-control">
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group">
                                  <label class="fw-700">
                                      Trạng thái
                                  </label>
                                  <select class="form-control" name="status">
                                       <option value="1" @if($member->status==1) selected="" @endif>Hoạt động</option>
                                       <option value="2" @if($member->status==2) selected="" @endif>Ngừng hoạt đông</option>
                                   </select>
                              </div>
                            </div>
                          </div>
                          <button type="submit" class="btn btn-primary">Lưu</button>
                      </form>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
</section>
<div class="modal fade" id="setPassword" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document" style="width: 30%">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Thiết lập lại mật khẩu</h4>
      </div>
      <form role="form" method="post" class="formPassword">
        @csrf
      <div class="modal-body">
        <div class="form-group">
          <label for="" class="fw-700">Mật khẩu</label>
          <input type="password" name="password" class="form-control" id="password">
          <input type="hidden" name="id" value="{{$member->id}}">
          <input type="hidden" name="name" value="{{$member->name}}">
        </div>
        <div class="form-group">
          <label for="" class="fw-700">Xác nhận lại mật khẩu</label>
          <input type="password" name="confirm" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Lưu</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
      </div>
    </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<div class="modal fade" id="editAddress" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>
<div class="modal fade" id="addAddress" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div>

<script type="text/javascript">
  function showAddAddress(id){
      $.ajax({
        type: 'post',
        url: '/admin/member/get_addaddress',
        data: {id: id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('#addAddress').modal('show');
            $('#addAddress .modal-content').html(res);
        }
    })
  }
    $('body').on('click','.del_address',function(){
      var id = $(this).attr('data-id');
      var $this = $(this);
      if (confirm('Bạn có chắc chắc muốn xóa dữ liệu này?')) {
        $.ajax({
            type: 'post',
            url: '/admin/member/del-address',
            data: {id: id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                if(res.status == 'error'){
                  toastr.error(res.message, 'Thông báo'); 
                }else{
                  $this.closest('tr').remove();
                }
            }
        })
      }else{
        return false;
      }
    });
    $('.formPassword').validate({
        rules: { 
          password:{
              required: true, 
          },
          confirm: {
             required: true, 
             equalTo: '#password',
          },
      },
      messages: {
          password:{
              required: "Không được bỏ trống"
          },
          confirm: {
             required:"Không được bỏ trống",
             equalTo: "Xác nhận mật khẩu không đúng",
          },
      },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '/admin/member/edit-password',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                  $('.box_img_load_ajax').addClass('hidden');
                  if(res.status == 'error'){
                      var errTxt = '';
                      if(res.errors !== undefined) {
                          Object.keys(res.errors).forEach(key => {
                              errTxt += '<li>'+res.errors[key][0]+'</li>';
                          });
                      } else {
                          errTxt = '<li>'+res.message+'</li>';
                      } 
                      toastr.error(errTxt, 'Thông báo'); 
                  }else{
                    $('#setPassword').modal('hide');
                    toastr.success(res.alert, 'Thông báo');
                  }
              }
          });
          return false;
      }
    });
    $('#formEdit').validate({
        rules: { 
          first_name:{
              required: true, 
          },
          last_name:{
              required: true, 
          },
          phone: {
             required: true, 
             number: true,
          },
          email:{
              required: true, 
              email: true,
          }
      },
      messages: {
          first_name:{
              required: "Không được bỏ trống"
          },
          last_name:{
              required: "Không được bỏ trống"
          },
          phone: {
             required:"Không được bỏ trống",
             number: "Số điện thoại không đúng",
          },
          email:{
              required: 'Không được bỏ trống', 
              email: 'Địa chỉ email không đúng',
          }
      },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '/admin/member/edit',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                  $('.box_img_load_ajax').addClass('hidden');
                  if(res.status == 'error'){
                      var errTxt = '';
                      if(res.errors !== undefined) {
                          Object.keys(res.errors).forEach(key => {
                              errTxt += '<li>'+res.errors[key][0]+'</li>';
                          });
                      } else {
                          errTxt = '<li>'+res.message+'</li>';
                      } 
                      toastr.error(errTxt, 'Thông báo'); 
                  }else{
                    toastr.success(res.alert, 'Thông báo');
                  }
              }
          });
          return false;
      }
    });
  
  $('.list_address').on('click','.btn_edit',function(){
    var id = $(this).attr('data-id');
      $.ajax({
        type: 'post',
        url: '/admin/member/get_editaddress',
        data: {id: id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
            $('.box_img_load_ajax').addClass('hidden');
            $('#editAddress').modal('show');
            $('#editAddress .modal-content').html(res);
        }
    })
  });
</script>
@endsection