@extends('Layout::layout')
@section('title','Thống kê số lượng bán ra')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thống kê số lượng bán ra',
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
                            <th width="10%">Tổng SL nhập</th> 
                            <th width="10%">Tổng SL xuất</th>  
                            <th width="10%">Tồn kho</th>    
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
                            @php
                                $stockDto = app(\App\Services\Inventory\Contracts\InventoryServiceInterface::class)->getStock((int) $value->id);
                                $importTotal = (int) ($stockDto->physicalStock ?? 0);
                                $exportTotal = (int) ($stockDto->reservedStock ?? 0);
                                $availableTotal = (int) ($stockDto->availableStock ?? max(0, $importTotal - $exportTotal));
                            @endphp
                            <td>
                                {{$importTotal}}
                            </td>
                            <td>
                                {{$exportTotal}}
                            </td>
                            <td>
                                {{$availableTotal}}
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