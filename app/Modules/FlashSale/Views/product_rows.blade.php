@if($products->count() > 0)
@foreach($products as $product)
@php 
    $variant = $product->variant($product->id);
    $price_sale = '';
    $number_sale = '';
    if(isset($productsales)){
        $pro = $productsales->where('product_id', $product->id)->first();
        if($pro) {
            $price_sale = $pro->price_sale;
            $number_sale = $pro->number;
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
        <input type="hidden" name="price_product" value="{{$variant->price}}">
    @endif</td>
    <td>
        <input type="text" name="pricesale[{{$product->id}}]" class="form-control pricesale price" value="{{$price_sale}}">
    </td>
    <td><input type="number" name="numbersale[{{$product->id}}]" class="form-control" value="{{$number_sale}}"></td>
    <td><a class="btn btn-danger btn-xs delete_item"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
</tr>
@endforeach
@endif
