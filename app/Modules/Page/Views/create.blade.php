@extends('Layout::layout')
@section('title','Thêm trang nội dung')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm trang nội dung',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('page.store')}}">
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
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy bài viết trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status')
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['number' => 1, 'folder' => 'pages'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['number' => 2,'title' => 'Banner','name' =>'banner', 'folder' => 'pages'])
                    <div class="panel-body" style="padding-top: 0px">
                        <div class="form-group">
                            <label>Liên kết</label>
                            <input type="text" name="link" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Giao diện:</label>
                            <select class="form-control" name="temp">
                                <option value="page.index">Mặc định</option>
                                <option value="page.home">Trang chủ</option>
                                <option value="page.product">Sản phẩm</option>
                                <option value="page.flashsale">Flash Sale</option>
                                <option value="post.index">Tất cả bài viết</option>
                                <option value="page.policy">Trang chính sách</option>
                                <option value="page.promotion">Trang ưu đãi</option>
                                <option value="page.brand">Trang thương hiệu</option>
                                <option value="page.search">Tra cứu thành phần</option>
                                <option value="dictionary.index">Thư viện thành phần</option>
                                <option value="page.tracking">Tra cứu đơn hàng</option>
                                <option value="page.contact">Liên hệ</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('page')])
        </div>
    </form>
</section>
@endsection