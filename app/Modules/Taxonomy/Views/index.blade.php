@extends('Layout::layout')
@section('title','Danh mục sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh mục sản phẩm',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="{{route('taxonomy')}}"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="{{route('taxonomy.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
                <a class="button add btn btn-warning pull-right" href="{{route('taxonomy.sort')}}" style="margin-right:5px;"><i class="fa fa-arrows-v" aria-hidden="true"></i> Sắp xếp</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('taxonomy.delete')}}" action-url="{{route('taxonomy.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="10%">Hình ảnh</th>
                            <th width="30%">Tiêu đề</th>         
                            <th width="15%">Ngày tạo</th>
                            <th width="15%">Người tạo</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        {!! listTaxonomy($categories,0,'','taxonomy')!!}
                    </tbody>
                </table>
            </div>
        </form>
        
    </div>
</div>
</section>
@endsection