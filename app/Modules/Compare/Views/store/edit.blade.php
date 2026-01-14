@extends('Layout::layout')
@section('title','Sửa website lấy dữ liệu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa website lấy dữ liệu',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('compare.store.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" value="{{$detail->id}}" name="id">
                                @include('Layout::title',['title' => $detail->name])
                            </div>
                            <div class="col-lg-4">
                                @include('Layout::status',['status' => $detail->status])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    @include('Layout::image-r2',['image' => $detail->logo,'number' => 1, 'name' => 'logo', 'folder' => 'compares'])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('compare.store')])
        </div>
    </form>
</section>
@endsection