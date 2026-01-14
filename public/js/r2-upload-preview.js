/**
 * R2 Upload Preview Component
 * Handles local preview and deferred upload to Cloudflare R2 with WebP conversion.
 */

// Logging utility
const R2Logger = {
    logs: [],
    maxLogs: 1000,
    
    log: function(level, message, data = null) {
        const timestamp = new Date().toISOString();
        const logEntry = {
            timestamp: timestamp,
            level: level,
            message: message,
            data: data
        };
        
        this.logs.push(logEntry);
        if (this.logs.length > this.maxLogs) {
            this.logs.shift(); // Remove oldest log
        }
        
        // Also log to console
        const consoleMethod = level === 'error' ? 'error' : (level === 'warn' ? 'warn' : 'log');
        const prefix = `[R2-${level.toUpperCase()}] ${timestamp}`;
        if (data) {
            console[consoleMethod](prefix, message, data);
        } else {
            console[consoleMethod](prefix, message);
        }
        
        // Store in window for easy access
        window.R2Logs = this.logs;
    },
    
    info: function(message, data) { this.log('info', message, data); },
    warn: function(message, data) { this.log('warn', message, data); },
    error: function(message, data) { this.log('error', message, data); },
    
    export: function() {
        return JSON.stringify(this.logs, null, 2);
    },
    
    download: function() {
        const blob = new Blob([this.export()], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `r2-logs-${new Date().toISOString().replace(/:/g, '-')}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};

function initR2UploadPreview(options) {
    const settings = Object.assign({
        fileInputSelector: '',
        triggerSelector: '',
        previewContainerSelector: '',
        previewItemClass: 'has-img',
        hiddenInputName: 'images[]',
        uploadRoute: '',
        folder: 'image',
        maxFiles: 9,
        convertWebP: true,
        quality: 85,
        onUploadStart: null,
        onUploadComplete: null,
        onUploadError: null,
        onPreviewAdd: null,
        onPreviewRemove: null
    }, options);

    let pendingFiles = [];
    let previewUrls = [];

    R2Logger.info('Initializing R2 Upload Preview', { settings: settings });

    const $fileInput = $(settings.fileInputSelector);
    const $trigger = $(settings.triggerSelector);
    const $container = $(settings.previewContainerSelector);
    
    if (!$fileInput.length) {
        R2Logger.error('File input not found', { selector: settings.fileInputSelector });
        return null;
    }
    
    const $form = $fileInput.closest('form');
    if (!$form.length) {
        R2Logger.error('Form not found', { fileInputSelector: settings.fileInputSelector });
        return null;
    }
    
    R2Logger.info('Form found', { formId: $form.attr('id'), ajaxUrl: $form.attr('ajax') });

    // Trigger file selection
    // Fix: Prevent infinite recursion when trigger contains file input
    $trigger.on('click', function(e) {
        // If the click target is the file input itself or its children, don't handle
        const target = e.target;
        if (target === $fileInput[0] || $fileInput[0].contains(target)) {
            return;
        }
        // Stop event propagation to prevent bubbling
        e.stopPropagation();
        // Use native DOM click instead of jQuery trigger to avoid event system issues
        if ($fileInput.length > 0 && $fileInput[0]) {
            $fileInput[0].click();
        }
    });
    
    // Prevent event bubbling from file input
    $fileInput.on('click', function(e) {
        e.stopPropagation();
    });

    // Handle file selection
    $fileInput.on('change', function() {
        const files = this.files;
        if (!files.length) return;

        for (let i = 0; i < files.length; i++) {
            const currentCount = $container.find('.has-img').length;
            if (currentCount >= settings.maxFiles) {
                alert(`Bạn chỉ có thể upload tối đa ${settings.maxFiles} ảnh.`);
                break;
            }

            const file = files[i];
            
            // Validate type
            if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/i)) {
                alert('File ' + file.name + ' không phải là ảnh hợp lệ.');
                continue;
            }

            const url = URL.createObjectURL(file);
            const id = 'r2-file-' + Date.now() + '-' + i;

            R2Logger.info('Creating preview for file', {
                fileName: file.name,
                fileSize: file.size,
                fileType: file.type,
                previewId: id,
                blobUrl: url
            });

            // Create Preview Element
            const previewHtml = `
                <div class="${settings.previewItemClass} has-img" id="${id}" data-pending="true" data-file-name="${file.name}" data-file-size="${file.size}" data-file-type="${file.type}">
                    <img src="${url}">
                    <input type="hidden" name="${settings.hiddenInputName}" value="" class="r2-pending-url">
                    <a href="javascript:void(0)" class="remove-btn" data-r2-id="${id}"><i class="fa fa-times"></i></a>
                </div>
            `;

            // Append preview
            const $previewItem = $(previewHtml);
            if ($trigger.length) {
                $trigger.before($previewItem);
                R2Logger.info('Preview inserted before trigger', { id: id });
            } else {
                $container.append($previewItem);
                R2Logger.info('Preview appended to container', { id: id });
            }
            
            // Store file object in jQuery data for later retrieval
            $previewItem.data('r2-file-object', file);
            $previewItem.data('r2-blob-url', url);

            pendingFiles.push({ id: id, file: file });
            previewUrls.push(url);
            
            R2Logger.info('File added to pending list', {
                totalPending: pendingFiles.length,
                fileId: id
            });

            if (settings.onPreviewAdd) settings.onPreviewAdd(file, url, pendingFiles.length - 1);
        }

        $(this).val(''); // Reset input
        updateTriggerText();
    });

    // Handle removal
    $container.on('click', '.remove-btn', function() {
        const id = $(this).data('r2-id');
        if (id) {
            pendingFiles = pendingFiles.filter(f => f.id !== id);
        }
        $(this).closest('.has-img').remove();
        updateTriggerText();
        if (settings.onPreviewRemove) settings.onPreviewRemove();
    });

    function updateTriggerText() {
        const count = $container.find('.has-img').length;
        const span = $trigger.find('span');
        if (span.length) {
            span.text(`Thêm hình ảnh (${count}/${settings.maxFiles})`);
        }
    }

    // Helper function to find pending items from DOM (more reliable than array)
    const findPendingItemsFromDOM = function() {
        const pendingItems = [];
        $container.find('.has-img[data-pending="true"]').each(function() {
            const $item = $(this);
            const id = $item.attr('id');
            const inputVal = $item.find('input').val();
            const imgSrc = $item.find('img').attr('src');
            
            // Check if this is a pending item (has pending flag or empty input or blob URL)
            if (id && (inputVal === '' || inputVal.indexOf('blob:') !== -1 || imgSrc.indexOf('blob:') !== -1)) {
                // Try to get file object from jQuery data (stored when preview was created)
                let file = $item.data('r2-file-object');
                
                // If not in data, try to find from pendingFiles array
                if (!file) {
                    const pendingFile = pendingFiles.find(f => f.id === id);
                    if (pendingFile) {
                        file = pendingFile.file;
                    }
                }
                
                if (file) {
                    pendingItems.push({
                        id: id,
                        file: file,
                        $item: $item
                    });
                    R2Logger.info('Found pending item in DOM', {
                        id: id,
                        fileName: file.name,
                        hasFileObject: !!file
                    });
                } else {
                    // File not found - this is a problem
                    R2Logger.error('Found pending item in DOM but cannot retrieve file object', {
                        id: id,
                        inputValue: inputVal,
                        imgSrc: imgSrc
                    });
                }
            }
        });
        return pendingItems;
    };
    
    // Intercept form submit - Must be registered early to catch before $.validate()
    // Use both submit event and button click to ensure we catch it
    const handleFormSubmit = function(e) {
        // Check both array and DOM for pending items
        const domPendingItems = findPendingItemsFromDOM();
        const totalPending = Math.max(pendingFiles.length, domPendingItems.length);
        
        R2Logger.info('Form submit triggered', {
            pendingFilesCount: pendingFiles.length,
            domPendingCount: domPendingItems.length,
            totalPending: totalPending,
            isUploading: $form.data('r2-uploading'),
            eventType: e.type,
            target: e.target ? e.target.tagName : 'unknown'
        });
        
        if (totalPending > 0) {
            // Check if we are already uploading to prevent loop
            if ($form.data('r2-uploading')) {
                R2Logger.warn('Form already uploading, allowing normal submit');
                // Allow form to submit normally if already uploading (this means upload completed)
                return true;
            }

            e.preventDefault();
            e.stopPropagation();
            R2Logger.info('Prevented default form submit, starting upload process', {
                pendingFilesFromArray: pendingFiles.length,
                pendingFilesFromDOM: domPendingItems.length
            });
            
            // If pendingFiles array is empty but DOM has pending items, rebuild array
            if (pendingFiles.length === 0 && domPendingItems.length > 0) {
                R2Logger.warn('pendingFiles array is empty but DOM has pending items. Rebuilding array.');
                pendingFiles = domPendingItems.map(item => ({
                    id: item.id,
                    file: item.file
                }));
            }
            
            $form.data('r2-uploading', true);
            if (settings.onUploadStart) settings.onUploadStart(pendingFiles.length);

            uploadAllFiles().then(urls => {
                R2Logger.info('All files uploaded successfully', { urls: urls, count: urls.length });
                
                // CRITICAL: Force update all inputs from upload results
                // Match upload results with pending files by itemId
                const urlMap = {};
                urls.forEach(result => {
                    if (result.itemId && result.url) {
                        urlMap[result.itemId] = result.url;
                    }
                });
                
                R2Logger.info('URL map created for force update', {
                    urlMap: urlMap,
                    pendingFilesCount: pendingFiles.length
                });
                
                pendingFiles.forEach((item) => {
                    const url = urlMap[item.id];
                    if (url) {
                        const $item = $('#' + item.id);
                        if ($item.length > 0) {
                            const $input = $item.find('input[name="imageOther[]"]');
                            const $img = $item.find('img');
                            
                            if ($input.length > 0) {
                                // Force update input value
                                const oldVal = $input.val();
                                $input.val(url);
                                R2Logger.info('Force updated input from upload result', {
                                    itemId: item.id,
                                    fileName: item.file.name,
                                    oldValue: oldVal,
                                    newValue: url,
                                    inputValue: $input.val()
                                });
                            }
                            
                            if ($img.length > 0) {
                                $img.attr('src', url);
                            }
                            
                            // Remove pending flag
                            $item.removeAttr('data-pending');
                            if ($input.length > 0) {
                                $input.removeClass('r2-pending-url');
                            }
                        }
                    } else {
                        R2Logger.warn('No URL found for item in upload results', {
                            itemId: item.id,
                            fileName: item.file.name
                        });
                    }
                });
                
                // Wait a bit for DOM to update and return urls for next promise
                return new Promise(resolve => {
                    setTimeout(() => resolve(urls), 300);
                });
            }).then((urls) => {
                // Verify all inputs are updated before submitting
                let allUpdated = true;
                const updateStatus = [];
                pendingFiles.forEach(item => {
                    const $item = $('#' + item.id);
                    if ($item.length === 0) {
                        R2Logger.error('Preview item not found during verification', {
                            itemId: item.id,
                            fileName: item.file.name
                        });
                        updateStatus.push({
                            id: item.id,
                            fileName: item.file.name,
                            error: 'Item not found in DOM',
                            isValid: false
                        });
                        allUpdated = false;
                        return;
                    }
                    
                    // Check both preview item input and actual form input
                    let $input = $item.find('input[name="' + settings.hiddenInputName + '"]');
                    let inputVal = $input.length > 0 ? $input.val() : '';
                    
                    // If preview item input is empty or blob, check form input
                    if (!inputVal || inputVal.indexOf('blob:') !== -1) {
                        const $formInput = $form.find('input[name="' + settings.hiddenInputName + '"]');
                        if ($formInput.length > 0) {
                            inputVal = $formInput.val();
                            $input = $formInput;
                            R2Logger.info('Using form input for verification', {
                                inputId: $formInput.attr('id'),
                                inputName: $formInput.attr('name'),
                                inputValue: inputVal
                            });
                        }
                    }
                    
                    // Also check by ID pattern for compatibility (especially for Slider with id="r2-image-url")
                    if ((!inputVal || inputVal.indexOf('blob:') !== -1) && settings.hiddenInputName === 'image') {
                        const $idInput = $form.find('input[id="r2-image-url"], input[id^="ImageUrl"]');
                        if ($idInput.length > 0) {
                            inputVal = $idInput.val();
                            $input = $idInput;
                            R2Logger.info('Using input by ID pattern for verification', {
                                inputId: $idInput.attr('id'),
                                inputValue: inputVal
                            });
                        }
                    }
                    
                    const imgSrc = $item.find('img').attr('src');
                    const hasPendingFlag = $item.attr('data-pending') !== undefined;
                    const status = {
                        id: item.id,
                        fileName: item.file.name,
                        inputValue: inputVal,
                        imgSrc: imgSrc,
                        hasPendingFlag: hasPendingFlag,
                        isValid: !!(inputVal && inputVal !== '' && inputVal.indexOf('blob:') === -1 && inputVal.indexOf('no-image.png') === -1 && !hasPendingFlag)
                    };
                    updateStatus.push(status);
                    
                    if (!status.isValid) {
                        R2Logger.warn('Input not updated for item', status);
                        allUpdated = false;
                    } else {
                        R2Logger.info('Input updated successfully', status);
                    }
                });
                
                R2Logger.info('Input update verification', {
                    allUpdated: allUpdated,
                    status: updateStatus,
                    totalPending: pendingFiles.length,
                    validCount: updateStatus.filter(s => s.isValid).length
                });
                
                if (!allUpdated) {
                    R2Logger.error('Some inputs were not updated. Aborting form submit.', { updateStatus: updateStatus });
                    $form.data('r2-uploading', false);
                    if (settings.onUploadError) {
                        settings.onUploadError('Một số ảnh chưa được upload thành công. Vui lòng thử lại.');
                    }
                    return;
                }
                
                // Call onUploadComplete callback if provided
                if (settings.onUploadComplete && urls) {
                    settings.onUploadComplete(urls);
                }
                
                // Clear object URLs to free memory
                previewUrls.forEach(url => URL.revokeObjectURL(url));
                R2Logger.info('Cleared blob URLs', { count: previewUrls.length });
                
                // Clear pending files to prevent re-upload
                pendingFiles = [];
                previewUrls = [];
                
                // Wait longer and verify inputs multiple times to ensure they're updated
                // Use a recursive check with increasing delays
                let retryCount = 0;
                const maxRetries = 5;
                const checkAndSubmit = () => {
                    // Final verification: Check all imageOther inputs
                    const finalCheck = [];
                    const allImageOtherInputs = $form.find('input[name="imageOther[]"]');
                    
                    allImageOtherInputs.each(function() {
                        const $input = $(this);
                        const val = $input.val();
                        finalCheck.push({
                            value: val,
                            isEmpty: !val || val === '',
                            isBlob: val && val.indexOf('blob:') !== -1,
                            isNoImage: val && val.indexOf('no-image.png') !== -1,
                            isValid: val && val !== '' && val.indexOf('blob:') === -1 && val.indexOf('no-image.png') === -1
                        });
                    });
                    
                    const validInputs = finalCheck.filter(c => c.isValid);
                    
                    R2Logger.info('Input check before form submit', {
                        retryCount: retryCount,
                        totalInputs: finalCheck.length,
                        validInputs: validInputs.length,
                        details: finalCheck
                    });
                    
                    // If we have valid inputs or max retries reached, proceed
                    if (validInputs.length > 0 || retryCount >= maxRetries) {
                        // Check if form has ajax attribute (uses ControlPanel.js handler)
                        const ajaxUrl = $form.attr('ajax');
                        R2Logger.info('Preparing form submit', { ajaxUrl: ajaxUrl, validInputsCount: validInputs.length });
                        
                        if (ajaxUrl) {
                            // CRITICAL: Manually collect ALL imageOther[] inputs to ensure they're included
                            // This ensures both existing images and newly uploaded images are sent
                            const allImageOtherInputs = $form.find('input[name="imageOther[]"]');
                            const imageOtherValues = [];
                            
                            allImageOtherInputs.each(function() {
                                const $input = $(this);
                                const val = $input.val();
                                // Only include valid URLs (not empty, not blob, not no-image)
                                if (val && val !== '' && val.indexOf('blob:') === -1 && val.indexOf('no-image.png') === -1) {
                                    imageOtherValues.push(val);
                                }
                            });
                            
                            R2Logger.info('Collected all imageOther[] inputs', {
                                totalInputs: allImageOtherInputs.length,
                                validValues: imageOtherValues.length,
                                values: imageOtherValues
                            });
                            
                            // Serialize form normally first
                            let formData = $form.serialize();
                            
                            // Remove any existing imageOther[] from serialized data
                            // We'll add them manually to ensure all are included
                            formData = formData.replace(/&?imageOther%5B%5D=[^&]*/g, '');
                            
                            // Manually add ALL imageOther[] values
                            imageOtherValues.forEach(url => {
                                formData += (formData ? '&' : '') + 'imageOther[]=' + encodeURIComponent(url);
                            });
                            
                            // Get ALL session keys from upload results
                            const sessionKeys = [];
                            urls.forEach((urlObj, idx) => {
                                if (urlObj && urlObj.sessionKey) {
                                    if (sessionKeys.indexOf(urlObj.sessionKey) === -1) {
                                        sessionKeys.push(urlObj.sessionKey);
                                    }
                                }
                            });
                            
                            // Add ALL session keys to form data (comma-separated)
                            if (sessionKeys.length > 0) {
                                const allSessionKeys = sessionKeys.join(',');
                                formData += (formData ? '&' : '') + 'r2_session_key=' + encodeURIComponent(allSessionKeys);
                                R2Logger.info('Added R2 session keys to form data', {
                                    sessionKeys: sessionKeys,
                                    allSessionKeys: allSessionKeys,
                                    count: sessionKeys.length
                                });
                            }
                            
                            // Count imageOther[] in serialized data
                            const imageOtherMatches = (formData.match(/imageOther%5B%5D=/g) || []).length;
                            
                            R2Logger.info('Form data serialized', { 
                                dataLength: formData.length,
                                preview: formData.substring(0, 500) + '...',
                                imageOtherCountInData: imageOtherMatches,
                                validInputsCount: imageOtherValues.length,
                                hasSessionKey: sessionKeys.length > 0,
                                sessionKeys: sessionKeys,
                                match: imageOtherMatches === imageOtherValues.length ? 'OK' : 'MISMATCH',
                                imageOtherValues: imageOtherValues
                            });
                            
                            if (imageOtherMatches !== imageOtherValues.length) {
                                R2Logger.error('Mismatch between collected values and serialized data', {
                                    collectedCount: imageOtherValues.length,
                                    serializedCount: imageOtherMatches
                                });
                            }
                            
                            // Submit via AJAX using the same handler as ControlPanel.js
                            $.ajax({
                                type: 'post',
                                url: ajaxUrl,
                                data: formData,
                            beforeSend: function () {
                                R2Logger.info('AJAX request sending', { url: ajaxUrl });
                                $('.box_img_load_ajax').removeClass('hidden');
                            },
                            success: function (res) {
                                R2Logger.info('AJAX request successful', { response: res });
                                $('.box_img_load_ajax').addClass('hidden');
                                $form.data('r2-uploading', false);
                                if(res.status == 'error'){
                                    var errTxt = '';
                                    if(res.errors !== undefined) {
                                        Object.keys(res.errors).forEach(key => {
                                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                                        });
                                    } else {
                                        errTxt = res.message;
                                    }
                                    R2Logger.error('Server returned error', { errors: res.errors, message: res.message });
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error(errTxt, 'Thông báo');
                                    } else {
                                        alert('Lỗi: ' + errTxt);
                                    }
                                } else {
                                    R2Logger.info('Form submit successful', { alert: res.alert, url: res.url });
                                    if (typeof toastr !== 'undefined') {
                                        toastr.success(res.alert, 'Thông báo');
                                    } else {
                                        alert(res.alert);
                                    }
                                    if(res.url != ""){
                                        R2Logger.info('Redirecting to URL', { 
                                            url: res.url,
                                            gallery_count: res.gallery_count || 'unknown'
                                        });
                                        // Increase delay to 2000ms to ensure DB commit is complete
                                        // Also add cache busting if not already present
                                        let redirectUrl = res.url;
                                        if (redirectUrl.indexOf('?t=') === -1) {
                                            redirectUrl += (redirectUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
                                        }
                                        setTimeout(function () {
                                            // Force reload without cache
                                            window.location.href = redirectUrl;
                                            // Fallback: if redirect doesn't work, force reload
                                            setTimeout(function() {
                                                window.location.reload(true);
                                            }, 100);
                                        }, 2000);
                                    }
                                }
                            },
                            error: function(xhr, status, error){
                                R2Logger.error('AJAX request failed', {
                                    status: status,
                                    error: error,
                                    statusCode: xhr.status,
                                    statusText: xhr.statusText,
                                    responseText: xhr.responseText ? xhr.responseText.substring(0, 500) : null
                                });
                                $('.box_img_load_ajax').addClass('hidden');
                                $form.data('r2-uploading', false);
                                const errorMsg = 'Có lỗi xảy ra, xin vui lòng thử lại';
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(errorMsg, 'Thông báo');
                                } else {
                                    alert(errorMsg);
                                }
                            }
                        });
                    } else {
                        R2Logger.warn('No ajax attribute found, using regular form submit');
                        // Fallback: regular form submit
                        $form.data('r2-uploading', false);
                        $form[0].submit();
                    }
                    } else {
                        // Retry if inputs not ready yet
                        retryCount++;
                        if (retryCount < maxRetries) {
                            setTimeout(checkAndSubmit, 200 * retryCount); // Increasing delay
                        } else {
                            R2Logger.error('Max retries reached, submitting anyway', {
                                validInputs: validInputs.length,
                                totalInputs: finalCheck.length
                            });
                            // Submit anyway - session URLs should still work
                            checkAndSubmit();
                        }
                    }
                };
                
                // Start checking after initial delay
                setTimeout(checkAndSubmit, 300);
            }).catch(err => {
                $form.data('r2-uploading', false);
                if (settings.onUploadError) settings.onUploadError(err.message || err);
            });
            
            return false;
        }
    };
    
    // Register submit handler with high priority (early binding)
    // Use namespace to allow removal if needed
    $form.on('submit.r2upload', handleFormSubmit);
    
    // Also intercept button click as fallback (in case $.validate() prevents submit event)
    $form.find('button[type="submit"]').on('click.r2upload', function(e) {
        const domPendingItems = findPendingItemsFromDOM();
        const totalPending = Math.max(pendingFiles.length, domPendingItems.length);
        
        R2Logger.info('Submit button clicked', {
            pendingFilesCount: pendingFiles.length,
            domPendingCount: domPendingItems.length,
            totalPending: totalPending,
            isUploading: $form.data('r2-uploading')
        });
        
        if (totalPending > 0 && !$form.data('r2-uploading')) {
            R2Logger.info('Submit button clicked with pending files - preventing default', {
                pendingFilesCount: pendingFiles.length,
                domPendingCount: domPendingItems.length,
                totalPending: totalPending
            });
            
            // If pendingFiles array is empty but DOM has pending items, rebuild array
            if (pendingFiles.length === 0 && domPendingItems.length > 0) {
                R2Logger.warn('pendingFiles array is empty but DOM has pending items. Rebuilding array from DOM.');
                pendingFiles = domPendingItems.map(item => ({
                    id: item.id,
                    file: item.file
                }));
            }
            
            // Prevent default to stop $.validate() from processing
            e.preventDefault();
            e.stopPropagation();
            
            // Manually trigger form submit to ensure our handler gets called
            setTimeout(() => {
                if (!$form.data('r2-uploading')) {
                    R2Logger.info('Manually triggering form submit after button click');
                    $form.trigger('submit');
                }
            }, 50);
        }
    });

    async function uploadAllFiles() {
        const results = [];
        let uploadIndex = 0; // Track actual upload index for backend
        
        R2Logger.info('Starting uploadAllFiles', {
            pendingFilesCount: pendingFiles.length
        });
        
        for (let idx = 0; idx < pendingFiles.length; idx++) {
            const item = pendingFiles[idx];
            // Check if still in DOM
            const $item = $('#' + item.id);
            if ($item.length === 0) {
                R2Logger.warn('Skipping file (removed from DOM)', {
                    fileName: item.file.name,
                    itemId: item.id
                });
                continue;
            }
            
            // Verify item still has pending flag
            if ($item.attr('data-pending') !== 'true') {
                R2Logger.warn('Item no longer has pending flag, checking input value', {
                    fileName: item.file.name,
                    itemId: item.id,
                    inputValue: $item.find('input').val()
                });
            }

            R2Logger.info('Uploading file', {
                fileName: item.file.name,
                fileSize: item.file.size,
                fileType: item.file.type,
                uploadIndex: uploadIndex
            });

            const formData = new FormData();
            // Append file with indexed key for backend (files0, files1, etc.)
            formData.append('files' + uploadIndex, item.file);
            formData.append('TotalFiles', 1); // Upload one at a time
            formData.append('folder', settings.folder || 'image');
            formData.append('convert_webp', settings.convertWebP !== false ? '1' : '0');
            formData.append('quality', settings.quality || 85);
            
            console.log('FormData prepared. Folder:', settings.folder || 'image', 'ConvertWebP:', settings.convertWebP !== false, 'Index:', uploadIndex);
            console.log('File details:', {
                name: item.file.name,
                size: item.file.size,
                type: item.file.type,
                lastModified: item.file.lastModified
            });
            uploadIndex++;

            try {
                const response = await fetch(settings.uploadRoute, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    let errorMessage = 'Upload failed';
                    try {
                        const errorData = await response.json();
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                    }
                    throw new Error(errorMessage);
                }
                
                const data = await response.json();
                R2Logger.info('Server response received', {
                    fileName: item.file.name,
                    responseType: Array.isArray(data) ? 'array' : typeof data,
                    responseData: data
                });
                
                let url = '';
                let sessionKey = null;
                let logId = null;
                
                // Handle new response format with session key
                if (data && typeof data === 'object' && data.urls && Array.isArray(data.urls)) {
                    url = data.urls[0] || '';
                    sessionKey = data.session_key || null;
                    logId = data.log_id || null;
                    R2Logger.info('R2 Upload response with session', {
                        url: url,
                        sessionKey: sessionKey,
                        logId: logId,
                        urlsCount: data.urls.length
                    });
                } else if (Array.isArray(data) && data.length > 0) {
                    // Fallback: old format (array of URLs)
                    url = data[0];
                } else if (typeof data === 'string' && data.length > 0) {
                    // Fallback: old format (single URL string)
                    url = data;
                }
                
                if (url) {
                    const $item = $('#' + item.id);
                    if ($item.length === 0) {
                        R2Logger.error('Preview item not found in DOM', {
                            itemId: item.id,
                            fileName: item.file.name
                        });
                        throw new Error('Preview item not found: ' + item.id);
                    }
                    
                    // Find input field - try multiple strategies
                    let $input = $item.find('input[name="' + settings.hiddenInputName + '"]');
                    
                    // If not found in preview item, try to find in the form (for single image uploads)
                    if ($input.length === 0) {
                        $input = $form.find('input[name="' + settings.hiddenInputName + '"]');
                        R2Logger.info('Input not found in preview item, searching in form', {
                            inputName: settings.hiddenInputName,
                            found: $input.length > 0
                        });
                    }
                    
                    // Fallback: try to find by ID pattern (ImageUrl + number)
                    if ($input.length === 0 && settings.hiddenInputName === 'image') {
                        const inputId = $item.closest('.panel-body, .form-group, .input-group').find('input[id^="ImageUrl"]');
                        if (inputId.length > 0) {
                            $input = inputId.first();
                            R2Logger.info('Found input by ID pattern', { inputId: $input.attr('id') });
                        }
                    }
                    
                    const $img = $item.find('img');
                    
                    if ($input.length === 0) {
                        R2Logger.error('Input field not found in preview item', {
                            itemId: item.id,
                            fileName: item.file.name,
                            inputName: settings.hiddenInputName,
                            html: $item.html(),
                            formInputs: $form.find('input[type="hidden"], input[type="text"]').map(function() {
                                return { name: $(this).attr('name'), id: $(this).attr('id') };
                            }).get()
                        });
                        throw new Error('Input field not found in item: ' + item.id);
                    }
                    
                    const oldInputVal = $input.val();
                    const oldImgSrc = $img.attr('src');
                    
                    // Update input field with R2 URL
                    $input.val(url);
                    
                    // Also update the actual form input if it's different from preview item input
                    // (for single image uploads where preview item input is just a placeholder)
                    const $formInput = $form.find('input[name="' + settings.hiddenInputName + '"]');
                    if ($formInput.length > 0 && $formInput[0] !== $input[0]) {
                        $formInput.val(url);
                        R2Logger.info('Updated form input field', {
                            inputId: $formInput.attr('id'),
                            inputName: $formInput.attr('name'),
                            url: url
                        });
                    }
                    
                    // Also try to update by ID pattern for compatibility
                    if (settings.hiddenInputName === 'image') {
                        const $idInput = $form.find('input[id="r2-image-url"], input[id^="ImageUrl"]');
                        if ($idInput.length > 0 && $idInput[0] !== $input[0] && $idInput[0] !== $formInput[0]) {
                            $idInput.val(url);
                            R2Logger.info('Updated input by ID pattern', {
                                inputId: $idInput.attr('id'),
                                url: url
                            });
                        }
                    }
                    
                    // Update img src with R2 URL (replace Blob URL) - set immediately
                    $img.attr('src', url);
                    
                    // Preload image in background to ensure it loads properly
                    const newImg = new Image();
                    newImg.onload = function() {
                        R2Logger.info('Image preloaded successfully', {
                            itemId: item.id,
                            url: url
                        });
                    };
                    newImg.onerror = function() {
                        R2Logger.warn('Image preload failed (may still display)', {
                            itemId: item.id,
                            url: url
                        });
                    };
                    newImg.src = url;
                    
                    // Remove pending flag
                    $item.removeAttr('data-pending');
                    // Remove pending class if exists
                    $input.removeClass('r2-pending-url');
                    
                    // Small delay to ensure DOM updates are complete
                    await new Promise(resolve => setTimeout(resolve, 50));
                    
                    // Verify update was successful
                    const newInputVal = $input.val();
                    const newImgSrc = $img.attr('src');
                    
                    R2Logger.info('Preview item updated', {
                        itemId: item.id,
                        fileName: item.file.name,
                        oldInputValue: oldInputVal,
                        newInputValue: newInputVal,
                        oldImgSrc: oldImgSrc,
                        newImgSrc: newImgSrc,
                        updateSuccess: (newInputVal === url && newImgSrc === url),
                        sessionKey: sessionKey,
                        logId: logId,
                        inputExists: $input.length > 0,
                        imgExists: $img.length > 0
                    });
                    
                    if (newInputVal !== url || newImgSrc !== url) {
                        R2Logger.error('Update verification failed', {
                            itemId: item.id,
                            expectedUrl: url,
                            actualInputValue: newInputVal,
                            actualImgSrc: newImgSrc,
                            inputLength: $input.length,
                            imgLength: $img.length
                        });
                        throw new Error('Failed to update preview item: ' + item.id);
                    }
                    
                    // Store URL with session key for later use
                    results.push({
                        url: url,
                        sessionKey: sessionKey,
                        logId: logId,
                        itemId: item.id,
                        fileName: item.file.name
                    });
                } else {
                    R2Logger.error('Server did not return a valid URL', {
                        fileName: item.file.name,
                        responseData: data
                    });
                    throw new Error('Server did not return a valid URL');
                }
            } catch (error) {
                R2Logger.error('Upload error for file', {
                    fileName: item.file.name,
                    error: error.message,
                    stack: error.stack
                });
                throw error;
            }
        }
        
        R2Logger.info('All uploads completed', {
            totalUploads: pendingFiles.length,
            successfulUploads: results.length,
            results: results.map(r => ({ url: r.url, itemId: r.itemId, fileName: r.fileName }))
        });
        
        return results;
    }

    // Expose logger for debugging
    window.R2Logger = R2Logger;
    
    // Expose function for $.validate() to check pending uploads
    window.R2UploadPendingCheck = function() {
        const domPendingItems = findPendingItemsFromDOM();
        const totalPending = Math.max(pendingFiles.length, domPendingItems.length);
        const isUploading = $form.data('r2-uploading');
        const hasPending = totalPending > 0 && !isUploading;
        
        R2Logger.info('R2UploadPendingCheck called', {
            pendingFilesCount: pendingFiles.length,
            domPendingCount: domPendingItems.length,
            totalPending: totalPending,
            isUploading: isUploading,
            hasPending: hasPending
        });
        
        return hasPending;
    };
    
    // Add download logs button to console
    R2Logger.info('R2 Upload Preview initialized', {
        formId: $form.attr('id'),
        uploadRoute: settings.uploadRoute
    });
    
    // Add helper to download logs
    console.log('%cR2 Upload Logs Available', 'color: blue; font-weight: bold;');
    console.log('%cDebug Commands:', 'color: green; font-weight: bold;');
    console.log('  R2Logger.download() - Download logs as JSON file');
    console.log('  R2Logger.logs - View all logs array');
    console.log('  window.R2Logs - Access logs array');
    console.log('  R2Logger.logs.filter(l => l.level === "error") - View only errors');
    console.log('  R2Logger.logs.filter(l => l.message.includes("input")) - View input-related logs');
    
    // Add helper function to check current state
    window.checkR2State = function() {
        const state = {
            pendingFiles: pendingFiles.length,
            previewUrls: previewUrls.length,
            formUploading: $form.data('r2-uploading'),
            formId: $form.attr('id'),
            ajaxUrl: $form.attr('ajax'),
            previewItems: $container.find('.has-img').length,
            inputs: []
        };
        
        $container.find('.has-img').each(function() {
            const $item = $(this);
            state.inputs.push({
                id: $item.attr('id'),
                inputValue: $item.find('input').val(),
                imgSrc: $item.find('img').attr('src'),
                hasPendingFlag: $item.attr('data-pending') !== undefined
            });
        });
        
        console.log('%cCurrent R2 State:', 'color: purple; font-weight: bold;', state);
        return state;
    };
    
    console.log('  checkR2State() - Check current upload state');
    
    return {
        getPendingCount: () => pendingFiles.length,
        updateTrigger: updateTriggerText,
        getLogger: () => R2Logger
    };
}
