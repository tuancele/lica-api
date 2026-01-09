@extends('Layout::layout')
@section('title','Danh sách mã khuyến mại')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách mã khuyến mại',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('promotion')}}"> 
                    <div class="col-md-5 pr-0">
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
                    <div class="col-md-5">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-3">
                <a class="button add btn btn-info pull-right" href="{{route('promotion.create')}}"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('promotion.delete')}}" action-url="{{route('promotion.action')}}" update-url="{{route('promotion.sort')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="30%">Tiêu đề</th>
                            <th width="10%">Giá trị giảm</th>
                            <th width="10%">Lượt sử dụng</th>
                            <th width="10%">Bắt đầu</th>
                            <th width="10%">Kết thúc</th>
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
                            <td>{{$value->name}}<p>Code: <strong>{{$value->code}}</strong></p></td>
                            <td>{{$value->value}} @if($value->unit == 0) % @else đ @endif</td>
                            <td>{{$value->order->count()}}/{{$value->number}}</td>
                            <td>{{date('d/m/Y',strtotime($value->start))}}</td>
                            <td>{{date('d/m/Y',strtotime($value->end))}}</td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="{{route('promotion.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Hoạt động</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ngừng</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('promotion.edit',['id' =>$value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
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