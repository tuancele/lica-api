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
        <div class="col-md-4">
            <label>Thao tác</label>
            <div>
                <button type="button" class="btn btn-default pull-left" id="deleteAll">Xóa hàng loạt</button>
            </div>
        </div>
    </div>
</div>
<div class="updateSale">
<table class="table table-bordered table-striped box-body">
    <thead>
        <tr>
            <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall2" class="wgr-checkbox"></th>
            <th width="40%">Sản phẩm</th>
            <th width="10%">Giá gốc</th>
            <th width="10%">Giá khuyến mại</th>
            <th width="10%">Số lượng</th>
            <th width="10%">Trạng thái</th>
            <th width="10%">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        @if($products->count() > 0)
        @foreach($products as $product)
        @php $variant = $product->variant($product->id) @endphp
        <tr class="item-{{$product->id}}">
            <input type="hidden" name="productid[]" value="{{$product->id}}">
            <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}"></td>
            <td>
                <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                <p>{{$product->name}}</p>
            </td>
            <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
            @endif</td>
            <td>
                @if(!empty($variant)){{number_format($variant->sale)}}đ 
                @endif
            </td>
            <td>@php 
                $total1 = countProductWarehouse($product->id,'import');
                $total2 = countProductWarehouse($product->id,'export'); @endphp
                {{$total1-$total2}}
            </td>
            <td><input type="checkbox" name="statusdeal[{{$product->id}}]" class="wgr-checkbox" value="1" checked=""></td>
            <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
        </tr>
        @endforeach
        @endif
    </tbody>
</table>
</div>
<script>
    $('#checkall2').click(function(){
        if (this.checked) { 
            $('.checkbox2').each(function () { 
                this.checked = true; 
            });
        } else {
            $('.checkbox2').each(function () { 
                this.checked = false;
            });
        }
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });
    $('.updateSale').on('click','input[name="checklist[]"]',function(){
        $('.count_choose').html($('input[name="checklist[]"]:checked').length);
    });
    $('.updateSale').on('click','.delete_item',function(){
        var id = $(this).attr('data-id');
        var mang = [];
        mang.push(id);
        $(this).parent().parent().remove();
        $.ajax({
            type: 'post',
            url: '/admin/deal/del-product',
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
        $(".updateSale tr td").each(function () {
            if($(this).find("input").is(':checked')){
                $('.updateSale tr.item-'+$(this).find("input").val()+'').remove();
                mang.push($(this).find("input").val());
            }
        })
        $.ajax({
            type: 'post',
            url: '/admin/deal/del-product',
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
</script>