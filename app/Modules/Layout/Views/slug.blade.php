<div class="form-group">
    <label class="control-label">Đường dẫn:</label>
    <input type="text" id="slug-target" name="slug" value="@if(isset($slug)){{$slug}}@endif" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max150" data-validation-error-msg-length="Không được vượt quá 150 ký tự!">
</div>