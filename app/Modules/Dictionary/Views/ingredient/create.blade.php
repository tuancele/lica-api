@extends('Layout::layout')
@section('title','Thêm thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm thành phần',
])
<section class="content">
    <form role="form" id="tblForm">
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('Layout::title')
                                @include('Layout::slug')
                                @include('Layout::description')
                                <div class="form-group">
                                    <label>Sơ lược</label>
                                    <textarea class="form-control ckeditor" name="glance"></textarea>
                                </div> 
                                @include('Layout::content')
                                <div class="form-group">
                                    <label>Tài liệu</label>
                                    <textarea class="form-control ckeditor" name="reference"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Trách nhiệm</label>
                                    <textarea class="form-control" rows="5" name="disclaimer"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy bài viết trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Danh mục</label>
                            <div class="box-category box-body">
                                <div id="catBox">Đang tải...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Lợi ích</label>
                            <div class="box-category box-body">
                                <div id="benefitBox">Đang tải...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Đánh giá</label>
                            <select class="form-control" name="rate_id">
                                <select class="form-control" name="rate_id" id="rateSelect"></select>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status')
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('dictionary.ingredient')])
        </div>
    </form>
</section>
<style>
    .box-category{
        height: 250px;
        overflow-y: scroll;
        width: 100%;
        padding:0px;
    }
    .box-category label{
        display: block;
        overflow: hidden;
    }
    .box-category label.parent{
        font-weight: normal;
        margin-left: 30px;
    }
    .box-category input{
        float: left;
    }
</style>
@endsection
@section('footer')
<script>
(function($){
    const API_BASE = '/admin/api';
    const token = $('meta[name="api-token"]').attr('content') || localStorage.getItem('api_token') || '';
    if (!token) {
        alert('Thiếu API token. Vui lòng kiểm tra tài khoản admin đã có api_token.');
    }
    const headers = token ? { 'Authorization': 'Bearer ' + token } : {};

    function loadOptions() {
        loadDictionary('ingredient-categories', '#catBox', 'cat_id[]');
        loadDictionary('ingredient-benefits', '#benefitBox', 'benefit_id[]');
        loadRates();
    }

    function loadDictionary(path, container, name) {
        $.ajax({
            url: `${API_BASE}/${path}?limit=500&status=1`,
            method: 'GET',
            headers: headers,
            success: function(res){
                if(!res.success) { $(container).html(''); return; }
                const items = res.data || [];
                const html = items.map(item => `
                    <label style="font-weight: normal;">
                        <input type="checkbox" class="wgr-checkbox" name="${name}" value="${item.id}">
                        <span>${item.name}</span>
                    </label>
                `).join('');
                $(container).html(html || 'Không có dữ liệu');
            },
            error: function(){ $(container).html(''); }
        });
    }

    function loadRates() {
        $.ajax({
            url: `${API_BASE}/ingredient-rates?limit=500&status=1`,
            method: 'GET',
            headers: headers,
            success: function(res){
                if(!res.success) { return; }
                const items = res.data || [];
                const html = items.map(item => `<option value="${item.id}">${item.name}</option>`).join('');
                $('#rateSelect').html(html);
            }
        });
    }

    function getCk(name) {
        if (window.CKEDITOR && CKEDITOR.instances[name]) {
            return CKEDITOR.instances[name].getData();
        }
        return $(`[name="${name}"]`).val();
    }

    function collectForm() {
        const catIds = [];
        $('input[name="cat_id[]"]:checked').each(function(){ catIds.push($(this).val()); });
        const benIds = [];
        $('input[name="benefit_id[]"]:checked').each(function(){ benIds.push($(this).val()); });
        return {
            name: $('input[name="name"]').val(),
            slug: $('input[name="slug"]').val(),
            description: $('textarea[name="description"]').val(),
            content: getCk('content'),
            glance: getCk('glance'),
            reference: getCk('reference'),
            disclaimer: $('textarea[name="disclaimer"]').val(),
            shortcode: $('textarea[name="shortcode"]').val(),
            seo_title: $('input[name="seo_title"]').val(),
            seo_description: $('textarea[name="seo_description"]').val(),
            status: $('select[name="status"]').val(),
            rate_id: $('#rateSelect').val(),
            cat_id: catIds,
            benefit_id: benIds
        };
    }

    function submitForm(e){
        e.preventDefault();
        const payload = collectForm();
        $.ajax({
            url: `${API_BASE}/ingredients`,
            method: 'POST',
            headers: Object.assign({'Content-Type': 'application/json'}, headers),
            data: JSON.stringify(payload),
            success: function(res){
                if(!res.success){
                    alert(res.message || 'Lưu thất bại');
                    return;
                }
                alert(res.message || 'Thêm thành công');
                window.location.href = "{{route('dictionary.ingredient')}}";
            },
            error: function(xhr){
                if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                    alert(Object.values(xhr.responseJSON.errors).join('\n'));
                } else {
                    alert('Lưu thất bại');
                }
            }
        });
    }

    $(document).ready(function(){
        loadOptions();
        $('#tblForm').on('submit', submitForm);
    });
})(jQuery);
</script>
@endsection