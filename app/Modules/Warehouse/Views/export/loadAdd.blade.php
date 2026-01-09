<td>
<select class="form-control select_product select" name="product_id[]" required="" style="width: 300px;height:34px">
    <option value="0">Không</option>
    @if($products->count() > 0)
    @foreach($products as $variant)
    <option value="{{$variant->id}}">{{$variant->sku}} - {{$variant->product->name??''}}</option>
    @endforeach
    @endif
</select>
</td>
<td>
<select class="form-control select_color" name="color_id[]" required="" >
</select>
</td>
<td>
<select class="form-control select_size" name="size_id[]" required="" >
</select>
</td>
<td>
<input type="text" name="price[]" class="form-control price" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
</td>
<td>

<input type="number" name="qty[]" class="form-control" min="1" data-validation="required" data-validation-error-msg="Không được bỏ trống" >
</td>
<td>
<a href="javascript:;" class="btnDelete" style="color:red"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
</td>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>$('body .select').select2();</script>