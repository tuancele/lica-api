@extends('Layout::layout')
@section('title','Thêm sản phẩm mới')
@section('content')
<style>
    /* Shopee Advanced 3-Column Layout */
    :root {
        --shopee-orange: #ee4d2d;
        --shopee-bg: #f5f5f5;
        --text-primary: #333;
        --text-secondary: #999;
        --border-color: #e5e5e5;
    }

    body { font-family: -apple-system,Helvetica Neue,Helvetica,Roboto,Droid Sans,Arial,sans-serif; background-color: var(--shopee-bg); }
    .content-wrapper { background-color: var(--shopee-bg) !important; }
    .shopee-main-container { max-width: 1400px; margin: 0 auto; padding: 20px; display: flex; gap: 20px; align-items: flex-start; }

    /* Left Column: Tips */
    .shopee-left-col { width: 220px; flex-shrink: 0; }
    .tip-card { background: #fff; border-radius: 4px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .tip-title { font-weight: 500; margin-bottom: 16px; color: #333; }
    .tip-list { list-style: none; padding: 0; margin: 0; }
    .tip-item { display: flex; align-items: center; margin-bottom: 12px; font-size: 13px; color: #666; }
    .tip-item i { margin-right: 8px; font-size: 14px; color: #ccc; }
    .tip-item.done i { color: #52c41a; }
    .tip-item.done span { color: #333; }

    /* Middle Column: Main Form */
    .shopee-mid-col { flex-grow: 1; min-width: 0; }
    .shopee-tabs { background: #fff; border-radius: 4px 4px 0 0; display: flex; border-bottom: 1px solid #f0f0f0; }
    .shopee-tab { padding: 16px 24px; cursor: pointer; color: #333; border-bottom: 2px solid transparent; font-weight: 500; }
    .shopee-tab.active { color: var(--shopee-orange); border-bottom-color: var(--shopee-orange); }

    .shopee-card { background: #fff; border-radius: 0 0 4px 4px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .section-title { font-size: 18px; font-weight: 500; margin-bottom: 24px; color: #333; }

    /* Right Column: Preview */
    .shopee-right-col { width: 320px; flex-shrink: 0; position: sticky; top: 20px; }
    .preview-phone { background: #fff; border-radius: 20px; border: 8px solid #333; width: 100%; height: 550px; overflow: hidden; position: relative; }
    .preview-header { height: 40px; background: #f8f8f8; display: flex; align-items: center; justify-content: center; font-weight: 500; font-size: 12px; border-bottom: 1px solid #eee; }
    .preview-body { padding: 0; height: calc(100% - 40px); overflow-y: auto; }
    .preview-img-box { width: 100%; aspect-ratio: 1/1; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
    .preview-info { padding: 12px; }
    .preview-name { font-size: 14px; font-weight: 500; margin-bottom: 8px; height: 40px; overflow: hidden; }
    .preview-price { color: var(--shopee-orange); font-size: 18px; font-weight: 600; }

    /* Form Styling */
    .form-item { margin-bottom: 24px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
    .form-label.required::before { content: '*'; color: var(--shopee-orange); margin-right: 4px; }
    
    .shopee-input { width: 100%; border: 1px solid var(--border-color); border-radius: 4px; padding: 10px 12px; transition: border-color .2s; }
    .shopee-input:focus { border-color: var(--shopee-orange); outline: none; }
    
    .shopee-textarea {
        border: 1px solid var(--border-color);
        border-radius: 4px;
        padding: 12px;
        width: 100% !important;
        min-height: 120px;
        resize: vertical;
        font-size: 14px;
        display: block;
    }

    /* Image Upload Grid */
    .image-grid { display: grid; grid-template-columns: repeat(5, 80px); gap: 12px; margin-bottom: 16px; }
    .image-upload-box { width: 80px; height: 80px; border: 1px dashed #d8d8d8; border-radius: 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; color: var(--shopee-orange); font-size: 11px; text-align: center; background: #fff; position: relative; }
    .image-upload-box:hover { border-color: var(--shopee-orange); background: rgba(238, 77, 45, .02); }
    .image-upload-box.has-img { border-style: solid; }
    .image-upload-box.has-img img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }
    .image-upload-box.is-cover::after { content: 'Ảnh bìa'; position: absolute; bottom: 0; width: 100%; background: var(--shopee-orange); color: #fff; font-size: 9px; padding: 1px 0; border-radius: 0 0 4px 4px; }
    .image-upload-box .remove-btn { position: absolute; top: -5px; right: -5px; background: rgba(0,0,0,.5); color: #fff; width: 16px; height: 16px; border-radius: 50%; display: none; align-items: center; justify-content: center; font-size: 10px; z-index: 5; }
    .image-upload-box:hover .remove-btn { display: flex; }

    /* Fixed Footer */
    .shopee-footer { position: fixed; bottom: 0; right: 0; left: 230px; background: #fff; padding: 12px 40px; display: flex; justify-content: flex-end; gap: 12px; box-shadow: 0 -2px 8px rgba(0,0,0,.05); z-index: 100; }
    .btn-shopee { padding: 8px 24px; border-radius: 4px; font-weight: 500; cursor: pointer; }
    .btn-shopee-outline { border: 1px solid var(--border-color); background: #fff; }
    .btn-shopee-primary { background: var(--shopee-orange); color: #fff; border: none; }
    .btn-shopee-primary:hover { background: #d73211; }

    /* Hide CSRF/Slug/SEO but keep in DOM for data submission */
    .hidden-data { display: none !important; }

    /* Reset Bootstrap */
    .content-header { display: none; }
    .content { padding: 0 !important; }
</style>

<div class="shopee-main-container">
    <!-- Left Column: Tips -->
    <div class="shopee-left-col hidden-sm hidden-xs">
        <div class="tip-card">
            <div class="tip-title">Gợi ý điền Thông tin</div>
            <ul class="tip-list">
                <li class="tip-item" id="tip-img"><i class="fa fa-check-circle"></i> <span>Thêm ít nhất 3 hình ảnh</span></li>
                <li class="tip-item"><i class="fa fa-circle-o"></i> <span>Thêm video sản phẩm</span></li>
                <li class="tip-item" id="tip-name"><i class="fa fa-check-circle"></i> <span>Tên sản phẩm 25~100 kí tự</span></li>
                <li class="tip-item" id="tip-brand"><i class="fa fa-check-circle"></i> <span>Thêm thương hiệu</span></li>
                <li class="tip-item" id="tip-cat"><i class="fa fa-circle-o"></i> <span>Chọn ngành hàng</span></li>
            </ul>
        </div>
        <div class="tip-card">
            <div class="tip-title">Gợi ý</div>
            <p style="font-size: 12px; color: #999;">Tên sản phẩm rõ ràng, hình ảnh sắc nét sẽ giúp tăng tỉ lệ chuyển đổi.</p>
        </div>
    </div>

    <!-- Middle Column: Main Form -->
    <div class="shopee-mid-col">
        <form id="tblForm" method="post" ajax="{{route('product.create')}}">
            @csrf
            <!-- Hidden Fields -->
            <input type="text" name="slug" id="slug-target" class="hidden-data">
            <input type="text" name="seo_title" id="seo-title-auto" class="hidden-data">
            <textarea name="seo_description" id="seo-desc-auto" class="hidden-data"></textarea>

            <div class="shopee-tabs">
                <div class="shopee-tab active">Thông tin cơ bản</div>
                <div class="shopee-tab">Thông tin chi tiết</div>
                <div class="shopee-tab">Mô tả</div>
                <div class="shopee-tab">Thông tin bán hàng</div>
                <div class="shopee-tab">Vận chuyển</div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Thông tin cơ bản</div>

                <div class="form-item">
                    <label class="form-label required">Hình ảnh sản phẩm</label>
                    <p style="font-size: 12px; color: #999; margin-bottom: 12px;">Hình ảnh tỷ lệ 1:1</p>
                    <div class="image-grid list_image" id="shopee-image-grid">
                        <!-- Add Button (Triggers hidden input) -->
                        <div class="image-upload-box" id="trigger-upload">
                            <i class="fa fa-camera fa-2x"></i>
                            <span style="margin-top: 4px;">Thêm hình ảnh (0/9)</span>
                        </div>
                        <input type="file" id="hidden-file-input" multiple style="display: none;" accept="image/*">
                    </div>
                </div>

                <div class="form-item">
                    <label class="form-label">Video sản phẩm</label>
                    <p style="font-size: 12px; color: #999; margin-bottom: 12px;">
                        Định dạng MP4/WEBM, dung lượng tối đa 30MB, thời lượng khuyến nghị 10-60s.
                    </p>
                    <div class="image-grid">
                        <div class="image-upload-box" id="product-video-trigger">
                            <div class="video-upload-inner">
                                <i class="fa fa-video-camera fa-2x"></i>
                                <span style="margin-top: 4px;">Thêm video</span>
                            </div>
                            <!-- video preview sẽ được thêm bằng JS, không che click nhờ pointer-events:none -->
                        </div>
                    </div>
                    <input type="file" id="product-video-input" accept="video/*" style="display:none;">
                    <input type="hidden" name="video" id="product-video-url">
                </div>

                <div class="form-item">
                    <label class="form-label required">Tên sản phẩm</label>
                    <div style="position: relative;">
                        <input type="text" name="name" id="product-name-input" class="shopee-input" placeholder="Nhập tên sản phẩm" maxlength="120" required>
                        <span id="name-count" style="position: absolute; right: 12px; top: 10px; color: #999; font-size: 12px;">0/120</span>
                    </div>
                </div>

                <div class="form-item">
                    <label class="form-label">Số CBMP</label>
                    <input type="text" name="cbmp" class="shopee-input" placeholder="Nhập số công bố mỹ phẩm">
                </div>

                <div class="form-item">
                    <label class="form-label required">Ngành hàng</label>
                    <select class="shopee-input" name="cat_id[]" id="cat-selector" required>
                        <option value="">Chọn ngành hàng</option>
                        @foreach($categories as $category)
                            <option value="{{$category->id}}">{{$category->name}}</option>
                            @foreach($category->children as $sub)
                                <option value="{{$sub->id}}">-- {{$sub->name}}</option>
                                @foreach($sub->children as $sub2)
                                    <option value="{{$sub2->id}}">---- {{$sub2->name}}</option>
                                @endforeach
                            @endforeach
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-item">
                            <label class="form-label">Thương hiệu</label>
                            <select class="shopee-input" name="brand_id" id="brand-selector">
                                <option value="">Chọn thương hiệu</option>
                                @foreach($brands as $brand)
                                    <option value="{{$brand->id}}">{{$brand->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-item">
                            <label class="form-label">Xuất xứ</label>
                            <select class="shopee-input" name="origin_id">
                                <option value="">Chọn xuất xứ</option>
                                @foreach($origins as $origin)
                                    <option value="{{$origin->id}}">{{$origin->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Thông tin bán hàng</div>
                <input type="hidden" name="has_variants" id="has_variants" value="0">
                <input type="hidden" name="option1_name" id="option1_name" value="">
                <input type="hidden" name="variants_json" id="variants_json" value="">

                <div id="single-selling">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-item">
                                <label class="form-label required">Giá bán</label>
                                <input type="text" name="price" class="shopee-input price" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-item">
                                <label class="form-label required">Kho hàng</label>
                                <input type="number" name="stock_qty" class="shopee-input" value="0" min="0">
                                <input type="hidden" name="stock" value="1">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="form-label">Mã SKU</label>
                                <input type="text" name="sku" class="shopee-input" placeholder="SKU sản phẩm (Không bắt buộc)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="form-label">Trọng lượng (kg)</label>
                                <input type="text" name="weight" class="shopee-input" value="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="variant-selling" style="display:none;">
                    <div class="form-item" style="margin-bottom: 10px;">
                        <div class="align-center space-between">
                            <label class="form-label required" style="margin:0;">Phân loại hàng</label>
                            <button type="button" class="btn-shopee btn-shopee-outline" id="btn_disable_variants" style="padding: 4px 12px; font-size: 12px;">Tắt phân loại</button>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 12px;">
                        <div class="col-md-4">
                            <div class="form-item">
                                <label class="form-label required">Tên phân loại 1</label>
                                <input type="text" class="shopee-input" id="variant_option1_name" placeholder="VD: Dung tích">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-item">
                                <label class="form-label required">Tùy chọn</label>
                                <div style="display:flex; gap:8px;">
                                    <input type="text" class="shopee-input" id="variant_option1_value_input" placeholder="VD: 100ML (Enter để thêm)">
                                    <button type="button" class="btn-shopee btn-shopee-primary" id="btn_add_option1" style="white-space:nowrap;">Thêm</button>
                                </div>
                                <div id="option1_tags" style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-item">
                        <div class="align-center space-between" style="margin-bottom: 10px;">
                            <label class="form-label" style="margin:0;">Danh sách phân loại</label>
                            <button type="button" class="btn-shopee btn-shopee-outline" id="btn_apply_all" style="padding: 4px 12px; font-size: 12px;">Áp dụng cho tất cả</button>
                        </div>

                        <table class="variant-table" id="variant_table">
                            <thead>
                                <tr>
                                    <th width="18%">Ảnh</th>
                                    <th width="18%">Phân loại</th>
                                    <th width="16%">Giá</th>
                                    <th width="16%">Kho hàng</th>
                                    <th width="20%">SKU</th>
                                    <th width="12%">Xóa</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div style="font-size:12px; color:#999; margin-top:8px;">
                            Tip: Ảnh phân loại sẽ dùng để hiển thị ảnh chính khi khách chọn phân loại trên website.
                        </div>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="button" class="btn-shopee btn-shopee-outline" id="btn_enable_variants" style="padding: 6px 14px;">+ Thêm nhóm phân loại</button>
                </div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Mô tả sản phẩm</div>
                <textarea name="content" class="shopee-textarea description" rows="15"></textarea>
                <textarea name="description" class="hidden-data"></textarea>
            </div>

            <div class="shopee-card">
                <div class="section-title">Thành phần sản phẩm</div>
                <textarea name="ingredient" id="ingredient" class="shopee-textarea" rows="10"></textarea>
            </div>

            <div class="shopee-footer">
                <a href="{{route('product')}}" class="btn-shopee btn-shopee-outline">Hủy</a>
                <button type="submit" class="btn-shopee btn-shopee-primary">Lưu & Hiển thị</button>
            </div>
        </form>
    </div>

    <!-- Right Column: Preview -->
    <div class="shopee-right-col hidden-md hidden-sm hidden-xs">
        <div class="preview-phone">
            <div class="preview-header">Xem trước sản phẩm</div>
            <div class="preview-body">
                <div class="preview-img-box" id="preview-main-img">
                    <i class="fa fa-picture-o fa-3x" style="color: #eee;"></i>
                </div>
                <div class="preview-info">
                    <div class="preview-name" id="preview-name-text">Tên sản phẩm sẽ hiển thị tại đây</div>
                    <div class="preview-price" id="preview-price-text">₫0</div>
                    <div style="margin-top: 20px; border-top: 1px solid #f5f5f5; padding-top: 12px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #999;">
                            <span>Vận chuyển</span>
                            <span>Miễn phí vận chuyển</span>
                        </div>
                    </div>
                </div>
            </div>
            <div style="position: absolute; bottom: 0; width: 100%; display: flex; height: 50px;">
                <div style="flex: 1; background: #26aa99; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 500;">Thêm vào giỏ hàng</div>
                <div style="flex: 1; background: var(--shopee-orange); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 500;">Mua ngay</div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript" src="/public/admin/slugify.js"></script>
<script type="text/javascript" src="/public/js/r2-upload-preview.js"></script>
<script type="text/javascript" src="/public/js/r2-video-upload.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.price').number(true, 0);

    // Initialize R2 Upload Preview Component
    initR2UploadPreview({
        fileInputSelector: '#hidden-file-input',
        triggerSelector: '#trigger-upload',
        previewContainerSelector: '.list_image',
        previewItemClass: 'image-upload-box',
        hiddenInputName: 'imageOther[]',
        uploadRoute: "{{ route('r2.upload') }}",
        folder: 'image',
        maxFiles: 9,
        convertWebP: true,
        quality: 85,
        onUploadStart: function(totalFiles) {
            const btn = $('#tblForm').find('button[type="submit"]');
            btn.html('<i class="fa fa-spinner fa-spin"></i> Đang upload ' + totalFiles + ' ảnh...').prop('disabled', true);
        },
        onUploadComplete: function(urls) {
            const btn = $('#tblForm').find('button[type="submit"]');
            btn.html('Lưu & Hiển thị').prop('disabled', false);
            
            // Force refresh preview after a short delay to ensure images are loaded
            setTimeout(function() {
                refreshImgStatus();
                // Also trigger preview update callback if exists
                if (typeof window.updateProductPreview === 'function') {
                    window.updateProductPreview();
                }
            }, 100);
        },
        onUploadError: function(errorMsg) {
            const btn = $('#tblForm').find('button[type="submit"]');
            btn.html('Lưu & Hiển thị').prop('disabled', false);
            alert('Lỗi upload ảnh: ' + errorMsg);
        },
        onPreviewAdd: function(file, previewUrl, index) {
            refreshImgStatus();
        },
        onPreviewRemove: function(index) {
            refreshImgStatus();
        }
    });

    const r2VariantUploadRoute = "{{ route('r2.upload') }}";

    function resetUploadBtn() {
        let count = $('.image-upload-box.has-img').length;
        $('#trigger-upload').html('<i class="fa fa-camera fa-2x"></i><span style="margin-top:4px;">Thêm hình ảnh ('+count+'/9)</span>');
    }

    function refreshImgStatus() {
        $('.image-upload-box.has-img').removeClass('is-cover');
        let first = $('.image-upload-box.has-img').first();
        first.addClass('is-cover');
        
        if(first.length > 0) {
            $('#preview-main-img').html('<img src="'+first.find('img').attr('src')+'" style="width:100%; height:100%; object-fit:cover;">');
            $('#tip-img').addClass('done');
        } else {
            $('#preview-main-img').html('<i class="fa fa-picture-o fa-3x" style="color: #eee;"></i>');
            $('#tip-img').removeClass('done');
        }
    }

    // Auto Slug & SEO
    $('#product-name-input').on('input', function() {
        let val = $(this).val();
        $('#name-count').text(val.length + '/120');
        $('#preview-name-text').text(val || 'Tên sản phẩm sẽ hiển thị tại đây');
        
        // Slug generate
        let slug = val.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^\w ]+/g,'').replace(/ +/g,'-');
        $('#slug-target').val(slug);
        
        // Auto SEO
        $('#seo-title-auto').val(val);
        $('#seo-desc-auto').val(val);

        if(val.length >= 25) $('#tip-name').addClass('done');
        else $('#tip-name').removeClass('done');
    });

    // Price Sync
    $('input[name="price"]').on('input', function() {
        $('#preview-price-text').text('₫' + $(this).val());
    });

    // --- Variant (Shopee style, 1-level) ---
    const $singleSelling = $('#single-selling');
    const $variantSelling = $('#variant-selling');
    const $btnEnable = $('#btn_enable_variants');
    const $btnDisable = $('#btn_disable_variants');
    const $hasVariants = $('#has_variants');
    const $opt1NameInput = $('#variant_option1_name');
    const $opt1ValueInput = $('#variant_option1_value_input');
    const $opt1Tags = $('#option1_tags');
    const $variantTableBody = $('#variant_table tbody');
    const $variantsJson = $('#variants_json');
    const $opt1NameHidden = $('#option1_name');
    let variantRowCounter = 0;

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
        });
    }

    function setMode(has) {
        if (has) {
            $hasVariants.val('1');
            $singleSelling.hide();
            $variantSelling.show();
            $btnEnable.hide();
        } else {
            $hasVariants.val('0');
            $variantSelling.hide();
            $singleSelling.show();
            $btnEnable.show();
            $opt1Tags.html('');
            $variantTableBody.html('');
            $opt1NameInput.val('');
            $opt1NameHidden.val('');
            $variantsJson.val('');
        }
        buildVariantsJson();
    }

    function renderTag(value) {
        const v = escapeHtml(value);
        return `<span class="badge bg-light" style="border:1px solid #eee; color:#333; padding:6px 10px; border-radius:14px;">
                    <span class="tag-text">${v}</span>
                    <a href="javascript:;" class="tag-remove" style="margin-left:6px; color:#999;">×</a>
                </span>`;
    }

    function getOptionValues() {
        const values = [];
        $opt1Tags.find('.tag-text').each(function() {
            const t = $(this).text().trim();
            if (t) values.push(t);
        });
        return values;
    }

    function ensureVariantRow(optionValue) {
        const safe = optionValue;
        const exists = $variantTableBody.find('tr').filter(function(){ return $(this).attr('data-option') === safe; }).length > 0;
        if (exists) return;

        variantRowCounter++;
        const rowId = 'v' + variantRowCounter;
        const rowHtml = `
            <tr data-option="${escapeHtml(safe)}" data-row-id="${rowId}">
                <td>
                    <div class="variant-img-box" style="width:46px;height:46px;border:1px solid #eee;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fafafa;cursor:pointer;">
                        <img id="variant-img-${rowId}" src="/public/admin/no-image.png" style="width:46px;height:46px;object-fit:cover;">
                    </div>
                    <input type="file" class="variant-file-input" accept="image/*" style="display:none;">
                    <input type="hidden" id="variant-image-${rowId}" class="variant-image" value="">
                    <small class="variant-img-note" style="font-size:11px;color:#999;display:block;margin-top:4px;">Mặc định dùng ảnh sản phẩm</small>
                </td>
                <td><strong>${escapeHtml(safe)}</strong></td>
                <td><input type="text" class="shopee-input price variant-price" value="0"></td>
                <td><input type="number" class="shopee-input variant-stock" value="0" min="0"></td>
                <td><input type="text" class="shopee-input variant-sku" value=""></td>
                <td><button type="button" class="btn btn-danger btn-xs variant-delete-row"><i class="fa fa-trash"></i></button></td>
            </tr>
        `;
        $variantTableBody.append(rowHtml);
        $variantTableBody.find('tr:last .variant-price').number(true, 0);
    }

    function uploadVariantImage(file, rowId) {
        if (!file) return;
        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('folder', 'image');
        formData.append('convert_webp', true);
        formData.append('quality', 85);
        formData.append('files', file);

        const $row = $variantTableBody.find('tr[data-row-id="' + rowId + '"]');
        const $img = $row.find('#variant-img-' + rowId);

        $.ajax({
            url: r2VariantUploadRoute,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                const url = res && res.urls && res.urls.length ? res.urls[0] : null;
                if (url) {
                    $img.attr('src', url);
                    $row.find('#variant-image-' + rowId).val(url);
                    $row.find('.variant-img-note').text('Đã chọn ảnh riêng');
                    buildVariantsJson();
                } else {
                    alert('Upload ảnh không thành công, vui lòng thử lại.');
                }
            },
            error: function() {
                alert('Lỗi upload ảnh, vui lòng thử lại.');
            }
        });
    }

    function syncRowsWithTags() {
        const values = getOptionValues();
        $variantTableBody.find('tr').each(function() {
            const opt = $(this).attr('data-option');
            if (!values.includes(opt)) $(this).remove();
        });
        values.forEach(v => ensureVariantRow(v));
        buildVariantsJson();
    }

    function buildVariantsJson() {
        if ($hasVariants.val() !== '1') return;
        const name = $opt1NameInput.val().trim();
        $opt1NameHidden.val(name);
        const variants = [];
        $variantTableBody.find('tr').each(function(pos) {
            const $tr = $(this);
            variants.push({
                id: null,
                option1_value: $tr.attr('data-option'),
                image: $tr.find('.variant-image').val() || '',
                price: String($tr.find('.variant-price').val() || '0').replace(/,/g,''),
                stock: parseInt($tr.find('.variant-stock').val() || '0', 10),
                sku: $tr.find('.variant-sku').val() || '',
                position: pos
            });
        });
        $variantsJson.val(JSON.stringify({ option1_name: name, variants }));
    }

    $btnEnable.on('click', function() { setMode(true); });
    $btnDisable.on('click', function() { if (confirm('Tắt phân loại? Dữ liệu phân loại sẽ không được lưu.')) setMode(false); });

    $('#btn_add_option1').on('click', function() {
        const v = $opt1ValueInput.val().trim();
        if (!v) return;
        const current = getOptionValues();
        if (current.includes(v)) { $opt1ValueInput.val(''); return; }
        $opt1Tags.append(renderTag(v));
        $opt1ValueInput.val('');
        syncRowsWithTags();
    });

    $opt1ValueInput.on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btn_add_option1').click();
        }
    });

    $opt1Tags.on('click', '.tag-remove', function() {
        $(this).closest('span').remove();
        syncRowsWithTags();
    });

    $opt1NameInput.on('input', buildVariantsJson);
    $variantTableBody.on('input change', 'input', buildVariantsJson);

    // Variant image click & upload
    $variantTableBody.on('click', '.variant-img-box', function() {
        const $tr = $(this).closest('tr');
        $tr.find('.variant-file-input').trigger('click');
    });

    $variantTableBody.on('change', '.variant-file-input', function() {
        const file = this.files[0];
        const rowId = $(this).closest('tr').attr('data-row-id');
        uploadVariantImage(file, rowId);
        this.value = '';
    });

    $variantTableBody.on('click', '.variant-delete-row', function() {
        const $tr = $(this).closest('tr');
        const opt = $tr.attr('data-option');
        $opt1Tags.find('.tag-text').each(function() {
            if ($(this).text().trim() === opt) $(this).closest('span').remove();
        });
        $tr.remove();
        buildVariantsJson();
    });

    $('#btn_apply_all').on('click', function() {
        const p = prompt('Giá áp dụng cho tất cả (bỏ trống để không thay đổi):', '');
        const s = prompt('Kho áp dụng cho tất cả (bỏ trống để không thay đổi):', '');
        $variantTableBody.find('tr').each(function() {
            if (p !== null && p !== '') $(this).find('.variant-price').val(p);
            if (s !== null && s !== '') $(this).find('.variant-stock').val(s);
        });
        $variantTableBody.find('.variant-price').number(true, 0);
        buildVariantsJson();
    });

    $('#tblForm').on('submit', function() { buildVariantsJson(); });

    // Tip sync
    $('#brand-selector').change(function() {
        if($(this).val()) $('#tip-brand').addClass('done');
        else $('#tip-brand').removeClass('done');
    });
    $('#cat-selector').change(function() {
        if($(this).val()) $('#tip-cat').addClass('done');
        else $('#tip-cat').removeClass('done');
    });

    $(".list_image").sortable({
        items: ".image-upload-box.has-img",
        update: function() { refreshImgStatus(); }
    });

    // Video upload (R2)
    initR2VideoUpload({
        fileInputSelector: '#product-video-input',
        triggerSelector: '#product-video-trigger',
        previewContainerSelector: '#product-video-trigger',
        hiddenInputSelector: '#product-video-url',
        uploadRoute: "{{ route('r2.uploadVideo') }}",
        folder: 'videos/products'
    });
});
</script>
<style>
    /* Variant table layout - make it compact & clean like Shopee */
    #variant-selling {
        margin-top: 10px;
    }
    #variant-selling .variant-table {
        width: 100%;
        border: 1px solid #eee;
        border-radius: 4px;
        border-collapse: collapse;
        font-size: 12px;
    }
    #variant-selling .variant-table th,
    #variant-selling .variant-table td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }
    #variant-selling .variant-table thead tr {
        background: #fafafa;
        font-weight: 600;
    }
    #variant-selling .variant-table .shopee-input {
        width: 100%;
        padding: 6px 8px;
        font-size: 12px;
        height: 34px;
    }
    #variant-selling .variant-table .variant-img-box {
        margin-bottom: 4px;
    }
    @media (max-width: 768px) {
        #variant-selling .variant-table th,
        #variant-selling .variant-table td {
            padding: 6px;
            font-size: 11px;
        }
    }
</style>
@endsection
