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
                                    <button type="button" class="button add btn btn-info pull-right" data-toggle="modal" data-target="#myModal"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product"></div>
                        <div class="updateSale">
                            <table class="table table-bordered table-striped box-body">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall2" class="wgr-checkbox"></th>
                                        <th width="40%">Sản phẩm</th>
                                        <th width="10%">Giá gốc</th>
                                        <th width="10%">Giá khuyến mại</th>
                                        <th width="10%">Số lượng</th>
                                        <th width="10%">Trạng thái</th>
                                        <th width="10%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- rows được append từ marketing-product-search.js -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <h4 class="pull-left">Sản phẩm mua kèm</h4>
                                    <button type="button" class="button add btn btn-info pull-right" data-toggle="modal" data-target="#myModal2"><i class="fa fa-plus" aria-hidden="true"></i> Thêm sản phẩm</button>
                                </div>
                            </div>
                        </div>
                        <div class="load-product2"></div>
                        <div class="updateSale2">
                            <table class="table table-bordered table-striped box-body">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;"><input type="checkbox" id="checkall3" class="wgr-checkbox"></th>
                                        <th width="40%">Sản phẩm</th>
                                        <th width="10%">Giá gốc</th>
                                        <th width="10%">Giá khuyến mại</th>
                                        <th width="10%">Số lượng</th>
                                        <th width="10%">Trạng thái</th>
                                        <th width="10%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- rows được append từ marketing-product-search.js -->
                                </tbody>
                            </table>
                        </div>
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
        <input type="text" id="modalSearch" class="form-control" placeholder="Tìm kiếm sản phẩm..." style="margin-top: 10px;">
      </div>
      <form class="choseProduct" method="post" onsubmit="return false;">
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
<div class="modal fade box-body" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel2">Chọn sản phẩm mua kèm</h4>
        <input type="text" id="modalSearch2" class="form-control" placeholder="Tìm kiếm sản phẩm..." style="margin-top: 10px;">
      </div>
      <form class="choseProduct2" method="post" onsubmit="return false;">
        @csrf
      <div class="modal-body">
        <div style="padding-right: 17px;">
            <table class="table table-bordered table-striped" style="margin-bottom: 0px;">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"><input style="margin-right: 0px;" type="checkbox" id="checkall2" class="wgr-checkbox"></th>
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
            <table class="table table-bordered table-striped" id="productTable2">
                <tbody id="product-list-body2">
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
<script src="/public/js/marketing-product-search.js"></script>
<script>
   // Initialize Marketing Product Search for Deal - Sản phẩm chính
   $(document).ready(function() {
       MarketingProductSearch.init({
           modalId: '#myModal',
           searchInputId: '#modalSearch',
           productListBodyId: '#product-list-body',
           searchRoute: '{{route("deal.search_product")}}?type=main',
           choseRoute: '{{route("deal.chose_product")}}',
           mainProductBodyId: '.updateSale tbody',
           appendToSelector: '.updateSale tbody',
           checkAllId: '#checkall'
       });
       
       // Initialize Marketing Product Search for Deal - Sản phẩm mua kèm
       MarketingProductSearch.init({
           modalId: '#myModal2',
           searchInputId: '#modalSearch2',
           productListBodyId: '#product-list-body2',
           searchRoute: '{{route("deal.search_product")}}?type=sale',
           choseRoute: '{{route("deal.chose_product2")}}',
           mainProductBodyId: '.updateSale2 tbody',
           appendToSelector: '.updateSale2 tbody',
           checkAllId: '#checkall2'
       });
   });
</script>
@endsection