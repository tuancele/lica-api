@extends('Layout::layout')
@section('title','Danh sách Showroom')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách Showroom',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="/admin/showroom"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                     <div class="col-md-2 pr-0">
                        <?php $cat_id = request()->get('cat_id'); ?>
                        <select class="form-control" name="cat_id">
                            <option value=""  @if($cat_id == "") selected="" @endif>---Khu vực---</option>
                            @if($categories->count() > 0)
                            @foreach($categories as $category)
                            <option value="{{$category->id}}" @if($cat_id == $category->id && $cat_id != "") selected="" @endif>{{$category->name}}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-2 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-3">
                <a class="button add btn btn-info pull-right" href="/admin/showroom/create"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
                <button class="button add btn btn-success _update pull-right" type="button" style="margin-right:5px"><i class="fa fa-refresh" aria-hidden="true"></i> Cập nhật</button>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/showroom/delete" action-url="/admin/showroom/action" update-url="/admin/showroom/sort">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="10%">Tên</th>
                            <th width="25%">Địa chỉ</th>
                            <th width="10%">Điện thoại</th>
                            <th width="10%">Thứ tự</th>
                            <th width="10%">Ngày tạo</th>
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
                            <td>{{$value->name}}</td>
                            <td>{{$value->address}}</td>
                            <td>{{$value->phone}}</td>
                            <td><input type="number" name="sort[{{$value->id}}]" value="{{$value->sort}}" class="form-control"></td>
                            <td>{{date('d/m/Y',strtotime($value->created_at))}}</td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="/admin/showroom/status">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="/admin/showroom/edit/{{$value->id}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$value->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
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