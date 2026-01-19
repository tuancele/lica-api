@extends('admin.layout')
@section('title','Sản phẩm đã xuất')
@section('content')
@include('admin.layout.breadcrumb',[
    'title' => 'Sản phẩm đã xuất',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <div class="row">  
                    <form method="get" action="/admin/import-goods/product"> 
                    <div class="col-md-6 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-2">
                <a class="button add btn btn-success pull-right" href="/admin/import-goods/product/export"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Export</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/import-goods/product/delete" action-url="/admin/import-goods/product/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="20%">Sản phẩm</th>
                            <th width="10%">Phân loại</th>
                            <th width="10%">Đơn giá ($)</th> 
                            <th width="10%">Số lượng </th>  
                            <th width="10%">Thành tiền ($)</th>    
                            <th width="10%">Ngày xuất</th>
                            
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list) && !empty($list))
                        @foreach($list as $value)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$value->id}}"></td>
                            <td>
                               <a href=""> {{$value->variant->product->name ?? ''}} </a>
                               <p>Sku: <strong>{{$value->variant->sku ?? ''}}</strong></p>
                            </td>
                            <td>
                               {{$value->variant->option1_value ?? 'Mặc định'}}
                            </td>
                            <td>
                                ${{$value->price}}
                            </td>
                            <td>{{$value->qty}}</td>
                            <td>${{$value->qty * $value->price}}</td>
                            <td>
                               {{date('d-m-Y',strtotime($value->created_at))}}
                            </td>
                            <td>
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
                        <option value="2">Xóa </option>
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