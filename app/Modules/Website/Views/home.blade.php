@extends('Layout::layout')
@section('title','Thiết lập trang chủ')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thiết lập trang chủ',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/themes/home">
	<div class="row">
        <div class="col-lg-6">
            <img src="/public/admin/themes/page-home.jpg" alt="home" class="img-responsive">
        </div>
        <div class="col-lg-6">
                @csrf
                <input type="hidden" name="id" value="{{$home->id}}">
                @php $block0 = json_decode($home->block_0);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">1. Giới thiệu</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                       <div class="row form-group">
                            <div class="col-md-4">
                                <label>Hình ảnh </label>
                            </div>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{$block0->image}}" name="image_0" id="ImageUrl10">
                                    <span class="input-group-btn">
                                      <button class="btn btn-info btn-flat btnImage" number="10" type="button"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Link youtube</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="video_0" class="form-control" value="{{$block0->video}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề 1</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_01" class="form-control" value="{{$block0->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề 2</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_02" class="form-control" value="{{$block0->title1}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Mô tả</label>
                            </div>
                            <div class="col-md-8">
                                <textarea class="form-control" name="content_0" rows="5">{{$block0->content}}</textarea>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_0" @if($block0->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block1 = json_decode($home->block_1);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">2. Chứng nhận</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Nhóm banner</label>
                            </div>
                            <div class="col-md-8">
                                <select class="form-control" name="category_1">
                                    <option value="1" @if($block1->category == 1) selected="" @endif>Chứng nhận</option>
                                    <option value="2" @if($block1->category == 2) selected="" @endif>Truyền thông</option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_1" class="form-control" value="{{$block1->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_1" @if($block1->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block2 = json_decode($home->block_2);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">3. Sản phẩm nổi bật</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_2" class="form-control" value="{{$block2->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_2" class="form-control" value="{{$block2->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_2" @if($block2->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block3 = json_decode($home->block_3);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">4. Sản phẩm bán chạy</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_3" class="form-control" value="{{$block3->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_3" class="form-control" value="{{$block3->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_3" @if($block3->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block5 = json_decode($home->block_5);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">5. Sản phẩm mới</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_5" class="form-control" value="{{$block5->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Hình ảnh </label>
                            </div>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{$block5->image}}" name="image_5" id="ImageUrl50">
                                    <span class="input-group-btn">
                                        <button class="btn btn-info btn-flat btnImage" number="50" type="button"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <div class="row form-group">
                             <div class="col-md-6">
                                <input type="text" name="title_51" class="form-control" value="{{$block5->title1}}">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="title_52" class="form-control" value="{{$block5->title2}}">
                            </div>
                        </div>
                        <hr/>
                        <div class="row form-group">
                             <div class="col-md-6">
                                <input type="text" name="title_53" class="form-control" value="{{$block5->title3}}">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="title_54" class="form-control" value="{{$block5->title4}}">
                            </div>
                        </div>
                        <hr/>
                        <div class="row form-group">
                             <div class="col-md-6">
                                <input type="text" name="title_55" class="form-control" value="{{$block5->title5}}">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="title_56" class="form-control" value="{{$block5->title6}}">
                            </div>
                        </div>
                        <hr/>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_5" @if($block5->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block6 = json_decode($home->block_6);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">6. Phản hồi từ khách</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_6" class="form-control" value="{{$block6->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_6" class="form-control" value="{{$block6->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_6" @if($block6->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block7 = json_decode($home->block_7);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">7. Quyền lợi khách hàng</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_7" class="form-control" value="{{$block7->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_7" class="form-control" value="{{$block7->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_7" @if($block7->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block8 = json_decode($home->block_8);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">8. Truyền thông</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_8" class="form-control" value="{{$block8->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Nhóm banner</label>
                            </div>
                            <div class="col-md-8">
                                <select class="form-control" name="category_8">
                                    <option value="1" @if($block8->category == 1) selected="" @endif>Chứng nhận</option>
                                    <option value="2" @if($block8->category == 2) selected="" @endif>Truyền thông</option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_8" class="form-control" value="{{$block8->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_8" @if($block8->status == 1) checked="" @endif/>
                                      Hiển thị
                            </div>
                        </div>
                    </div>
                </div>
                @php $block9 = json_decode($home->block_9);@endphp
                <div class="box box-primary collapsed-box box-solid">
                    <div class="box-header with-border">
                      <h5 class="mb-0 mt-0 fs-15 box-title">9. Tin tức</h5>
                      <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" type="button"><i class="fa fa-plus"></i></button>
                      </div>
                    </div>
                    <div class="box-body">
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Tiêu đề</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="title_9" class="form-control" value="{{$block9->title}}">
                            </div>
                        </div>
                        <div class="row form-group">
                             <div class="col-md-4">
                                <label>Số lượng hiển thị</label>
                            </div>
                            <div class="col-md-8">
                                <input type="number" name="number_9" class="form-control" value="{{$block9->number}}">
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-md-4">
                                <label>Trạng thái </label>
                            </div>
                            <div class="col-md-8">
                                <input type="checkbox" class="minimal" value="1" name="status_9" @if($block9->status == 1) checked="" @endif/>
                                      Hiển thị
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
<style type="text/css">
	.box_image{
		width: 30%;border: 1px dashed #ddd;margin-right: 10px;border-radius: 5px;overflow: hidden;height: 70px;text-align: center;
	}
	.box_image img{
		height: 100%;display: inline-block;
	}
    .mb-0{margin-bottom: 0px !important}.fs-12{font-size: 12px !important}.mt-0{margin-top: 0px !important}.mb-10{margin-bottom: 10px !important}.mt-10{margin-top: 10px !important}
    .cl-blue{color:#3c8dbc;}.fs-15{font-size: 15px !important}
    .item-variant{display: flex;border-bottom: 1px solid #eee;padding:10px;}
    .item-variant p{color:#333;}.item-variant .box_image{width: 20%;height: 50px}
    .item-variant.active{background-color: #3c8dbc;}.item-variant.active p{color:#fff;}
    .icon .form-group img{
        width: initial !important;
        display: inline-block;
        max-width: 100%;
    }
</style>
@endsection