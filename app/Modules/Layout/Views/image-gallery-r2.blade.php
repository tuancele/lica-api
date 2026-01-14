<div class="panel panel-default">
    <div class="panel-heading">
       Hình ảnh khác
        <a href="javascript:;" class="pull-right" id="r2-gallery-trigger-{{$number}}"><i class="fa fa-plus-square" aria-hidden="true"></i> Thêm hình ảnh</a>
    </div>
    <div class="panel-body">
        <div class="list_image row" id="r2-gallery-container-{{$number}}">
            @if(isset($gallery) && !empty($gallery))
                @php
                    $galleryArray = is_array($gallery) ? $gallery : json_decode($gallery, true);
                    $galleryArray = $galleryArray ?: [];
                @endphp
                @foreach($galleryArray as $idx => $img)
                    <div class="col-md-3 item{{$idx+1}} has-img" data-existing="true">
                        <img src="{{getImage($img)}}">
                        <input type="hidden" value="{{getImage($img)}}" name="imageOther[]">
                        <a href="javascript:;" title="Xóa ảnh" class="delete_image"><i class="fa fa-times" aria-hidden="true"></i></a>
                    </div>
                @endforeach
            @endif
        </div>
        <input type="file" id="r2-gallery-input-{{$number}}" multiple style="display: none;" accept="image/*">
    </div>
</div>

<script type="text/javascript" src="/public/js/r2-upload-preview.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    if (typeof initR2UploadPreview === 'function') {
        const number = {{$number}};
        const folder = '{{isset($folder) ? $folder : 'image'}}';
        let itemCounter = $('.list_image#r2-gallery-container-' + number + ' .has-img').length;
        
        // Trigger file selection
        $('#r2-gallery-trigger-' + number).on('click', function() {
            $('#r2-gallery-input-' + number).trigger('click');
        });
        
        initR2UploadPreview({
            fileInputSelector: '#r2-gallery-input-' + number,
            triggerSelector: '#r2-gallery-trigger-' + number,
            previewContainerSelector: '#r2-gallery-container-' + number,
            previewItemClass: 'col-md-3',
            hiddenInputName: 'imageOther[]',
            uploadRoute: "{{ route('r2.upload') }}",
            folder: folder,
            maxFiles: 20,
            onUploadStart: function(totalFiles) {
                $('#r2-gallery-trigger-' + number).html('<i class="fa fa-spinner fa-spin"></i> Đang upload...').prop('disabled', true);
            },
            onUploadComplete: function(urls) {
                $('#r2-gallery-trigger-' + number).html('<i class="fa fa-plus-square"></i> Thêm hình ảnh').prop('disabled', false);
            },
            onUploadError: function(msg) {
                alert('Lỗi upload: ' + msg);
                $('#r2-gallery-trigger-' + number).html('<i class="fa fa-plus-square"></i> Thêm hình ảnh').prop('disabled', false);
            },
            onPreviewAdd: function(file, url, index) {
                itemCounter++;
                const html = `
                    <div class="col-md-3 item${itemCounter} has-img">
                        <img src="${url}">
                        <input type="hidden" value="${url}" name="imageOther[]">
                        <a href="javascript:;" title="Xóa ảnh" class="delete_image"><i class="fa fa-times" aria-hidden="true"></i></a>
                    </div>
                `;
                $('#r2-gallery-container-' + number).append(html);
            },
            onPreviewRemove: function() {
                // Handled by delete_image click handler
            }
        });
        
        // Handle delete image
        $(document).on('click', '#r2-gallery-container-' + number + ' .delete_image', function() {
            if (confirm('Bạn có chắc muốn xóa ảnh này?')) {
                $(this).closest('.has-img').remove();
            }
        });
    }
});
</script>

<style type="text/css">
    #r2-gallery-container-{{$number}} img {
        height: 100% !important;
        max-width: 100%;
        display: inline-block;
    }
    #r2-gallery-container-{{$number}} .has-img {
        height: 120px;
        margin-bottom: 20px;
        text-align: center;
        position: relative;
    }
    #r2-gallery-container-{{$number}} .delete_image {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0,0,0,0.5);
        color: #fff;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
</style>
