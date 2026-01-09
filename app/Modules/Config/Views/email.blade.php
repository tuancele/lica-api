@extends('Layout::layout')
@section('title','Cài đặt')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cài đặt',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/config/update">
        @csrf
        <div class="row">
            <div class="col-lg-3">
                @include('Config::sidebar',['active' => 'email'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Tên người gửi:</label>
                                    <input type="text" name="data[email_name_send]" class="form-control" value="{{getConfig('email_name_send')}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Email người gửi:</label>
                                    <input type="text" name="data[email_send]" class="form-control" value="{{getConfig('email_send')}}">   
                                </div> 
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Tên người nhận phản hồi:</label>
                                    <input type="text" name="data[reply_name]" class="form-control" value="{{getConfig('reply_name')}}">
                                </div> 
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Email nhận phản hồi:</label>
                                    <input type="text" name="data[reply_email]" class="form-control" value="{{getConfig('reply_email')}}">
                                </div> 
                            </div>
                        </div>
                        <hr/>
                        <h4>Cấu hình máy chủ gửi mail</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Mail driver:</label>
                                    <input type="text" name="data[mail_driver]" class="form-control" value="{{getConfig('mail_driver')}}">
                                </div> 
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Máy chủ (SMTP):</label>
                                    <input type="text" name="data[smtp_host]" class="form-control" value="{{getConfig('smtp_host')}}">
                                </div> 
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Cổng gửi mail:</label>
                                    <input type="text" name="data[smtp_port]" class="form-control" value="{{getConfig('smtp_port')}}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                 <div class="form-group">
                                    <label class="control-label">Sử dụng Xác thực:</label>
                                    <select class="form-control" name="data[smtp_encryption]">
                                        <option value="">Không</option>
                                        <option value="SSL" @if(getConfig('smtp_encryption') == 'SSL') selected="" @endif>SSL</option>
                                        <option value="TLS" @if(getConfig('smtp_encryption') == 'TLS') selected="" @endif>TLS</option>
                                    </select>
                                </div>  
                            </div>
                        </div>
                        <hr/>
                        <h4>Tài khoản gửi mail</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Tài khoản gửi:</label>
                                    <input type="text" name="data[smtp_email]" class="form-control" value="{{getConfig('smtp_email')}}"> 
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Mật khẩu:</label>
                                    <input type="password" name="data[smtp_password]" class="form-control" value="{{getConfig('smtp_password')}}"> 
                                </div> 
                            </div>
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