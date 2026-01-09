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
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="form-label required">Giá bán</label>
                            <input type="text" name="price" class="shopee-input price" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="form-label">Giá khuyến mại</label>
                            <input type="text" name="sale" class="shopee-input price" value="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-item">
                            <label class="form-label required">Kho hàng</label>
                            <input type="number" name="stock_qty" class="shopee-input" value="100">
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
                            <input type="text" name="weight" class="shopee-input" value="0.5">
                        </div>
                    </div>
                </div>
            </div>

            <div class="shopee-card">
                <div class="section-title">Mô tả sản phẩm</div>
                <textarea name="content" class="shopee-textarea description" rows="15"></textarea>
                <textarea name="description" class="hidden-data"></textarea>
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
<script type="text/javascript">
$(document).ready(function() {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('.price').number(true, 0);

    // 1-Click Upload Logic
    $('#trigger-upload').click(function() {
        $('#hidden-file-input').click();
    });

    $('#hidden-file-input').change(function() {
        let files = this.files;
        if(files.length === 0) return;

        let formData = new FormData();
        // Laravel Validation expects files[] array
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
            formData.append('files' + i, files[i]); // Keep files0, files1 for current controller loop
        }
        formData.append('TotalFiles', files.length);

        $.ajax({
            type: 'POST',
            url: "{{ route('product.upload') }}",
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $('#trigger-upload').html('<i class="fa fa-spinner fa-spin fa-2x"></i><span style="margin-top:4px;">Đang tải...</span>');
            },
            success: function(data) {
                for(let i = 0; i < data.length; i++) {
                    let html = `<div class="image-upload-box has-img">
                                    <img src="${data[i]}">
                                    <input type="hidden" name="imageOther[]" value="${data[i]}">
                                    <a href="javascript:void(0)" class="remove-btn"><i class="fa fa-times"></i></a>
                                </div>`;
                    $('#trigger-upload').before(html);
                }
                resetUploadBtn();
                refreshImgStatus();
            },
            error: function() {
                alert('Lỗi upload ảnh (Có thể do kích thước hoặc định dạng không hỗ trợ)');
                resetUploadBtn();
            }
        });
    });

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

    $('.list_image').on('click', '.remove-btn', function() {
        $(this).closest('.image-upload-box').remove();
        resetUploadBtn();
        refreshImgStatus();
    });

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
});
</script>
@endsection
