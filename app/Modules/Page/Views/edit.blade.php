@extends('Layout::layout')
@section('title','Sửa trang nội dung')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa trang nội dung',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('page.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" value="{{$detail->id}}" name="id">
                                @include('Layout::title',['title' => $detail->name])
                                @include('Layout::slug',['slug' => $detail->slug])
                                @include('Layout::description',['description' => $detail->description])
                                @include('Layout::content',['content' => $detail->content])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy bài viết trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo',['title' => $detail->seo_title,'description' => $detail->seo_description])
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status',['status' => $detail->status])
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['image' => $detail->image,'number' => 1])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['image' => $detail->banner,'number' => 2,'title' => 'Banner','name' => 'banner'])
                    <div class="panel-body" style="padding-top: 0px">
                        <div class="form-group">
                            <label>Liên kết</label>
                            <input type="text" name="link" class="form-control" value="{{$detail->link}}">
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Giao diện:</label>
                            <select class="form-control" name="temp">
                                <option value="page.index" @if($detail->temp == "page.index") selected="" @endif>Mặc định</option>
                                <option value="page.home" @if($detail->temp == "page.home") selected="" @endif>Trang chủ</option>
                                <option value="page.product" @if($detail->temp == "page.product") selected="" @endif>Sản phẩm</option>
                                <option value="page.flashsale" @if($detail->temp == "page.flashsale") selected="" @endif>Flash Sale</option>
                                <option value="post.index" @if($detail->temp == "post.index") selected="" @endif>Tất cả bài viết</option>
                                <option value="page.policy" @if($detail->temp == "page.policy") selected="" @endif>Trang chính sách</option>
                                <option value="page.promotion" @if($detail->temp == "page.promotion") selected="" @endif>Trang ưu đãi</option>
                                <option value="page.brand" @if($detail->temp == "page.brand") selected="" @endif>Trang thương hiệu</option>
                                <option value="page.search" @if($detail->temp == "page.search") selected="" @endif>Trang cứu thành phần</option>
                                 <option value="dictionary.index" @if($detail->temp == "dictionary.index") selected="" @endif>Trang thư viện thành phần</option>
                                 <option value="page.tracking" @if($detail->temp == "page.tracking") selected="" @endif>Trang cứu đơn hàng</option>
                                <option value="page.contact" @if($detail->temp == "page.contact") selected="" @endif>Liên hệ</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('page')])
        </div>
    </form>
</section>
@endsection