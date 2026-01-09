@extends('admin.layout')
@section('title','Menu top')
@section('content')
@include('admin.layout.breadcrumb',[
    'title' => 'Menu top',
])

<script src="/public/admin/jquery-ui-1.9.1.custom.min.js" type="text/javascript"></script>
<script src="/public/admin/jquery.mjs.nestedSortable.js" type="text/javascript"></script>
<link rel="stylesheet" href="/public/admin/nestedSortable.css" />

<section class="content">
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-body">
                <p>Chọn menu sau đó kéo thả vào vị trí cần sắp xếp. Sau đó click button Lưu lại để lưu vị trí sắp xếp.</p>
                <div id="Tree">
                </div>
                <button type="button" class="btn btn-primary"  id="Save"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <a href="/admin/menu/create" class="btn btn-info"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div>
</div>
</section>
<style type="text/css">
    .sortable li a{
        float: right;
    }
    .sortable li a.btn-delete{
        color:red;
    }
</style>
<script type="text/javascript">
            
    $(function(){
        $.ajax({
            type: 'post',
            url: '/admin/menu/tree',
            data: {},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#Tree').html(res);
            }
        });
        $('#Save').click(function(){
            oSortable = $('.sortable').nestedSortable('toArray'); 
            $('#Tree').slideUp(function(){
                $.ajax({
                    type: 'post',
                    url: '/admin/menu/tree',
                    data: {sortable:oSortable},
                    headers:
                    {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        $('#Tree').html(res);
                        $('#Tree').slideDown();
                    }
                });
            });                   
        });
        $('#Tree').on('click','.deleteCate', function(){
            var id = $(this).attr('CatID');
            if (confirm('Bạn có muốn xóa dữ liệu này?'))
            {
                $.ajax({
                    type: 'post',
                    url: '/admin/menu/delete',
                    data: {id:id},
                    headers:
                    {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res1) {
                        $('#Tree').slideUp(function(){
                            $.ajax({
                                type: 'post',
                                url: '/admin/menu/tree',
                                data: {},
                                headers:
                                {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (data) {
                                    $('#Tree').html(data);
                                    $('#Tree').slideDown();
                                }
                            });
                        });
                    }
                });
            }
            else
            {
                return false;
            }
            
        });
    });
</script>
@endsection