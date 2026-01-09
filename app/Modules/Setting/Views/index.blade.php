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
                @include('Setting::sidebar',['active' => ''])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Logo:</label>
                            <input type="text" name="data[logo]" class="form-control" value="{{getSetting('logo')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Favicon:</label>
                            <input type="text" name="data[favicon]" class="form-control" value="{{getSetting('favicon')}}">   
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Cho phép google đánh chỉ mục:</label>
                            <p><label><input class="minimal" type="radio" name="data[index]" value="1" @if(getSetting('index')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;"><input class="minimal" type="radio" name="data[index]" value="0" @if(getSetting('index')==0) checked="" @endif> &nbsp;&nbsp;Không</label></p>
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Code Header:</label>
                            <textarea class="form-control" name="data[code_header]" rows="6">{{getSetting('code_header')}}</textarea> 
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Code Body:</label>
                            <textarea class="form-control" name="data[code_body]" rows="6">{{getSetting('code_body')}}</textarea> 
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