@extends('Layout::layout')
@section('title','Sửa đánh giá')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa đánh giá',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/rate/edit">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Sản phẩm</label>
                            <select class="form-control" name="product_id">
                                @if($products->count() > 0)
                                @foreach($products as $product)
                                <option value="{{$product->id}}" @if($product->id == $detail->product_id) selected="" @endif>{{$product->name}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tên</label>
                            <input id="slug-source" type="text" name="name" value="{{$detail->name}}" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                            <input type="hidden" name="id" value="{{$detail->id}}">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Số điện thoại</label>
                                    <input type="text" name="phone" class="form-control" value="{{$detail->phone}}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Địa chỉ email</label>
                                    <input type="text" name="email" class="form-control" value="{{$detail->email}}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Tóm tắt</label>
                            <input type="text" name="title" class="form-control" value="{{$detail->title}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label" style="display: block">Hình ảnh <a style="float:right" href="javascript:;"  class="pull-right" data-toggle="modal" data-target="#uploadImage"><i class="fa fa-plus-square" aria-hidden="true"></i> Thêm hình ảnh</a></label>
                            <div  style="padding:10px;border:1px solid #eee">
                                <div class="list_image row">
                                    @if(isset($gallerys) && !empty($gallerys))
                                    @foreach($gallerys as $ga => $gallery)
                                        <div class="col-md-3 item{{$ga+1}}"><img src="{{getImage($gallery)}}"><input type="hidden" value="{{$gallery}}" name="imageOther[]"><a data-id="{{$ga+1}}" href="javascript:;" title="Xóa ảnh" class="delete_image"><i class="fa fa-times"   aria-hidden="true"></i></a></div>
                                    @endforeach
                                    @endif
                                </div> 
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Nội dung</label>
                            <textarea name="content" class="form-control" rows="5">{{$detail->content}}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Đánh giá</label>
                                    <select class="form-control" name="rate">
                                        <option value="1" @if($detail->rate == 1) selected @endif>*</option>
                                        <option value="2" @if($detail->rate == 2) selected @endif>**</option>
                                        <option value="3" @if($detail->rate == 3) selected @endif>***</option>
                                        <option value="4" @if($detail->rate == 4) selected @endif>****</option>
                                        <option value="5" @if($detail->rate == 5) selected @endif>*****</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="1" @if($detail->status == 1) selected @endif>Hiển thị</option>
                                        <option value="0" @if($detail->status == 0) selected @endif>Ẩn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                <a href="/admin/rate" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
<div class="modal fade" id="uploadImage" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <form enctype="multipart/form-data" id="formUpload" method="post">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Thêm ảnh khác</h4>
      </div>
      <div class="modal-body">
        <input type="file" name="files[]" id="files" multiple required/>
        <p style="margin-top:10px">(<i>Có thể chọn nhiều ảnh cùng lúc. Ảnh có đuôi: jpeg,png,jpg,gif</i>)</p>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="btnUploadMultiple"><i class="fa fa-upload" aria-hidden="true"></i> Upload</button>
        <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times" aria-hidden="true"></i> Đóng</button>
      </div>
        </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
$(document).ready(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $('#formUpload').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        let TotalFiles = $('#files')[0].files.length; //Total files
        let files = $('#files')[0];
        for (let i = 0; i < TotalFiles; i++) {
            formData.append('files' + i, files.files[i]);
        }
        formData.append('TotalFiles', TotalFiles);
        $.ajax({
            type:'POST',
            url: "/admin/rate/upload",
            data: formData,
            cache:false,
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: function () {
                $('#btnUploadMultiple').prop('disabled', true);
            },
            success: (data) => {
                this.reset();
                $('#btnUploadMultiple').prop('disabled', false);
                $('#uploadImage').modal('hide');
                var html = "";
                for(var i = 0; i< data.length; i++){
                    html +='<div class="col-md-3 item'+i+'"><img src="'+data[i]+'"><input type="hidden" value="'+data[i]+'" name="imageOther[]"><a data-id="'+i+'" href="javascript:;" title="Xóa ảnh" class="delete_image"><i class="fa fa-times" aria-hidden="true"></i></a></div>';
                }
                $('.list_image').append(html);
            },
            error: function(data){
                alert(data.responseJSON.errors.files[0]);
            }
        });
    });
});
</script>
<style type="text/css">
        .box-category{
        height: 300px;
        overflow-y: scroll;
        width: 100%;
        padding:0px;
    }
    .box-category label{
        display: block;
        overflow: hidden;
    }
    .box-category label.parent{
        font-weight: normal;
        margin-left: 30px;
    }
    .box-category input{
        float: left;
    }
</style>
@endsection