@extends('Layout::layout')
@section('title','Block Footer')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Block Footer',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="/admin/footer-block"> 
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
                        <a class="button btn btn-default" href="/admin/footer-block"><i class="fa fa-times" aria-hidden="true"></i> Bỏ tìm kiếm</a>
                        @endif
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="/admin/footer-block/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/footer-block/delete" action-url="/admin/footer-block/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="25%">Tiêu đề</th>         
                            <th width="15%">Số tags</th>
                            <th width="15%">Số links</th>
                            <th width="10%">Thứ tự</th>
                            <th width="10%">Trạng thái</th>
                            <th width="12%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($blocks->count() > 0)
                        @foreach($blocks as $block)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$block->id}}"></td>
                            <td>
                                {{$block->title ?: 'Không có tiêu đề'}}
                            </td>
                            <td>{{count($block->tags)}}</td>
                            <td>{{count($block->links)}}</td>
                            <td>{{$block->sort}}</td>
                            <td>
                                <select class="select_status form-control" data-id="{{$block->id}}" data-url="/admin/footer-block/status">
                                    <option value="1" @if($block->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($block->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn_delete btn btn-danger btn-xs pull-right" data-page="{{ app('request')->input('page') }}" data-id="{{$block->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                                <a class="btn btn-primary btn-xs pull-right" href="/admin/footer-block/edit/{{$block->id}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="7" class="text-center">Chưa có dữ liệu</td>
                        </tr>
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
                    {{$blocks->links()}}
                </div>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection
