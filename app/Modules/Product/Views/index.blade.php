@extends('Layout::layout')
@section('title','Danh sách sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách sản phẩm',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <form method="get" action="{{route('product')}}"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $catid = request()->get('cat_id'); ?>
                        <select class="form-control" name="cat_id">
                            <option value=""  @if($catid == "") selected="" @endif>---Chọn danh mục---</option>
                            {!! menuMulti($categories,0,'',$catid) !!}
                        </select>
                    </div>
                    <div class="col-md-2 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="{{route('product.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
                <button class="button add btn btn-success pull-right _update"  style="margin-right:5px;"><i class="fa fa-refresh" aria-hidden="true"></i> Cập nhật</button>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('product.delete')}}" action-url="{{route('product.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="7%">Hình ảnh</th>
                            <th width="20%">Tiêu đề</th>
                            <th width="15%">Danh mục</th>
                            <th width="10%">Biến thể</th>
                            <th width="10%">Ngày tạo</th>
                            <th width="10%">Thứ tự</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($products) && !empty($products))
                        @foreach($products as $product)
                        @php $cats = json_decode($product->cat_id);$variant = $product->variant($product->id);@endphp
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$product->id}}"></td>
                            <td>
                               <img class="img-responsive" src="{{getImage($product->image)}}">
                            </td>
                            <td>
                                <a href="{{asset($product->slug)}}" target="_blank">{{$product->name}}</a>
                                @if(!empty($variant))
                                <p style="color:red;font-weight: 600;">@if($variant->price == 0) Liên hệ @else {{number_format($variant->price)}}đ @endif</p>
                                @endif
                            </td>
                            <td>
                                @if(isset($cats) && !empty($cats))
                                @foreach($cats as $key => $cat)
                                    @php $getCat = App\Modules\Product\Models\Product::select('id','name')->where('id',$cat)->first(); @endphp
                                    @if(isset($getCat) && !empty($getCat))
                                        <a href="/admin/taxonomy/{{$getCat->id}}" target="_blank">{{$getCat->name}}</a>,
                                    @endif
                                @endforeach
                                @endif
                            </td>
                            <td>
                                <a href="{{route('product.variantnew',['id' => $product->id])}}">{{$product->variants->count()}}</a>
                            </td>
                            <td>
                               {{date('d-m-Y',strtotime($product->created_at))}}
                            </td>

                            <td> 
                                <input type="number" value="{{$product->sort}}" class="form-control" name="sort[{{$product->id}}]">
                            </td>
                            <td>
                                <select class="select_status form-control" data-id="{{$product->id}}" data-url="{{route('product.status')}}">
                                    <option value="1" @if($product->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($product->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                 <a class="btn btn-primary btn-xs" href="{{route('product.edit',['id'=>$product->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$product->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
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
                        <option value="2">Xóa </option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$products->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection