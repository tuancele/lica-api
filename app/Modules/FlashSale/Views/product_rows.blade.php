@if($products->count() > 0)
@foreach($products as $product)
@php 
    $product->load('variants');
    $hasVariants = $product->has_variants == 1 && $product->variants->count() > 0;
    
    if(isset($productsales)){
        $productSales = $productsales->where('product_id', $product->id);
    } else {
        $productSales = collect([]);
    }
@endphp

@if($hasVariants)
    {{-- Sản phẩm có variants - hiển thị từng variant --}}
    @foreach($product->variants as $variant)
    @php
        $productSale = $productSales->where('variant_id', $variant->id)->first();
        $price_sale = $productSale ? $productSale->price_sale : '';
        $number_sale = $productSale ? $productSale->number : '';
    @endphp
    <tr class="item-{{$product->id}}-variant-{{$variant->id}}">
        <td style="text-align: center;">
            <input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}_v{{$variant->id}}">
            <input type="hidden" name="variant_ids[{{$product->id}}][{{$variant->id}}]" value="{{$variant->id}}">
        </td>
        <td>
            <img src="{{getImage($product->image)}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
            <p><strong>{{$product->name}}</strong></p>
            <small class="text-muted">
                Phân loại: {{$variant->option1_value ?? 'N/A'}}
                @if($variant->color) - Màu: {{$variant->color->name}} @endif
                @if($variant->size) - Size: {{$variant->size->name}} @endif
                @if($variant->sku) <br>SKU: {{$variant->sku}} @endif
            </small>
        </td>
        <td>
            {{number_format($variant->price)}}đ
            <input type="hidden" name="price_product[{{$product->id}}][{{$variant->id}}]" value="{{$variant->price}}">
        </td>
        <td>
            <input type="text" name="pricesale[{{$product->id}}][{{$variant->id}}]" class="form-control pricesale price" value="{{$price_sale}}">
        </td>
        <td>
            <input type="number" name="numbersale[{{$product->id}}][{{$variant->id}}]" class="form-control" value="{{$number_sale}}">
        </td>
        <td>
            <a class="btn btn-danger btn-xs delete_item"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
        </td>
    </tr>
    @endforeach
@else
    {{-- Sản phẩm không có variants - logic cũ --}}
    @php 
        $variant = $product->variant($product->id);
        $price_sale = '';
        $number_sale = '';
        $productSale = $productSales->whereNull('variant_id')->first();
        if($productSale) {
            $price_sale = $productSale->price_sale;
            $number_sale = $productSale->number;
        }
    @endphp
    <tr class="item-{{$product->id}}">
        <td style="text-align: center;">
            <input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}">
        </td>
        <td>
            <img src="{{getImage($product->image)}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
            <p>{{$product->name}}</p>
        </td>
        <td>
            @if(!empty($variant))
                {{number_format($variant->price)}}đ 
                <input type="hidden" name="price_product[{{$product->id}}]" value="{{$variant->price}}">
            @endif
        </td>
        <td>
            <input type="text" name="pricesale[{{$product->id}}]" class="form-control pricesale price" value="{{$price_sale}}">
        </td>
        <td>
            <input type="number" name="numbersale[{{$product->id}}]" class="form-control" value="{{$number_sale}}">
        </td>
        <td>
            <a class="btn btn-danger btn-xs delete_item"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
        </td>
    </tr>
@endif
@endforeach
@endif
