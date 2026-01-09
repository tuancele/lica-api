@extends('admin.layout')
@section('title','Thiết lập trang liên hệ')
@section('content')
@include('admin.layout.breadcrumb',[
    'title' => 'Thiết lập trang liên hệ',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/themes/contact">
	<div class="row">
        <div class="col-lg-6">
        	<img src="/public/admin/themes/page-contact.jpg" alt="contact" class="img-responsive">
        </div>
        <div class="col-lg-6">
            @csrf
            <input type="hidden" name="id" value="{{$contact->id}}">
            @php $map = json_decode($contact->block_0) @endphp
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">1. Google maps</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="row form-group">
                         <div class="col-md-4">
                            <label>Nhúng mã</label>
                        </div>
                        <div class="col-md-8">
                            <textarea class="form-control" rows="4" name="map_1">{!!$map->map!!}</textarea>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Trạng thái </label>
                        </div>
                        <div class="col-md-8">
                            <input type="checkbox" class="minimal" value="1" name="status_1" @if($map->status == 1) checked="" @endif/>
                                  Hiển thị
                        </div>
                    </div>
                </div>
            </div>
             @php $info = json_decode($contact->block_1) @endphp
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">2. Thông tin liên hệ</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Tiêu đề </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="title_2" value="{{$info->title}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                         <div class="col-md-4">
                            <label>Địa chỉ </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="address_2" value="{{$info->address}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                         <div class="col-md-4">
                            <label>Điện thoại </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="phone_2" value="{{$info->phone}}" class="form-control">
                        </div>
                    </div>
                     <div class="row form-group">
                         <div class="col-md-4">
                            <label>Email </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="email_2" value="{{$info->email}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                         <div class="col-md-4">
                            <label>Website </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="website_2" value="{{$info->website}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Trạng thái </label>
                        </div>
                        <div class="col-md-8">
                            <input type="checkbox" class="minimal" value="1" name="status_2" @if($info->status == 1) checked="" @endif/>
                                  Hiển thị
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        
    </div>
    <div class="fix_action">
        <div class="form-group">
            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu thay đổi</button>
        </div>
    </div>
    </form>
</section>
<link href="/public/admin/plugins/iCheck/all.css" rel="stylesheet" type="text/css" />
<script src="/public/admin/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
<script type="text/javascript">
     $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
          checkboxClass: 'icheckbox_minimal-blue',
          radioClass: 'iradio_minimal-blue'
        });
</script>
@endsection