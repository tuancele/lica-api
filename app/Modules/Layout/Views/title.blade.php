<div class="form-group">
    <label for="inputEmail3" class="control-label">Tiêu đề:</label>
    <input id="slug-source" type="text" name="name" value="@if(isset($title)){{$title}}@endif" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
</div>