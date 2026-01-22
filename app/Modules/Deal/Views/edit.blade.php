@extends('Layout::layout')
@section('title','Sửa chương trình deal sốc')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa chương trình deal sốc',
])
<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript">
    $(function(){
        $('body .price').number( true, 0);
    });
</script>
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('deal.update')}}">
        @csrf
        <input type="hidden" name="id" value="{{$detail->id}}">
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tiêu đề : </label>
                            <input type="text" name="name" class="form-control" value="{{$detail->name}}" required="">
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Từ ngày: </label>
                                    <input type="datetime-local" name="start" value="{{date('Y-m-d H:i:s',$detail->start)}}" class="form-control" required="">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Đến ngày: </label>
                                    <input type="datetime-local" name="end" value="{{date('Y-m-d H:i:s',$detail->end)}}" class="form-control" required=""> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Giới hạn sản phẩm mua kèm: </label>
                                    <input type="number" name="limited" value="{{$detail->limited}}" class="form-control" required=""> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái </label>
                                    <select name="status" class="form-control">
                                        <option value="1" @if($detail->status==1) selected @endif>Kích hoạt</option>
                                        <option value="0" @if($detail->status==0) selected @endif>Ngừng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm chính</h4>
                                    <button type="button" class="pull-right button add btn btn-info" data-toggle="modal" data-target="#myModal"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product">
                            <div style="background-color: #f9f9f9;padding:15px;border-radius: 5px;margin-bottom: 15px;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h5>Chỉnh sửa hàng loạt</h5>
                                        <p>Đã chọn <strong class="count_choose">0</strong> sản phẩm</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Thao tác</label>
                                        <div>
                                            <button type="button" class="btn btn-default pull-left" id="deleteAll">Xóa hàng loạt</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="updateSale">
                            <table class="table table-bordered table-striped box-body">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall2" class="wgr-checkbox"></th>
                                        <th width="40%">Sản phẩm</th>
                                        <th width="10%">Giá gốc</th>
                                        <th width="10%">Giá khuyến mại</th>
                                    <th width="8%">Số lượng</th>
                                    <th width="8%">Đăng ký</th>
                                    <th width="8%">Đã bán</th>
                                    <th width="8%">Còn lại</th>
                                    <th width="10%">Hiệu suất</th>
                                    <th width="8%">Trạng thái</th>
                                    <th width="8%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($productdeals->count() > 0)
                                    @foreach($productdeals as $productdeal)
                                    @php 
                                        $product = $productdeal->product;
                                        $selectedVariant = $productdeal->variant;
                                        $variant = $selectedVariant ?: $product->variant($product->id);
                                    @endphp
                                    <tr class="item-{{$product->id}}@if($productdeal->variant_id)-variant-{{$productdeal->variant_id}}@endif">
                                        <input type="hidden" name="productid[]" value="{{$product->id}}@if($productdeal->variant_id)_v{{$productdeal->variant_id}}@endif">
                                        <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}"></td>
                                        <td>
                                            <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                            <p><strong>{{$product->name}}</strong></p>
                                            @if($selectedVariant)
                                            <small class="text-muted">
                                                Phân loại: {{$selectedVariant->option1_value ?? 'N/A'}}
                                                @if($selectedVariant->sku) <br>SKU: {{$selectedVariant->sku}} @endif
                                            </small>
                                            @endif
                                        </td>
                                        <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
                                        @endif</td>
                                        <td>
                                            @if(!empty($variant)){{number_format($variant->sale)}}đ 
                                            @endif
                                        </td>
                                        <td>@php 
                                            $total1 = countProductWarehouse($product->id,'import');
                                            $total2 = countProductWarehouse($product->id,'export'); @endphp
                                            {{$total1-$total2}}
                                        </td>
                                        <td><input type="checkbox" name="statusdeal[{{$product->id}}]" class="wgr-checkbox" value="1" @if($productdeal->status)checked="" @endif></td>
                                        <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}@if($productdeal->variant_id)_v{{$productdeal->variant_id}}@endif"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                            </div>
                            <script>
                                $('#checkall2').click(function(){
                                    if (this.checked) { 
                                        $('.checkbox2').each(function () { 
                                            this.checked = true; 
                                        });
                                    } else {
                                        $('.checkbox2').each(function () { 
                                            this.checked = false;
                                        });
                                    }
                                    $('.count_choose').html($('input[name="checklist[]"]:checked').length);
                                });
                                $('.updateSale').on('click','input[name="checklist[]"]',function(){
                                    $('.count_choose').html($('input[name="checklist[]"]:checked').length);
                                });
                                $('.updateSale').on('click','.delete_item',function(){
                                    var id = $(this).attr('data-id');
                                    var mang = [];
                                    mang.push(id);
                                    $(this).parent().parent().remove();
                                    $.ajax({
                                        type: 'post',
                                        url: '/admin/deal/del-product',
                                        data:  {mang:mang},
                                        headers:
                                        {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function (res) {
                                        },error: function(xhr, status, error){
                                            alert('Có lỗi xảy ra, xin vui lòng thử lại');
                                        }
                                     })
                                })
                                $('#deleteAll').click(function(){
                                    var mang =[];
                                    $(".updateSale tr td").each(function () {
                                        if($(this).find("input").is(':checked')){
                                            $('.updateSale tr.item-'+$(this).find("input").val()+'').remove();
                                            mang.push($(this).find("input").val());
                                        }
                                    })
                                    $.ajax({
                                        type: 'post',
                                        url: '/admin/deal/del-product',
                                        data:  {mang:mang},
                                        headers:
                                        {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function (res) {
                                        },error: function(xhr, status, error){
                                            alert('Có lỗi xảy ra, xin vui lòng thử lại');
                                        }
                                     })
                                    $('.count_choose').html('0');
                                });
                            </script>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm mua kèm</h4>
                                    <button type="button" class="pull-right button add btn btn-info" data-toggle="modal" data-target="#myModal2"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product2">
                           <div style="background-color: #f9f9f9;padding:15px;border-radius: 5px;margin-bottom: 15px;">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5>Chỉnh sửa hàng loạt</h5>
                                    <p>Đã chọn <strong class="count_choose2">0</strong> sản phẩm</p>
                                </div>
                                <div class="col-md-2">
                                    <label>Khuyến mại (%)</label>
                                    <input type="number" name="sale" max="100" min="0" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label>Số lượng SP khuyến mại</label>
                                    <input type="number" name="number" max="100" min="0" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>Thao tác</label>
                                    <div>
                                        <button type="button" class="btn btn-default pull-left" id="updateAll" style="margin-right: 5px;">Cập nhật hàng loạt</button>
                                        <button type="button" class="btn btn-default pull-left" id="deleteAll">Xóa hàng loạt</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="updateSale2">
                        <table class="table table-bordered table-striped box-body">
                            <thead>
                                <tr>
                                    <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall3" class="wgr-checkbox"></th>
                                    <th width="35%">Sản phẩm</th>
                                    <th width="10%">Giá gốc</th>
                                    <th width="10%">Giá mua kèm</th>
                                    <th width="10%">Số lượng</th>
                                    <th width="10%">Kho hàng</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="10%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($saledeals->count() > 0)
                                @foreach($saledeals as $saledeal)
                                @php 
                                    $product2 = $saledeal->product;
                                    $selectedVariant = $saledeal->variant;
                                    $variant2 = $selectedVariant ?: $product2->variant($product2->id);
                                @endphp
                                <tr class="item-{{$product2->id}}@if($saledeal->variant_id)-variant-{{$saledeal->variant_id}}@endif">
                                    <input type="hidden" name="productsale[]" value="{{$product2->id}}@if($saledeal->variant_id)_v{{$saledeal->variant_id}}@endif">
                                    <td style="text-align: center;"><input type="checkbox" name="checklist2[]" class="checkbox3 wgr-checkbox" value="{{$product2->id}}"></td>
                                    <td>
                                        <img src="{{$product2->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                        <p><strong>{{$product2->name}}</strong></p>
                                        @if($selectedVariant)
                                        <small class="text-muted">
                                            Phân loại: {{$selectedVariant->option1_value ?? 'N/A'}}
                                            @if($selectedVariant->sku) <br>SKU: {{$selectedVariant->sku}} @endif
                                        </small>
                                        @endif
                                    </td>
                                    <td>@if(!empty($variant2))
                                        @php
                                            $priceProductName = $saledeal->variant_id 
                                                ? "price_product[{$product2->id}][{$saledeal->variant_id}]" 
                                                : "price_product[{$product2->id}]";
                                        @endphp
                                        {{number_format($variant2->price)}}đ 
                                        <input type="hidden" name="{{$priceProductName}}" value="{{$variant2->price}}">
                                    @endif</td>
                                    <td>
                                        @php
                                            $pricesaleName = $saledeal->variant_id 
                                                ? "pricesale[{$product2->id}][{$saledeal->variant_id}]" 
                                                : "pricesale[{$product2->id}]";
                                        @endphp
                                        <input type="text" name="{{$pricesaleName}}" value="{{number_format($saledeal->price)}}" class="form-control pricesale price">
                                        
                                    </td>
                                    <td>
                                        @php
                                            $numbersaleName = $saledeal->variant_id 
                                                ? "numbersale[{$product2->id}][{$saledeal->variant_id}]" 
                                                : "numbersale[{$product2->id}]";
                                        @endphp
                                        <input type="number" value="{{$saledeal->qty}}" name="{{$numbersaleName}}" class="form-control">
                                    </td>
                                    <td style="text-align: center;">
                                        <strong>{{number_format($saledeal->qty ?? 0)}}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        <strong class="text-success">{{number_format($saledeal->buy ?? 0)}}</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $remaining = ($saledeal->qty ?? 0) - ($saledeal->buy ?? 0);
                                            $badgeClass = $remaining > 10 ? 'label-success' : ($remaining > 0 ? 'label-warning' : 'label-danger');
                                        @endphp
                                        <span class="label {{$badgeClass}}">{{number_format($remaining)}}</span>
                                    </td>
                                    <td>
                                        @php
                                            $registered = (int) ($saledeal->qty ?? 0);
                                            $sold = (int) ($saledeal->buy ?? 0);
                                            $sales_percentage = $registered > 0 ? round(($sold / $registered) * 100, 1) : 0;
                                        @endphp
                                        <div class="progress" style="margin-bottom: 0; height: 20px;">
                                            <div class="progress-bar 
                                                @if($sales_percentage >= 80) progress-bar-success
                                                @elseif($sales_percentage >= 50) progress-bar-warning
                                                @else progress-bar-info
                                                @endif" 
                                                role="progressbar" 
                                                aria-valuenow="{{$sales_percentage}}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100" 
                                                style="width: {{min(100, $sales_percentage)}}%">
                                                <span style="font-size: 11px; color: #333; font-weight: bold;">{{$sales_percentage}}%</span>
                                            </div>
                                        </div>
                                        <small class="text-muted" style="font-size: 10px;">{{number_format($sold)}}/{{number_format($registered)}}</small>
                                    </td>
                                    <td>@php 
                                        $total3 = countProductWarehouse($product2->id,'import');
                                        $total4 = countProductWarehouse($product2->id,'export'); @endphp
                                        {{$total3-$total4}}
                                    </td>
                                    <td>
                                        @php
                                            $status2Name = $saledeal->variant_id 
                                                ? "status2[{$product2->id}][{$saledeal->variant_id}]" 
                                                : "status2[{$product2->id}]";
                                        @endphp
                                        <input type="checkbox" name="{{$status2Name}}" class="wgr-checkbox" value="1" @if($saledeal->status)checked="" @endif>
                                    </td>
                                    <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product2->id}}@if($saledeal->variant_id)_v{{$saledeal->variant_id}}@endif"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                        </div>
                        <script>
                            $('#checkall3').click(function(){
                                if (this.checked) { 
                                    $('.checkbox3').each(function () { 
                                        this.checked = true; 
                                    });
                                } else {
                                    $('.checkbox3').each(function () { 
                                        this.checked = false;
                                    });
                                }
                                $('.count_choose2').html($('input[name="checklist2[]"]:checked').length);
                            });
                            $('.updateSale2').on('click','input[name="checklist2[]"]',function(){
                                $('.count_choose2').html($('input[name="checklist2[]"]:checked').length);
                            });
                            $('.updateSale2').on('click','.delete_item',function(){
                                var id = $(this).attr('data-id');
                                var mang = [];
                                mang.push(id);
                                $(this).parent().parent().remove();
                                $.ajax({
                                    type: 'post',
                                    url: '/admin/deal/del-product2',
                                    data:  {mang:mang},
                                    headers:
                                    {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function (res) {
                                    },error: function(xhr, status, error){
                                        alert('Có lỗi xảy ra, xin vui lòng thử lại');
                                    }
                                 })
                            })
                            $('#deleteAll').click(function(){
                                var mang =[];
                                $(".updateSale2 tr td").each(function () {
                                    if($(this).find("input").is(':checked')){
                                        $('.updateSale2 tr.item-'+$(this).find("input").val()+'').remove();
                                        mang.push($(this).find("input").val());
                                    }
                                })
                                $.ajax({
                                    type: 'post',
                                    url: '/admin/deal/del-product2',
                                    data:  {mang:mang},
                                    headers:
                                    {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function (res) {
                                    },error: function(xhr, status, error){
                                        alert('Có lỗi xảy ra, xin vui lòng thử lại');
                                    }
                                 })
                                $('.count_choose').html('0');
                            });
                            $('#updateAll').click(function(){
                                var number = $('input[name="number"]').val();
                                var percent = $('input[name="sale"]').val(); 
                                $(".updateSale2 tr td").each(function () {
                                    if($(this).find("input").is(':checked')){
                                        var id = $(this).find("input").val();
                                        $('.updateSale2 tr.item-'+id+' input[type="number"]').val(number);
                                        var price = $('.updateSale2 tr.item-'+id+' input[name="price_product"]').val();
                                        var sale =  parseInt(price) - (parseInt(price/100)*parseInt(percent));
                                        console.log(sale);
                                        $('.updateSale2 tr.item-'+id+' input.pricesale').val(sale);
                                    }
                                })
                            });
                        </script>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('deal')])
        </div>
    </form>
