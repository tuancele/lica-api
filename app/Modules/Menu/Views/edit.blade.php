@extends('Layout::layout')
@section('title','Sửa menu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa menu',
])
<script src="/public/admin/jquery-ui-1.9.1.custom.min.js" type="text/javascript"></script>
<script src="/public/admin/jquery.mjs.nestedSortable.js" type="text/javascript"></script>
<link rel="stylesheet" href="/public/admin/nestedSortable.css" />
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/menu/edit">
        @csrf
        <div class="row">
            <div class="col-lg-5">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Tên menu</label>
                            <input type="text" value="{{$detail->name}}" class="form-control" name="name">
                            <input type="hidden" name="id" value="{{$detail->id}}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <label>Liên kết menu</label>
                        <p>Chọn menu sau đó kéo thả vào vị trí cần sắp xếp. Sau đó click button Lưu lại để lưu vị trí sắp xếp.</p>
                        <div id="Tree">
                        </div>
                        <button class="btn btn-info" type="button" data-toggle="modal" data-target="#addMenu"><i class="fa fa-plus" aria-hidden="true"></i> Thêm liên kết</button>
                    </div>
                </div>
                
            </div>
        </div>
        <div class="fix_action">
            <div class="form-group">
                    <button type="button" id="Save" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                    <a href="/admin/menu" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
<div class="modal fade" id="editMenu" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Sửa liên kết</h4>
      </div>
      <form class="formEditMenu" method="post">
        @csrf
        <div class="modal-body">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
          </div>
      </form>
    </div>
  </div>
</div>
<div class="modal fade" id="addMenu" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Thêm liên kết</h4>
      </div>
      <form class="formAddMenu" method="post">
        @csrf
      <div class="modal-body">
        <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
            <tr>
                <td width="30%"><label for="inputEmail3" class="control-label">Tiêu đề:</label></td>
                <td width="70%"><input  type="text" name="name" class="form-control" required="">         
                    <input type="hidden" name="group_id" value="{{$detail->id}}">
                </td>
            </tr>
            <tr>
                <td width="30%"><label class="control-label">Hình ảnh:</label></td>
                <td width="30%">
                    @include('Layout::image-r2-input-group',['number' => 1, 'folder' => 'menus'])
                </td>
            </tr>
            <tr>
                <td><label for="inputEmail3" class="control-label">Menu cha:</label></td>
                <td>
                    <select class="form-control" name="parent">
                        <option value="0">None</option>
                        {!! menusMulti($menus,0,'') !!}
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
                <td><input  type="text" name="url" value="#" class="form-control" required="">
                </td>
            </tr>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary">Thêm</button>
      </div>
      </form>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script type="text/javascript">
    $('#addMenu select.select_menu').change(function(){   
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
<style type="text/css">
    .sortable li a{
        float: right;
    }
    .sortable li a.btn-delete{
        color:red;
    }
</style>
<script type="text/javascript">
    function getData(){
        var id = "{{$detail->id}}";
        $.ajax({
            type: 'post',
            url: '/admin/menu/tree',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#Tree').html(res);
            }
        });
    }     
    getData();
    $('#Save').click(function(){
        oSortable = $('.sortable').nestedSortable('toArray'); 
        var name = $('input[name="name"]').val();
        var id = $('input[name="id"]').val();
        $.ajax({
            type: 'post',
            url: '/admin/menu/edit',
            data: {sortable:oSortable,name:name,id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                toastr.success('Cập nhật thành công', 'Thông báo');
                getData();
            }
        });                 
    });
    $('#Tree').on('click','.deleteCate', function(){
        var id = $(this).attr('CatID');
        if (confirm('Bạn có muốn xóa dữ liệu này?'))
        {
            $.ajax({
                type: 'post',
                url: '/admin/menu/delete-link',
                data: {id:id},
                headers:
                {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    getData();
                }
            });
        }
        else
        {
            return false;
        }
    });
    $(".formAddMenu").submit(function (e) {
      e.preventDefault();
        $.ajax({
          type: "POST",
          url: "/admin/menu/add-link",
          data: new FormData(this),
          cache: false,
          contentType: false,
          processData: false,
          dataType: "json",
          beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
          },
          success: (res) => {
            $('.box_img_load_ajax').addClass('hidden');
            if(res.status == 'error'){
                var errTxt = '';
                if(res.errors !== undefined) {
                    Object.keys(res.errors).forEach(key => {
                        errTxt += '<li>'+res.errors[key][0]+'</li>';
                    });
                } else {
                    errTxt = res.message;
                } 
                toastr.error(errTxt, 'Thông báo'); 
            }else{
                toastr.success(res.alert, 'Thông báo');
                getData();
                $(".formAddMenu").find("input[type=text]").val("");
                $(".formAddMenu").find("select").val("0");
                $('#addMenu .show_item').html('<td><label for="inputEmail3" class="control-label">Đường dẫn:</label></td><td><input  type="text" name="url" value="#" class="form-control" required=""></td>');
                $('#addMenu').modal('hide');
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $('.box_img_load_ajax').addClass('hidden');
            if(xhr.status === 403){
                toastr.error('Bạn không có quyền sử dụng thao tác này', 'Thông báo');
            }else{
                toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo');
            }
        }
      });
    });
    $(".formEditMenu").submit(function (e) {
      e.preventDefault();
        $.ajax({
          type: "POST",
          url: "/admin/menu/edit-link",
          data: new FormData(this),
          cache: false,
          contentType: false,
          processData: false,
          dataType: "json",
          beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
          },
          success: (res) => {
            $('.box_img_load_ajax').addClass('hidden');
            if(res.status == 'error'){
                var errTxt = '';
                if(res.errors !== undefined) {
                    Object.keys(res.errors).forEach(key => {
                        errTxt += '<li>'+res.errors[key][0]+'</li>';
                    });
                } else {
                    errTxt = res.message;
                } 
                toastr.error(errTxt, 'Thông báo'); 
            }else{
                toastr.success(res.alert, 'Thông báo');
                getData();
                $(".formEditMenu").find("input[type=text]").val("");
                $(".formEditMenu").find("select").val("0");
                $('#editMenu .show_item').html('<td><label for="inputEmail3" class="control-label">Đường dẫn:</label></td><td><input  type="text" name="url" value="#" class="form-control" required=""></td>');
                $('#editMenu').modal('hide');
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            $('.box_img_load_ajax').addClass('hidden');
            if(xhr.status === 403){
                toastr.error('Bạn không có quyền sử dụng thao tác này', 'Thông báo');
            }else{
                toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo');
            }
        }
      });
    });

    $('#Tree').on('click','.btn_edit_link',function(){
        var item = $(this).data('id');
        $.ajax({
            type:'get',
            url:'/admin/menu/edit-link/'+item,
            data:{},
            success: function (res) {
                $('#editMenu').modal('show');
                $('#editMenu .modal-body').html(res);
            }
        })
    });
</script>
@endsection