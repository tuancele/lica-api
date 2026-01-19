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
            <th width="30%">Sản phẩm</th>
            <th width="12%">Giá gốc</th>
            <th width="12%">Giá khuyến mại</th>
            <th width="12%">Số lượng khuyến mại</th>
            <th width="10%" style="text-align: center;">Tồn kho thực tế</th>
            <th width="10%" style="text-align: center;">Tồn kho khả dụng</th>
            <th width="10%">Thao tác</th>
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
                var row = $(this);
                var stock = parseInt(row.data('stock')) || 0;
                
                // Update number with validation
                if(number) {
                    var numValue = parseInt(number);
                    if (numValue > stock) {
                        alert('Số lượng khuyến mại (' + numValue + ') không thể lớn hơn tồn kho (' + stock + ')');
                        return false;
                    }
                    row.find('input.number-sale').val(numValue);
                }
                
                // Update price with validation
                var originalPrice = parseFloat(row.data('original-price')) || 0;
                if(originalPrice && percent) {
                    var sale = parseInt(originalPrice) - (parseInt(originalPrice)/100 * parseFloat(percent));
                    if (sale > originalPrice) {
                        alert('Giá khuyến mại không thể lớn hơn giá gốc');
                        return false;
                    }
                    row.find('input.pricesale').val(Math.round(sale));
                }
            }
        });
    });
    
    // Real-time validation for price sale
    $('#mainProductTable').on('keyup change', 'input.pricesale', function(){
        var row = $(this).closest('tr');
        var salePrice = parseFloat($(this).val().toString().replace(/,/g, '')) || 0;
        var originalPrice = parseFloat(row.data('original-price')) || 0;
        var errorMsg = row.find('.price-error');
        
        if (salePrice > originalPrice) {
            errorMsg.text('Giá khuyến mại không thể lớn hơn giá gốc (' + number_format(originalPrice) + 'đ)').show();
            $(this).addClass('has-error');
        } else {
            errorMsg.hide();
            $(this).removeClass('has-error');
        }
    });
    
    // Real-time validation for number sale
    $('#mainProductTable').on('keyup change', 'input.number-sale', function(){
        var row = $(this).closest('tr');
        var numberValue = parseInt($(this).val()) || 0;
        var stock = parseInt(row.data('stock')) || 0;
        var availableStock = parseInt(row.data('available-stock')) || stock;
        var errorMsg = row.find('.stock-error');
        
        // Validate: numberValue <= actual_stock (S_phy)
        if (numberValue > stock) {
            errorMsg.text('Số lượng khuyến mại không thể lớn hơn tồn kho thực tế (' + stock + ')').show();
            $(this).addClass('has-error');
        } else if (numberValue < 1) {
            errorMsg.text('Số lượng khuyến mại phải lớn hơn 0').show();
            $(this).addClass('has-error');
        } else {
            // Warning if numberValue > available_stock (but still allow, as this creates Flash Sale Virtual Stock)
            if (numberValue > availableStock) {
                errorMsg.text('Cảnh báo: Số lượng này sẽ tạo Flash Sale Virtual Stock. Tồn kho khả dụng: ' + availableStock).show();
                errorMsg.removeClass('text-danger').addClass('text-warning');
            } else {
                errorMsg.hide();
                errorMsg.removeClass('text-warning').addClass('text-danger');
            }
            $(this).removeClass('has-error');
        }
    });
    
    // Format number helper
    function number_format(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
</script>
