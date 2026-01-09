@extends('Layout::layout')
@section('title','Danh sách chương trình khuyến mại')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách chương trình khuyến mại',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8">
                <form class="form-inline">
                    <div class="form-group">
                        <select name="status" class="form-control">
                            <option value="">Trạng thái</option>
                            <option value="1" @if(request()->get('status') == 1) selected @endif>Đang chạy</option>
                            <option value="0" @if(request()->get('status') == 0 && request()->get('status') != "") selected @endif>Ngừng</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm..." value="{{request()->get('keyword')}}">
                    </div>
                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i> Tìm kiếm</button>
                </form>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="{{route('marketing.campaign.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Tạo chương trình mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('marketing.campaign.delete')}}" action-url="{{route('marketing.campaign.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="30%">Tên chương trình</th>
                            <th width="20%">Thời gian</th>
                            <th width="15%">Trạng thái</th>
                            <th width="15%">Người tạo</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $item)
                        <tr id="row-{{$item->id}}">
                            <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$item->id}}"></td>
                            <td><a href="{{route('marketing.campaign.edit',$item->id)}}">{{$item->name}}</a></td>
                            <td>
                                <p>Bắt đầu: {{$item->start_at}}</p>
                                <p>Kết thúc: {{$item->end_at}}</p>
                            </td>
                            <td>
                                @if($item->status == 1)
                                <span class="label label-success">Đang chạy</span>
                                @else
                                <span class="label label-danger">Ngừng</span>
                                @endif
                            </td>
                            <td>@if($item->user) {{$item->user->name}} @endif</td>
                            <td>
                                <a class="btn_delete btn btn-danger btn-xs pull-right" data-page="" data-id="{{$item->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                                <a class="btn btn-primary btn-xs pull-right" href="{{route('marketing.campaign.edit',$item->id)}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>
                        @endif
                    </tbody>
                </table>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-inline">
                            <div class="form-group">
                                <select name="action" class="form-control">
                                    <option value="">Hành động</option>
                                    <option value="1">Hiển thị</option>
                                    <option value="0">Ẩn</option>
                                    <option value="-1">Xóa</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-default">Áp dụng</button>
                        </div>
                    </div>
                    <div class="col-md-7 text-right">
                        {{$list->links()}}
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection
