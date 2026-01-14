<div class="panel-body">
    <label class="fw-700">@if(isset($title)){{$title}}@else Hình ảnh đại diện @endif</label>
    <div class="form-group avantar{{$number}}">
        <img src="@if(isset($image) && $image){{getImage($image)}}@else{{asset('public/admin/no-image.png')}}@endif" class="img-responsive" alt="" id="r2-preview-img-{{$number}}">
    </div>
    <div class="form-group" style="text-align: center;">
        <input type="hidden" id="ImageUrl{{$number}}" name="@if(isset($name)){{$name}}@else{{'image'}}@endif" value="@if(isset($image)){{$image}}@endif" class="form-control medium_input pull-left">
        <input type="file" id="r2-file-input-{{$number}}" style="display: none;" accept="image/*">
        <button type="button" class="btn btn-default btn_image btn-sm" id="r2-upload-trigger-{{$number}}"><i class="fa fa-folder-open-o" aria-hidden="true"></i> Chọn ảnh</button>
        <button type="button" class="btn btn-danger btn_delete_image btn-sm" id="r2-remove-{{$number}}" number="{{$number}}"><i class="fa fa-times" aria-hidden="true"></i> Xóa ảnh</button>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    if (typeof initR2UploadPreview === 'function') {
        const number = {{$number}};
        const folder = '{{isset($folder) ? $folder : 'image'}}';
        const inputName = '@if(isset($name)){{$name}}@else{{'image'}}@endif';
        
        initR2UploadPreview({
            fileInputSelector: '#r2-file-input-' + number,
            triggerSelector: '#r2-upload-trigger-' + number,
            previewContainerSelector: '.avantar' + number,
            previewItemClass: 'none',
            hiddenInputName: inputName,
            uploadRoute: "{{ route('r2.upload') }}",
            folder: folder,
            maxFiles: 1,
            onUploadStart: function() {
                $('#r2-upload-trigger-' + number).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang upload...');
            },
            onUploadComplete: function(urls) {
                $('#r2-upload-trigger-' + number).prop('disabled', false).html('<i class="fa fa-folder-open-o"></i> Chọn ảnh');
                if (urls && urls.length > 0) {
                    $('#r2-preview-img-' + number).attr('src', urls[0].url || urls[0]);
                }
            },
            onUploadError: function(msg) {
                alert('Lỗi upload: ' + msg);
                $('#r2-upload-trigger-' + number).prop('disabled', false).html('<i class="fa fa-folder-open-o"></i> Chọn ảnh');
            },
            onPreviewAdd: function(file, url) {
                $('#r2-preview-img-' + number).attr('src', url);
            },
            onPreviewRemove: function() {
                $('#r2-preview-img-' + number).attr('src', "{{asset('public/admin/no-image.png')}}");
                $('#ImageUrl' + number).val('');
            }
        });
        
        // Handle remove button
        $('#r2-remove-' + number).on('click', function() {
            if (confirm('Bạn có chắc muốn xóa ảnh này?')) {
                $('#r2-preview-img-' + number).attr('src', "{{asset('public/admin/no-image.png')}}");
                $('#ImageUrl' + number).val('');
                // Clear any pending uploads
                const $form = $(this).closest('form');
                if ($form.length) {
                    $form.find('input[name="' + inputName + '"]').val('');
                }
            }
        });
    }
});
</script>
