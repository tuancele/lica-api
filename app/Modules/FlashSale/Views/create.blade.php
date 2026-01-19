@extends('Layout::layout')
@section('title','Tạo chương trình mới')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo chương trình mới',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('flashsale.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h4>Thời gian Sale</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Từ : </label>
                                    <input type="datetime-local" name="start" class="form-control" required="">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Đến : </label>
                                    <input type="datetime-local" name="end" class="form-control" required=""> 
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
                                    <h4>Sản phẩm sale</h4>
                                    <button type="button" class="button add btn btn-info" data-toggle="modal" data-target="#myModal"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-sale">
                            @include('FlashSale::load_product', ['products' => null])
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
                        <th width="12%">Giá gốc</th>
                        <th width="12%">Giá khuyến mại</th>
                        <th width="12%" style="text-align: center;">Tồn kho thực tế</th>
                        <th width="12%" style="text-align: center;">Tồn kho khả dụng</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="scroll-table" style="height: 400px;overflow-y: scroll;">
            <table class="table table-bordered table-striped" id="productTable">
                <tbody id="product-list-body">
                    <!-- Ajax loaded -->
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

   $('#myModal').on('shown.bs.modal', function () {
       if($('#product-list-body').children().length === 0) {
           loadProducts('');
       }
   });

   function loadProducts(keyword) {
       $.ajax({
           url: '{{route("flashsale.search_product")}}',
           type: 'POST',
           data: {
               _token: '{{ csrf_token() }}',
               keyword: keyword
           },
           beforeSend: function() {
               $('#product-list-body').html('<tr><td colspan="6" class="text-center">Đang tải...</td></tr>');
           },
           success: function(res) {
               $('#product-list-body').html(res.html);
           },
           error: function() {
               $('#product-list-body').html('<tr><td colspan="6" class="text-center text-danger">Lỗi tải dữ liệu</td></tr>');
           }
       });
   }

   // Select All Logic
   $('#checkall').click(function(){
       var isChecked = $(this).is(':checked');
       $('#product-list-body input[name="productid[]"]').prop('checked', isChecked);
   });

   $(".choseProduct").on("submit", function (e) {
     e.preventDefault();
     
     // 1. Get IDs already in the main table
     var existingIds = [];
     $('#main-product-body tr').each(function() {
         var id = $(this).attr('class').replace('item-', '');
         if(id) existingIds.push(id);
     });

     // 2. Get IDs selected in modal
     var selectedIds = [];
     $('#product-list-body input[name="productid[]"]:checked').each(function() {
         var id = $(this).val();
         if (!existingIds.includes(id)) {
             selectedIds.push(id);
         }
     });

     if(selectedIds.length === 0) {
         if($('#product-list-body input[name="productid[]"]:checked').length > 0) {
             alert('Các sản phẩm đã chọn đều đã có trong danh sách!');
         } else {
             alert('Vui lòng chọn ít nhất 1 sản phẩm');
         }
         return;
     }

      $.ajax({
        type: 'post',
        url: '{{route("flashsale.chose_product")}}',
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
            // Append
            $('#main-product-body').append(res);
            $('.count_choose').html($('input[name="checklist[]"]:checked').length);
            
            // Initialize validation for newly added rows
            initializeValidation();
        },error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            $('.choseProduct button[type="submit"]').html('Xác nhận');
            $('.choseProduct button[type="submit"]').prop('disabled',false);
         }
      })
    })
    
    // Initialize validation function
    function initializeValidation() {
        // Real-time validation for price sale
        $('#main-product-body').off('keyup change', 'input.pricesale').on('keyup change', 'input.pricesale', function(){
            var row = $(this).closest('tr');
            var salePrice = parseFloat($(this).val().toString().replace(/,/g, '')) || 0;
            var originalPrice = parseFloat(row.data('original-price')) || 0;
            var errorMsg = row.find('.price-error');
            
            if (originalPrice > 0 && salePrice > originalPrice) {
                errorMsg.text('Giá khuyến mại không thể lớn hơn giá gốc (' + number_format(originalPrice) + 'đ)').show();
                $(this).addClass('has-error');
            } else {
                errorMsg.hide();
                $(this).removeClass('has-error');
            }
        });
        
        // Real-time validation for number sale
        $('#main-product-body').off('keyup change', 'input.number-sale').on('keyup change', 'input.number-sale', function(){
            var row = $(this).closest('tr');
            var numberValue = parseInt($(this).val()) || 0;
            var stock = parseInt(row.data('stock')) || 0;
            var availableStock = parseInt(row.data('available-stock')) || stock;
            var errorMsg = row.find('.stock-error');
            
            // Validate: numberValue <= actual_stock (S_phy)
            if (numberValue > stock) {
                errorMsg.text('Số lượng khuyến mại không thể lớn hơn tồn kho thực tế (' + stock + ')').show();
                errorMsg.removeClass('text-warning').addClass('text-danger');
                $(this).addClass('has-error');
            } else if (numberValue < 1) {
                errorMsg.text('Số lượng khuyến mại phải lớn hơn 0').show();
                errorMsg.removeClass('text-warning').addClass('text-danger');
                $(this).addClass('has-error');
            } else {
                // Warning if numberValue > available_stock (but still allow, as this creates Flash Sale Virtual Stock)
                if (numberValue > availableStock) {
                    errorMsg.text('Cảnh báo: Số lượng này sẽ tạo Flash Sale Virtual Stock. Tồn kho khả dụng: ' + availableStock).show();
                    errorMsg.removeClass('text-danger').addClass('text-warning');
                } else {
                    errorMsg.hide();
                    errorMsg.removeClass('text-warning').addClass('text-danger');
                }
                $(this).removeClass('has-error');
            }
        });
    }
    
    // Format number helper
    function number_format(number) {
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Initialize validation on page load
    $(document).ready(function() {
        initializeValidation();
    });
</script>
@endsection
