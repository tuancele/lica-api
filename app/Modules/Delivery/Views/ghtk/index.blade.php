@extends('Layout::layout')
@section('title','Đơn hàng GHTK')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Đơn hàng GHTK',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <form method="get" action="{{route('ghtk')}}"> 
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
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('ghtk.cancel')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="10%">Mã đơn</th>  
                            <th width="10%">Mã vận đơn</th>
                            <th width="10%">Khách hàng</th>     
                            <th width="30%">Địa chỉ</th>
                            <th width="10%">Phí vận chuyển</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $ship)
                        @php 
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => getConfig('ghtk_url')."/services/shipment/v2/".$ship->label_id,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_HTTPHEADER => array(
                                    "Token: ".getConfig('ghtk_token'),
                                ),
                            ));
                            $response = curl_exec($curl);
                            curl_close($curl);
                            $result = json_decode($response);
                            if($result->success){
                                $name = $result->order->customer_fullname;
                                $address = $result->order->address;
                                $ship_money = $result->order->ship_money;
                                $status = $result->order->status_text;
                            }else{
                                $name = "";
                                $address = "";
                                $ship_money = "0";
                                $status = "";
                            }
                        @endphp
                        <tr>
                            <td><a  href="/admin/order/view/{{$ship->code}}" target="_blank">{{$ship->code}}</a></td>
                            <td><a href="#">{{$ship->label_id}}</a></td>
                            <td>{{$name}}</td>
                            <td>{{$address}}</td>
                            <td>
                                {{number_format($ship_money)}}
                            </td>
                            <td>
                              {{$status}}
                            </td>
                            <td>
                                <a type="button" class="btn btn-primary btn-xs" style="margin-bottom:3px;" href="{{route('ghtk.print',['id' =>$ship->label_id])}}" target="_blank">In hóa đơn GHTK</a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$ship->label_id}}"> Hủy đơn</a>
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