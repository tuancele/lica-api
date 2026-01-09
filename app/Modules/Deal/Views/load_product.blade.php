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
            <th width="35%">Sản phẩm</th>
            <th width="10%">Giá gốc</th>
            <th width="10%">Giá mua kèm</th>
            <th width="10%">Số lượng</th>
            <th width="10%">Kho hàng</th>
            <th width="10%">Trạng thái</th>
            <th width="10%">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        @if($products->count() > 0)
        @foreach($products as $product)
        @php $variant = $product->variant($product->id) @endphp
        <tr class="item-{{$product->id}}">
            <input type="hidden" name="productsale[]" value="{{$product->id}}">
            <td style="text-align: center;"><input type="checkbox" name="checklist2[]" class="checkbox3 wgr-checkbox" value="{{$product->id}}"></td>
            <td>
                <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                <p>{{$product->name}}</p>
            </td>
            <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
                <input type="hidden" name="price_product" value="{{$variant->price}}">
            @endif</td>
            <td>
                <input type="text" name="pricesale[{{$product->id}}]" class="form-control pricesale price">
                
            </td>
            <td><input type="number" name="numbersale[{{$product->id}}]" class="form-control"></td>
             <td>@php 
                $total3 = countProductWarehouse($product->id,'import');
                $total4 = countProductWarehouse($product->id,'export'); @endphp
                {{$total3-$total4}}
            </td>
            <td><input type="checkbox" name="status2[{{$product->id}}]" class="wgr-checkbox" value="1" checked=""></td>
            <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
        </tr>
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
                $('.updateSale2 tr.item-'+$(this).find("input").val()+'').remove();
                mang.push($(this).find("input").val());
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
        $('.count_choose').html('0');
    });
    $('#updateAll').click(function(){
        var number = $('input[name="number"]').val();
        var percent = $('input[name="sale"]').val(); 
        $(".updateSale2 tr td").each(function () {
            if($(this).find("input").is(':checked')){
                var id = $(this).find("input").val();
                $('.updateSale2 tr.item-'+id+' input[type="number"]').val(number);
                var price = $('.updateSale2 tr.item-'+id+' input[name="price_product"]').val();
                var sale =  parseInt(price) - (parseInt(price/100)*parseInt(percent));
                console.log(sale);
                $('.updateSale2 tr.item-'+id+' input.pricesale').val(sale);
            }
        })
    });
</script>