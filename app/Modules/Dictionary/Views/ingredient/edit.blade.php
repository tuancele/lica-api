@extends('Layout::layout')
@section('title','Sửa thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa thành phần',
])
<section class="content">
    <form role="form" id="tblForm">
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" value="{{$detail->id}}" name="id" id="ingredientId">
                                @include('Layout::title',['title' => $detail->name])
                                @include('Layout::description',['description' => $detail->description])
                                <div class="form-group">
                                    <label>Sơ lược</label>
                                    <textarea class="form-control ckeditor" name="glance">{{$detail->glance}}</textarea>
                                </div> 
                                <div class="form-group">
                                    <label style="display: block;width: 100%;overflow: hidden;">Nội dung</label>
                                    <textarea class="form-control ckeditor" name="content">{{$detail->content}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label style="display: block;width: 100%;overflow: hidden;">Shortcode sản phẩm <button class="btn btn-default pull-right" type="button" data-toggle="modal" data-target="#myModal">Chèn shortcode</button></label>
                                    <textarea class="form-control" id="shortcode" name="shortcode">{{$detail->shortcode}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Tài liệu</label>
                                    <textarea class="form-control ckeditor" name="reference">{{$detail->reference}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Trách nhiệm</label>
                                    <textarea class="form-control" rows="5" name="disclaimer">{{$detail->disclaimer}}</textarea>
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
                        @include('Layout::seo',['title' => $detail->seo_title,'description' => $detail->seo_description])
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
                            <select class="form-control" name="rate_id" id="rateSelect"></select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status',['status' => $detail->status])
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
<div class="modal fade" tabindex="-1" id="myModal" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Chọn sản phẩm</h4>
      </div>
      <div class="modal-body" style="height: 300px;overflow-y: scroll;">
        @if($products->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="5%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                    <th width="15%">Hình ảnh</th>
                    <th width="50%">Tiêu đề</th>
                </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$product->id}}"></td>
                    <td>
                        <img src="{{$product->image}}" style="width: 70px;">
                    </td>
                    <td>{{$product->name}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
        <button type="button" class="btn btn-primary btn_chose_product">Chọn</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

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
    const ingredientId = $('#ingredientId').val();

    function loadOptions(selectedCat = [], selectedBen = [], selectedRate = null) {
        loadDictionary('ingredient-categories', '#catBox', 'cat_id[]', selectedCat);
        loadDictionary('ingredient-benefits', '#benefitBox', 'benefit_id[]', selectedBen);
        loadRates(selectedRate);
    }

    function loadDictionary(path, container, name, selected) {
        $.ajax({
            url: `${API_BASE}/${path}?limit=500&status=1`,
            method: 'GET',
            headers: headers,
            success: function(res){
                if(!res.success){ $(container).html(''); return; }
                const items = res.data || [];
                const html = items.map(item => `
                    <label style="font-weight: normal;">
                        <input type="checkbox" class="wgr-checkbox" name="${name}" value="${item.id}" ${selected.includes(item.id) ? 'checked' : ''}>
                        <span>${item.name}</span>
                    </label>
                `).join('');
                $(container).html(html || 'Không có dữ liệu');
            },
            error: function(){ $(container).html(''); }
        });
    }

    function loadRates(selected) {
        $.ajax({
            url: `${API_BASE}/ingredient-rates?limit=500&status=1`,
            method: 'GET',
            headers: headers,
            success: function(res){
                if(!res.success) { return; }
                const items = res.data || [];
                const html = items.map(item => `<option value="${item.id}" ${item.id == selected ? 'selected' : ''}>${item.name}</option>`).join('');
                $('#rateSelect').html(html);
            }
        });
    }

    function setDetail(detail) {
        $('input[name="name"]').val(detail.name || '');
        $('input[name="slug"]').val(detail.slug || '');
        $('textarea[name="description"]').val(detail.description || '');
        $('textarea[name="shortcode"]').val(detail.shortcode || '');
        $('textarea[name="disclaimer"]').val(detail.disclaimer || '');
        $('input[name="seo_title"]').val(detail.seo_title || '');
        $('textarea[name="seo_description"]').val(detail.seo_description || '');
        $('select[name="status"]').val(detail.status);
        if (window.CKEDITOR && CKEDITOR.instances['content']) {
            CKEDITOR.instances['content'].setData(detail.content || '');
        } else {
            $('textarea[name="content"]').val(detail.content || '');
        }
        if (window.CKEDITOR && CKEDITOR.instances['glance']) {
            CKEDITOR.instances['glance'].setData(detail.glance || '');
        } else {
            $('textarea[name="glance"]').val(detail.glance || '');
        }
        if (window.CKEDITOR && CKEDITOR.instances['reference']) {
            CKEDITOR.instances['reference'].setData(detail.reference || '');
        } else {
            $('textarea[name="reference"]').val(detail.reference || '');
        }
    }

    function loadDetail() {
        $.ajax({
            url: `${API_BASE}/ingredients/${ingredientId}`,
            method: 'GET',
            headers: headers,
            success: function(res){
                if(!res.success){ alert(res.message || 'Không tải được dữ liệu'); return; }
                const d = res.data || {};
                setDetail(d);
                const cats = (d.categories || []).map(c => c.id);
                const bens = (d.benefits || []).map(b => b.id);
                loadOptions(cats, bens, d.rate ? d.rate.id : null);
            },
            error: function(){ alert('Không tải được dữ liệu'); }
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
            url: `${API_BASE}/ingredients/${ingredientId}`,
            method: 'PUT',
            headers: Object.assign({'Content-Type': 'application/json'}, headers),
            data: JSON.stringify(payload),
            success: function(res){
                if(!res.success){
                    alert(res.message || 'Lưu thất bại');
                    return;
                }
                alert(res.message || 'Cập nhật thành công');
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

    $('.btn_chose_product').click(function(){
        var product = [];
        $("#myModal tr td").each(function () {
            if($(this).find("input").is(':checked')){
                product.push($(this).find("input").val());
            }
        });
        var string = '[title Sản phẩm có chứa thành phần][products slug='+product.join(',')+']';
        $('#shortcode').val(string);
        $('#myModal').modal('hide');
    });

    $(document).ready(function(){
        loadDetail();
        $('#tblForm').on('submit', submitForm);
    });
})(jQuery);
</script>
@endsection