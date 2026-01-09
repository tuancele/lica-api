@extends('Layout::layout')
@section('title','Chỉnh sửa đơn hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Chỉnh sửa đơn hàng',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/import-goods/edit">
        @csrf
        <div class="panel panel-default">
            <div class="panel-body">
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>
                            <label class="control-label">Mã đơn hàng *: </label>
                            <input type="text" name="code" class="form-control" value="{{$detail->code}}" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
                            <input name="id" type="hidden" value="{{$detail->id}}">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="control-label">Nội dung nhập:</label>
                            <input type="text" name="subject" class="form-control" value="{{$detail->subject}}" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label class="control-label">Ghi chú:</label>
                            <textarea class="form-control" name="content" rows="4">{{$detail->content}}</textarea>
                        </td>
                    </tr>
                </table>
                <h4>Sản phẩm nhập</h4>
                <table class="table table-bordered table-striped" id="listProduct" number="{{$list->count()}}">
                    <tr>
                    <th width="30%"><label class="control-label">Mã sản phẩm</label></th>
                        <th width="15%"><label class="control-label">Màu sắc</label></th>
                        <th width="15%"><label class="control-label">Size</label></th>
                        <th width="20%"><label class="control-label">Giá nhập (đ)</label></th>
                        <th width="20%"><label class="control-label">Số lượng</label></th>
                    </tr>
                    @if($list->count() > 0)
                    @foreach($list as $key => $value)
                    <tr class="item-{{$key+1}}" item="{{$key+1}}">
                        <td>
                            <select class="form-control select_product select" name="product_id[]" required="">
                                <option value="0">Không</option>
                                @if($products->count() > 0)
                                @foreach($products as $variant)
                                <option value="{{$variant->id}}" @if($variant->id == $value->variant_id) selected="" @endif>{{$variant->sku}} - {{$variant->product->name??''}}</option>
                                @endforeach
                                @endif
                            </select>
                        </td>
                        <td>
                            <select class="form-control select_color" name="color_id[]" >
                                <option value="{{$value->variant->color_id}}" selected>{{$value->variant->color->name}}</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-control select_size" name="size_id[]">
                            <option value="{{$value->variant->size_id}}" selected>{{$value->variant->size->name}}{{$value->variant->size->unit}}</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" name="price[]" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống" value="{{$value->price}}">
                        </td>
                        <td>
                            
                            <input type="number" name="qty[]" class="form-control" value="{{$value->qty}}" min="1" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
                        </td>
                        <td>
                            <a href="javascript:;" class="btnDelete" style="color:red"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </table>
                <div class="form-group">
                    <button class="btn btn-info pull-right" type="button" id="btnAddProduct"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                    <a href="/admin/import-goods" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
                </div>
            </div>
        </div>
    </form>
</section>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>$('body .select').select2();</script>
<style type="text/css">
    .form-group{
        overflow: hidden;
    }
    select.select{
        height:34px;
        background-image: linear-gradient(#fff, #f1f1f1);
        border-radius:initial !important;
    }
    .select2-container{
        height:34px;
        background-image: linear-gradient(#fff, #f1f1f1);
        border-radius:initial !important;
    }
    .select2-container--default .select2-selection--single{
        height:34px;
        background-image: linear-gradient(#fff, #f1f1f1);
        border-radius:initial !important;
        border-color: #d2d6de;
    }
</style>
<script>
    $('#btnAddProduct').click(function(){
        var html = '';
        var id = $('#listProduct').attr('number');
        var next = parseInt(id) + 1;
        $('#listProduct').attr('number',next);
        $.ajax({
            type: 'get',
            url: '/admin/import-goods/loadAdd',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#listProduct').append('<tr class="item-'+next+'" item="'+next+'">'+res+'</tr>');
            }
        })
    });
</script>
<script type="text/javascript">
    $('#listProduct').on('change','.select_product',function(){
        var item = $(this).parent().parent().attr('item');
        var id = $(this).val();
        $(".item-"+item+" .select_size").load("/admin/import-goods/size/"+id);
        $(".item-"+item+" .select_color").load("/admin/import-goods/color/"+id);
    }); 
    $('#listProduct').on('click','.btnDelete',function(){
        var item = $(this).parent().parent().attr('item');
        $(".item-"+item+"").remove();
    });
</script>
@endsection