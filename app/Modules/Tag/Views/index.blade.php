@extends('Layout::layout')
@section('title','Từ khóa')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Từ khóa',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="/admin/tag"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                         <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                        @if(request()->get('keyword')!= null || request()->get('status') != null)
                        <a class="button btn btn-default" href="/admin/tag"><i class="fa fa-times" aria-hidden="true"></i> Bỏ tìm kiếm</a>
                        @endif
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="/admin/tag/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/tag/delete" action-url="/admin/tag/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="30%">Tiêu đề</th>         
                            <th width="15%">Ngày tạo</th>
                            <th width="15%">Người tạo</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($tags->count() > 0)
                        @foreach($tags as $tag)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$tag->id}}"></td>
                            <td>
                                <a href="{{asset('tu-khoa/'.$tag->slug)}}" target="_blank">{{$tag->name}}</a>
                            </td>
                            <td>{{$tag->created_at}}</td>
                            <td>
                                @if($tag->user)
                                    {{$tag->user->name}}
                                @endif
                            </td>
                            <td>
                                <select class="select_status form-control" data-id="{{$tag->id}}" data-url="/admin/tag/status">
                                    <option value="1" @if($tag->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($tag->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn_delete btn btn-danger btn-xs pull-right" data-page="{{ app('request')->input('page') }}" data-id="{{$tag->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                                <a class="btn btn-primary btn-xs pull-right" href="/admin/tag/edit/{{$tag->id}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
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
                        <option value="2">Xóa </option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$tags->links()}}
                </div>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection