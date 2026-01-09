<div class="form-group">
    <div class="title_input">
        <label class="fw-700">Seo title </label><p class="number_char number_seo_title"><span>@if(isset($title)) {{strlen($title)}} @else 0 @endif </span> / 70 ký tự</p>
    </div>
    <input type="text" name="seo_title" class="form-control" value="@if(isset($title)){{$title}}@endif">
</div>
<div class="form-group">
    <div class="title_input">
        <label class="fw-700">Seo description</label><p class="number_char number_seo_description"><span>@if(isset($description)) {{strlen($description)}} @else 0 @endif</span> / 320 ký tự</p>
    </div>
    <textarea class="form-control" name="seo_description" rows="5" data-validation="length" data-validation-length="max320" data-validation-error-msg-length="Không được vượt quá 320 ký tự!">@if(isset($description)){{$description}}@endif</textarea>
</div>