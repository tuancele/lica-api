@if($products->count() > 0)
@foreach($products as $product)
@php 
    $variant = $product->variant($product->id);
    // Check if price is set for existing items (edit mode)
    $price_sale = '';
    $percent = '';
    if(isset($campaign_products)){
        $cp = $campaign_products->where('product_id', $product->id)->first();
        if($cp) {
            $price_sale = $cp->price;
            if($variant && $variant->price > 0){
                $percent = round(100 - ($price_sale / $variant->price * 100), 2);
            }
        }
    }
@endphp
<tr class="item-{{$product->id}}">
    <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}"></td>
    <td>
        <img src="{{getImage($product->image)}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
        <p>{{$product->name}}</p>
    </td>
    <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
        <input type="hidden" name="price_product" class="price_product" value="{{$variant->price}}">
    @endif</td>
    <td>
        <div class="input-group">
            <input type="number" class="form-control discount_percent" value="{{$percent}}" placeholder="%" min="0" max="99" style="width: 70px;">
            <span class="input-group-addon">%</span>
        </div>
    </td>
    <td>
        <input type="text" name="pricesale[{{$product->id}}]" class="form-control pricesale price" value="{{$price_sale}}" placeholder="Nhập giá KM">
    </td>
    <td><a class="btn btn-danger btn-xs delete_item"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
</tr>
@endforeach
@endif
