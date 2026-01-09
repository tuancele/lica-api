@extends('Layout::layout')
@section('title','Cấu hình R2 Storage')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cấu hình R2 Storage',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/config/update">
        @csrf
        <div class="row">
            <div class="col-lg-3">
                @include('Config::sidebar',['active' => 'r2'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                         <div class="form-group">
                            <label class="control-label">R2 Account ID:</label>
                            <input class="form-control" name="data[r2_account_id]" value="{{getConfig('r2_account_id')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">R2 Access Key ID:</label>
                            <input class="form-control" name="data[r2_access_key_id]" value="{{getConfig('r2_access_key_id')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">R2 Secret Access Key:</label>
                            <input class="form-control" type="password" name="data[r2_secret_access_key]" value="{{getConfig('r2_secret_access_key')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Bucket Name:</label>
                            <input class="form-control" name="data[r2_bucket_name]" value="{{getConfig('r2_bucket_name')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Public Domain (Custom Domain):</label>
                            <input class="form-control" name="data[r2_public_domain]" value="{{getConfig('r2_public_domain')}}">
                            <p class="help-block">Ví dụ: https://cdn.example.com</p>
                        </div>
                        
                        <hr>
                        <h4>Công cụ đồng bộ</h4>
                         <div class="form-group">
                            <p>Sử dụng công cụ này để upload toàn bộ ảnh từ local lên R2.</p>
                            <a href="/admin/config/tool-sync-r2" class="btn btn-primary"><i class="fa fa-cloud-upload"></i> Truy cập công cụ Upload Media</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu cài đặt</button>
            </div>
        </div>
    </form>
</section>
@endsection