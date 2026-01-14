@extends('Layout::layout')
@section('title','Thiết lập đầu trang')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thiết lập đầu trang',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/themes/header">
	<div class="row">
        <div class="col-lg-6">
        	<img src="/public/admin/themes/header.jpg" alt="header" class="img-responsive">
        </div>
        <div class="col-lg-6">
            @csrf
            <input type="hidden" name="id" value="{{$header->id}}">
            @php $info = json_decode($header->block_0) @endphp
            <div class="box box-primary box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">Thông tin đầu trang</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-minus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Tiêu đề </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="title" value="{{$info->title}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Logo </label>
                        </div>
                        <div class="col-md-8">
                            @include('Layout::image-r2-input-group',['image' => $info->logo,'number' => 1, 'name' => 'logo', 'folder' => 'website'])
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Chú thích logo </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="alt" value="{{$info->alt}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                         <div class="col-md-4">
                            <label>Menu chính </label>
                        </div>
                        <div class="col-md-8">
                            <select class="form-control" name="menu">
                                @if($groups->count() > 0)
                                @foreach($groups as $group)
                                <option value="{{$group->id}}" @if($info->menu == $group->id) selected @endif>{{$group->name}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="fix_action">
        <div class="form-group">
            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu thay đổi</button>
        </div>
    </div>
    </form>
</section>
<link href="/public/admin/plugins/iCheck/all.css" rel="stylesheet" type="text/css" />
<script src="/public/admin/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
<script type="text/javascript">
     $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
          checkboxClass: 'icheckbox_minimal-blue',
          radioClass: 'iradio_minimal-blue'
        });
</script>
@endsection