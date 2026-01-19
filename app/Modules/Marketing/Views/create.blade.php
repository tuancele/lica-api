@extends('Layout::layout')
@section('title','Tạo chương trình khuyến mại mới')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo chương trình khuyến mại mới',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('marketing.campaign.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h4>Thông tin chung</h4>
                        <div class="form-group">
                            <label for="name" class="control-label">Tên chương trình:</label>
                            <input type="text" name="name" class="form-control" required="" placeholder="Ví dụ: Siêu sale 9/9">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_at" class="control-label">Từ : </label>
                                    <input type="datetime-local" name="start_at" class="form-control" required="">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_at" class="control-label">Đến : </label>
                                    <input type="datetime-local" name="end_at" class="form-control" required=""> 
                                </div>
                            </div>
                            <div class="col-md-4">
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
                                    <h4>Sản phẩm tham gia</h4>
                                    <button type="button" class="button add btn btn-info" data-toggle="modal" data-target="#myModal"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-sale">
                            @include('Marketing::load_product', ['products' => null])
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
    </form>
</section>
<div class="modal fade box-body" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Chọn sản phẩm</h4>
        <input type="text" id="modalSearch" class="form-control" placeholder="Tìm kiếm sản phẩm..." style="margin-top: 10px;">
      </div>
      <form class="choseProduct" method="post">
        @csrf
      <div class="modal-body">
        <div style="padding-right: 17px;">
            <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"><input style="margin-right: 0px;" type="checkbox" id="checkall" class="wgr-checkbox"></th>
                        <th width="40%">Sản phẩm</th>
                        <th width="15%">Giá gốc</th>
                        <th width="15%">Giá khuyến mại</th>
                        <th width="15%" style="text-align: center;">Tồn kho thực tế</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="scroll-table" style="height: 400px;overflow-y: scroll;">
            <table class="table table-bordered table-striped" id="productTable">
                <tbody id="product-list-body">
                    <!-- Products loaded via Ajax -->
                </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
        <button type="submit" class="btn btn-primary" style="height:32px">Xác nhận</button>
      </div>
        </form>
    </div>
  </div>
</div>
<script>
   // Ajax Search for Modal
   var searchTimeout;
   $('#modalSearch').on('keyup', function() {
       var keyword = $(this).val();
       clearTimeout(searchTimeout);
       searchTimeout = setTimeout(function() {
           loadProducts(keyword);
       }, 500);
   });

   // Load products when modal opens
   $('#myModal').on('shown.bs.modal', function () {
       if($('#product-list-body').children().length === 0) {
           loadProducts('');
       }
   });

   function loadProducts(keyword) {
       $.ajax({
           url: '{{route("marketing.campaign.search_product")}}',
           type: 'POST',
           data: {
               _token: '{{ csrf_token() }}',
               keyword: keyword
           },
           beforeSend: function() {
               $('#product-list-body').html('<tr><td colspan="5" class="text-center">Đang tải...</td></tr>');
           },
           success: function(res) {
               $('#product-list-body').html(res.html);
           },
           error: function() {
               $('#product-list-body').html('<tr><td colspan="5" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>');
           }
       });
   }

   // Select All Logic
   $('#checkall').click(function(){
       var isChecked = $(this).is(':checked');
       $('#product-list-body input[name="productid[]"]').prop('checked', isChecked);
   });

   // Handle Form Submit
   $(".choseProduct").on("submit", function (e) {
     e.preventDefault();
     
     // 1. Get IDs already in the main table to avoid duplicates
     var existingIds = [];
     $('#main-product-body tr').each(function() {
         var id = $(this).attr('class').replace('item-', '');
         if(id) existingIds.push(id);
     });

     // 2. Get IDs selected in modal
     var selectedIds = [];
     $('#product-list-body input[name="productid[]"]:checked').each(function() {
         var id = $(this).val();
         // Only add if not already exists
         if (!existingIds.includes(id)) {
             selectedIds.push(id);
         }
     });

     if(selectedIds.length === 0) {
         // If user selected items but all are duplicates
         if($('#product-list-body input[name="productid[]"]:checked').length > 0) {
             alert('Các sản phẩm đã chọn đều đã có trong danh sách!');
         } else {
             alert('Vui lòng chọn ít nhất 1 sản phẩm');
         }
         return;
     }

      $.ajax({
        type: 'post',
        url: '{{route("marketing.campaign.load_product")}}',
        data: {
            _token: '{{ csrf_token() }}',
            productid: selectedIds,
        },
        beforeSend: function () {
            $('.choseProduct button[type="submit"]').html('<img src="/public/image/load.gif" style="height:100%;">');
            $('.choseProduct button[type="submit"]').prop('disabled',true);
        },
        success: function (res) {
          $('.choseProduct button[type="submit"]').html('Xác nhận');
          $('.choseProduct button[type="submit"]').prop('disabled',false);
            $('#myModal').modal('hide');
            
            // Append rows
            $('#main-product-body').append(res);
            
            // Update counts (if any logic depends on it)
            $('.count_choose').html($('input[name="checklist[]"]:checked').length);
            
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            $('.choseProduct button[type="submit"]').html('Xác nhận');
            $('.choseProduct button[type="submit"]').prop('disabled',false);
         }
      })
    });
    
    // Fix: To support adding more products without losing existing ones:
    // We need to collect existing IDs before submitting.
    var originalSubmit = $(".choseProduct").trigger; // ... wait, I can just modify the data object above.
    
    // Let's modify the submit handler above to include existing IDs.
    
</script>
@endsection
