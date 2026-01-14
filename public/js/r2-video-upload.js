/**
 * Simple R2 video upload helper for single video file
 * Usage: initR2VideoUpload({ ...options })
 */

function initR2VideoUpload(options) {
    const settings = Object.assign({
        fileInputSelector: '',
        triggerSelector: '',
        previewContainerSelector: '',
        hiddenInputSelector: '',
        uploadRoute: '',
        folder: 'videos/products',
        maxSizeBytes: 30 * 1024 * 1024 // 30MB
    }, options || {});

    const $fileInput = $(settings.fileInputSelector);
    const $trigger = $(settings.triggerSelector);
    const $previewContainer = $(settings.previewContainerSelector);
    const $hiddenInput = $(settings.hiddenInputSelector);

    if (!$fileInput.length || !$trigger.length || !$hiddenInput.length) {
        console.error('[R2 Video] Missing required selectors', settings);
        return;
    }

    // Ensure preview container exists
    // Lưu ý:
    // - Nếu edit có sẵn <video> từ server → chỉ cần đảm bảo style/pointer-events.
    // - Nếu create (chưa có video) → KHÔNG tự tạo <video> trống, giữ icon "Thêm video".
    let $video = null;
    if ($previewContainer.length) {
        $video = $previewContainer.find('video');
        if ($video.length) {
            $video
                .attr('playsinline', true)
                .attr('muted', true)
                .css({
                    width: '100%',
                    height: '100%',
                    objectFit: 'cover',
                    borderRadius: '4px',
                    pointerEvents: 'none'
                });
        } else {
            $video = null; // sẽ tạo khi upload xong
        }
    }

    $trigger.on('click', function (e) {
        e.preventDefault();
        $fileInput.trigger('click');
    });

    $fileInput.on('change', function () {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) return;

        // Validate type
        if (!file.type || !file.type.match(/^video\//i)) {
            alert('Vui lòng chọn file video hợp lệ (mp4, webm, mov...).');
            $fileInput.val('');
            return;
        }

        // Validate size
        if (file.size > settings.maxSizeBytes) {
            alert('Dung lượng video tối đa 30MB. Vui lòng chọn file nhỏ hơn.');
            $fileInput.val('');
            return;
        }

        // Local preview
        if ($video && $video.length) {
            const url = URL.createObjectURL(file);
            $video.attr('src', url);
            $previewContainer.show();
        }

        // Upload to R2
        if (!settings.uploadRoute) {
            console.error('[R2 Video] uploadRoute missing');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('folder', settings.folder);

        const originalText = $trigger.html();
        $trigger
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Đang upload video...');

        $.ajax({
            url: settings.uploadRoute,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                if (res && res.url) {
                    $hiddenInput.val(res.url);
                    // Nếu chưa có video element (trang create), tạo mới
                    if (!$video || !$video.length) {
                        $video = $('<video />')
                            .attr('playsinline', true)
                            .attr('muted', true)
                            .css({
                                width: '100%',
                                height: '100%',
                                objectFit: 'cover',
                                borderRadius: '4px',
                                pointerEvents: 'none'
                            });
                        $previewContainer.append($video);
                    }

                    $video.attr('src', res.url);

                    // Ẩn placeholder, hiện video
                    $previewContainer.find('.video-upload-inner').hide();
                    $video.show();
                } else {
                    alert('Upload video thất bại. Vui lòng thử lại.');
                }
            },
            error: function (xhr) {
                let msg = 'Upload video thất bại. Vui lòng thử lại.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            },
            complete: function () {
                $trigger
                    .prop('disabled', false)
                    .html(originalText);
                $fileInput.val('');
            }
        });
    });
}

