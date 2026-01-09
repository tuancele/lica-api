@extends('Layout::layout')
@section('title','Thêm thương hiệu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm thương hiệu',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('brand.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('Layout::title')
                                @include('Layout::slug')
                                @include('Layout::content')
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                       Hình ảnh khác
                        <a href="javascript:;"  class="pull-right" data-toggle="modal" data-target="#uploadImage"><i class="fa fa-plus-square" aria-hidden="true"></i> Thêm hình ảnh</a>
                    </div>
                    <div class="panel-body">
                        <div class="list_image row">
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy sản phẩm trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status')
                    </div>
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['number' => 1,'title' => 'Logo thương hiệu','name' => 'logo'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['number' => 2,'title' => 'Ảnh đại diện','name' => 'image'])
                </div>
                <div class="panel panel-default">
                    @include('Layout::image',['number' => 3,'title' => 'Banner','name' => 'banner'])
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('brand')])
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
        <button type="submit" class="btn btn-primary"><i class="fa fa-upload" aria-hidden="true"></i> Upload</button>
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
            url: "/admin/brand/upload",
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
    .icon .box_image{
        height: 100px;
        overflow: hidden;
        text-align: center;
        margin-bottom: 5px;
    }
    .icon .box_image img{
        display: inline-block;
        height: 100%;
        width: inherit !important;
    }
    .list_image img{
        height: 100% !important;
        max-width: 100%;
        display: inline-block;
    }
    .list_image div{
        height: 120px;
        margin-bottom: 20px;
        text-align: center;
    }
</style>
@endsection