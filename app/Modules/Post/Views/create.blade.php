@extends('Layout::layout')
@section('title','Thêm bài viết')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm bài viết',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('post.store')}}">
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
                                 <div class="note"><strong>Shortcode: </strong>
                                    <p style="margin-bottom: 0px">Lấy sản phẩm theo đường dẫn danh mục: <strong>[products slug=cham-da-vung-kin type=category]</strong></p>
                                    <p style="margin-bottom: 0px">Lấy sản phẩm theo đường dẫn thương hiệu: <strong>[products slug=epictetos type=brand]</strong></p>
                                    <p style="margin-bottom: 0px">Lấy sản phẩm theo id sản phẩm: <strong>[products slug=60,63,71,81 type=product]</strong></p>
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
                            <label class="control-label">Thuộc chuyên mục</label>
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
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['number' => 1])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('post')])
        </div>
    </form>
</section>
@endsection