</section>
<div class="modal fade box-body" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Chọn sản phẩm</h4>
        <input type="text" id="modalSearch" class="form-control" placeholder="Tìm kiếm sản phẩm..." style="margin-top: 10px;">
      </div>
      <form class="choseProduct" method="post" onsubmit="return false;">
        @csrf
      <div class="modal-body">
        <div style="padding-right: 17px;">
            <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"><input style="margin-right: 0px;" type="checkbox" id="checkall" class="wgr-checkbox"></th>
                        <th width="40%">Sản phẩm</th>
                        <th width="12%">Giá gốc</th>
                        <th width="12%">Giá khuyến mại</th>
                        <th width="12%" style="text-align: center;">Tồn kho thực tế</th>
                        <th width="12%" style="text-align: center;">Tồn kho khả dụng</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="scroll-table" style="height: 400px;overflow-y: scroll;">
            <table class="table table-bordered table-striped" id="productTable">
                <tbody id="product-list-body">
                    <!-- Ajax loaded -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary" style="height:32px">Xác nhận</button>
      </div>
        </form>
    </div>
  </div>
</div>
<div class="modal fade box-body" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel2">Chọn sản phẩm mua kèm</h4>
        <input type="text" id="modalSearch2" class="form-control" placeholder="Tìm kiếm sản phẩm..." style="margin-top: 10px;">
      </div>
      <form class="choseProduct2" method="post" onsubmit="return false;">
        @csrf
      <div class="modal-body">
        <div style="padding-right: 17px;">
            <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"><input style="margin-right: 0px;" type="checkbox" id="checkall2" class="wgr-checkbox"></th>
                        <th width="40%">Sản phẩm</th>
                        <th width="12%">Giá gốc</th>
                        <th width="12%">Giá khuyến mại</th>
                        <th width="12%" style="text-align: center;">Tồn kho thực tế</th>
                        <th width="12%" style="text-align: center;">Tồn kho khả dụng</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="scroll-table" style="height: 400px;overflow-y: scroll;">
            <table class="table table-bordered table-striped" id="productTable2">
                <tbody id="product-list-body2">
                    <!-- Ajax loaded -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary" style="height:32px">Xác nhận</button>
      </div>
        </form>
    </div>
  </div>
</div>
<script src="/public/js/marketing-product-search.js"></script>
<script>
   // Initialize Marketing Product Search for Deal Edit - Sản phẩm chính
   $(document).ready(function() {
       MarketingProductSearch.init({
           modalId: '#myModal',
           searchInputId: '#modalSearch',
           productListBodyId: '#product-list-body',
           searchRoute: '{{route("deal.search_product")}}?type=main&deal_id={{$detail->id}}',
           choseRoute: '{{route("deal.chose_product")}}',
           mainProductBodyId: '.updateSale tbody',
           appendToSelector: '.updateSale tbody',
           checkAllId: '#checkall'
       });
       
       // Initialize Marketing Product Search for Deal Edit - Sản phẩm mua kèm
       MarketingProductSearch.init({
           modalId: '#myModal2',
           searchInputId: '#modalSearch2',
           productListBodyId: '#product-list-body2',
           searchRoute: '{{route("deal.search_product")}}?type=sale&deal_id={{$detail->id}}',
           choseRoute: '{{route("deal.chose_product2")}}',
           mainProductBodyId: '.updateSale2 tbody',
           appendToSelector: '.updateSale2 tbody',
           checkAllId: '#checkall2'
       });
   });
</script>
@endsection