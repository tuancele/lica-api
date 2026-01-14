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
                @include('Config::sidebar',['active' => ''])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Tên trang:</label>
                            <input class="form-control" name="data[site_name]" value="{{getConfig('site_name')}}">
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Logo:</label>
                            @include('Layout::image-r2-input-group',['image' => getConfig('logo'),'number' => 1, 'name' => 'data[logo]', 'folder' => 'config'])
                        </div>
                        <div class="form-group">
                            <label class="control-label">Favicon: (32x32px)</label>  
                            @include('Layout::image-r2-input-group',['image' => getConfig('favicon'),'number' => 2, 'name' => 'data[favicon]', 'folder' => 'config'])
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Banner:</label>
                            @include('Layout::image-r2-input-group',['image' => getConfig('banner'),'number' => 3, 'name' => 'data[banner]', 'folder' => 'config'])
                        </div>
                        <div class="form-group">
                            <label class="control-label">Cho phép google đánh chỉ mục:</label>
                            <p><label><input class="minimal" type="radio" name="data[index]" value="1" @if(getConfig('index')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;"><input class="minimal" type="radio" name="data[index]" value="0" @if(getConfig('index')==0) checked="" @endif> &nbsp;&nbsp;Không</label></p>
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Code Header:</label>
                            <textarea class="form-control" name="data[code_header]" rows="6">{{getConfig('code_header')}}</textarea> 
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Code Body:</label>
                            <textarea class="form-control" name="data[code_body]" rows="6">{{getConfig('code_body')}}</textarea> 
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
<style type="text/css">
    .showimage{
        margin-top: 10px;
        width: 200px;
    }
    .show_favicon{
        margin-top: 10px;
        width: 40px;
    }
</style>
@endsection