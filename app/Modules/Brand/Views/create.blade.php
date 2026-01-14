@extends('Layout::layout')
@section('title','Thêm thương hiệu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm thương hiệu',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('brand.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('Layout::title')
                                @include('Layout::slug')
                                @include('Layout::content')
                            </div>
                        </div>
                    </div>
                </div>
                @include('Layout::image-gallery-r2',['number' => 1, 'folder' => 'brands'])
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy sản phẩm trên công cụ tìm kiếm như Google.</p>
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
                    @include('Layout::image-r2',['number' => 1,'title' => 'Logo thương hiệu','name' => 'logo','folder' => 'brands'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['number' => 2,'title' => 'Ảnh đại diện','name' => 'image','folder' => 'brands'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['number' => 3,'title' => 'Banner','name' => 'banner','folder' => 'brands'])
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