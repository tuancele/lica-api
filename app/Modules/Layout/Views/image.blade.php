<div class="panel-body">
    <label class="fw-700">@if(isset($title)){{$title}}@else Hình ảnh đại diện @endif</label>
    <div class="form-group avantar{{$number}}">
        <img src="@if(isset($image)){{$image}}@else{{asset('public/admin/no-image.png')}}@endif" class="img-responsive" alt="">
    </div>
    <div class="form-group" style="text-align: center;">
        <input type="hidden" id="ImageUrl{{$number}}" name="@if(isset($name)){{$name}}@else{{'image'}}@endif" value="@if(isset($image)){{$image}}@endif" class="form-control medium_input pull-left">
        <button type="button" class="btn btn-default btn_image btn-sm btnImage" type="button" number="{{$number}}"><i class="fa fa-folder-open-o" aria-hidden="true"></i> Chọn ảnh</button>
        <button type="button" class="btn btn-danger btn_delete_image btn-sm" number="{{$number}}"><i class="fa fa-times" aria-hidden="true"></i> Xóa ảnh</button>
    </div>
</div>