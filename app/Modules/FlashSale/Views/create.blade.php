@extends('Layout::layout')
@section('title','Flash Sale (V2)')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Flash Sale (V2)',
])

<section class="content" data-api-token="{{ $apiToken ?? '' }}">
    <form role="form" id="flashsale-form" method="post" ajax="{{route('flashsale.store')}}">
        @csrf
        <div class="box">
            <div class="box-header with-border">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="box-title">Basic</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-default js-open-picker">
                            <i class="fa fa-plus" aria-hidden="true"></i> Add items
                        </button>
                    </div>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4">
                        <label>Start</label>
                        <input type="datetime-local" name="start" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>End</label>
                        <input type="datetime-local" name="end" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="1">Active</option>
                            <option value="0">Off</option>
                        </select>
                    </div>
                </div>

                <div class="row" style="margin-top:15px;">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="margin-bottom:10px;">
                            Data source: Inventory API v2 (stocks). Items are variant-based.
                        </div>

                        <div class="flashsale-toolbar">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Bulk discount (%)</label>
                                    <input type="number" class="form-control js-bulk-percent" min="0" max="100" placeholder="0-100">
                                </div>
                                <div class="col-md-3">
                                    <label>Bulk qty</label>
                                    <input type="number" class="form-control js-bulk-qty" min="1" placeholder="min 1">
                                </div>
                                <div class="col-md-6 text-right" style="margin-top:24px;">
                                    <button type="button" class="btn btn-default js-bulk-apply">Apply</button>
                                    <button type="button" class="btn btn-default js-bulk-remove">Remove</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive" style="margin-top:10px;">
                            <table class="table table-bordered table-striped" id="flashsale-items-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px; text-align:center;">
                                            <input type="checkbox" class="js-check-all">
                                        </th>
                                        <th>Item</th>
                                        <th style="width:120px;">Price</th>
                                        <th style="width:220px;">Sale</th>
                                        <th style="width:120px;">Qty</th>
                                        <th style="width:90px; text-align:right;">Phy</th>
                                        <th style="width:90px; text-align:right;">Avail</th>
                                        <th style="width:90px; text-align:right;">Sell</th>
                                        <th style="width:70px; text-align:center;">Act</th>
                                    </tr>
                                </thead>
                                <tbody class="js-items-body">
                                    <tr class="js-empty-row">
                                        <td colspan="9" class="text-center text-muted">No items</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-danger js-form-error" style="display:none;"></div>
                    </div>
                </div>
            </div>

            <div class="box-footer text-right">
                <a href="{{route('flashsale')}}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-danger">Save</button>
            </div>
        </div>
    </form>
</section>

<!-- Picker modal -->
<div class="modal fade" id="flashsalePicker" tabindex="-1" role="dialog" aria-labelledby="flashsalePickerLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="flashsalePickerLabel">Pick variants</h4>
            </div>
            <div class="modal-body">
                <div class="row" style="margin-bottom:10px;">
                    <div class="col-md-8">
                        <input type="text" class="form-control js-picker-keyword" placeholder="Search by product name or SKU">
                    </div>
                    <div class="col-md-4 text-right">
                        <button type="button" class="btn btn-default js-picker-search">Search</button>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:420px; overflow:auto;">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;">
                                    <input type="checkbox" class="js-picker-check-all">
                                </th>
                                <th>Variant</th>
                                <th style="width:90px; text-align:right;">Phy</th>
                                <th style="width:90px; text-align:right;">Avail</th>
                                <th style="width:90px; text-align:right;">Sell</th>
                            </tr>
                        </thead>
                        <tbody class="js-picker-body">
                            <tr><td colspan="5" class="text-center text-muted">Type keyword to search</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="row" style="margin-top:10px;">
                    <div class="col-md-6">
                        <div class="text-muted js-picker-meta"></div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-default js-picker-prev">Prev</button>
                        <button type="button" class="btn btn-default js-picker-next">Next</button>
                    </div>
                </div>
                <div class="text-danger js-picker-error" style="display:none; margin-top:10px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary js-picker-add">Add selected</button>
            </div>
        </div>
    </div>
</div>

<style>
.flashsale-toolbar label{font-weight:600;}
.flashsale-toolbar{background:#f7f7f7;padding:10px;border:1px solid #e5e5e5;}
.js-items-body td{vertical-align:middle;}
.js-item-sub{display:block;color:#777;font-size:12px;}
.js-badge{display:inline-block;padding:2px 6px;border:1px solid #ddd;border-radius:10px;font-size:12px;color:#555;}
.js-warn{color:#b36b00;font-size:12px;display:none;}
.js-err{color:#b30000;font-size:12px;display:none;}
.js-sale-wrap{display:flex;gap:6px;align-items:center;}
.js-sale-wrap input{width:100%;}
.js-sale-wrap .js-percent{width:90px;}
</style>

<script src="/js/flashsale-admin-v2.js"></script>
@endsection
