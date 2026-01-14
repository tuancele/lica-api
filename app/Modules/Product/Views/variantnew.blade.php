@extends('Layout::layout')
@section('title','Tạo biến thể sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo biến thể sản phẩm',
])
<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript">
    $(function(){
        $('body .price').number( true, 0);
    });
</script>
<section class="content">
	<div class="row">
        <div class="col-lg-4">
            <div class="panel panel-default">
                <div class="panel-body" style="display: flex;">
                	<div class="box_image">
                		<img src="{{getImage($product->image)}}">
                	</div>
                	<div class="div_description">
                		<p class="mb-0"><strong>{{$product->name}}</strong></p>
                		<p class="mb-0 fs-12">{{$variants->count()}} chi tiết biến thể</p>
                		<p class="mb-0 fs-12"><a href="{{route('product.edit',['id' => $product->id])}}"><i class="fa fa-long-arrow-left" aria-hidden="true"></i> Quay về chi tiết sản phẩm</a></p>
                	</div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body">
                	<div class="div" style="overflow: hidden;">
                        <h5 class="mb-0 mt-0 cl-blue fs-15 pull-left">Danh sách biến thể</h5>
                        <a class="pull-right" href="{{route('product.variantnew',['id' => $product->id])}}">Thêm biến thể mới</a>
                    </div>
                	<hr class="mb-0 mt-10" />
                    <div class="list-variants">
                        @if($variants->count() > 0)
                        @foreach($variants as $variant)
                        <a class="item-variant" href="{{route('product.variant',['id' => $product->id,'code' => $variant->id])}}">
                            <div class="box_image">
                                <img src="{{getImage($variant->image)}}">
                            </div>
                            <div class="div_description">
                                <p class="mb-0"><strong>
                                    @if(!isset($variant->color) && !isset($variant->size)) Mặc định @else
                                    @if(isset($variant->color)){{$variant->color->name}} @else Mặc định @endif / @if(isset($variant->size)){{$variant->size->name}} @else Mặc định @endif @endif
                                </strong></p>
                                <p class="mb-0 fs-12">SKU: {{$variant->sku}}</p>
                            </div>
                        </a>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <form role="form" id="tblForm" method="post" ajax="{{route('product.createvariant')}}">
                @csrf
                <input type="hidden" name="product_id" value="{{$product->id}}">
            <div class="panel panel-default">
                <div class="panel-body">
                	<h5 class="mb-0 mt-0 cl-blue fs-15">Các thuộc tính</h5>
                	<hr class="mb-10 mt-10" />
                    <div class="row">
                        <div class="col-md-9">
                            <div class="form-group">
                                <label>Màu sắc</label>
                                <select class="form-control" name="color_id">
                                    <option value="22">Không</option>
                                    @if($colors->count() > 0)
                                    @foreach($colors as $color)
                                    @if($color->id != '22')
                                    <option value="{{$color->id}}">{{$color->name}}</option>
                                    @endif
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kích thước</label>
                                <select class="form-control" name="size_id">
                                    <option value="0">Không</option>
                                    @if($sizes->count() > 0)
                                    @foreach($sizes as $size)
                                    <option value="{{$size->id}}">{{$size->name}}{{$size->unit}}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            @include('Layout::image-r2',['number' => 1, 'folder' => 'variants'])
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-body">
                	<h5 class="mb-0 mt-0 cl-blue fs-15">Chi tiết biến thể</h5>
                	<hr class="mb-10 mt-10" />
                    <div class="row">
                        <div class="col-md-3">
                            <label>SKU</label>
                            <input type="text" name="sku" class="form-control" value="">
                        </div>
                        <div class="col-md-3">
                            <label>Giá bán</label>
                            <input type="text" name="price" class="form-control price" value="@if(!empty($first)){{number_format($first->price)}} @else  0 @endif">
                        </div>
                        <div class="col-md-3">
                            <label>Giá khuyến mại</label>
                            <input type="text" name="sale" class="form-control price" value="@if(!empty($first)){{number_format($first->sale)}}@else  0 @endif">
                        </div>
                        <div class="col-md-3">
                            <label>Trọng lượng (kg)</label>
                            <input type="text" name="weight" class="form-control" value="@if(!empty($first)){{$first->weight}}@endif">
                        </div>
                    </div>
                </div>
            </div>
             <button type="submit" class="btn btn-primary"><i class="fa fa-floppy-o" aria-hidden="true"></i> Tạo biến thể</button>
             </form>
        </div>
    </div>
</section>
<style type="text/css">
	.box_image{
		width: 30%;border: 1px dashed #ddd;margin-right: 10px;border-radius: 5px;overflow: hidden;height: 70px;text-align: center;
	}
	.box_image img{
		height: 100%;display: inline-block;
	}
    .mb-0{margin-bottom: 0px !important}.fs-12{font-size: 12px !important}.mt-0{margin-top: 0px !important}.mb-10{margin-bottom: 10px !important}.mt-10{margin-top: 10px !important}
    .cl-blue{color:#3c8dbc;}.fs-15{font-size: 15px !important}
    .item-variant{display: flex;border-bottom: 1px solid #eee;padding:10px;}
    .item-variant p{color:#333;}.item-variant .box_image{width: 20%;height: 50px}
    .item-variant.active{background-color: #3c8dbc;}.item-variant.active p{color:#fff;}
</style>
@endsection