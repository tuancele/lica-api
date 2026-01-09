@extends('Layout::layout')
@section('title','Sửa thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa thành phần',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('ingredient.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tên thành phần: </label>
                                    <input type="text"  id="ingredient_search" value="{{$detail->name}}" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                                    <input type="hidden" name="id" value="{{$detail->id}}">
                                </div>
                                <div class="list_ingredient">
                                </div> 
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Đường dẫn: </label>
                                    <input type="text" value="{{$detail->slug}}" name="slug" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Nội dung: </label> 
                                    <textarea class="form-control ckeditor" name="content">{{$detail->content}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Link crawl</label>
                                    <input type="text" name="link" value="{{$detail->link}}" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                            @include('Layout::status',['status' => $detail->status])
                            </div>
                        </div>
                      
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('ingredient')])
        </div>
    </form>
</section>
<script>
    $('#ingredient_search').change(function(){
        var keyword = $(this).val();
        $.ajax({
            type: 'get',
            url: '/admin/ingredient/getList?s='+keyword,
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.list_ingredient').show();
                $('.list_ingredient').html(res);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    $('.list_ingredient').on('click','a',function(){
        var href = $(this).attr('data-href');
        $.ajax({
            type: 'post',
            url: '/admin/ingredient/getDetail',
            data: {href:href},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.list_ingredient').hide();
                $(".detail_ingredient").html(res);
                $('input[name="content"]').val(res);
                $('input[name="link"]').val(href.replace('https://www.ewg.org/',''));
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
</script>
<style>
    .list_ingredient,.detail_ingredient{
        width:100%;
        height:auto;
        border:1px solid #eee;
        padding:10px 0px;
        margin-bottom:15px;
        display:none;
    }
    .detail_ingredient{
        display: block;
        min-height:100px;
    }
    .list_ingredient{
        position: absolute;
        z-index: 999;
        top: 58px;
        background-color: #fff;
        box-shadow: 0px 0px 5px #ddd;
    }
    .detail_ingredient{
        padding-left:15px;
        padding-right:15px;
        display:block;
    }
    .list_ingredient a{
        display:block;
        color:#000;
        padding:3px 10px;
    }
    .list_ingredient a:hover{
        background-color:#eee;
    }
</style>
@endsection