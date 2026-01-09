@extends('Layout::layout')
@section('title','Cài đặt')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cài đặt',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/setting/update">
        @csrf
        <div class="row">
            <div class="col-lg-3">
                @include('Setting::sidebar',['active' => 'email'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Tên người gửi:</label>
                            <input type="text" name="data[emai_name_send]" class="form-control" value="{{getSetting('emai_name_send')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Email người gửi:</label>
                            <input type="text" name="data[email_send]" class="form-control" value="{{getSetting('email_send')}}">   
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Tên người nhận phản hồi:</label>
                            <input type="text" name="data[reply_name]" class="form-control" value="{{getSetting('reply_name')}}">
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Email nhận phản hồi:</label>
                            <input type="text" name="data[reply_email]" class="form-control" value="{{getSetting('reply_email')}}">     
                        </div> 
                        <h4>Cấu hình máy chủ gửi mail</h4>
                        <div class="form-group">
                            <label class="control-label">Máy chủ (SMTP) Thư Gửi đi:</label>
                            <input type="text" name="data[smtp_host]" class="form-control" value="{{getSetting('smtp_host')}}"> 
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Cổng gửi mail:</label>
                            <input type="text" name="data[smtp_port]" class="form-control" value="{{getSetting('smtp_port')}}"> 
                        </div>
                        <div class="form-group">
                            <label class="control-label">Sử dụng Xác thực:</label>
                            <select class="form-control" name="data[smtp_encryption]">
                                <option value="">Không</option>
                                <option value="SSL" @if(getSetting('smtp_encryption') == 'SSL') selected="" @endif>SSL</option>
                                <option value="TLS" @if(getSetting('smtp_encryption') == 'TLS') selected="" @endif>TLS</option>
                            </select>
                        </div>  
                         <h4>Tài khoản gửi mail</h4>
                         <div class="form-group">
                            <label class="control-label">Tài khoản gửi:</label>
                            <input type="text" name="data[smtp_email]" class="form-control" value="{{getSetting('smtp_email')}}"> 
                        </div>
                        <div class="form-group">
                            <label class="control-label">Mật khẩu:</label>
                            <input type="password" name="data[smtp_password]" class="form-control" value="{{getSetting('smtp_password')}}"> 
                        </div>   
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu cài đặt</button>
            </div>
        </div>
    </form>
</section>
@endsection