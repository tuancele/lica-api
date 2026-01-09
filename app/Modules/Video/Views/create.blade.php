@extends('Layout::layout')
@section('title','Thêm video')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm video',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/video/create">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề</label>
                                    <input id="slug-source" type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">             
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Đường dẫn</label>
                                    <input type="text" id="slug-target" name="slug" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max150" data-validation-error-msg-length="Không được vượt quá 150 ký tự!">
                                </div>             
                                <div class="form-group">
                                    <label class="control-label">Mô tả</label>
                                    <textarea class="form-control" name="description" rows="5"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Nội dung:</label>
                                    <textarea class="form-control text-input ckeditor" name="content" rows="8" ></textarea>
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
                        <div class="form-group">
                            <div class="title_input">
                                <label class="fw-700">Seo title </label><p class="number_char number_seo_title"><span>0 </span> / 70 ký tự</p>
                            </div>
                            <input type="text" name="seo_title" value="" class="form-control">
                        </div>
                        <div class="form-group">
                            <div class="title_input">
                                <label class="fw-700">Seo description</label><p class="number_char number_seo_description"><span>0</span> / 320 ký tự</p>
                            </div>
                            <textarea class="form-control" name="seo_description" rows="5" data-validation="length" data-validation-length="max320" data-validation-error-msg-length="Không được vượt quá 320 ký tự!"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Trạng thái</label>
                            <select class="form-control" name="status">
                                <option value="1">Hiển thị</option>
                                <option value="0">Ẩn</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <label class="fw-700">Hình ảnh đại diện</label>
                        <div class="form-group avantar1">
                            <img src="{{asset('public/admin/no-image.png')}}" class="img-responsive" alt="">
                        </div>
                        <div class="form-group" style="text-align: center;">
                            <input type="hidden" id="ImageUrl1" name="image" class="form-control medium_input pull-left">
                            <button type="button" class="btn btn-default btn_image btn-sm btnImage" type="button" number="1"><i class="fa fa-folder-open-o" aria-hidden="true"></i> Chọn ảnh</button>
                            <button type="button" class="btn btn-danger btn_delete_image btn-sm" number="1"><i class="fa fa-times" aria-hidden="true"></i> Xóa ảnh</button>
                        </div>     
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                <a href="/admin/video" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
@endsection