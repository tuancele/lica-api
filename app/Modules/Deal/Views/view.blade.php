@extends('Layout::layout')
@section('title','Chi tiết chương trình deal sốc')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Chi tiết chương trình deal sốc',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="#">
        @csrf
        <input type="hidden" name="id" value="{{$detail->id}}">
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tiêu đề : </label>
                            <input type="text" name="name" class="form-control" value="{{$detail->name}}" required="" disabled>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Từ ngày: </label>
                                    <input type="datetime-local" name="start" value="{{date('Y-m-d H:i:s',$detail->start)}}" class="form-control" required="" disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Đến ngày: </label>
                                    <input type="datetime-local" name="end" value="{{date('Y-m-d H:i:s',$detail->end)}}" class="form-control" required="" disabled> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Giới hạn sản phẩm mua kèm: </label>
                                    <input type="number" name="limited" value="{{$detail->limited}}" class="form-control" required="" disabled> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái </label>
                                    <select name="status" class="form-control" disabled>
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
                                </div>
                            </div>
                        </div>
                        <div class="load-product">
                            <div class="updateSale">
                            <table class="table table-bordered table-striped box-body">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;">STT</th>
                                        <th width="40%">Sản phẩm</th>
                                        <th width="10%">Giá gốc</th>
                                        <th width="10%">Giá khuyến mại</th>
                                        <th width="10%">Số lượng</th>
                                        <th width="10%">Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($productdeals->count() > 0)
                                    @foreach($productdeals as $key => $productdeal)
                                    @php 
                                        $product = $productdeal->product;
                                        $variant = $product->variant($product->id)
                                    @endphp
                                    <tr class="item-{{$product->id}}">
                                        <input type="hidden" name="productid[]" value="{{$product->id}}">
                                        <td style="text-align: center;">{{$key + 1}}</td>
                                        <td>
                                            <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                            <p>{{$product->name}}</p>
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
                                        <td><input type="checkbox" name="statusdeal[{{$product->id}}]" class="wgr-checkbox" value="1" @if($productdeal->status)checked="" @endif disabled></td>
                                    </tr>
                                    @endforeach
                                    @endif
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm mua kèm</h4>
                                </div>
                            </div>
                        </div>
                        <div class="load-product2">
                        <div class="updateSale2">
                        <table class="table table-bordered table-striped box-body">
                            <thead>
                                <tr>
                                    <th width="5%" style="text-align: center;">STT</th>
                                    <th width="35%">Sản phẩm</th>
                                    <th width="10%">Giá gốc</th>
                                    <th width="10%">Giá mua kèm</th>
                                    <th width="10%">Số lượng</th>
                                    <th width="10%">Đã mua</th>
                                    <th width="10%">Kho hàng</th>
                                    <th width="10%">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($saledeals->count() > 0)
                                @foreach($saledeals as $key2 => $saledeal)
                                @php $product2 = $saledeal->product;$variant2 = $product2->variant($product2->id) @endphp
                                <tr class="item-{{$product2->id}}">
                                    <input type="hidden" name="productsale[]" value="{{$product2->id}}">
                                    <td style="text-align: center;">{{$key2 + 1}}</td>
                                    <td>
                                        <img src="{{$product2->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                        <p>{{$product2->name}}</p>
                                    </td>
                                    <td>@if(!empty($variant2)){{number_format($variant2->price)}}đ 
                                        <input type="hidden" name="price_product" value="{{$variant2->price}}">
                                    @endif</td>
                                    <td>
                                        <input type="text" name="pricesale[{{$product2->id}}]" disabled value="{{number_format($saledeal->price)}}" class="form-control pricesale price">
                                    </td>
                                    <td><input type="number" value="{{$saledeal->qty}}" name="numbersale[{{$product2->id}}]" class="form-control" disabled></td>
                                    <td>{{$saledeal->buy}}</td>
                                    <td>@php 
                                        $total3 = countProductWarehouse($product2->id,'import');
                                        $total4 = countProductWarehouse($product2->id,'export'); @endphp
                                        {{$total3-$total4}}
                                    </td>
                                    <td><input type="checkbox" name="status2[{{$product2->id}}]" class="wgr-checkbox" value="1" @if($saledeal->status)checked="" @endif></td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
    </form>
</section>
@endsection