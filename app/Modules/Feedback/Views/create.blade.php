@extends('Layout::layout')
@section('title','Thêm phản hồi khách hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm phản hồi khách hàng',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/feedback/create">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Họ tên</label>
                            <input type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                        </div>
                        <div class="form-group">
                            <div class="title_input">
                                <label class="fw-700">Chức vụ</label>
                            </div>
                            <input type="text" name="position" value="" class="form-control">
                        </div>
                        <div class="form-group">
                            <div class="title_input">
                                <label class="fw-700">Nội dung</label>
                            </div>
                            <textarea class="form-control" name="content" rows="5" rows="5"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Trạng thái</label>
                            <select class="form-control" name="status" style="width: 200px">
                                <option value="1">Hiển thị</option>
                                <option value="0">Ẩn</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
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
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                <a href="/admin/feedback" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
@endsection