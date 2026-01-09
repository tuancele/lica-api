@extends('Layout::layout')
@section('title','Thêm thành viên')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm thành viên',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('member.create')}}">
        @csrf
          <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-body">
                       <div class="div" style="overflow: hidden;">
                            <h5 class="mb-0 mt-0 cl-blue fs-15">Thông tin chung</h5>
                        </div>
                        <hr class="mb-10 mt-10" />
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                   <label class="fw-700">Họ</label>
                                   <input type="text" name="first_name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                   <label class="fw-700">Tên</label>
                                   <input type="text" name="last_name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                               <div class="form-group">
                                   <label class="fw-700">Email đăng nhập</label>
                                   <input type="text" name="email" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                               </div> 
                            </div>
                            <div class="col-md-6">
                               <div class="form-group">
                                   <label class="fw-700">Điện thoại</label>
                                   <input type="text" name="phone" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                               </div> 
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                               <div class="form-group">
                                   <label class="fw-700">Mật khẩu đăng nhập</label>
                                   <input type="password" name="password" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                               </div> 
                            </div>
                            <div class="col-md-6">
                               <div class="form-group">
                                   <label class="fw-700">Trạng thái</label>
                                   <select class="form-control" name="status">
                                       <option value="1">Hoạt động</option>
                                       <option value="2">Ngừng hoạt đông</option>
                                   </select>
                               </div> 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                       <div class="div" style="overflow: hidden;">
                            <h5 class="mb-0 mt-0 cl-blue fs-15">Địa chỉ</h5>
                        </div>
                        <hr class="mb-10 mt-10" />
                        <div class="form-group">
                           <label class="fw-700">Địa chỉ nhà</label>
                           <input type="text" name="address" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                               <div class="form-group">
                                   <label class="fw-700">Tỉnh/Thành phố</label>
                                   <select class="form-control" name="provinceid" id="province">
                                       {!!$province!!}
                                   </select>
                               </div> 
                            </div>
                            <div class="col-md-4">
                               <div class="form-group">
                                   <label class="fw-700">Quận/Huyện</label>
                                   <select class="form-control" name="districtid" id="district">
                                       <option value="">---</option>
                                   </select>
                               </div> 
                            </div>
                            <div class="col-md-4">
                               <div class="form-group">
                                   <label class="fw-700">Phường/Xã</label>
                                   <select class="form-control" name="wardid" id="ward">
                                       <option value="">---</option>
                                   </select>
                               </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('member')])
        </div>
    </form>
</section>
<script type="text/javascript">
    $('#province').change(function(){
        var province = $(this).val();
        $("#district").load("/admin/member/district/"+province);
    });
    $('#district').change(function(){
        var district = $(this).val();
        $("#ward").load("/admin/member/ward/"+district);
    });
</script>
@endsection