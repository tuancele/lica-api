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
                @include('Config::sidebar',['active' => 'facebook'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Facebook Api:</label>
                            <input type="text" name="data[facebook_api]" class="form-control" value="{{getConfig('facebook_api')}}">
                        </div>
                        <hr/>
                        <h4>Facebook Ads Conversions API</h4>
                        <div class="form-group">
                            <label class="control-label">Cho phép hoạt động:</label>
                            <p>
                            <label><input class="minimal" type="radio" name="data[facebook_status]" value="1" @if(getConfig('facebook_status')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;"><input class="minimal" type="radio" name="data[facebook_status]" value="0" @if(getConfig('facebook_status')==0) checked="" @endif> &nbsp;&nbsp;Không</label>
                            </p>
                        </div>    
                        <div class="form-group">
                            <label class="control-label">Access Token:</label>
                            <textarea class="form-control" rows="4" name="data[facebook_access_token]">{{getConfig('facebook_access_token')}}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Pixel Id:</label>
                            <input type="text" name="data[facebook_pixel_id]" class="form-control" value="{{getConfig('facebook_pixel_id')}}">
                        </div>
                    </div>
                </div>
                <hr/>
                
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