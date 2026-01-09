@extends('Layout::layout')
@section('title','Danh sách thành viên')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thành viên',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <div class="row">  
                    <form method="get" action="{{route('member')}}"> 
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-2 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hoạt động</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ngừng hoạt động</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-2">
                <a class="button add btn btn-info pull-right" href="{{route('member.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('member.delete')}}" action-url="{{route('member.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%" style="text-align: center;"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="15%">Họ tên</th>
                            <th width="15%">Email</th>         
                            <th width="15%">Tổng đơn hàng</th>
                            <th width="15%">Đơn gần nhất</th>
                            <th width="15%">Tổng</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $member)
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$member->id}}">
                            </td>
                            <td>
                               <a href="/admin/member/view/{{$member->id}}"> {{$member->first_name}} {{$member->last_name}}</a>
                            </td>
                            <td>{{$member->email}}</td>
                            <td>{{$member->order->count()}}</td>
                            <td>
                                @php $order = App\Modules\Order\Models\Order::select('id','code')->where('member_id',$member->id)->orderBy('created_at','desc')->first();@endphp
                                @if(isset($order) && !empty($order)) <a href="/admin/order/view/{{$order->code}}" target="_blank">#{{$order->code}}</a> @else --- @endif
                            </td>
                            <td>
                                @php $arr_income = $member->order->toArray(); 
                                    $total = array_sum(array_column($arr_income, 'total')) + array_sum(array_column($arr_income, 'fee_ship')) - array_sum(array_column($arr_income, 'sale'));
                                  @endphp
                                {{number_format($total)}}₫
                            </td>
                    
                            <td>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$member->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
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
                        <option value="2">Xóa khách hàng đã chọn</option>
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
<style type="text/css">.rate i{color:#fd9727;}</style>
@endsection