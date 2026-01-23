<script type="text/javascript" src="/public/js/jquery.number.js"></script>
<script type="text/javascript">
    $(function(){
        $('body .price').number( true, 0);
    });
</script>
<div style="background-color: #f9f9f9;padding:15px;border-radius: 5px;margin-bottom: 15px;">
    <div class="row">
        <div class="col-md-4">
            <h5>Chỉnh sửa hàng loạt</h5>
            <p>Đã chọn <strong class="count_choose">0</strong> sản phẩm</p>
        </div>
        <div class="col-md-3">
            <label>Khuyến mại (%)</label>
            <input type="number" name="sale" max="100" min="0" class="form-control">
        </div>
        <div class="col-md-4">
            <label>Thao tác</label>
            <div>
                <button type="button" class="btn btn-default pull-left" id="updateAll" style="margin-right: 5px;">Cập nhật hàng loạt</button>
                <button type="button" class="btn btn-default pull-left" id="deleteAll">Xóa hàng loạt</button>
            </div>
        </div>
    </div>
</div>
<div class="updateSale">
<table class="table table-bordered table-striped box-body" id="mainProductTable">
    <thead>
        <tr>
            <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall2" class="wgr-checkbox"></th>
            <th width="30%">Sản phẩm</th>
            <th width="12%">Giá gốc</th>
            <th width="12%">Giảm giá</th>
            <th width="12%">Giá khuyến mại</th>
            <th width="12%" style="text-align: center;">Tồn kho thực tế</th>
            <th width="10%">Thao tác</th>
        </tr>
    </thead>
    <tbody id="main-product-body">
        @if(isset($products))
            @include('Marketing::product_rows', ['products' => $products, 'campaign_products' => $campaign_products ?? null])
        @endif
    </tbody>
</table>
</div>
<button type="submit" class="btn btn-primary" style="height:32px">Lưu lại</button>
<a type="button" href="{{route('marketing.campaign.index')}}" class="btn btn-default">Hủy</a>
<script>
    $('#checkall2').click(function(){
        var isChecked = $(this).is(':checked');
        $('#main-product-body .checkbox2').prop('checked', isChecked);
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });
    
    // Use event delegation properly
    $('#mainProductTable').on('click','input[name="checklist[]"]',function(){
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });
    
    $('#mainProductTable').on('click','.delete_item',function(){
        $(this).closest('tr').remove();
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });

    $('#deleteAll').click(function(){
        $("#main-product-body tr").each(function () {
            if($(this).find("input[type='checkbox']").is(':checked')){
                $(this).remove();
            }
        });
        $('.count_choose').html('0');
        $('#checkall2').prop('checked', false);
    });

    $('#updateAll').click(function(){
        var percent = $('input[name="sale"]').val(); 
        if(percent < 0 || percent > 100) { alert('Phần trăm không hợp lệ'); return; }
        
        $("#main-product-body tr").each(function () {
            if($(this).find("input[type='checkbox']").is(':checked')){
                var price = $(this).find('input.price_product').val();
                if(price) {
                    var sale =  parseInt(price) - (parseInt(price)/100 * parseFloat(percent));
                    $(this).find('input.pricesale').val(Math.round(sale));
                    $(this).find('input.discount_percent').val(percent);
                }
            }
        });
    });

    // Realtime calculation
    $('#mainProductTable').on('keyup change', 'input.discount_percent', function(){
        var row = $(this).closest('tr');
        var percent = $(this).val();
        var original = row.find('input.price_product').val();
        
        if(original && percent !== '') {
            var sale = parseInt(original) - (parseInt(original) * parseFloat(percent) / 100);
            row.find('input.pricesale').val(Math.round(sale));
        }
    });

    $('#mainProductTable').on('keyup change', 'input.pricesale', function(){
        var row = $(this).closest('tr');
        var sale = $(this).val().replace(/,/g, '');
        var original = row.find('input.price_product').val();
        
        if(original && sale !== '') {
            var percent = 100 - (parseFloat(sale) / parseInt(original) * 100);
            // Round to integer to avoid validation issues (integer requirement)
            row.find('input.discount_percent').val(Math.round(percent));
        }
    });
</script>
