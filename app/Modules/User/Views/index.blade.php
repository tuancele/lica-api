@extends('Layout::layout')
@section('title','Danh sách tài khoản')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách tài khoản',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="/admin/user"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hoạt động</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ngừng hoạt động</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit">Tìm kiếm</button>
                        @if(request()->get('keyword') != null || request()->get('status') != null)
                        <a class="button btn btn-default" href="/admin/user">Bỏ tìm kiếm</a>
                        @endif
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="/admin/user/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/user/delete">
            <div class="userContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="20%">Họ tên</th>         
                            <th width="15%">Email</th>
                            <th width="15%">Điện thoại</th>
                            <th width="10%">Phân quyền</th>
                            <th width="10%">Trạng thái</th>
                            <th width="20%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($users) && !empty($users))
                        @foreach($users as $user)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$user->id}}"></td>
                            <td><a href="/admin/user/edit/{{$user->id}}">{{$user->name}}</a></td>
                            <td>
                                {{$user->email}}
                            </td>
                            <td>{{$user->phone}}</td>
                            <td>
                               @if($user->role_id == 1)
                                    Admin  
                               @else
                                    Đăng bài
                               @endif
                            </td>
                            <td>
                                @if($user->role_id != 1)
                                <select class="select_status form-control" data-id="{{$user->id}}" data-url="/admin/user/status">
                                    <option value="1" @if($user->status == 1) selected="selected" @endif> Hoạt động</option>
                                    <option value="0" @if($user->status == 0) selected="selected" @endif> Ngừng hoạt động</option>
                                </select>
                                @endif
                            </td>
                            <td>
                                @if($user->role_id != 1)
                                <a class="btn_delete btn btn-danger btn-xs pull-right" data-user="{{ app('request')->input('user') }}" data-id="{{$user->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                                @endif
                                <a class="btn btn-primary btn-xs pull-right" href="/admin/user/edit/{{$user->id}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                                <a class="btn btn-warning btn-xs pull-right" href="/admin/user/change/{{$user->id}}" style="margin-right:3px"><i class="fa fa-key" aria-hidden="true"></i> Đổi mật khẩu</a>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-4">
                   
                </div>
                <div class="col-md-8 text-right">
                    {{$users->links()}}
                </div>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection