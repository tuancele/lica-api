@extends('Layout::layout')
@section('title','Sửa danh mục sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa danh mục sản phẩm',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('taxonomy.update')}}">
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
                        <div class="form-group">
                            <label class="control-label">Danh mục cha:</label>
                            <select class="form-control" name="cat_id">
                                <option value="0">Không</option>
                                {!! menuMulti($categories,0,'',$detail->cat_id) !!}
                            </select>
                        </div>
                        @include('Layout::status',['status' => $detail->status])
                        <div class="form-group">
                            <label class="control-label">Danh mục nổi bật:</label>
                            <select class="form-control" name="feature">
                                <option value="1" @if($detail->feature == 1) selected @endif>Có</option>
                                <option value="0" @if($detail->feature == 0) selected @endif>Không</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hiển thị trang chủ:</label>
                            <select class="form-control" name="is_home">
                                <option value="1" @if($detail->is_home == 1) selected @endif>Có</option>
                                <option value="0" @if($detail->is_home == 0) selected @endif>Không</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Hiển thị trong tra cứu đơn:</label>
                            <select class="form-control" name="tracking">
                                <option value="1" @if($detail->tracking == 1) selected @endif>Có</option>
                                <option value="0" @if($detail->tracking == 0) selected @endif>Không</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image-r2',['image' => $detail->image,'number' => 1, 'folder' => 'taxonomies'])
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