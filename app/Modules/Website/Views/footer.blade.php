@extends('Layout::layout')
@section('title','Thiết lập chân trang')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thiết lập chân trang',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/themes/footer">
	<div class="row">
        <div class="col-lg-6">
        	<img src="/public/admin/themes/footer.jpg" alt="footer" class="img-responsive">
        </div>
        <div class="col-lg-6">
            @csrf
            <input type="hidden" name="id" value="{{$footer->id}}">
            @php $block4 = json_decode($footer->block_4) @endphp
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">1.Thông tin cơ bản</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Logo </label>
                        </div>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{$block4->logo}}" name="logo" id="ImageUrl1">
                                <span class="input-group-btn">
                                  <button class="btn btn-info btn-flat btnImage" number="1" type="button"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Chú thích logo </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="alt" value="{{$block4->alt}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Liên kết facebook </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="facebook" value="{{$block4->facebook}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Liên kết instagram </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="instagram" value="{{$block4->instagram}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Liên kết tiktok </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="tiktok" value="{{$block4->tiktok}}" class="form-control">
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-4">
                            <label>Liên kết bộ công thương </label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" name="link" value="{{$block4->link}}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">2. Thông tin liên hệ</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <textarea class="form-control ckeditor"  name="block_2">{{$footer->block_2}}</textarea>
                    </div>
                </div>
            </div>
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">3. Liên kết</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="row form-group">
                         <div class="col-md-3">
                            <label>Chọn menu</label>
                        </div>
                        <div class="col-md-9">
                            <select class="form-control" name="block_0">
                                @if($groups->count() > 0)
                                @foreach($groups as $group)
                                <option value="{{$group->id}}" @if($footer->block_0 == $group->id) selected="" @endif>{{$group->name}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">4. Bản đồ</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <textarea class="form-control" name="block_3" rows="6">{{$footer->block_3}}</textarea>
                    </div>
                </div>
            </div>
            <div class="box box-primary collapsed-box box-solid">
                <div class="box-header with-border">
                  <h5 class="mb-0 mt-0 fs-15 box-title">5. Nội dung chân trang</h5>
                  <div class="box-tools pull-right">
                    <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                  </div>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <textarea class="form-control" id="textarea2" name="block_1" rows="4">{{$footer->block_1}}</textarea>
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