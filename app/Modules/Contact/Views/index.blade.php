@extends('Layout::layout')
@section('title','Liên hệ')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Liên hệ',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-12"> 
                <div class="row">  
                     <form method="get" action="/admin/contact"> 
                    <div class="col-md-9">
                   <div class="row">
                    <div class="col-md-5 pr-0">
                        <input type="text" name="code" value="{{ request()->get('code') }}" class="large_input float_left form-control" placeholder="Mã đơn hàng">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Mới</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Đã đọc</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default pull-left" type="submit">Tìm kiếm</button>
                    </div>
                </div>
                </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/contact/delete" action-url="/admin/contact/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="20%" colspan="2">Họ tên</th>    
                            <th width="10%">Điện thoại</th>  
                            <th width="15%">Email</th>    
                            <th width="15%">Ngày gửi</th>
                            <th width="10%">Trạng thái</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($contacts) && !empty($contacts))
                        @foreach($contacts as $contact)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$contact->id}}"></td>
                            <td>
                                @if($contact->status == '0')
                                    <i class="fa fa-envelope text-yellow" aria-hidden="true"></i>
                                @else
                                    <i class="fa fa-envelope-open-o text-yellow" aria-hidden="true"></i>
                                @endif
                            </td>
                            <td>
                                {{$contact->name}}
                            </td>
                            <td>{{$contact->phone}}</td>
                            <td>{{$contact->email}}</td>
                            <td>
                                {{date('H:i:s d-m-Y',strtotime($contact->created_at))}}
                            </td>
                            
                            <td>

                               @if($contact->status == 0)
                                    <span class="badge bg-yellow">Mới</span>
                               @elseif($contact->status == 1)
                                    <span class="badge bg-green">Đã đọc</span>
                               @endif
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="/admin/contact/view/{{$contact->id}}" style="margin-right:3px"><i class="fa fa-eye" aria-hidden="true"></i> Xem</a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$contact->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
             <div class="row">
                <div class="col-md-4">
                    <select class="form-control" name="action" style="width:50%;float:left;margin-right:5px;">
                        <option value="">---Chọn thao tác---</option>
                        <option value="0">Đánh dấu chưa đọc</option>
                        <option value="1">Đánh dấu đã đọc</option>
                        <option value="2">Xóa </option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$contacts->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection