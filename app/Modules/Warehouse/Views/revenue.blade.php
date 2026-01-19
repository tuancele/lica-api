@extends('Layout::layout')
@section('title','Thống kê doanh thu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thống kê doanh thu',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <div class="row">  
                    <form method="get" action="/admin/warehouse"> 
                    <div class="col-md-6 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="30%" colspan="2">Sản phẩm</th>
                            <th width="20%">Phân loại</th>  
                            <th width="10%">Tổng tiền nhập (đ)</th> 
                            <th width="10%">Tổng tiền xuất (đ)</th>   
                            <th width="10%">Lợi nhuận (đ)</th>  
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list) && !empty($list))
                        @foreach($list as $value)
                        <tr>
                            <td><img src="{{getImage($value->image)}}" style="width:50px"></td>
                            <td>
                               <a href="/admin/product/edit/{{$value->id}}" target="_blank"> {{$value->name}} </a>
                               <p>Sku: <strong>{{$value->sku}}</strong></p>
                            </td>
                            <td>
                               {{$value->option1_value ?? 'Mặc định'}}
                            </td>
                            <td>
                                {{number_format(countPrice($value->id,'import'))}}
                            </td>
                            <td>
                                {{number_format(countPrice($value->id,'export'))}}
                            </td>
                            <td>
                                @php    $total1 = countPrice($value->id,'export');
                                    $total2 = countPrice($value->id,'import');@endphp
                                {{number_format($total1-$total2)}}
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-4">
                    
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