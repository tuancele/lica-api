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
                @include('Setting::sidebar',['active' => 'google'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default box-body">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Mã Google API:</label>
                            <input type="text" name="data[google_api]" class="form-control" value="{{getSetting('google_api')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Google API Client ID:</label>
                            <input type="text" name="data[google_api_client_id]" class="form-control" value="{{getSetting('google_api_client_id')}}">   
                        </div> 
                        <hr/>
                        <h4>reCAPTCHA</h4>
                        <div class="form-group">
                            <label class="control-label">SiteKey:</label>
                            <input type="text" name="data[recaptcha_site_key]" class="form-control" value="{{getSetting('recaptcha_site_key')}}">
                        </div> 
                        <div class="form-group">
                            <label class="control-label">SecretKey:</label>
                            <input type="text" name="data[recaptcha_secret_key]" class="form-control" value="{{getSetting('recaptcha_secret_key')}}">     
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Cho phép reCAPTCHA hoạt động:</label>
                            <p>
                            <label><input class="minimal" type="radio" name="data[recaptcha_status]" value="1" @if(getSetting('recaptcha_status')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;"><input class="minimal" type="radio" name="data[recaptcha_status]" value="0" @if(getSetting('recaptcha_status')==0) checked="" @endif> &nbsp;&nbsp;Không</label>
                            </p>
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
<link href="/public/admin/plugins/iCheck/all.css" rel="stylesheet" type="text/css" />
<script src="/public/admin/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $('input[type="radio"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass: 'iradio_minimal-blue'
    });
</script>
@endsection