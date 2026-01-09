@extends('Layout::layout')
@section('title','Quyền quản trị')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Quyền quản trị',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <h4 style="margin-bottom: 0px;">{{$detail->title}}</h4>
            </div>
            <div class="col-md-4">
                <button class="button add btn btn-success pull-right _update" type="button"><i class="fa fa-refresh" aria-hidden="true"></i> Cập nhật</button>
                <a class="button add btn btn-info pull-right" href="/admin/permission/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/permission/delete" update-url="/admin/permission/sort"  action-url="/admin/permission/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="30%">Tiêu đề</th>  
                            <th width="15%">Code</th>       
                            <th width="15%">Ngày tạo</th>
                            <th width="10%">Sắp xếp</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $value)
                        <tr>
                            <td>
                               {{$value->title}}
                            </td>
                            <td>{{$value->name}}</td>
                            <td>{{formatDate($value->created_at)}}</td>
                            <td>
                               <input type="number" class="form-control" name="sort[{{$value->id}}]" value="{{$value->sort}}">
                            </td>
                            <td>
                            	<a class="btn btn-primary btn-xs" href="/admin/permission/edit/{{$value->id}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$value->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                             
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection