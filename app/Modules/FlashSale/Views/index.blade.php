@extends('Layout::layout')
@section('title','Danh sách chương trình')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách chương trình',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('flashsale')}}"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-2 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Kích hoạt</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ngừng</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-3">
                <a class="button add btn btn-info pull-right" href="{{route('flashsale.create')}}"><i class="fa fa-plus" aria-hidden="true"></i> Tạo mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('flashsale.delete')}}" action-url="{{route('flashsale.action')}}" update-url="{{route('flashsale.sort')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="15%">Bắt đầu</th>
                            <th width="15%">Kết thúc</th>
                            <th width="15%">Sản phẩm</th>
                            <th width="15%">Lượt mua</th>
                            <th width="15%">Trạng thái</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $value)
                        @php $date = strtotime(date('Y-m-d H:i:s')) @endphp
                        <tr @if($date > $value->end) style="background-color:pink" @elseif($date <= $value->end && $date >= $value->start && $value->status==1) style="background-color:#c5ffe4" @else @endif>
                            <td style="text-align: center;">
                                <input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$value->id}}">
                            </td>
                            <td>{{date('H:i:s d/m/Y',$value->start)}}</td>
                            <td>{{date('H:i:s d/m/Y',$value->end)}}</td>
                            <td>{{$value->products->count()}}</td>
                            <td>{{$value->products->sum('buy')}}/{{$value->products->sum('number')}}</td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="{{route('flashsale.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Kích hoạt</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ngừng</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('flashsale.edit',['id' =>$value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
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