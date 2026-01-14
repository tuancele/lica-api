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
                    @include('Layout::image-r2',['image' => $detail->image,'number' => 1, 'folder' => 'members'])
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Giao diện:</label>
                            <select class="form-control" name="temp">
                                <option value="page.index" @if($detail->temp == "page.index") selected="" @endif>Mặc định</option>
                                <option value="page.home" @if($detail->temp == "page.home") selected="" @endif>Trang chủ</option>
                                <option value="page.product" @if($detail->temp == "page.product") selected="" @endif>Sản phẩm</option>
                                <option value="page.policy" @if($detail->temp == "page.policy") selected="" @endif>Trang chính sách</option>
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