@csrf
<div class="modal-body">
    <div class="form-group" style="display: flex;">
        <input type="search" name="search" class="form-control" placeholder="Tìm theo tên sản phẩm">
        <select class="form-control" style="width:400px" name="brand">
            <option value="">---Thương hiệu---</option>
            @if($brands->count() > 0)
            @foreach($brands as $brand)
            <option value="{{$brand->id}}">{{$brand->name}}</option>
            @endforeach
            @endif
        </select>
        <button type="button" class="btn btn-primary btn_search" style="border-radius: initial;">Tìm sản phẩm</button>
    </div>
    <div style="padding-right: 17px;">
        <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;"><input style="margin-right: 0px;" type="checkbox" id="checkall" class="wgr-checkbox"></th>
                    <th width="50%">Sản phẩm</th>
                    <th width="15%">Giá gốc</th>
                    <th width="15%">Giá khuyến mại</th>
                    <th width="15%">Số lượng</th>
                </tr>
            </thead>
        </table>
    </div>
    <div class="scroll-table">
        <div class="list_product">
            <table class="table table-bordered table-striped">
                @if($products->count() > 0)
                @php 
                    if(Session::has('ss_product_deal')){
                        $mang = Session::get('ss_product_deal');
                    }
                @endphp
                <tbody>
                    @foreach($products as $product)
                    @php 
                        $hasVariants = $product->has_variants == 1 && isset($product->variants) && $product->variants->count() > 0;
                    @endphp
                    
                    @if($hasVariants)
                        {{-- Sản phẩm có variants - hiển thị từng variant --}}
                        @foreach($product->variants as $variant)
                        <tr class="item-{{$product->id}}-variant-{{$variant->id}}">
                            <td width="5%" style="text-align: center;">
                                <input @if(isset($mang) && in_array($product->id.'_v'.$variant->id,$mang)) checked @endif style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="{{$product->id}}_v{{$variant->id}}" data-product-id="{{$product->id}}" data-variant-id="{{$variant->id}}">
                                <input type="hidden" name="variant_ids[{{$product->id}}][{{$variant->id}}]" value="{{$variant->id}}">
                            </td>
                            <td width="50%">
                                <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                <p><strong>{{$product->name}}</strong></p>
                                <small class="text-muted">
                                    Phân loại: {{$variant->option1_value ?? 'N/A'}}
                                    @if($variant->sku) <br>SKU: {{$variant->sku}} @endif
                                </small>
                            </td>
                            <td width="15%">{{number_format($variant->price)}}đ</td>
                            <td width="15%">{{number_format($variant->sale)}}đ</td>
                            <td width="15%">
                                @php 
                                $total1 = countProductWarehouse($product->id,'import');
                                $total2 = countProductWarehouse($product->id,'export'); @endphp
                                {{$total1-$total2}}
                            </td>
                        </tr>
                        @endforeach
                    @else
                        {{-- Sản phẩm không có variants --}}
                        @php $variant = $product->variant($product->id) @endphp
                        <tr class="item-{{$product->id}}">
                            <td width="5%" style="text-align: center;">
                                <input @if(isset($mang) && in_array($product->id,$mang)) checked @endif style="margin: 0px;display: inline-block;" type="checkbox" name="productid[]" class="checkbox wgr-checkbox" value="{{$product->id}}" data-product-id="{{$product->id}}" data-variant-id="">
                            </td>
                            <td width="50%">
                                <img src="{{$product->image}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
                                <p>{{$product->name}}</p>
                            </td>
                            <td width="15%">@if(!empty($variant)){{number_format($variant->price)}}đ @endif</td>
                            <td width="15%">@if(!empty($variant)){{number_format($variant->sale)}}đ @endif</td>
                            <td width="15%">
                                @php 
                                $total1 = countProductWarehouse($product->id,'import');
                                $total2 = countProductWarehouse($product->id,'export'); @endphp
                                {{$total1-$total2}}
                            </td>
                        </tr>
                    @endif
                    @endforeach
                </tbody>
                @endif
            </table>
        </div>
        <ul class="pagination" role="navigation">
            @if($pages > 0)
            @for($page = 1;$page <= $pages; $page++)
            <li class="page-item page-item-{{$page}} @if($page == 1) active @endif">
                <a class="page-link " href="javascript:;" data-page="{{$page}}">{{$page}}</a>
            </li>
            @endfor
            @endif
        </ul>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
    <button type="submit" class="btn btn-primary" style="height:32px">Xác nhận</button>
  </div>
  <script>
    $('#checkall').click(function(){
        if (this.checked) {
            var mang = []; 
            $('.checkbox').each(function () { 
                this.checked = true;
                var value = $(this).val();
                mang.push(value);
            });
            $.ajax({
                type: 'post',
                url: '/admin/deal/add-session',
                data:  {mang:mang},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                }
             })
        } else {
            var mang = [];
            $('.checkbox').each(function () { 
                this.checked = false;
                var value = $(this).val();
                mang.push(value);
            });
            $.ajax({
                type: 'post',
                url: '/admin/deal/del-session',
                data:  {mang:mang},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                }
            })
        }
    });
    $('.list_product').on('click','.checkbox',function(){
        var value = $(this).val();
        if (this.checked) {
            var mang = []; 
            mang.push(value);
            $.ajax({
                type: 'post',
                url: '/admin/deal/add-session',
                data:  {mang:mang},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                }
             })
        }else{
            var mang = []; 
            mang.push(value);
            $.ajax({
                type: 'post',
                url: '/admin/deal/del-session',
                data:  {mang:mang},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                }
            })
        }
    })
      $('.btn_search').click(function(){
        var search = $('#myModal input[name="search"]').val();
        var brand = $('#myModal select[name="brand"]').val();
        $.ajax({
            type: 'post',
            url: '/admin/deal/load-product',
            data:  {search:search,page:1,brand:brand,deal_id:'{{$deal_id}}'},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#myModal .choseProduct').html(res.html);
                $('#myModal .choseProduct input[name="search"]').val(res.search);
                $('#myModal .choseProduct select[name="brand"]').val(res.brand);
                $('body #myModal .page-item').removeClass('active');
                $('body #myModal .page-item-'+res.page+'').addClass('active');
            },error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
         })
      });
  </script>
