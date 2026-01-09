@extends('Layout::layout')
@section('title','Thêm quyền')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm quyền',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/permission/create">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề:</label>
                                    <input type="text" name="title" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">             
                                    
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Code:</label>
                                    <input type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max150" data-validation-error-msg-length="Không được vượt quá 150 ký tự!">
                                </div>             
                                <div class="form-group">
                                    <label class="control-label">Thuộc nhóm:</label>
                                    <select class="form-control" name="parent_id">
                                        <option value="0">Không</option>
                                        @if($permissions->count() > 0)
                                        @foreach($permissions as $permission)
                                        <option value="{{$permission->id}}">{{$permission->title}}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Sắp xếp:</label>
                                   <input type="number" name="sort" class="form-control" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                <a href="/admin/permission" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
@endsection