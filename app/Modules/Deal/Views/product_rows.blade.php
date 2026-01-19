@if($products->count() > 0)
@foreach($products as $product)
@php 
    $hasVariants = $product->has_variants == 1 && isset($product->variants) && $product->variants->count() > 0;
    // Get variant from session if exists
    $sessionKey = null;
    if(Session::has('ss_product_deal')){
        $mang = Session::get('ss_product_deal');
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
        $isSoldOut = ($selectedVariant->actual_stock ?? 0) <= 0;
    @endphp
    @if($selectedVariant)
    <tr class="item-{{$product->id}}-variant-{{$selectedVariant->id}} @if($isSoldOut) text-muted @endif">
        <input type="hidden" name="productid[]" value="{{$product->id}}_v{{$selectedVariant->id}}">
        <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}" @if($isSoldOut) disabled @endif></td>
        <td>
            <img src="{{getImage($product->image)}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
            <p><strong>{{$product->name}}</strong></p>
            <small class="text-muted">
                Phân loại: {{$selectedVariant->option1_value ?? 'N/A'}}
                @if($selectedVariant->sku) <br>SKU: {{$selectedVariant->sku}} @endif
            </small>
            @if($isSoldOut)
                <div class="text-danger fs-12 mt-1">Hết quà, không thể chọn</div>
            @endif
        </td>
        <td>{{number_format($selectedVariant->price)}}đ</td>
        <td>{{number_format($selectedVariant->sale)}}đ</td>
        <td>-</td>
        <td style="text-align: center;">
            <strong>{{number_format($selectedVariant->actual_stock ?? 0)}}</strong>
        </td>
        <td><input type="checkbox" name="statusdeal[{{$product->id}}]" class="wgr-checkbox" value="1" @if($isSoldOut) disabled @else checked @endif></td>
        <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}_v{{$selectedVariant->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
    </tr>
    @endif
@else
    {{-- Sản phẩm không có variants hoặc chưa chọn variant --}}
    @php 
        $variant = $product->variant($product->id);
        $isSoldOut = ($product->actual_stock ?? 0) <= 0;
    @endphp
    <tr class="item-{{$product->id}} @if($isSoldOut) text-muted @endif">
        <input type="hidden" name="productid[]" value="{{$product->id}}">
        <td style="text-align: center;"><input type="checkbox" name="checklist[]" class="checkbox2 wgr-checkbox" value="{{$product->id}}" @if($isSoldOut) disabled @endif></td>
        <td>
            <img src="{{getImage($product->image)}}" style="width:50px;height: 50px;float: left;margin-right: 5px;">
            <p>{{$product->name}}</p>
            @if($isSoldOut)
                <div class="text-danger fs-12 mt-1">Hết quà, không thể chọn</div>
            @endif
        </td>
        <td>@if(!empty($variant)){{number_format($variant->price)}}đ 
        @endif</td>
        <td>
            @if(!empty($variant)){{number_format($variant->sale)}}đ 
            @endif
        </td>
        <td>-</td>
        <td style="text-align: center;">
            <strong>{{number_format($product->actual_stock ?? 0)}}</strong>
        </td>
        <td><input type="checkbox" name="statusdeal[{{$product->id}}]" class="wgr-checkbox" value="1" @if($isSoldOut) disabled @else checked @endif></td>
        <td><a class="btn btn-danger btn-xs delete_item" data-id="{{$product->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
    </tr>
@endif
@endforeach
@endif
