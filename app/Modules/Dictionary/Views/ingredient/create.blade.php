@extends('Layout::layout')
@section('title','Thêm thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm thành phần',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('dictionary.ingredient.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('Layout::title')
                                @include('Layout::slug')
                                @include('Layout::description')
                                <div class="form-group">
                                    <label>Sơ lược</label>
                                    <textarea class="form-control ckeditor" name="glance"></textarea>
                                </div> 
                                @include('Layout::content')
                                <div class="form-group">
                                    <label>Tài liệu</label>
                                    <textarea class="form-control ckeditor" name="reference"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Trách nhiệm</label>
                                    <textarea class="form-control" rows="5" name="disclaimer"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy bài viết trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Danh mục</label>
                            <div class="box-category box-body">
                                @if($categories->count() > 0)
                                @foreach($categories as $category)
                                <label for="cate{{$category->id}}" style="font-weight: normal;">
                                    <input id="cate{{$category->id}}" type="checkbox" class="wgr-checkbox" name="cat_id[]" value="{{$category->id}}">
                                    <span>{{$category->name}}</span>
                                </label>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Lợi ích</label>
                            <div class="box-category box-body">
                                @if($benefits->count() > 0)
                                @foreach($benefits as $benefit)
                                <label for="cate{{$benefit->id}}" style="font-weight: normal;">
                                    <input id="cate{{$benefit->id}}" type="checkbox" class="wgr-checkbox" name="benefit_id[]" value="{{$benefit->id}}">
                                    <span>{{$benefit->name}}</span>
                                </label>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Đánh giá</label>
                            <select class="form-control" name="rate_id">
                                @if($rates->count() > 0)
                                @foreach($rates as $rate)
                                <option value="{{$rate->id}}">{{$rate->name}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status')
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('dictionary.ingredient')])
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