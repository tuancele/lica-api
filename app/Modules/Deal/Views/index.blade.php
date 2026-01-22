@extends('Layout::layout')
@section('title','Danh sách deal sốc')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách deal sốc',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('deal')}}"> 
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
                <a class="button add btn btn-info pull-right" href="{{route('deal.create')}}"><i class="fa fa-plus" aria-hidden="true"></i> Tạo mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('deal.delete')}}" action-url="{{route('deal.action')}}" update-url="{{route('deal.sort')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="15%">Tiêu đề</th>
                            <th width="10%">Bắt đầu</th>
                            <th width="10%">Kết thúc</th>
                            <th width="8%">Sản phẩm chính</th>
                            <th width="8%">Sản phẩm kèm</th>
                            <th width="8%">Tổng hàng</th>
                            <th width="8%">Đã bán</th>
                            <th width="12%">Tỷ lệ</th>
                            <th width="8%">Trạng thái</th>
                            <th width="8%">Thao tác</th>
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
                            <td>{{$value->name}}</td>
                            <td>{{date('H:i:s d/m/Y',$value->start)}}</td>
                            <td>{{date('H:i:s d/m/Y',$value->end)}}</td>
                            <td style="text-align: center;"><strong>{{$value->products->count()}}</strong></td>
                            <td style="text-align: center;"><strong>{{$value->sales->count()}}</strong></td>
                            <td style="text-align: center;"><strong>{{number_format($value->total_qty ?? 0)}}</strong></td>
                            <td style="text-align: center;">
                                <strong class="text-success">{{number_format($value->total_buy ?? 0)}}</strong>
                                @if(($value->total_remaining ?? 0) > 0)
                                    <br><small class="text-muted">Còn: {{number_format($value->total_remaining)}}</small>
                                @endif
                            </td>
                            <td>
                                <div class="progress" style="margin-bottom: 5px;">
                                    <div class="progress-bar 
                                        @if(($value->sales_percentage ?? 0) >= 80) progress-bar-success
                                        @elseif(($value->sales_percentage ?? 0) >= 50) progress-bar-warning
                                        @else progress-bar-info
                                        @endif" 
                                        role="progressbar" 
                                        aria-valuenow="{{$value->sales_percentage ?? 0}}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100" 
                                        style="width: {{min(100, $value->sales_percentage ?? 0)}}%">
                                        <span style="color: #333; font-weight: bold;">{{$value->sales_percentage ?? 0}}%</span>
                                    </div>
                                </div>
                                <small class="text-muted">{{number_format($value->total_buy ?? 0)}}/{{number_format($value->total_qty ?? 0)}}</small>
                            </td>
                            <td>
                                <select class="select_status form-control" data-id="{{$value->id}}" data-url="{{route('deal.status')}}">
                                    <option value="1" @if($value->status == 1) selected="selected" @endif> Kích hoạt</option>
                                    <option value="0" @if($value->status == 0) selected="selected" @endif> Ngừng</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-info btn-xs" href="{{route('deal.view',['id' =>$value->id])}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                <a class="btn btn-primary btn-xs" href="{{route('deal.edit',['id' =>$value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
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