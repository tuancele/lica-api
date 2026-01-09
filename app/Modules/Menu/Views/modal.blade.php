<table class="table table-bordered table-striped" style="margin-bottom: 0px;">
    <tr>
        <td width="30%"><label for="inputEmail3" class="control-label">Tiêu đề:</label></td>
        <td width="70%"><input value="{{$detail->name}}" type="text" name="name" class="form-control"></td>
        <input type="hidden" name="id" value="{{$detail->id}}">
    </tr>
    <tr>
        <td width="30%"><label class="control-label">Hình ảnh:</label></td>
            <td width="30%">
                <div class="input-group">
                    <input type="text" class="form-control" name="image" id="ImageUrl1" value="{{$detail->image}}">
                    <span class="input-group-btn">
                      <button class="btn btn-default btnImage" type="button" number="1"><i class="fa fa-folder-open-o" aria-hidden="true"></i></button>
                    </span>
                </div>
                <div class="avantar1 showimage" style="margin-top:10px"><img src="{{getImage($detail->image)}}" style="width:100%"></div>
            </td>
        </tr>
    <tr>
        <td><label for="inputEmail3" class="control-label">Menu cha:</label></td>
        <td>
            <select class="form-control" name="parent">
                <option value="0">None</option>
                {!! menusMulti($menus,0,'',$detail->parent) !!}
            </select>
        </td>
    </tr>
    <tr>
        <td><label for="inputEmail3" class="control-label">Giao điểm:</label></td>
        <td>
            <select class="form-control select_menu">
                <option value="slug">Đường dẫn</option>
                <option value="category">Chuyên mục bài viết</option>
                <option value="taxonomy">Danh mục sản phẩm</option>
                <option value="page">Trang tĩnh</option>
            </select>
        </td>
    </tr>
    <tr class="show_item">
        <td><label for="inputEmail3" class="control-label">Đường dẫn:</label></td>
        <td><input  type="text" name="url" value="{{$detail->url}}" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
        </td>
    </tr>
</table>
<script type="text/javascript">
    $('#editMenu select.select_menu').change(function(){   
        var item = $(this).val();
        $.ajax({
            type:'post',
            url:'/admin/menu/showurl',
            data:{item:item},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.show_item').html(res);
            }
        })
    })
</script>