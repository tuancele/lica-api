$.validate({
    form: '#tblForm',
    modules: 'security',
    onSuccess: function ($form) {
        if (typeof CKEDITOR !== 'undefined') {
            for (instance in CKEDITOR.instances) {
               CKEDITOR.instances[instance].updateElement();
            }
        }
        $.ajax({
            type: 'post',
            url: $('#tblForm').attr('ajax'),
            data: $('#tblForm').serialize(),
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
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
                    if(res.url != ""){
                        setTimeout(function () {
                            window.location = res.url;
                        }, 1500);
                    }
                }
            },
            error: function(xhr, status, error){
                toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
            }
        });
        return false;
    },
});
$('body').on('click','._update',function(){
    $.ajax({
        type: 'post',
        url: $('#tblForm').attr('update-url'),
        data: $('#tblForm').serialize(),
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
            $('.box_img_load_ajax').addClass('hidden');
            window.location = window.location.href;
        },
        error: function(xhr, status, error){
            toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
        }
    })
});
$('body').on('click','.btn_delete',function(){
    if (confirm('Bạn có chắc chắn muốn xóa dữ liệu này?')) {
        var id = $(this).attr('data-id');
        var page = $(this).attr('data-page');
        $.ajax({
            type: 'post',
            url: $('#tblForm').attr('delete-url'),
            data: {id: id,page:page},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                if(res.status == 'error'){
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += res.errors[key][0];
                        });
                    } else {
                        errTxt = res.message;
                    } 
                    toastr.error(errTxt, 'Thông báo'); 
                }else{
                    toastr.success(res.alert, 'Thông báo');
                    setTimeout(function () {
                        window.location = res.url;
                    }, 1500);
                }
            },
            error: function(xhr, status, error){
                toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
            }
        })
    }else{
        return false;
    }
});


$("select.select_status").change(function () {
    var status = $(this).val();
    var id = $(this).attr('data-id');
    $.ajax({
        type: 'post',
        url: $(this).attr('data-url'),
        data: {status: status,id:id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
            $('.box_img_load_ajax').addClass('hidden');
            if(res.status == 'error'){
                var errTxt = '';
                if(res.errors !== undefined) {
                    Object.keys(res.errors).forEach(key => {
                        errTxt += res.errors[key][0];
                    });
                } else {
                    errTxt = res.message;
                } 
                toastr.error(errTxt, 'Thông báo'); 
            }else{
                toastr.success(res.alert, 'Thông báo');
            }
        },
        error: function(xhr, status, error){
            toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
        }
    });
});


$("select.select_category").change(function () {
    var cat_id = $(this).val();
    var id = $(this).attr('data-id');
    $.ajax({
        type: 'post',
        url: $(this).attr('data-url'),
        data: {cat_id: cat_id,id:id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
            $('.box_img_load_ajax').addClass('hidden');
        },
        error: function(xhr, status, error){
            toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
        }
    });
});
$("body .select_sort").change(function () {
    var sort = $(this).val();
    var id = $(this).attr('data-id');
    $.ajax({
        type: 'post',
        url: $(this).attr('data-url'),
        data: {sort: sort,id:id},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
            $('.box_img_load_ajax').addClass('hidden');
            if(res.status == 'error'){
                var errTxt = '';
                if(res.errors !== undefined) {
                    Object.keys(res.errors).forEach(key => {
                        errTxt += res.errors[key][0];
                    });
                } else {
                    errTxt = res.message;
                } 
                toastr.error(errTxt, 'Thông báo'); 
            }else{
                toastr.success(res.alert, 'Thông báo');
                setTimeout(function () {
                    window.location = window.location.href;
                }, 1500);
            }
        },
        error: function(xhr, status, error){
            toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
        }
    });
});

$("input[name='seo_title']").on('keyup', function(){
    var words = $(this).val().length;
    $('.number_seo_title').html("<span>"+(words)+" </span> / 70 ký tự");
});

$("textarea[name='seo_description']").on('keyup', function(){
    var kt = $(this).val();
    var count = kt.length;
    $('.number_seo_description').html('<span>'+count+'</span> / 320 ký tự');
});
$('#checkall').click(function(){
    if (this.checked) { 
        $('.checkbox').each(function () { 
            this.checked = true; 
        });
    } else {
        $('.checkbox').each(function () { 
            this.checked = false;
        });
    }
});
$('.btn_action').click(function(){
    if (confirm('Bạn có chắc chắn muốn thực hiện thao tác này?')) {
        $.ajax({
            type: 'post',
            url: $('#tblForm').attr('action-url'),
            data: $('#tblForm').serialize(),
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                if(res.status == 'error'){
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += res.errors[key][0];
                        });
                    } else {
                        errTxt = res.message;
                    } 
                    toastr.error(errTxt, 'Thông báo'); 
                }else{
                    toastr.success(res.alert, 'Thông báo');
                    setTimeout(function () {
                        window.location = res.url;
                    }, 1500);
                }
            },
            error: function(xhr, status, error){
                toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo'); 
            }
        });
    }else{
        return false;
    }
});
setTimeout(function () {
    hiddenalert();
}, 3000);

var withurl = $('.duong_dan span').width() + 10;
$('.duong_dan input').css('padding-left',withurl);

function showalert(string) {
    $('.alert-success').removeClass('hidden');
    $('.alert-success').html('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><h4>  <i class="icon fa fa-check"></i> Thông báo!</h4>'+string+'');
}
function hiddenalert() {
    $('.alert-success').addClass('hidden');
}
function showalertError(string) {
    $('.alert-danger').removeClass('hidden');
    $('.alert-danger').html('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button> <h4><i class="icon fa fa-ban"></i> Thông báo!</h4>'+string+'');
}
function hiddenalertError() {
    $('.alert-danger').addClass('hidden');
}
