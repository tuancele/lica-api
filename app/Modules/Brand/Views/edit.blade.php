@extends('Layout::layout')
@section('title','Sửa thông tin thương hiệu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa thông tin thương hiệu',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('brand.update')}}">
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
                                @include('Layout::content',['content' => $detail->content])
                            </div>
                        </div>
                    </div>
                </div>
                @include('Layout::image-gallery-r2',['number' => 1, 'folder' => 'brands', 'gallery' => isset($gallerys) ? $gallerys : null])
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy sản phẩm trên công cụ tìm kiếm như Google.</p>
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
                    @include('Layout::image-r2',['image' => $detail->logo,'number' => 1,'title' => 'Logo','name' => 'logo','folder' => 'brands'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['image' => $detail->image,'number' => 2,'title' => 'Ảnh đại diện','name' => 'image','folder' => 'brands'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['image' => $detail->banner,'number' => 3,'title' => 'Banner','name' => 'banner','folder' => 'brands'])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
           @include('Layout::action',['link'=>route('brand')])
        </div>
    </form>
</section>
@endsection