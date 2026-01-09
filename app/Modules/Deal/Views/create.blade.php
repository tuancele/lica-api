@extends('Layout::layout')
@section('title','Tạo deal sốc')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo deal sốc',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('deal.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tiêu đề : </label>
                            <input type="text" name="name" class="form-control" required="">
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Từ ngày: </label>
                                    <input type="datetime-local" name="start" class="form-control" required="">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Đến ngày: </label>
                                    <input type="datetime-local" name="end" class="form-control" required=""> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Giới hạn sản phẩm mua kèm: </label>
                                    <input type="number" name="limited" class="form-control" required="" value="1"> 
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="control-label">Trạng thái </label>
                                    <select name="status" class="form-control">
                                        <option value="1">Kích hoạt</option>
                                        <option value="0">Ngừng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm chính</h4>
                                    <button type="button" class="button add btn btn-info pull-right btn_showproduct"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product"></div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm mua kèm</h4>
                                    <button type="button" class="button add btn btn-info pull-right btn_showproduct2"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product2"></div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('deal')])
        </div>
    </form>
</section>
<div class="modal fade box-body" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Chọn sản phẩm</h4>
      </div>
      <form class="choseProduct" method="post">
        
      
        </form>
    </div>
  </div>
</div>
<div class="modal fade box-body" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Chọn sản mua kèm</h4>
      </div>
      <form class="choseProduct2" method="post">
        
      
        </form>
    </div>
  </div>
</div>
<script>
    $('#myModal').on('click','.page-link',function(){
        var page = $(this).attr('data-page');
        var search = $('input[name="search"]').val();
        var brand = $('select[name="brand"]').val();
        $.ajax({
            type: 'post',
            url: '/admin/deal/load-product',
            data:  {page:page,search:search,brand:brand},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#myModal .choseProduct').html(res.html);
                $('#myModal .choseProduct input[name="search"]').val(res.search);
                $('#myModal .choseProduct select[name="brand"]').val(res.brand);
                $('body #myModal .page-item').removeClass('active');
                $('body #myModal .page-item-'+res.page+'').addClass('active');
            },error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
         })
   })
$('.btn_showproduct').click(function(){
    $.ajax({
        type: 'post',
        url: '/admin/deal/load-product',
        data:  {page:1,search:'',brand:''},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('#myModal').modal('show');
            $('#myModal .choseProduct').html(res.html);
            $('#myModal .choseProduct input[name="search"]').val(res.search);
            $('#myModal .choseProduct select[name="brand"]').val(res.brand);
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
        }
     })
});
   $(".choseProduct").on("submit", function (e) {
     e.preventDefault();
      $.ajax({
        type: 'post',
        url: '/admin/deal/chose-product',
        data:  $('.choseProduct').serialize(),
        beforeSend: function () {
            $('.choseProduct button[type="submit"]').html('<img src="/public/image/load.gif" style="height:100%;">');
            $('.choseProduct button[type="submit"]').prop('disabled',true);
        },
        success: function (res) {
          $('.choseProduct button[type="submit"]').html('Xác nhận');
          $('.choseProduct button[type="submit"]').prop('disabled',false);
            $('#myModal').modal('hide');
            $('.load-product').html(res);
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
         }
      })
    })
   $('.btn_showproduct2').click(function(){
    $.ajax({
        type: 'post',
        url: '/admin/deal/load-product2',
        data:  {page:1,search:'',brand:''},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (res) {
            $('#myModal2').modal('show');
            $('#myModal2 .choseProduct2').html(res.html);
            $('#myModal2 .choseProduct2 input[name="search"]').val(res.search);
            $('#myModal2 .choseProduct2 select[name="brand"]').val(res.brand);
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
        }
     })
});

   $('#myModal2').on('click','.page-link',function(){
        var page = $(this).attr('data-page');
        var search = $('input[name="search"]').val();
        var brand = $('select[name="brand"]').val();
        $.ajax({
            type: 'post',
            url: '/admin/deal/load-product2',
            data:  {page:page,search:search,brand:brand},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('#myModal2 .choseProduct2').html(res.html);
                $('#myModal2 .choseProduct2 input[name="search"]').val(res.search);
                $('#myModal2 .choseProduct2 select[name="brand"]').val(res.brand);
                $('body #myModal2 .page-item').removeClass('active');
                $('body #myModal2 .page-item-'+res.page+'').addClass('active');
            },error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
            }
         })
   })
   $(".choseProduct2").on("submit", function (e) {
     e.preventDefault();
      $.ajax({
        type: 'post',
        url: '/admin/deal/chose-product2',
        data:  $('.choseProduct2').serialize(),
        beforeSend: function () {
            $('.choseProduct2 button[type="submit"]').html('<img src="/public/image/load.gif" style="height:100%;">');
            $('.choseProduct2 button[type="submit"]').prop('disabled',true);
        },
        success: function (res) {
          $('.choseProduct2 button[type="submit"]').html('Xác nhận');
          $('.choseProduct2 button[type="submit"]').prop('disabled',false);
            $('#myModal2').modal('hide');
            $('.load-product2').html(res);
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
         }
      })
    })
</script>
@endsection