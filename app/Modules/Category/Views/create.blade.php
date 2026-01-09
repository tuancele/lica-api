@extends('Layout::layout')
@section('title','Chuyên mục bài viết')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Chuyên mục bài viết',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('category.store')}}">
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
                                @include('Layout::content')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy chuyên mục trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Chuyên mục cha:</label>
                            <select class="form-control" name="cat_id">
                                <option value="0">Không</option>
                                {!! menuMulti($categories,0,'') !!}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status')
                        <div class="form-group">
                            <label class="control-label">Hiển thị trang chủ:</label>
                            <select class="form-control" name="feature">
                                <option value="0" selected="">Không</option>
                                <option value="1">Có</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['number' => 1])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('category')])
        </div>
    </form>
</section>
@endsection