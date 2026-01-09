@extends('Layout::layout')
@section('title','Đổi mật khẩu')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Đổi mật khẩu',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <h4 style="margin:0px;">Thông tin mật khẩu</h4>
            </div>
            <div class="col-md-2">

            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form method="post" id="formChangePass">
            @csrf
        <table class="table table-bordered table-striped">
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Tài khoản</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" readonly="" value="{{$detail->email}}" name="email">
                            <input type="hidden" name="id" value="{{$detail->id}}">
                        </div>

                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Mật khẩu mới *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="password" id="password"> 
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td width="20%" style="vertical-align:middle;"><label style="margin-bottom: 0px;">Nhập lại mật khẩu mới *</label></td>
                <td width="80%" style="vertical-align:middle;">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="password" class="form-control" name="confirm"> 
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
    $('#formChangePass').validate({
        rules: { 
            password:{
                required: true,
            },
            confirm:{
                required: true,
                equalTo:"#password"
            },
        },
        messages: {
            password:{
                required: "Bạn chưa nhập mật khẩu mới",
            },
            confirm:{
                required: "Bạn chưa nhập lại mật khẩu mới",
                equalTo:"Nhập lại mật khẩu không đúng"
            },
        },
        submitHandler: function (form) {
            $.ajax({
                type: 'post',
                url:  '/admin/user/changepass',
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