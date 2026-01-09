@extends('Layout::layout')
@section('title','Danh sách sản phẩm so sánh')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách sản phẩm so sánh',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('compare')}}"> 
                    <div class="col-md-3 pr-0">
                        <?php $store_id = request()->get('store_id'); ?>
                        <select class="form-control" name="store_id">
                            <option value=""  @if($store_id == "") selected="" @endif>---Website---</option>
                            @if($stores->count() > 0)
                            @foreach($stores as $store)
                            <option value="{{$store->id}}" @if($store_id == $store->id && $store_id != "") selected="" @endif>{{$store->name}}</option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
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
                <a class="button btn btn-primary pull-right" href="{{route('compare.crawl')}}"><i class="fa fa-download" aria-hidden="true"></i> Crawl dữ liệu</a>
                <a class="button add btn btn-info pull-right" href="{{route('compare.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('compare.delete')}}" action-url="{{route('compare.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="10%">Cửa hàng</th>
                            <th width="15%">Thương hiệu</th>
                            <th width="20%">Sản phẩm</th>
                            <th width="10%">Giá</th>
                            <th width="10%">Thời gian</th>
                            <th width="10%">Hiển thị Url</th>
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
                            <td>
                                {{$value->storename}}
                            </td>
                            <td>{{$value->brand}}</td>
                            <td>{{$value->name}}</td>
                            <td>{{number_format($value->price)}}</td>
                            <td>{{$value->updated_at}}</td>
                            <td>
                                <select class="select_url form-control" data-id="{{$value->id}}" data-url="{{route('compare.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="{{route('compare.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('compare.edit',['id' =>$value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
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
                        <option value="3">Hiển thị link gốc</option>
                        <option value="4">Ẩn link gốc</option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right" style="display:flex;align-items:center;justify-content: end;">
                    <span style="margin-right:20px">Tổng: <strong>{{$list->total()}}</strong></span>
                    {{$list->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection