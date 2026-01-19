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
            <p>Đã chọn <strong class="count_choose2">0</strong> sản phẩm</p>
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
<div class="updateSale2">
<table class="table table-bordered table-striped box-body">
    <thead>
        <tr>
            <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall3" class="wgr-checkbox"></th>
            <th width="30%">Sản phẩm</th>
            <th width="10%">Giá gốc</th>
            <th width="10%">Giá mua kèm</th>
            <th width="10%">Số lượng</th>
            <th width="10%" style="text-align: center;">Tồn kho thực tế</th>
            <th width="10%">Trạng thái</th>
            <th width="10%">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        @if($products->count() > 0)
        @foreach($products as $product)
        @php 
            $hasVariants = $product->has_variants == 1 && isset($product->variants) && $product->variants->count() > 0;
            // Get variant from session if exists
            $sessionKey = null;
            if(Session::has('ss_sale_product')){
                $mang = Session::get('ss_sale_product');
                foreach($mang as $item) {
                    if(strpos($item, $product->id.'_v') === 0 || $item == $product->id) {
                        $sessionKey = $item;
                        break;
                    }
                }
            }
        @endphp
        
        @if($hasVariants && $sessionKey && strpos($sessionKey, '_v') !== false)
            {{-- Sản phẩm có variants và đã chọn variant --}}
            @php 
                $parts = explode('_v', $sessionKey);
                $selectedVariantId = $parts[1];
                $selectedVariant = $product->variants->where('id', $selectedVariantId)->first();
            @endphp
            @if($selectedVariant)
            <tr class="item-{{$product->id}}-variant-{{$selectedVariant->id}}">
                <input type="hidden" name="productsale[]" value="{{$product->id}}_v{{$selectedVariant->id}}">
                <td style="text-align: center;"><input type="checkbox" name="checklist2[]" class="checkbox3 wgr-checkbox" value="{{$product->id}}"></td>
                <td>
                    <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                    <p><strong>{{$product->name}}</strong></p>
                    <small class="text-muted">
                        Phân loại: {{$selectedVariant->option1_value ?? 'N/A'}}
                        @if($selectedVariant->sku) <br>SKU: {{$selectedVariant->sku}} @endif
                    </small>
                </td>
                <td>{{number_format($selectedVariant->price)}}đ 
                    <input type="hidden" name="price_product[{{$product->id}}][{{$selectedVariant->id}}]" value="{{$selectedVariant->price}}">
                </td>
                <td>
                    <input type="text" name="pricesale[{{$product->id}}][{{$selectedVariant->id}}]" class="form-control pricesale price">
                </td>
                <td><input type="number" name="numbersale[{{$product->id}}][{{$selectedVariant->id}}]" class="form-control"></td>
                <td style="text-align: center;">
                    <strong>{{number_format($selectedVariant->actual_stock ?? 0)}}</strong>
                </td>
                <td><input type="checkbox" name="status2[{{$product->id}}][{{$selectedVariant->id}}]" class="wgr-checkbox" value="1" checked=""></td>
                <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}_v{{$selectedVariant->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
            </tr>
            @endif
        @else
            {{-- Sản phẩm không có variants hoặc chưa chọn variant --}}
            @php $variant = $product->variant($product->id) @endphp
            <tr class="item-{{$product->id}}">
                <input type="hidden" name="productsale[]" value="{{$product->id}}">
                <td style="text-align: center;"><input type="checkbox" name="checklist2[]" class="checkbox3 wgr-checkbox" value="{{$product->id}}"></td>
                <td>
                    <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                    <p>{{$product->name}}</p>
                </td>
                <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
                    <input type="hidden" name="price_product[{{$product->id}}]" value="{{$variant->price}}">
                @endif</td>
                <td>
                    <input type="text" name="pricesale[{{$product->id}}]" class="form-control pricesale price">
                </td>
                <td><input type="number" name="numbersale[{{$product->id}}]" class="form-control"></td>
                <td style="text-align: center;">
                    <strong>{{number_format($product->actual_stock ?? 0)}}</strong>
                </td>
                <td><input type="checkbox" name="status2[{{$product->id}}]" class="wgr-checkbox" value="1" checked=""></td>
                <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
            </tr>
        @endif
        @endforeach
        @endif
    </tbody>
</table>
</div>
<script>
    $('#checkall3').click(function(){
        if (this.checked) { 
            $('.checkbox3').each(function () { 
                this.checked = true; 
            });
        } else {
            $('.checkbox3').each(function () { 
                this.checked = false;
            });
        }
        $('.count_choose2').html($('input[name="checklist2[]"]:checked').length);
    });
    $('.updateSale2').on('click','input[name="checklist2[]"]',function(){
        $('.count_choose2').html($('input[name="checklist2[]"]:checked').length);
    });
    $('.updateSale2').on('click','.delete_item',function(){
        var id = $(this).attr('data-id');
        var mang = [];
        mang.push(id);
        $(this).parent().parent().remove();
        $.ajax({
            type: 'post',
            url: '/admin/deal/del-product2',
            data:  {mang:mang},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
            },error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
         })
    })
    $('#deleteAll').click(function(){
        var mang =[];
        $(".updateSale2 tr td").each(function () {
            if($(this).find("input").is(':checked')){
                var dataId = $(this).closest('tr').find('.delete_item').attr('data-id');
                if(dataId) {
                    $('.updateSale2 tr.item-'+dataId.replace('_', '-').replace('_v', '-variant-')+'').remove();
                    mang.push(dataId);
                }
            }
        })
        $.ajax({
            type: 'post',
            url: '/admin/deal/del-product2',
            data:  {mang:mang},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
            },error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
         })
        $('.count_choose2').html('0');
    });
    $('#updateAll').click(function(){
        var number = $('input[name="number"]').val();
        var percent = $('input[name="sale"]').val(); 
        $(".updateSale2 tr td").each(function () {
            if($(this).find("input").is(':checked')){
                var tr = $(this).closest('tr');
                tr.find('input[type="number"]').val(number);
                var priceInput = tr.find('input[name^="price_product"]');
                if(priceInput.length > 0) {
                    var price = priceInput.val();
                    var sale = parseInt(price) - (parseInt(price/100)*parseInt(percent));
                    tr.find('input.pricesale').val(sale);
                }
            }
        })
    });
</script>
