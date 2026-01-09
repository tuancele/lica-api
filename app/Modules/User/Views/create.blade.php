@extends('Layout::layout')
@section('title','Tạo tài khoản')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo tài khoản',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <h4 style="margin:0px;">Thông tin tài khoản</h4>
            </div>
            <div class="col-md-2">

            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form method="post" id="formAddUser">
            @csrf
        <table class="table table-bordered table-striped">
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Họ tên *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="name">
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Điện thoại *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="phone">
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Phân quyền *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-control" name="role_id">
                                <option value="1">Admin</option>
                                <option value="2">Đăng bài</option>
                            </select>
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Email đăng nhập *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="email">
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Mật khẩu *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="password" id="password"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Nhập lại mật khẩu *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="confirm"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Trạng thái</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-control" name="status">
                                <option value="1">Hoạt động</option>
                                <option value="0">Ngừng hoạt động</option>
                            </select>
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;">
                </td>
                <td width="80%" style="vertical-align:middle;">
                    <button class="btn btn-primary" type="submit"><i class="fa fa-save" aria-hidden="true"></i> Lưu lại</button>
                </td>
            </tr>
        </table>
        </form>
    </div>
</div>
</section>
<script src="public/js/jquery.validate.min.js"></script>
<script type="text/javascript">
    $('#formAddUser').validate({
        rules: { 
            name:{
                required: true,
            },
            phone:{
                required: true,
            },
            email:{
                required: true,
                remote: {
                    url: '/admin/user/checkemail',
                    type: "post",
                    dataType: 'json',
                    headers:
                    {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        email: function () {
                            return $('#formAddUser :input[name="email"]').val();
                        }
                    }
                },
                email:true,   
            },
            password:{
                required: true,
            },
            confirm:{
                required: true,
                equalTo:"#password"
            },
        },
        messages: {
            name:{
                required: "Bạn chưa nhập họ tên",
            },
            phone:{
                required: "Bạn chưa nhập số điện thoại",
            },
            email:{
                required: "Bạn chưa nhập địa chỉ email",
                remote:'Địa chỉ email đã tồn tại',
                email:"Địa chỉ email không đúng",
            },
            password:{
                required: "Bạn chưa nhập mật khẩu",
            },
            confirm:{
                required: "Bạn chưa nhập lại mật khẩu",
                equalTo:"Nhập lại mật khẩu không đúng"
            },
        },
        submitHandler: function (form) {
            $.ajax({
                type: 'post',
                url:  '/admin/user/create',
                data: $(form).serialize(),
                beforeSend: function () {
                    $('.box_img_load_ajax').removeClass('hidden');
                },
                success: function (res) {
                    if(res.status == 'error'){
                        var errTxt = '';
                        if(res.errors !== undefined) {
                            Object.keys(res.errors).forEach(key => {
                                errTxt = '<li>'+res.errors[key][0]+'</li>';
                            });
                        } else {
                            errTxt = '<li>'+res.message+'</li>';
                        } 
                        toastr.error(errTxt, 'Thông báo'); 
                    }else{
                        toastr.success(res.alert, 'Thông báo');
                        setTimeout(() => {
                            window.location = res.url;
                        }, 1500)
                    }


                }
            });
            return false;
        }
    });
</script>
<style type="text/css">
    label.error{
        color:red;
        font-weight: normal;
    }
</style>
@endsection