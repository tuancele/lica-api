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
                @include('Config::sidebar',['active' => 'verified'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Xác thực bởi:</label>
                            <input type="text" name="data[verified]" class="form-control" value="{{getConfig('verified')}}">
                        </div>  
                        <div class="form-group">
                            <label class="control-label">Ảnh xác thực:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="data[verified_content]" id="ImageUrl1" value="{{getConfig('verified_content')}}">
                                <span class="input-group-btn">
                                  <button class="btn btn-default btnImage" type="button" number="1"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
                                </span>
                            </div>
                            <div class="avantar1 showimage">@if(getConfig('verified_content') != "") <img src="{{getConfig('verified_content')}}"> @endif</div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Link xác thực:</label>
                            <input type="text" name="data[link_verified]" class="form-control" value="{{getConfig('link_verified')}}">
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