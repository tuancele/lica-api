@extends('Layout::layout')
@section('title','Thêm menu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm menu',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/menu/create">
        @csrf
        <div class="row">
            <div class="col-lg-5">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Tên menu</label>
                            <input type="text" class="form-control" name="name" required="">
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="fix_action">
            <div class="form-group">
                    <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                    <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                    <a href="/admin/menu" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
@endsection