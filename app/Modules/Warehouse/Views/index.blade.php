@extends('Layout::layout')
@section('title','Kho hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Kho hàng',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10">
                <div class="row">
                    <form method="get" action="{{route('warehouse')}}"> 
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
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="30%" colspan="2">Sản phẩm</th>
                            <th width="10%">Mã sản phẩm</th>
                            <th width="10%">Màu sắc</th>
                            <th width="10%">Size</th>
                            <th width="10%">Giá nhập</th>
                            <th width="10%">Giá bán</th>
                            <th width="10%">Số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list) && !empty($list))
                        @foreach($list as $value)
                        @php $total = $value->variants->count(); 
                            $variants = $value->variants;
                        @endphp
                        <tr>
                            <td @if($total > 1) rowspan="{{$total}}" @endif><img src="{{getImage($value->image)}}" style="width:50px"></td>
                            <td rowspan="{{$value->variants->count()}}">
                               <a href="{{route('product.edit',['id'=>$value->id])}}" target="_blank"> {{$value->name}} </a>
                            </td>
                            <td>
                                {{$variants[0]->sku}}
                            </td>
                            <td><div style="float: left;border: 1px solid {{$variants[0]->color->color??'#000'}};width:15px;height:15px;background:{{$variants[0]->color->color??'#000'}};margin-right:3px"></div>{{$variants[0]->color->name??'Mặc định'}}</td>
                            <td>{{$variants[0]->size->name??'Mặc định'}}{{$variants[0]->size->unit??''}}</td>
                            <td>
                                @php 
                                $prIm = App\Modules\Warehouse\Models\ProductWarehouse::where('variant_id',$variants[0]->id)->first();
                                @endphp
                                @if(isset($prIm) && !empty($prIm))
                                    {{number_format($prIm->price)}}đ
                                @endif
                            </td>
                            <td>@if($variants[0]->sale != 0)<strong>{{number_format($variants[0]->sale)}}đ</strong><del style="color:#aaa;margin-left:10px;">{{number_format($variants[0]->price)}}đ</del>@else <strong>{{number_format($variants[0]->price)}}đ</strong> @endif</td>
                            <td>
                                @php $total1 = countProduct($variants[0]->id,'import');$total2 = countProduct($variants[0]->id,'export'); @endphp
                                {{$total1-$total2}}
                            </td>
                        </tr>
                        @if($total > 1)
                        @for($i = 1;$i < $total; $i++)
                            <tr>
                                <td>{{$variants[$i]->sku}}</td>
                                <td><div style="float: left;border: 1px solid {{$variants[$i]->color->color??'#000'}};width:15px;height:15px;background:{{$variants[$i]->color->color??'#000'}};margin-right:3px"></div>{{$variants[$i]->color->name??'Mặc định'}}</td>
                                <td>{{$variants[$i]->size->name??'Mặc định'}}{{$variants[$i]->size->unit??''}}</td>
                                <td>
                                    @php 
                                        $prIm = App\Modules\Warehouse\Models\ProductWarehouse::where('variant_id',$variants[$i]->id)->first();
                                    @endphp
                                    @if(isset($prIm) && !empty($prIm))
                                        {{number_format($prIm->price)}}đ
                                    @endif
                                </td>
                                <td>@if($variants[$i]->sale != 0)<strong>{{number_format($variants[$i]->sale)}}đ</strong><del style="color:#aaa;margin-left:10px;">{{number_format($variants[$i]->price)}}đ</del>@else <strong>{{number_format($variants[$i]->price)}}đ</strong> @endif</td>
                                <td>
                                    @php $total1 = countProduct($variants[$i]->id,'import');$total2 = countProduct($variants[$i]->id,'export'); @endphp
                                    {{$total1-$total2}}
                                </td>
                            </tr>
                        @endfor
                        @endif
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