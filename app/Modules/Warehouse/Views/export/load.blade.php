<tr class="item_product">
    <td>
        <select class="form-control select2" name="product_id[]" id="Product" required="">
            <option value="0">Không</option>
            @if($products->count() > 0)
            @foreach($products as $product)
            <option value="{{$product->id}}">{{$product->name}}</option>
            @endforeach
            @endif
        </select>
    </td>
    <td>
         
         <select class="form-control" name="size_id[]" id="Size" required="" >
         </select>
    </td>
    <td>
        
        <input type="text" name="price[]" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
    </td>
    <td>
        
        <input type="number" name="qty[]" class="form-control" min="1" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
    </td>
    <td>
        <a href="javascript:;" style="color:red"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
    </td>
</tr>
