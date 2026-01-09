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
                @include('Setting::sidebar',['active' => 'company'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Tên công ty:</label>
                            <input type="text" name="data[company_name]" class="form-control" value="{{getSetting('company_name')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Địa chỉ:</label>
                            <input type="text" name="data[company_address]" class="form-control" value="{{getSetting('company_address')}}">   
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Điện thoại:</label>
                                    <input type="text" name="data[company_phone]" class="form-control" value="{{getSetting('company_phone')}}">   
                                </div> 
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Hotline:</label>
                                    <input type="text" name="data[company_hotline]" class="form-control" value="{{getSetting('company_hotline')}}">   
                                </div> 
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="control-label">Địa chỉ email:</label>
                            <input type="text" name="data[company_email]" class="form-control" value="{{getSetting('company_email')}}">
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Website:</label>
                            <input type="text" name="data[company_website]" class="form-control" value="{{getSetting('company_website')}}">     
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Source map:</label>
                            <textarea class="form-control" rows="5" name="data[company_map]">{{getSetting('company_map')}}</textarea>
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