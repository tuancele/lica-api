@extends('Layout::layout')
@section('title','Sửa khu vực')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa khu vực',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/groupshowroom/edit">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề</label>
                                    <input  type="text" name="name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống" value="{{$detail->name}}">
                                    <input type="hidden" name="id" value="{{$detail->id}}">
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Trạng thái</label>
                                    <select class="form-control" name="status" style="width: 200px">
                                        <option value="1" @if($detail->status == 1) selected="" @endif>Hiển thị</option>
                                        <option value="0" @if($detail->status == 0) selected="" @endif>Ẩn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
            <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
            <a href="/admin/groupshowroom" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
        </div>
    </form>
</section>
@endsection