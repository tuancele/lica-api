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
        <div class="col-md-2">
            <label>Khuyến mại (%)</label>
            <input type="number" name="sale" max="100" min="0" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Số lượng SP khuyến mại</label>
            <input type="number" name="number" max="100" min="0" class="form-control">
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
            <th width="40%">Sản phẩm</th>
            <th width="15%">Giá gốc</th>
            <th width="15%">Giá khuyến mại</th>
            <th width="15%">Số lượng khuyến mại</th>
            <th width="15%">Thao tác</th>
        </tr>
    </thead>
    <tbody id="main-product-body">
        @if(isset($products))
            @include('FlashSale::product_rows', ['products' => $products, 'productsales' => $productsales ?? null])
        @endif
    </tbody>
</table>
</div>
<button type="submit" class="btn btn-primary" style="height:32px">Xác nhận</button>
<a type="button" href="{{route('flashsale')}}" class="btn btn-default">Hủy</a>
<script>
    $('#checkall2').click(function(){
        var isChecked = $(this).is(':checked');
        $('#main-product-body .checkbox2').prop('checked', isChecked);
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });
    
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
        var number = $('input[name="number"]').val();
        var percent = $('input[name="sale"]').val(); 
        $("#main-product-body tr").each(function () {
            if($(this).find("input[type='checkbox']").is(':checked')){
                if(number) $(this).find('input[type="number"]').val(number);
                
                var price = $(this).find('input[name="price_product"]').val();
                if(price && percent) {
                    var sale =  parseInt(price) - (parseInt(price)/100 * parseFloat(percent));
                    $(this).find('input.pricesale').val(Math.round(sale));
                }
            }
        });
    });
</script>
