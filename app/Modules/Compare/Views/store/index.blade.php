@extends('Layout::layout')
@section('title','Danh sách website lấy sản phẩm so sánh')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách website lấy sản phẩm so sánh',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="{{route('compare.store')}}"> 
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-2">
                        <button class="button btn btn-default" type="submit">Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
               
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="30%" colspan="2">Website</th>
                            <th width="15%">Tổng thương hiệu</th>
                            <th width="15%">Tổng sản phẩm</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($list->count() > 0)
                        @foreach($list as $value)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$value->id}}"></td>
                            <td width="120px">
                                <img src="{{getImage($value->logo)}}" style="width:100px">
                            </td>
                            <td>
                                {{$value->name}}
                            </td>
                            <td>
                                @php 
                                    $brands = App\Modules\Compare\Models\Compare::select('brand')->where('store_id',$value->id)->distinct()->get();
                                @endphp
                                {{$brands->count()}}
                            </td>
                            <td>
                                {{$value->compares->count()}}
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('compare.store.edit',['id' => $value->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
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
                    {{$list->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection