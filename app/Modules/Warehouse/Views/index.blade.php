@extends('Layout::layout')
@section('title','Kho hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Kho hàng',
])
<section class="content" data-api-token="{{ $apiToken }}">
<div class="box">
    <div class="box-header with-border">
        <form id="warehouse-filter-form">
            <div class="row">
                <div class="col-md-4">
                    <input id="warehouse-keyword" type="text" class="form-control" placeholder="Ten san pham / SKU">
                </div>
                <div class="col-md-2">
                    <input id="warehouse-min-stock" type="number" class="form-control" placeholder="Min stock">
                </div>
                <div class="col-md-2">
                    <input id="warehouse-max-stock" type="number" class="form-control" placeholder="Max stock">
                </div>
                <div class="col-md-2">
                    <select id="warehouse-limit" class="form-control">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="col-md-2 text-right">
                    <button id="warehouse-filter-submit" type="submit" class="btn btn-default">
                        <i class="fa fa-search" aria-hidden="true"></i> Filter
                    </button>
                </div>
            </div>
            <div class="row" style="margin-top:10px;">
                <div class="col-md-2">
                    <select id="warehouse-sort-by" class="form-control">
                        <option value="product_name" selected>Sort: Product</option>
                        <option value="variant_name">Sort: Variant</option>
                        <option value="stock">Sort: Available</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="warehouse-sort-order" class="form-control">
                        <option value="asc" selected>ASC</option>
                        <option value="desc">DESC</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <div id="warehouse-loading" style="display:none;">Loading...</div>
                    <div id="warehouse-error" class="text-danger" style="display:none;"></div>
                </div>
            </div>
        </form>
    </div><!-- /.box-header -->
    <div class="box-body">
        <div class="PageContent">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>San pham</th>
                        <th>SKU</th>
                        <th>Phan loai</th>
                        <th class="text-right">Physical</th>
                        <th class="text-right">Flash Sale</th>
                        <th class="text-right">Deal</th>
                        <th class="text-right">Available</th>
                    </tr>
                </thead>
                <tbody id="warehouse-table-body">
                    <tr><td colspan="7" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        <div class="row">
            <div class="col-md-12 text-right">
                <div id="warehouse-pagination"></div>
            </div>
        </div>
        
    </div>
</div>
</section>

<!-- Action buttons -->
<div class="row" style="margin-bottom:15px;">
    <div class="col-md-12 text-right">
        <button id="btn-open-import" type="button" class="btn btn-success">Import</button>
        <button id="btn-open-export" type="button" class="btn btn-warning">Export</button>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="modalImport" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Import Stock</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Product</label>
            <select class="form-control js-product-select"></select>
        </div>
        <div class="form-group">
            <label>Variant</label>
            <select class="form-control js-variant-select"></select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" class="form-control js-qty" min="1" placeholder="Nhap so luong">
        </div>
        <div class="text-info js-stock-info"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary js-submit">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="modalExport" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Export Stock</h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
            <label>Product</label>
            <select class="form-control js-product-select"></select>
        </div>
        <div class="form-group">
            <label>Variant</label>
            <select class="form-control js-variant-select"></select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" class="form-control js-qty" min="1" placeholder="Nhap so luong">
        </div>
        <div class="text-info js-stock-info"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary js-submit">Submit</button>
      </div>
    </div>
  </div>
</div>

<!-- Select2 for product search -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.full.min.js"></script>
<script src="/js/warehouse-admin-v2.js"></script>
@endsection