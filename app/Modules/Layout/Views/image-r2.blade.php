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
        <div id="r2-progress-wrapper-{{$number}}" style="display:none;margin-top:8px;">
            <div class="progress" style="height:6px;margin-bottom:4px;">
                <div id="r2-progress-bar-{{$number}}" class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%;"></div>
            </div>
            <small id="r2-progress-text-{{$number}}" style="font-size:11px;color:#666;">Đang upload...</small>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    const number = {{$number}};
    const folder = '{{isset($folder) ? $folder : 'image'}}';
    const inputName = '@if(isset($name)){{$name}}@else{{'image'}}@endif';
    const $fileInput = $('#r2-file-input-' + number);
    const $trigger = $('#r2-upload-trigger-' + number);
    const $previewImg = $('#r2-preview-img-' + number);
    const $hiddenInput = $('#ImageUrl' + number);
    const $progressWrapper = $('#r2-progress-wrapper-' + number);
    const $progressBar = $('#r2-progress-bar-' + number);
    const $progressText = $('#r2-progress-text-' + number);

    function resetProgress() {
        $progressBar.css('width', '0%').attr('aria-valuenow', 0);
        $progressText.text('Đang upload...');
        $progressWrapper.hide();
    }

    function uploadToR2(file) {
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('folder', folder);
        formData.append('convert_webp', 'true');
        formData.append('quality', '85');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/v1/media/upload', true);

        const token = $('meta[name="csrf-token"]').attr('content');
        if (token) {
            xhr.setRequestHeader('X-CSRF-TOKEN', token);
        }
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        $trigger.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang upload...');
        $progressWrapper.show();
        $progressBar.css('width', '0%').attr('aria-valuenow', 0);
        $progressText.text('Đang upload 0%');

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                $progressBar.css('width', percent + '%').attr('aria-valuenow', percent);
                $progressText.text('Đang upload ' + percent + '%');
            }
        };

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                $trigger.prop('disabled', false).html('<i class="fa fa-folder-open-o"></i> Chọn ảnh');
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const resp = JSON.parse(xhr.responseText || '{}');
                        if (resp && resp.success && resp.url) {
                            $hiddenInput.val(resp.url);
                            $previewImg.attr('src', resp.url);
                            $progressText.text('Hoàn thành');
                            setTimeout(function() {
                                $progressWrapper.fadeOut(300);
                            }, 600);
                        } else {
                            alert(resp.message || 'Upload thất bại, vui lòng thử lại.');
                            resetProgress();
                        }
                    } catch (e) {
                        alert('Upload thất bại (lỗi parse response), vui lòng thử lại.');
                        resetProgress();
                    }
                } else {
                    alert('Upload thất bại (HTTP ' + xhr.status + '), vui lòng thử lại.');
                    resetProgress();
                }
            }
        };

        xhr.onerror = function () {
            $trigger.prop('disabled', false).html('<i class="fa fa-folder-open-o"></i> Chọn ảnh');
            alert('Lỗi mạng khi upload, vui lòng thử lại.');
            resetProgress();
        };

        xhr.send(formData);
    }

    // Trigger choose file
    $trigger.on('click', function () {
        $fileInput.trigger('click');
    });

    // On file selected
    $fileInput.on('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) return;
        uploadToR2(file);
        // clear input so selecting same file again still triggers change
        this.value = '';
    });

    // Handle remove button
    $('#r2-remove-' + number).on('click', function() {
        if (confirm('Bạn có chắc muốn xóa ảnh này?')) {
            $previewImg.attr('src', "{{asset('public/admin/no-image.png')}}");
            $hiddenInput.val('');
            resetProgress();
            const $form = $(this).closest('form');
            if ($form.length) {
                $form.find('input[name="' + inputName + '"]').val('');
            }
        }
    });
});
</script>
