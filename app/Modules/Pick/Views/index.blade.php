@extends('Layout::layout')
@section('title','Địa chỉ lấy hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Địa chỉ lấy hàng',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('pick')}}"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-5">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-3">
                <button type="button" class="button add btn btn-success pull-right _update" style="margin-left:5px"><i class="fa fa-refresh" aria-hidden="true"></i> Cập nhật</button>
                <a class="button add btn btn-info pull-right" href="{{route('pick.create')}}"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('pick.delete')}}" action-url="{{route('pick.action')}}" update-url="{{route('pick.sort')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="40%">Tên kho hàng</th>
                            <th width="10%">Ngày tạo</th>
                            <th width="10%">Người tạo</th>
                            <th width="10%">Thứ tự</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $value)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$value->id}}">
                            </td>
                            <td><strong>{{$value->name}}</strong>
                                <p>{{$value->address}}, {{$value->ward->name??''}}, {{$value->district->name??''}}, {{$value->province->name??''}}</p>
                            </td>
                            <td>{{date('d/m/Y',strtotime($value->created_at))}}</td>
                            <td>@if(isset($value->user)){{$value->user->name}}@endif</td>
                            <td><input type="number" class="form-control" value="{{$value->sort}}" name="sort[{{$value->id}}]"></td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="{{route('pick.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('pick.edit',['id' =>$value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$value->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
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
                        <option value="0">Ẩn </option>
                        <option value="1">Hiển thị </option>
                        <option value="2">Xóa</option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$list->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection