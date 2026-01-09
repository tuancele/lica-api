@extends('Layout::layout')
@section('title','Sửa quyền lợi')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa quyền lợi',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/right/edit">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tiêu đề</label>
                            <input  type="text" name="name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống" value="{{$detail->name}}">
                            <input type="hidden" name="id" value="{{$detail->id}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Mô tả</label>
                            <input type="text" name="content" class="form-control" value="{{$detail->content}}">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="1" @if($detail->status == 1) selected="" @endif>Hiển thị</option>
                                        <option value="0" @if($detail->status == 0) selected="" @endif>Ẩn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <label class="fw-700">Hình ảnh đại diện</label>
                        <div class="form-group avantar1">
                            <img src="{{getImage($detail->image)}}" class="img-responsive" alt="">
                        </div>
                        <div class="form-group" style="text-align: center;">
                            <input type="hidden" id="ImageUrl1" name="image" value="{{$detail->image}}" class="form-control medium_input pull-left">
                            <button type="button" class="btn btn-default btn_image btn-sm btnImage" type="button" number="1"><i class="fa fa-folder-open-o" aria-hidden="true"></i> Chọn ảnh</button>
                            <button type="button" class="btn btn-danger btn_delete_image btn-sm" number="1"><i class="fa fa-times" aria-hidden="true"></i> Xóa ảnh</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
            <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
            <a href="/admin/right" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
        </div>
    </form>
</section>
@endsection