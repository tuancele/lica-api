@extends('Layout::layout')
@section('title','Thêm danh mục sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm danh mục sản phẩm',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/api/taxonomies">
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
                            <label class="control-label">Danh mục cha:</label>
                            <select class="form-control" name="cat_id">
                                <option value="0">Không</option>
                                {!! menuMulti($categories,0,'') !!}
                            </select>
                        </div>
                        @include('Layout::status')
                        <div class="form-group">
                            <label class="control-label">Danh mục nổi bật:</label>
                            <select class="form-control" name="feature">
                                <option value="1">Có</option>
                                <option value="0">Không</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hiển thị trang chủ:</label>
                            <select class="form-control" name="is_home">
                                <option value="1">Có</option>
                                <option value="0">Không</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hiển thị trong tra cứu đơn:</label>
                            <select class="form-control" name="tracking">
                                <option value="1">Có</option>
                                <option value="0" selected="">Không</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['number' => 1, 'folder' => 'taxonomies'])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
           @include('Layout::action',['link'=>route('taxonomy')])
        </div>
    </form>
</section>
@endsection