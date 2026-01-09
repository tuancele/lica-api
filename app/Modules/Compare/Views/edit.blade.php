@extends('Layout::layout')
@section('title','Sửa sản phẩm so sánh')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa sản phẩm so sánh',
])
<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript">
    $(function(){
        $('body .price').number( true, 0);
    });
</script>
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('compare.update')}}">
        @csrf
        <input type="hidden" name="id" value="{{$detail->id}}">
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label class="control-label">Website lấy dữ liệu</label>
                                    <select class="form-control" name="store_id">
                                        @if($stores->count() > 0)
                                        @foreach($stores as $store)
                                        <option value="{{$store->id}}" @if($detail->store_id == $store->id) selected @endif>{{$store->name}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Tên sản phẩm</label>
                                    <input type="text" class="form-control" value="{{$detail->name}}" name="name">
                                </div> 
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Thương hiệu</label>
                                            <input type="text" class="form-control" value="{{$detail->brand}}" name="brand">
                                        </div> 
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Giá</label>
                                            <input type="text" class="form-control price" value="{{number_format($detail->price)}}" name="price">
                                        </div> 
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Link sản phẩm </label>
                                    <input type="text" class="form-control" value="{{$detail->link}}" name="link">
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status',['status' => $detail->status])
                        <div class="form-group">
                            <label class="control-label">Hiển thị link sản phẩm gốc</label>
                            <select class="form-control" name="is_link">
                                <option value="1" @if($detail->is_link == 1) selected @endif>Hiển thị</option>
                                <option value="0" @if($detail->is_link == 0) selected @endif>Không</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('compare')])
        </div>
    </form>
</section>
<style>
    .box-category{
        height: 250px;
        overflow-y: scroll;
        width: 100%;
        padding:0px;
    }
    .box-category label{
        display: block;
        overflow: hidden;
    }
    .box-category label.parent{
        font-weight: normal;
        margin-left: 30px;
    }
    .box-category input{
        float: left;
    }
</style>
@endsection