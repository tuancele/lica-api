<div class="input-group">
    <input type="text" class="form-control" name="@if(isset($name)){{$name}}@else{{'image'}}@endif" id="ImageUrl{{$number}}" value="@if(isset($image)){{$image}}@endif">
    <span class="input-group-btn">
        <input type="file" id="r2-file-input-{{$number}}" style="display: none;" accept="image/*">
        <button type="button" class="btn btn-default" id="r2-upload-trigger-{{$number}}"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
    </span>
</div>
<div class="avantar{{$number}} showimage" style="margin-top:10px">
    @if(isset($image) && $image)
        <img src="{{getImage($image)}}" style="width:100%">
    @endif
</div>

<script type="text/javascript" src="/public/js/r2-upload-preview.js"></script>
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
            previewItemClass: 'r2-preview-item',
            hiddenInputName: inputName,
            uploadRoute: "{{ route('r2.upload') }}",
            folder: folder,
            maxFiles: 1,
            onUploadStart: function() {
                $('#r2-upload-trigger-' + number).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            },
            onUploadComplete: function(urls) {
                $('#r2-upload-trigger-' + number).prop('disabled', false).html('<i class="fa fa-folder-open-o"></i>');
                if (urls && urls.length > 0) {
                    const url = urls[0].url || urls[0];
                    $('#ImageUrl' + number).val(url);
                    $('.avantar' + number + ' .showimage').html('<img src="' + url + '" style="width:100%">');
                }
            },
            onUploadError: function(msg) {
                alert('Lỗi upload: ' + msg);
                $('#r2-upload-trigger-' + number).prop('disabled', false).html('<i class="fa fa-folder-open-o"></i>');
            },
            onPreviewAdd: function(file, url) {
                // Update the actual input field
                $('#ImageUrl' + number).val(url);
                // Update preview image
                $('.avantar' + number + ' .showimage').html('<img src="' + url + '" style="width:100%">');
            },
            onPreviewRemove: function() {
                $('#ImageUrl' + number).val('');
                $('.avantar' + number + ' .showimage').html('');
            }
        });
    }
});
</script>
