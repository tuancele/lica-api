@extends('Layout::layout')
@section('title','Menu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Menu',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="/admin/menu/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm menu</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/menu/delete">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="20%">Tiêu đề</th>
                            <th width="30%">Danh mục menu</th>         
                            <th width="15%">Thời gian</th>
                            <th width="15%">Người tạo</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($groups->count() > 0)
                        @foreach($groups as $group)
                        <tr>
                            <td><a href="/admin/menu/edit/{{$group->id}}">{{$group->name}}</a></td>
                            <td>
                                @php $menus = $group->menu;@endphp
                                @if($menus->count() > 0)
                                @foreach($menus as $menu)
                                    {{$menu->name}},&nbsp;
                                @endforeach
                                @endif
                            </td>
                            <td>{{formatDate($group->created_at)}}</td>
                            <td>@if($group->user) {{$group->user->name}} @endif</td>
                            <td>
                                <a class="btn_delete btn btn-danger btn-xs pull-right" data-page="" data-id="{{$group->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                                <a class="btn btn-primary btn-xs pull-right" href="/admin/menu/edit/{{$group->id}}" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i> Sửa</a>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr><td colspan="5">Không có dữ liệu</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection