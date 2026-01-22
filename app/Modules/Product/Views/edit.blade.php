@extends('Layout::layout')
@section('title','Sửa thông tin sản phẩm')
@section('content')
<style>
    /* Shopee Advanced 3-Column Layout for Edit */
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

    /* Variant Table */
    .variant-table { width: 100%; border: 1px solid #eee; border-radius: 4px; margin-top: 20px; border-collapse: collapse; }
    .variant-table th { background: #f8f8f8; padding: 12px; text-align: left; font-weight: 500; border-bottom: 1px solid #eee; }
    .variant-table td { padding: 12px; border-bottom: 1px solid #eee; }

    .hidden-data { display: none !important; }
    .content-header { display: none; }
    .content { padding: 0 !important; }
</style>

<div id="product-edit-app" class="shopee-main-container" data-id="{{$detail->id}}">
    <!-- Left Column: Tips -->
    <div class="shopee-left-col hidden-sm hidden-xs">
        <div class="tip-card">
            <div class="tip-title">Gợi ý điền Thông tin</div>
            <ul class="tip-list">
                <li class="tip-item done"><i class="fa fa-check-circle"></i> <span>Dữ liệu đã sẵn sàng</span></li>
                <li class="tip-item"><i class="fa fa-circle-o"></i> <span>Tối ưu tên sản phẩm</span></li>
                <li class="tip-item"><i class="fa fa-circle-o"></i> <span>Cập nhật hình ảnh mới</span></li>
            </ul>
        </div>
    </div>

    <!-- Middle Column: Main Form -->
    <div class="shopee-mid-col">
        <form id="tblForm" method="post" ajax="{{route('product.update')}}">
            @csrf
            <input type="hidden" name="id" value="{{$detail->id}}">
            <!-- Hidden Fields -->
            <input type="text" name="slug" id="slug-target" value="{{$detail->slug}}" class="hidden-data">
            <input type="text" name="seo_title" id="seo-title-auto" value="{{$detail->seo_title}}" class="hidden-data">
            <textarea name="seo_description" id="seo-desc-auto" class="hidden-data">{{$detail->seo_description}}</textarea>

            <div class="shopee-tabs">
                <div class="shopee-tab active">Thông tin cơ bản</div>
                <div class="shopee-tab">Phân loại hàng</div>
                <div class="shopee-tab">Mô tả</div>
                <div class="shopee-tab">Thông tin khác</div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Thông tin cơ bản</div>

                <div class="form-item">
                    <label class="form-label required">Hình ảnh sản phẩm</label>
                    <div class="image-grid list_image">
                        @if(isset($gallerys) && !empty($gallerys))
                            @foreach($gallerys as $idx => $gallery)
                                <div class="image-upload-box has-img @if($idx == 0) is-cover @endif" data-existing="true">
                                    <img src="{{getImage($gallery)}}">
                                    <input type="hidden" name="imageOther[]" value="{{getImage($gallery)}}">
                                    <a href="javascript:void(0)" class="remove-btn"><i class="fa fa-times"></i></a>
                                </div>
                            @endforeach
                        @endif
                        <div class="image-upload-box" id="trigger-upload">
                            <i class="fa fa-camera fa-2x"></i>
                            <span style="margin-top: 4px;">Thêm hình ảnh ({{isset($gallerys) && is_array($gallerys) ? count($gallerys) : 0}}/9)</span>
                        </div>
                        <input type="file" id="hidden-file-input" multiple style="display: none;" accept="image/*">
                    </div>
                    <div id="imageOtherRemovedContainer" style="display:none;"></div>
                </div>

                <div class="form-item">
                    <label class="form-label">Video sản phẩm</label>
                    <p style="font-size: 12px; color: #999; margin-bottom: 12px;">
                        Định dạng MP4/WEBM, dung lượng tối đa 30MB, thời lượng khuyến nghị 10-60s.
                    </p>
                    <div class="image-grid">
                        <div class="image-upload-box" id="product-video-trigger">
                            <div class="video-upload-inner" @if($detail->video) style="display:none;" @endif>
                                <i class="fa fa-video-camera fa-2x"></i>
                                <span style="margin-top: 4px;">@if($detail->video) Đổi video @else Thêm video @endif</span>
                            </div>
                            @if($detail->video)
                                <video playsinline muted style="width:100%;height:100%;object-fit:cover;border-radius:4px;pointer-events:none;">
                                    <source src="{{ getImage($detail->video) }}" type="video/mp4">
                                </video>
                            @endif
                        </div>
                    </div>
                    <input type="file" id="product-video-input" accept="video/*" style="display:none;">
                    <input type="hidden" name="video" id="product-video-url" value="{{$detail->video}}">
                </div>

                <div class="form-item">
                    <label class="form-label required">Tên sản phẩm</label>
                    <div style="position: relative;">
                        <input type="text" name="name" id="product-name-input" class="shopee-input" value="{{$detail->name}}" maxlength="120" required>
                        <span id="name-count" style="position: absolute; right: 12px; top: 10px; color: #999; font-size: 12px;">{{strlen($detail->name)}}/120</span>
                    </div>
                </div>

                <div class="form-item">
                    <label class="form-label">Số CBMP</label>
                    <input type="text" name="cbmp" class="shopee-input" value="{{$detail->cbmp}}" placeholder="Nhập số công bố mỹ phẩm">
                </div>

                <div class="form-item">
                    <label class="form-label required">Ngành hàng</label>
                    <div class="category-picker-field" style="display:flex;align-items:center;gap:8px;">
                        <div id="categoryDisplayEdit" class="category-display" style="flex:1;min-height:34px;border:1px solid #e5e5e5;border-radius:4px;padding:6px 10px;font-size:13px;background:#fafafa;cursor:pointer;">
                            <span class="text-muted">Chon nganh hang</span>
                        </div>
                    </div>
                    <input type="hidden" name="cat_id[]" id="cat_id_hidden_edit" value="{{ (isset($dcat) && is_array($dcat) && count($dcat)) ? (int) $dcat[count($dcat) - 1] : '' }}">
                    <select class="shopee-input" name="cat_id[]" id="cat_id_select_edit" required style="display:none;">
                        <option value="">Chon nganh hang</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-item">
                            <label class="form-label">Thương hiệu</label>
                            <select class="shopee-input" name="brand_id" id="brand-selector-edit" data-selected="{{ (int) ($detail->brand_id ?? 0) }}">
                                <option value="">Chọn thương hiệu</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-item">
                            <label class="form-label">Xuất xứ</label>
                            <select class="shopee-input" name="origin_id" id="origin-selector-edit" data-selected="{{ (int) ($detail->origin_id ?? 0) }}">
                                <option value="">Chọn xuất xứ</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $defaultVariant = $detail->variants->sortBy('position')->first() ?? $detail->variants->first();
                $initialHasVariants = (int)($detail->has_variants ?? 0);
                // Heuristic: if option1_name exists or any variant has option1_value, enable variants mode
                if(!$initialHasVariants){
                    $initialHasVariants = ($detail->option1_name || $detail->variants->whereNotNull('option1_value')->count() > 0) ? 1 : 0;
                }
            @endphp

            <div class="shopee-card">
                <div class="section-title">Thông tin bán hàng</div>
                <input type="hidden" name="has_variants" id="has_variants" value="{{$initialHasVariants}}">
                <input type="hidden" name="option1_name" id="option1_name" value="{{ $detail->option1_name ?? '' }}">
                <input type="hidden" name="variants_json" id="variants_json" value="">

                <div id="single-selling" @if($initialHasVariants) style="display:none;" @endif>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-item">
                                <label class="form-label required">Giá bán</label>
                                <input type="text" name="price" class="shopee-input price" value="{{number_format($defaultVariant->price ?? 0,0,'',',')}}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-item">
                                <label class="form-label required">Kho hàng <small class="text-muted">(Tự động từ hệ thống kho)</small></label>
                                <input type="number" name="stock_qty" id="single_stock_qty" class="shopee-input" value="{{ (int)($defaultVariant->stock ?? 0) }}" min="0" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                                <small class="text-muted" id="single_stock_loading" style="display:none;">Đang tải từ kho hàng...</small>
                                <small class="text-info" style="display:none;" id="single_stock_loaded">✓ Đã cập nhật từ hệ thống kho</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="form-label">Mã SKU</label>
                                <input type="text" name="sku" class="shopee-input" value="{{$defaultVariant->sku ?? ''}}" placeholder="SKU sản phẩm (Không bắt buộc)">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-item">
                                <label class="form-label">Trọng lượng (kg)</label>
                                <input type="text" name="weight" class="shopee-input" value="{{$defaultVariant->weight ?? 0}}">
                            </div>
                        </div>
                    </div>
                </div>

                <div id="variant-selling" @if(!$initialHasVariants) style="display:none;" @endif>
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
                                <input type="text" class="shopee-input" id="variant_option1_name" value="{{ $detail->option1_name ?? '' }}" placeholder="VD: Dung tích">
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
                                    <th width="16%">Kho hàng <small class="text-muted" style="font-weight: normal;">(Tự động)</small></th>
                                    <th width="20%">SKU</th>
                                    <th width="12%">Xóa</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="button" class="btn-shopee btn-shopee-outline" id="btn_enable_variants" style="padding: 6px 14px; @if($initialHasVariants)display:none;@endif">+ Thêm nhóm phân loại</button>
                </div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Lưu ý</div>
                <div style="color:#666; font-size:13px;">
                    - Nếu bật phân loại, giá/kho sẽ lấy theo từng phân loại.<br>
                    - Nếu tắt phân loại, hệ thống sẽ dùng 1 biến thể mặc định.
                </div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Mô tả sản phẩm</div>
                <textarea name="content" class="shopee-textarea description" rows="15">{{$detail->content}}</textarea>
            </div>

            <div class="shopee-card">
                <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                    <span>Thanh phan san pham</span>
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="btn-shopee btn-shopee-outline" id="btnAutoLinkIngredients" style="padding:4px 10px;font-size:12px;">
                            Tu dong gan link thanh phan
                        </button>
                        <button type="button" class="btn-shopee btn-shopee-primary" id="btnAnalyzeIngredients" style="padding:4px 10px;font-size:12px;background:#16a085;border:none;">
                            Phan tich thanh phan
                        </button>
                    </div>
                </div>
                <div class="row" style="margin-top:8px;">
                    <div class="col-md-6">
                        <textarea name="ingredient" id="ingredient" class="shopee-textarea" rows="10">{{strip_tags($detail->ingredient)}}</textarea>
                    </div>
                    <div class="col-md-6">
                        <div id="ingredient-analysis" style="padding:10px;border:1px dashed #e5e5e5;border-radius:4px;font-size:13px;color:#333;background:#fafafa;min-height:120px;display:none;"></div>
                    </div>
                </div>
            </div>

            <div class="shopee-footer">
                <a href="{{route('product')}}" class="btn-shopee btn-shopee-outline">Quay lại</a>
                <button type="submit" class="btn-shopee btn-shopee-primary">Cập nhật sản phẩm</button>
            </div>
        </form>
    </div>

    <!-- Right Column: Preview -->
    <div class="shopee-right-col hidden-md hidden-sm hidden-xs">
        <div class="preview-phone">
            <div class="preview-header">Xem trước sản phẩm</div>
            <div class="preview-body">
                <div class="preview-img-box" id="preview-main-img">
                    <img src="{{getImage($detail->image)}}" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="preview-info">
                    <div class="preview-name" id="preview-name-text">{{$detail->name}}</div>
                    <div class="preview-price" id="preview-price-text">₫{{number_format($detail->variants->first()->price ?? 0)}}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript" src="/public/js/r2-upload-preview.js"></script>
<script type="text/javascript" src="/public/js/r2-video-upload.js"></script>
<script type="text/javascript">
// Initialize R2 Upload BEFORE other scripts to ensure handlers are registered early
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.price').number(true, 0);

    // --- Brand options via API ---
    function loadBrandOptionsEdit() {
        var $select = $('#brand-selector-edit');
        if (!$select.length) return;

        var selectedId = ($select.attr('data-selected') || '').toString();

        fetch('/api/v1/brands/options', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !json.success || !Array.isArray(json.data)) return;
                $select.empty();
                $select.append('<option value="">Chọn thương hiệu</option>');
                json.data.forEach(function (b) {
                    var id = (b && b.id) ? String(b.id) : '';
                    var name = (b && b.name) ? String(b.name) : '';
                    if (!id || !name) return;
                    $select.append('<option value="' + id + '"' + (selectedId && selectedId === id ? ' selected' : '') + '>' + name + '</option>');
                });
            })
            .catch(function () {});
    }

    loadBrandOptionsEdit();

    // --- Origin options via API ---
    function loadOriginOptionsEdit() {
        var $select = $('#origin-selector-edit');
        if (!$select.length) return;

        var selectedId = ($select.attr('data-selected') || '').toString();

        fetch('/api/v1/origins/options', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !Array.isArray(json.data)) return;
                $select.empty();
                $select.append('<option value="">Chọn xuất xứ</option>');
                json.data.forEach(function (o) {
                    var id = (o && o.id) ? String(o.id) : '';
                    var name = (o && o.name) ? String(o.name) : '';
                    if (!id || !name) return;
                    $select.append('<option value="' + id + '"' + (selectedId && selectedId === id ? ' selected' : '') + '>' + name + '</option>');
                });
            })
            .catch(function () {});
    }

    loadOriginOptionsEdit();

    // Initialize R2 Upload Preview Component
    // IMPORTANT: Initialize BEFORE $.validate() processes the form
    const r2UploadInstance = initR2UploadPreview({
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
        onUploadStart: function(count) {
            console.log('R2 Upload: Starting upload of', count, 'files');
            const btn = $('#tblForm').find('button[type="submit"]');
            btn.html('<i class="fa fa-spinner fa-spin"></i> Đang upload ' + count + ' ảnh...').prop('disabled', true);
        },
        onUploadComplete: function(urls) {
            console.log('R2 Upload: Upload complete, URLs:', urls);
            const btn = $('#tblForm').find('button[type="submit"]');
            btn.html('Cập nhật').prop('disabled', false);
            refreshImgStatus();
        },
        onUploadError: function(msg) {
            console.error('R2 Upload: Upload error:', msg);
            alert('Lỗi upload: ' + msg);
            $('#tblForm').find('button[type="submit"]').html('Cập nhật').prop('disabled', false);
        },
        onPreviewAdd: () => refreshImgStatus(),
        onPreviewRemove: () => refreshImgStatus()
    });

    // Track removed existing gallery images explicitly to avoid accidental restore/lose
    $('.list_image').on('click', '.remove-btn', function () {
        const $box = $(this).closest('.image-upload-box.has-img');
        if (!$box.length) return;

        // Only mark remove for existing images (already in DB)
        if ($box.attr('data-existing') === 'true') {
            const url = ($box.find('input[name="imageOther[]"]').val() || '').toString();
            if (url) {
                $('#imageOtherRemovedContainer').append(
                    '<input type="hidden" name="imageOtherRemoved[]" value="' + url.replace(/"/g, '&quot;') + '">'
                );
            }
        }
    });
    
    const r2VariantUploadRoute = "{{ route('r2.upload') }}";
    
    // Override $.validate() onSuccess to check for pending uploads
    // This ensures we intercept form submission even if $.validate() processes it first
    if (typeof $.validate !== 'undefined') {
        const originalValidate = $.validate;
        // Note: $.validate is already initialized in ControlPanel.js
        // We need to intercept the form submit button click instead
        $('#tblForm').find('button[type="submit"]').on('click', function(e) {
            const pendingCount = r2UploadInstance ? r2UploadInstance.getPendingCount() : 0;
            if (pendingCount > 0) {
                console.log('R2 Upload: Submit button clicked with', pendingCount, 'pending files');
                // Let the form submit handler in r2-upload-preview.js handle it
                // Don't prevent default here, let the submit event handler do it
            }
        });
    }

    function refreshImgStatus() {
        $('.image-upload-box.has-img').removeClass('is-cover');
        let first = $('.image-upload-box.has-img').first();
        first.addClass('is-cover');
        if(first.length > 0) $('#preview-main-img img').attr('src', first.find('img').attr('src'));
    }

    // Video upload (R2)
    initR2VideoUpload({
        fileInputSelector: '#product-video-input',
        triggerSelector: '#product-video-trigger',
        previewContainerSelector: '#product-video-trigger',
        hiddenInputSelector: '#product-video-url',
        uploadRoute: "{{ route('r2.uploadVideo') }}",
        folder: 'videos/products'
    });

    $('#product-name-input').on('input', function() {
        let val = $(this).val();
        $('#name-count').text(val.length + '/120');
        $('#preview-name-text').text(val);
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

    @php
        // Precompute to avoid Blade @json parsing issues with complex expressions
        $existingVariantsForJs = $detail->variants
            ->sortBy('position')
            ->values()
            ->map(function($v){
                $label = $v->option1_value;
                if(!$label){
                    $color = optional($v->color)->name;
                    $size = optional($v->size)->name;
                    $label = trim(($color ?: '') . (($color && $size) ? ' / ' : '') . ($size ?: ''));
                }
                if(!$label) $label = 'Mặc định';
                return [
                    'id' => $v->id,
                    'option1_value' => $label,
                    'image' => $v->image,
                    'price' => (float)$v->price,
                    'sale' => (float)$v->sale,
                    'stock' => (int)($v->stock ?? 0),
                    'sku' => $v->sku,
                    'position' => (int)($v->position ?? 0),
                ];
            })
            ->all();
    @endphp
    const existingVariants = @json($existingVariantsForJs);

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

    function ensureVariantRow(optionValue, preset) {
        const safe = optionValue;
        const exists = $variantTableBody.find('tr').filter(function(){ return $(this).attr('data-option') === safe; }).length > 0;
        if (exists) return;

        variantRowCounter++;
        const rowId = 'v' + variantRowCounter;
        const img = preset && preset.image ? preset.image : '';
        const imgSrc = img ? img : '/public/admin/no-image.png';
        const price = preset && preset.price ? preset.price : 0;
        const stock = preset && typeof preset.stock !== 'undefined' ? preset.stock : 0;
        const sku = preset && preset.sku ? preset.sku : '';
        const id = preset && preset.id ? preset.id : '';

        const rowHtml = `
            <tr data-option="${escapeHtml(safe)}" data-row-id="${rowId}" data-variant-id="${escapeHtml(id)}">
                <td>
                    <div class="variant-img-box" style="width:46px;height:46px;border:1px solid #eee;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fafafa;cursor:pointer;">
                        <img id="variant-img-${rowId}" src="${escapeHtml(imgSrc)}" style="width:46px;height:46px;object-fit:cover;">
                    </div>
                    <input type="file" class="variant-file-input" accept="image/*" style="display:none;">
                    <input type="hidden" id="variant-image-${rowId}" class="variant-image" value="${escapeHtml(img)}">
                    <small class="variant-img-note" style="font-size:11px;color:#999;display:block;margin-top:4px;">Mặc định dùng ảnh sản phẩm</small>
                </td>
                <td><strong>${escapeHtml(safe)}</strong></td>
                <td><input type="text" class="shopee-input price variant-price" value="${escapeHtml(price)}"></td>
                <td><input type="number" class="shopee-input variant-stock" value="${escapeHtml(stock)}" min="0" readonly style="background-color: #f5f5f5; cursor: not-allowed;" title="Tồn kho được lấy tự động từ hệ thống kho hàng"></td>
                <td><input type="text" class="shopee-input variant-sku" value="${escapeHtml(sku)}"></td>
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
                id: $tr.attr('data-variant-id') || null,
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
    $btnDisable.on('click', function() {
        if (confirm('Tắt phân loại? Hệ thống sẽ chuyển về 1 biến thể mặc định.')) setMode(false);
    });

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
        // Stock không thể chỉnh sửa - chỉ hiển thị thông báo
        if (p !== null && p !== '') {
            $variantTableBody.find('tr').each(function() {
                $(this).find('.variant-price').val(p);
            });
            $variantTableBody.find('.variant-price').number(true, 0);
            buildVariantsJson();
        }
        // Thông báo về stock chỉ đọc
        alert('Lưu ý: Tồn kho được lấy tự động từ hệ thống kho hàng và không thể chỉnh sửa thủ công.');
    });

    // Prefill from existing variants (edit page)
    if ($hasVariants.val() === '1') {
        const name = ($opt1NameInput.val() || '').trim();
        if (name) $opt1NameHidden.val(name);
        const values = [];
        existingVariants.forEach(v => {
            const opt = v.option1_value || 'Mặc định';
            if (!values.includes(opt)) values.push(opt);
        });
        values.forEach(v => $opt1Tags.append(renderTag(v)));
        // create rows with preset
        existingVariants.forEach(v => {
            ensureVariantRow(v.option1_value || 'Mặc định', v);
        });
        buildVariantsJson();
        
        // Load stock from Warehouse API for each variant
        loadVariantStocksFromWarehouse();
    }
    
    /**
     * Load stock from Warehouse API for all variants
     */
    function loadVariantStocksFromWarehouse() {
        const variantIds = [];
        $variantTableBody.find('tr').each(function() {
            const variantId = $(this).attr('data-variant-id');
            if (variantId && variantId !== '') {
                variantIds.push(variantId);
            }
        });
        
        if (variantIds.length === 0) return;
        
        // Load stock for each variant
        variantIds.forEach(function(variantId) {
            $.ajax({
                url: '/admin/api/v1/warehouse/variants/' + variantId + '/stock',
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const stock = response.data.current_stock || 0;
                        const $row = $variantTableBody.find('tr[data-variant-id="' + variantId + '"]');
                        if ($row.length > 0) {
                            const $stockInput = $row.find('.variant-stock');
                            $stockInput.val(stock);
                            // Thêm class để hiển thị đã được cập nhật
                            $stockInput.addClass('stock-loaded');
                            buildVariantsJson();
                        }
                    }
                },
                error: function(xhr) {
                    console.warn('Failed to load stock for variant ' + variantId + ':', xhr.status);
                    // Keep existing stock value if API call fails
                }
            });
        });
    }

    $('#tblForm').on('submit', function() { buildVariantsJson(); });

    $(".list_image").sortable({
        items: ".image-upload-box.has-img",
        axis: "x",
        tolerance: "pointer",
        distance: 5,
        revert: true,
        helper: "clone",
        containment: ".list_image",
        forcePlaceholderSize: true,
        placeholder: "image-upload-placeholder",
        update: function() { refreshImgStatus(); }
    });
    
    // Load stock from Warehouse API for single product (no variants)
    if ($hasVariants.val() === '0') {
        const defaultVariantId = existingVariants.length > 0 ? existingVariants[0].id : null;
        if (defaultVariantId) {
            $('#single_stock_loading').show();
            $.ajax({
                url: '/admin/api/v1/warehouse/variants/' + defaultVariantId + '/stock',
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const stock = response.data.current_stock || 0;
                        $('#single_stock_qty').val(stock);
                        $('#single_stock_loaded').show();
                    }
                },
                error: function(xhr) {
                    console.warn('Failed to load stock for variant ' + defaultVariantId + ':', xhr.status);
                },
                complete: function() {
                    $('#single_stock_loading').hide();
                }
            });
        }
    }

    // --- Auto-link ingredients (public Dictionary API) + Ingredient analysis (admin API) ---
    let ingredientDictionaryCache = null;
    let ingredientDictionaryLoading = false;
    let ingredientAnalysisLibrary = null;
    let ingredientAnalysisLoading = false;

    function escapeRegexForIngredient(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function loadIngredientDictionary(callback) {
        if (ingredientDictionaryCache) {
            callback(ingredientDictionaryCache);
            return;
        }
        if (ingredientDictionaryLoading) {
            return;
        }
        ingredientDictionaryLoading = true;

        fetch('/api/dictionary/ingredients', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.json();
        }).then(function (json) {
            if (!json || !json.success || !Array.isArray(json.data)) {
                alert('Khong the tai danh sach thanh phan.');
                return;
            }
            // Ensure longest titles first as a safety net
            ingredientDictionaryCache = json.data.slice().sort(function (a, b) {
                var at = (a.title || '').length;
                var bt = (b.title || '').length;
                return bt - at;
            });
            callback(ingredientDictionaryCache);
        }).catch(function () {
            alert('Loi ket noi API thanh phan.');
        }).finally(function () {
            ingredientDictionaryLoading = false;
        });
    }

    function loadIngredientAnalysisLibrary(callback) {
        if (ingredientAnalysisLibrary) {
            callback(ingredientAnalysisLibrary);
            return;
        }
        if (ingredientAnalysisLoading) {
            return;
        }
        ingredientAnalysisLoading = true;

        fetch('/api/admin/dictionary/all-ingredients', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.json();
        }).then(function (json) {
            if (!json || !json.success || !Array.isArray(json.data)) {
                alert('Khong the tai thu vien phan tich thanh phan.');
                return;
            }
            // Already sorted by title length desc from API, but ensure again
            ingredientAnalysisLibrary = json.data.slice().sort(function (a, b) {
                var at = (a.title || '').length;
                var bt = (b.title || '').length;
                return bt - at;
            });
            callback(ingredientAnalysisLibrary);
        }).catch(function () {
            alert('Loi ket noi API phan tich thanh phan.');
        }).finally(function () {
            ingredientAnalysisLoading = false;
        });
    }

    function autoLinkIngredients() {
        var textarea = document.getElementById('ingredient');
        if (!textarea) return;
        var original = textarea.value || '';
        if (!original.trim()) {
            alert('Khong co noi dung thanh phan de quet.');
            return;
        }

        loadIngredientDictionary(function (list) {
            if (!list || !list.length) {
                alert('Danh sach thanh phan rong.');
                return;
            }

            var text = original;
            list.forEach(function (item) {
                var title = (item.title || '').trim();
                var slug = (item.slug || '').trim();
                if (!title || !slug) return;

                var pattern = '\\b' + escapeRegexForIngredient(title) + '\\b';
                var regex = new RegExp(pattern, 'gi');
                var href = '/thanh-phan/' + slug;

                text = text.replace(regex, function (match) {
                    return '<a href="' + href + '" target="_blank">' + match + '</a>';
                });
            });

            textarea.value = text;
        });
    }

    function analyzeProduct() {
        var textarea = document.getElementById('ingredient');
        var box = document.getElementById('ingredient-analysis');
        if (!textarea || !box) {
            return;
        }
        var original = textarea.value || '';
        if (!original.trim()) {
            alert('Khong co noi dung thanh phan de phan tich.');
            return;
        }

        loadIngredientAnalysisLibrary(function (list) {
            if (!list || !list.length) {
                alert('Thu vien thanh phan rong.');
                return;
            }

            var textLower = original.toLowerCase();
            var matched = [];
            var benefitCount = {};
            var sensitiveWarning = false;
            var oilyCount = 0;

            list.forEach(function (item) {
                var title = (item.title || '').trim();
                var slug = (item.slug || '').trim();
                if (!title) {
                    return;
                }

                var pattern = '\\b' + escapeRegexForIngredient(title) + '\\b';
                var regex = new RegExp(pattern, 'i');
                if (!regex.test(original)) {
                    return;
                }

                matched.push({
                    title: title,
                    slug: slug,
                    benefits: Array.isArray(item.benefits) ? item.benefits : [],
                    rates: Array.isArray(item.rates) ? item.rates : []
                });

                (item.benefits || []).forEach(function (b) {
                    var name = (b && b.name) ? String(b.name) : '';
                    if (!name) {
                        return;
                    }
                    var key = name;
                    if (!benefitCount[key]) {
                        benefitCount[key] = 0;
                    }
                    benefitCount[key] += 1;
                });

                (item.rates || []).forEach(function (r) {
                    var name = (r && r.name) ? String(r.name) : '';
                    if (!name) {
                        return;
                    }
                    var lower = name.toLowerCase();
                    // Heuristic: not suitable for sensitive skin
                    if (lower.indexOf('khong hop da nhay cam') !== -1 ||
                        (lower.indexOf('sensitive') !== -1 && (lower.indexOf('avoid') !== -1 || lower.indexOf('not for') !== -1))) {
                        sensitiveWarning = true;
                    }
                    // Heuristic: oily skin friendly
                    if (lower.indexOf('oily skin') !== -1 || lower.indexOf('da dau') !== -1) {
                        oilyCount += 1;
                    }
                });
            });

        var html = '';
            html += '<div style="font-weight:600;margin-bottom:6px;">Tong quan thanh phan</div>';

        if (!matched.length) {
            html += '<div>Khong tim thay thanh phan nao trong thu vien.</div>';
            } else {
            html += '<div style="margin-bottom:6px;">Tim thay <strong>' + matched.length + '</strong> thanh phan trong thu vien.</div>';

            var benefitList = Object.keys(benefitCount).sort(function (a, b) {
                return benefitCount[b] - benefitCount[a];
            });
            if (benefitList.length) {
                html += '<div style="margin-top:6px;"><strong>Nhom cong dung chinh:</strong></div>';
                html += '<div style="margin-top:4px;">';
                benefitList.slice(0, 6).forEach(function (name) {
                    var count = benefitCount[name];
                    var badgeClass = 'label-default';
                    var lower = name.toLowerCase();
                    if (lower.indexOf('cap am') !== -1 || lower.indexOf('hydrate') !== -1 || lower.indexOf('moistur') !== -1) {
                        badgeClass = 'label-success';
                    } else if (lower.indexOf('chong oxy hoa') !== -1 || lower.indexOf('anti-oxidant') !== -1) {
                        badgeClass = 'label-primary';
                    } else if (lower.indexOf('tẩy') !== -1 || lower.indexOf('peel') !== -1 || lower.indexOf('exfoliat') !== -1) {
                        badgeClass = 'label-warning';
                    }
                    html += '<span class="label ' + badgeClass + '" style="margin-right:4px;margin-bottom:4px;display:inline-block;">' +
                        name + ' (x' + count + ')</span>';
                });
                html += '</div>';
            }

            if (sensitiveWarning) {
                html += '<div style="margin-top:8px;" class="text-danger"><strong>Canh bao:</strong> Co thanh phan co ghi chu khong hop da nhay cam.</div>';
            }

            if (oilyCount >= 3) {
                html += '<div style="margin-top:8px;color:#16a085;font-weight:600;">Nhan dinh: San pham phu hop cho da dau (nhieu thanh phan danh cho oily skin).</div>';
            }

            html += '<div style="margin-top:10px;font-size:12px;color:#666;">Danh sach thanh phan khop:</div>';
            html += '<div style="margin-top:4px;">';
            html += matched.map(function (m) {
                if (m.slug) {
                    return '<span class="label label-default" style="margin-right:4px;margin-bottom:4px;display:inline-block;"><a href="/thanh-phan/' + m.slug + '" target="_blank" style="color:inherit;text-decoration:none;">' + m.title + '</a></span>';
                }
                return '<span class="label label-default" style="margin-right:4px;margin-bottom:4px;display:inline-block;">' + m.title + '</span>';
            }).join(' ');
            html += '</div>';
            }

            box.innerHTML = html;
            box.style.display = 'block';

            // Auto-suggest: if main benefits contain hydration/cap am, try to tick related checkbox
            var hasHydration = false;
            benefitList = Object.keys(benefitCount);
            benefitList.forEach(function (name) {
                var lower = name.toLowerCase();
                if (lower.indexOf('cap am') !== -1 || lower.indexOf('hydrate') !== -1 || lower.indexOf('moistur') !== -1) {
                    hasHydration = true;
                }
            });
            if (hasHydration) {
                var $hydrationCheckbox = $('input[type=checkbox][name^=\"benefit_\"][data-benefit-key=\"cap-am\"], input[type=checkbox][data-benefit-label*=\"Cap am\"], input[type=checkbox][data-benefit-label*=\"cấp ẩm\"]');
                if ($hydrationCheckbox.length) {
                    $hydrationCheckbox.prop('checked', true);
                }
            }
        });
    }

    $('#btnAutoLinkIngredients').on('click', function () {
        autoLinkIngredients();
        analyzeProduct();
    });
    $('#btnAnalyzeIngredients').on('click', function () {
        analyzeProduct();
    });
    $('#ingredient').on('input', function () {
        analyzeProduct();
    });

    // --- Category Picker (Edit) using /api/categories/hierarchical ---
    var catFlatCache = null;
    var catChildrenMap = {};
    var catById = {};
    var catActivePath = [0, 0, 0, 0]; // [lvl1,lvl2,lvl3,lvl4]
    var catSelectedId = ($('#cat_id_hidden_edit').val() || $('#cat_id_select_edit').val() || '').toString();
    var catSelectedNames = [];

    function catNormalizeId(v) {
        var n = parseInt(v, 10);
        return isNaN(n) ? 0 : n;
    }

    function buildCatMaps(items) {
        catChildrenMap = {};
        catById = {};
        (items || []).forEach(function (it) {
            var id = catNormalizeId(it.id);
            var pid = catNormalizeId(it.parent_id);
            catById[id] = it;
            if (!catChildrenMap[pid]) catChildrenMap[pid] = [];
            catChildrenMap[pid].push(it);
        });
    }

    function populateCategorySelectEdit() {
        var $select = $('#cat_id_select_edit');
        if (!$select.length) return;

        var selectedId = (catSelectedId || '').toString();
        $select.empty();
        $select.append('<option value="">Chon nganh hang</option>');

        function appendChildren(parentId, depth) {
            var items = catChildrenMap[catNormalizeId(parentId)] || [];
            (items || []).forEach(function (it) {
                var id = catNormalizeId(it.id);
                var title = (it.title || '').toString();
                var prefix = '';
                for (var i = 0; i < depth; i++) prefix += '-- ';
                var isSelected = selectedId && catNormalizeId(selectedId) === id;
                $select.append('<option value="' + id + '"' + (isSelected ? ' selected' : '') + '>' + prefix + title + '</option>');
                appendChildren(id, depth + 1);
            });
        }

        appendChildren(0, 0);
    }

    function loadCategoryFlat(cb) {
        if (catFlatCache) {
            cb(catFlatCache);
            return;
        }
        fetch('/api/categories/hierarchical', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json || !json.success || !Array.isArray(json.data)) return;
                catFlatCache = json.data;
                buildCatMaps(catFlatCache);
                populateCategorySelectEdit();
                cb(catFlatCache);
            })
            .catch(function () {});
    }

    function findParentId(id) {
        var it = catById[catNormalizeId(id)];
        return it ? catNormalizeId(it.parent_id) : 0;
    }

    function buildPathToRoot(selectedId) {
        var path = [];
        var current = catNormalizeId(selectedId);
        var guard = 0;
        while (current > 0 && guard < 10) {
            path.unshift(current);
            current = findParentId(current);
            guard += 1;
        }
        return path;
    }

    function setEmptyColumn(containerId) {
        var $c = $(containerId);
        $c.empty();
    }

    function renderColumn(containerId, parentId, level) {
        var $c = $(containerId);
        $c.empty();
        var items = catChildrenMap[catNormalizeId(parentId)] || [];
        if (!items.length) {
            return;
        }
        items.forEach(function (it) {
            var id = catNormalizeId(it.id);
            var title = (it.title || '').toString();
            var children = catChildrenMap[id] || [];
            var arrow = children.length ? ' &gt;' : '';
            var isActive = catActivePath[level - 1] === id;
            var isSelected = catNormalizeId(catSelectedId) === id;
            var cls = 'category-item';
            if (isActive) cls += ' active';
            if (isSelected) cls += ' selected';
            $c.append(
                '<div class="' + cls + '" data-id="' + id + '" data-level="' + level + '">' +
                '<span class="category-title">' + title + arrow + '</span>' +
                '</div>'
            );
        });
    }

    function updateSelectedPath() {
        var parts = [];
        for (var i = 0; i < 4; i++) {
            var id = catActivePath[i];
            if (id && catById[id]) parts.push((catById[id].title || '').toString());
        }
        if (!parts.length && catSelectedId && catById[catNormalizeId(catSelectedId)]) {
            parts = buildPathToRoot(catSelectedId).map(function (id2) {
                return (catById[id2].title || '').toString();
            });
        }
        catSelectedNames = parts;
        $('#categoryBreadcrumbEdit').text(parts.length ? ('Da chon: ' + parts.join(' > ')) : 'Da chon:');
    }

    function applySelectedToView() {
        var finalId = 0;
        for (var i = 3; i >= 0; i--) {
            if (catActivePath[i]) { finalId = catActivePath[i]; break; }
        }
        if (!finalId && catSelectedId) finalId = catNormalizeId(catSelectedId);
        if (!finalId) return;

        catSelectedId = finalId.toString();
        $('#cat_id_select_edit').val(catSelectedId);
        $('#cat_id_hidden_edit').val(catSelectedId);
        populateCategorySelectEdit();
        var pathIds = buildPathToRoot(finalId);
        var parts = pathIds.map(function (id) { return (catById[id] ? (catById[id].title || '').toString() : '').toString(); }).filter(Boolean);
        $('#categoryDisplayEdit').text(parts.length ? parts.join(' -> ') : 'Chon nganh hang');
    }

    function openCategoryModalEdit() {
        loadCategoryFlat(function () {
            // Preselect path based on existing selected id
            var path = buildPathToRoot(catSelectedId);
            catActivePath = [0, 0, 0, 0];
            if (path[0]) catActivePath[0] = path[0];
            if (path[1]) catActivePath[1] = path[1];
            if (path[2]) catActivePath[2] = path[2];
            if (path[3]) catActivePath[3] = path[3];

            // First open: only render column 1, clear column 2-4
            renderColumn('#categoryCol1Edit', 0, 1);
            setEmptyColumn('#categoryCol2Edit');
            setEmptyColumn('#categoryCol3Edit');
            setEmptyColumn('#categoryCol4Edit');
            updateSelectedPath();

            $('#categoryPickerModalEdit').modal('show');
        });
    }

    $(document).on('click', '#btnEditCategoryEdit, #categoryDisplayEdit', function () {
        openCategoryModalEdit();
    });

    $(document).on('click', '#categoryPickerModalEdit .category-item', function () {
        var id = catNormalizeId($(this).attr('data-id'));
        var level = catNormalizeId($(this).attr('data-level'));
        if (level < 1 || level > 4) return;

        catActivePath[level - 1] = id;
        for (var i = level; i < 4; i++) {
            catActivePath[i] = 0;
        }

        // Render next level, clear deeper levels
        if (level === 1) {
            renderColumn('#categoryCol1Edit', 0, 1);
            renderColumn('#categoryCol2Edit', id, 2);
            setEmptyColumn('#categoryCol3Edit');
            setEmptyColumn('#categoryCol4Edit');
        } else if (level === 2) {
            renderColumn('#categoryCol2Edit', catActivePath[0] || 0, 2);
            renderColumn('#categoryCol3Edit', id, 3);
            setEmptyColumn('#categoryCol4Edit');
        } else if (level === 3) {
            renderColumn('#categoryCol3Edit', catActivePath[1] || 0, 3);
            renderColumn('#categoryCol4Edit', id, 4);
        } else {
            renderColumn('#categoryCol4Edit', catActivePath[2] || 0, 4);
        }
        updateSelectedPath();

        // If this node has no children, consider it the selected final node
        var children = catChildrenMap[id] || [];
        if (!children.length) {
            catSelectedId = id.toString();
        }
    });

    $('#btnConfirmCategoryEdit').on('click', function () {
        applySelectedToView();
        $('#categoryPickerModalEdit').modal('hide');
    });

    // Search filter in modal (Edit)
    $('#categorySearchEdit').on('input', function () {
        var q = ($(this).val() || '').toString().toLowerCase();
        var $c1 = $('#categoryCol1Edit');
        var $c2 = $('#categoryCol2Edit');
        var $c3 = $('#categoryCol3Edit');
        var $c4 = $('#categoryCol4Edit');

        if (!catFlatCache) {
            return;
        }
        if (!q) {
            renderColumn('#categoryCol1Edit', 0, 1);
            setEmptyColumn('#categoryCol2Edit');
            setEmptyColumn('#categoryCol3Edit');
            setEmptyColumn('#categoryCol4Edit');
            return;
        }

        $c1.empty(); $c2.empty(); $c3.empty(); $c4.empty();
        var matches = catFlatCache.filter(function (it) {
            var t = (it.title || '').toString().toLowerCase();
            return t.indexOf(q) !== -1;
        });
        if (!matches.length) {
            $c1.append('<div class="text-muted" style="font-size:12px;">Khong tim thay</div>');
            return;
        }
        matches.forEach(function (it) {
            var id = catNormalizeId(it.id);
            var title = (it.title || '').toString();
            $c1.append(
                '<div class="category-item" data-id="' + id + '" data-level="1">' +
                '<span class="category-title">' + title + '</span>' +
                '</div>'
            );
        });
    });

    // Init display text on page load (Edit)
    loadCategoryFlat(function () {
        if (catSelectedId) {
            var path = buildPathToRoot(catSelectedId);
            var parts = path.map(function (id) { return (catById[id] ? (catById[id].title || '').toString() : '').toString(); }).filter(Boolean);
            if (parts.length) $('#categoryDisplayEdit').text(parts.join(' -> '));
            $('#cat_id_hidden_edit').val(catSelectedId.toString());
        }
    });
});
</script>

<!-- Category Picker Modal (Edit) -->
<div class="modal fade" id="categoryPickerModalEdit" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:8px;overflow:hidden;">
            <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;">
                <h4 class="modal-title" style="margin:0;">Chon nganh hang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="category-search" style="margin-bottom:8px;">
                    <input type="text" id="categorySearchEdit" class="form-control" placeholder="Tim nhanh nganh hang..." style="font-size:13px;height:30px;">
                </div>
                <div class="category-container" style="display:flex;gap:10px;">
                    <div class="category-column"><div id="categoryCol1Edit"></div></div>
                    <div class="category-column"><div id="categoryCol2Edit"></div></div>
                    <div class="category-column"><div id="categoryCol3Edit"></div></div>
                    <div class="category-column"><div id="categoryCol4Edit"></div></div>
                </div>
                <div id="categoryBreadcrumbEdit" class="category-breadcrumb" style="margin-top:10px;font-size:12px;color:#666;">Da chon:</div>
            </div>
            <div class="modal-footer" style="display:flex;align-items:center;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Dong</button>
                <button type="button" class="btn btn-primary" id="btnConfirmCategoryEdit">Ap dung</button>
            </div>
        </div>
    </div>
</div>

<style>
    .category-container { overflow-x: auto; }
    .category-column { min-width: 200px; border: 1px solid #eee; border-radius: 6px; padding: 8px; min-height: 260px; background: #fff; }
    .category-item { padding: 6px 8px; border-radius: 6px; cursor: pointer; font-size: 13px; border: 1px solid transparent; }
    .category-item:hover { background: rgba(0,0,0,0.03); }
    .category-item.active { background: #e6f7ff; font-weight: 600; }
    .category-item.selected { background: #e6f7ff; font-weight: 600; border-color: transparent; }
</style>
<style>
    /* Variant table layout - compact & clean */
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
    
    /* Readonly stock input styling */
    .variant-stock[readonly],
    #single_stock_qty[readonly] {
        background-color: #f5f5f5 !important;
        cursor: not-allowed !important;
        color: #666;
    }
    
    .variant-stock.stock-loaded {
        border-left: 3px solid #52c41a;
        padding-left: 5px;
    }
    
    .variant-stock[readonly]:hover {
        background-color: #f0f0f0 !important;
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
