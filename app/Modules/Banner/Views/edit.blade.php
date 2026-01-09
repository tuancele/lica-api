@extends('Layout::layout')
@section('title','Sửa banner')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa banner',
])
<style>
    .image-upload-wrapper {
        border: 1px dashed #d8d8d8;
        border-radius: 4px;
        padding: 20px;
        text-align: center;
        background: #fff;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    .image-upload-wrapper:hover {
        border-color: #ee4d2d;
        background: rgba(238, 77, 45, 0.02);
    }
    .preview-box img {
        max-width: 100%;
        max-height: 200px;
        margin-bottom: 15px;
        border-radius: 4px;
        border: 1px solid #eee;
    }
    .upload-hint {
        color: #ee4d2d;
        font-weight: 500;
        font-size: 13px;
    }
    .remove-image {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.5);
        color: #fff;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
    }
    .image-upload-wrapper.has-img .remove-image {
        display: flex;
    }
    .image-upload-wrapper.has-img .upload-hint {
        display: none;
    }
</style>

<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/banner/edit">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Tiêu đề</label>
                            <input  type="text" name="name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống" value="{{$detail->name}}">
                            <input type="hidden" name="id" value="{{$detail->id}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Liên kết</label>
                            <input type="text" name="link" class="form-control" value="{{$detail->link}}">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Nhóm banner</label>
                                    <select class="form-control" name="cat_id">
                                        <option value="1" @if($detail->cat_id == 1) selected @endif>Banner trang chủ</option>
                                        <option value="2" @if($detail->cat_id == 2) selected @endif>Banner sidebar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="1" @if($detail->status == 1) selected="" @endif>Hiển thị</option>
                                        <option value="0" @if($detail->status == 0) selected="" @endif>Ẩn</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <label class="fw-700">Hình ảnh đại diện</label>
                        <div class="image-upload-wrapper {{ $detail->image ? 'has-img' : '' }}" id="r2-upload-trigger">
                            <div class="preview-box" id="r2-preview">
                                <img src="{{ $detail->image ? getImage($detail->image) : asset('public/admin/no-image.png') }}" id="preview-img">
                            </div>
                            <div class="upload-hint" id="upload-text">
                                <i class="fa fa-camera fa-2x"></i><br>
                                <span>Đổi hình ảnh</span>
                            </div>
                            <div class="remove-image" id="r2-remove"><i class="fa fa-times"></i></div>
                            <input type="file" id="r2-file-input" style="display: none;" accept="image/*">
                            <input type="hidden" name="image" id="r2-image-url" value="{{$detail->image}}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action text-right">
            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
            <a href="/admin/banner" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
        </div>
    </form>
</section>

<script type="text/javascript">
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('#r2-upload-trigger').click(function(e) {
        if (!$(e.target).closest('#r2-remove, label').length) {
            $('#r2-file-input').trigger('click');
        }
    });

    $('#r2-file-input').on('click', function(e) {
        e.stopPropagation();
    });

    $('#r2-file-input').change(function() {
        let file = this.files[0];
        if(!file) return;

        let formData = new FormData();
        formData.append('files[]', file);
        formData.append('TotalFiles', 1);

        $.ajax({
            type: 'POST',
            url: "{{ route('product.upload') }}",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#upload-text').show().html('<i class="fa fa-spinner fa-spin fa-2x"></i><br><span>Đang tải...</span>');
            },
            success: function(data) {
                // Kiểm tra nếu data trả về là string (URL đơn) hoặc mảng
                let url = '';
                if(Array.isArray(data) && data.length > 0) {
                    url = data[0];
                } else if (typeof data === 'string' && data.length > 0) {
                    url = data;
                }

                if(url) {
                    $('#preview-img').attr('src', url);
                    $('#r2-image-url').val(url);
                    $('#r2-upload-trigger').addClass('has-img');
                    $('#upload-text').hide();
                } else {
                    console.log('Upload response:', data);
                    alert('Lỗi: Hệ thống không trả về đường dẫn ảnh. Vui lòng thử lại.');
                    resetBtn();
                }
            },
            error: function(xhr) {
                console.log('Upload error:', xhr);
                alert('Lỗi kết nối đến máy chủ. Mã lỗi: ' + xhr.status);
                resetBtn();
            }
        });
    });

    $('#r2-remove').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#preview-img').attr('src', "{{asset('public/admin/no-image.png')}}");
        $('#r2-image-url').val('');
        $('#r2-upload-trigger').removeClass('has-img');
        $('#upload-text').show();
        resetBtn();
    });

    function resetBtn() {
        $('#upload-text').html('<i class="fa fa-camera fa-2x"></i><br><span>Thêm hình ảnh</span>').show();
        $('#r2-file-input').val('');
    }
});
</script>
@endsection
