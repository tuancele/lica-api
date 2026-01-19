<td class="text-center"><strong></strong></td>
<td>
    <select class="form-control select_product select" name="product_id[]" required>
        <option value="0">-- Chọn sản phẩm --</option>
    </select>
</td>
<td>
    <select class="form-control select_variant" name="variant_id[]" required>
        <option value="">-- Chọn phân loại --</option>
    </select>
    <small class="text-muted variant-info" style="display:none;"></small>
</td>
<td>
    <input type="text" name="price[]" class="form-control input-price" placeholder="0" required data-original-value="">
</td>
<td>
    <input type="text" name="qty[]" class="form-control input-qty" placeholder="0" required data-original-value="">
</td>
<td>
    <input type="text" class="form-control text-total" readonly value="0 đ" style="background-color:#f5f5f5; font-weight:bold; color:#d9534f;">
</td>
<td class="text-center">
    <button type="button" class="btn btn-sm btn-danger btnDelete" title="Xóa">
        <i class="fa fa-trash"></i>
    </button>
</td>
