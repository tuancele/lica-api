@extends('admin.layout')
@section('title','Cấu hình')
@section('content')
@include('admin.layout.breadcrumb',[
    'title' => 'Cấu hình',
])
<form role="form" id="tblForm" method="post" ajax="/admin/order/config">
        @csrf
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <h4 style="margin:0px;">Giao hàng tiết kiệm</h4>
            </div>
            <div class="col-md-2">
                
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        
        <table class="table table-bordered table-striped">
            <tr>
                <td width="40%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Tiêu đề</label></td>
                <td width="60%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="name" value="{{$valdata->name}}">
                            <input type="hidden" name="id" value="{{$giaohang->id}}">
                        </div>
                       
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Link </label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="url" value="{{$valdata->url}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Token API *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="token" value="{{$valdata->token}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Số gram kiện hàng</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="weight" value="{{$valdata->weight}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Trạng thái</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <select class="form-control" name="status">
                                <option value="1" @if($giaohang->status == 1) selected="" @endif>Kích hoạt</option>
                                <option value="0" @if($giaohang->status == 0) selected="" @endif>Ngừng hoạt động</option>
                            </select>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    
    </div>
</div>

<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <h4 style="margin:0px;">Cổng thanh toán Onepay</h4>
            </div>
            <div class="col-md-2">
                
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        
        <table class="table table-bordered table-striped">
            <tr>
                <td width="40%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Tên cổng thanh toán</label></td>
                <td width="60%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="name_onepay" value="{{$onepay->name_onepay}}">
                            <input type="hidden" name="idonepay" value="{{$row_onepay->id}}">
                        </div>
                       
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Merchant ID </label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="merchant_id" value="{{$onepay->merchant_id}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Merchant AccessCode</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="acccesscode" value="{{$onepay->acccesscode}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Secure secret</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="secure_secret" value="{{$onepay->secure_secret}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">VPC Version</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="vpc_version" value="{{$onepay->vpc_version}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Command Type</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="command_type" value="{{$onepay->command_type}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Payment Server Display Language Locale</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="vpc_locale" value="{{$onepay->vpc_locale}}"> 
                        </div>
                    </div>
                </td>
            </tr>
             <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Virtual Payment Client URL</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="vpcURL" value="{{$onepay->vpcURL}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">URL vấn tin QueryDR</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" name="queryDr" value="{{$onepay->queryDr}}"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Trạng thái</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-10">
                            <select class="form-control" name="status_onepay">
                                <option value="1" @if($row_onepay->status == 1) selected="" @endif>Kích hoạt</option>
                                <option value="0" @if($row_onepay->status == 0) selected="" @endif>Ngừng hoạt động</option>
                            </select>
                        </div>
                    </div>
                </td>
            </tr>
           
        </table>
        
    </div>
</div>
<button class="btn btn-primary" type="submit"><i class="fa fa-save" aria-hidden="true"></i> Lưu lại</button>
</section>

</form>
@endsection