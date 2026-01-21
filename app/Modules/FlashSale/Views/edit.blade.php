@extends('Layout::layout')
@section('title','Flash Sale (V2) - Edit')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Flash Sale (V2) - Edit',
])

<section class="content" data-api-token="{{ $apiToken ?? '' }}">
    <form role="form" id="tblForm" method="post" action="{{route('flashsale.update')}}" ajax="{{route('flashsale.update')}}">
        @csrf
        <input type="hidden" name="id" value="{{$detail->id}}">

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
                        <input type="datetime-local" value="{{date('Y-m-d\TH:i',$detail->start)}}" name="start" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>End</label>
                        <input type="datetime-local" name="end" value="{{date('Y-m-d\TH:i',$detail->end)}}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="1" @if($detail->status==1) selected @endif>Active</option>
                            <option value="0" @if($detail->status==0) selected @endif>Off</option>
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
                                    @php
                                        $rows = [];
                                        $stockMap = [];
                                        // Build stock map from backend-calculated data (same as create page logic)
                                        foreach(($products ?? []) as $product) {
                                            if ($product->has_variants == 1 && $product->variants) {
                                                foreach($product->variants as $variant) {
                                                    $stockMap[$variant->id] = [
                                                        'physical_stock' => (int) ($variant->actual_stock ?? 0),
                                                        'available_stock' => (int) ($variant->available_stock ?? 0),
                                                        'sellable_stock' => (int) ($variant->sellable_stock ?? 0),
                                                    ];
                                                }
                                            } else {
                                                $variant = $product->variant($product->id);
                                                $stockId = $variant ? $variant->id : $product->id;
                                                $stockMap[$stockId] = [
                                                    'physical_stock' => (int) ($product->actual_stock ?? 0),
                                                    'available_stock' => (int) ($product->available_stock ?? 0),
                                                    'sellable_stock' => (int) ($product->sellable_stock ?? 0),
                                                ];
                                            }
                                        }
                                        foreach(($productsales ?? []) as $ps) {
                                            $variant = $ps->variant;
                                            $product = $ps->product;
                                            if (!$variant || !$product) { continue; }
                                            $variantId = (int) $variant->id;
                                            $stock = $stockMap[$variantId] ?? ['physical_stock' => 0, 'available_stock' => 0, 'sellable_stock' => 0];
                                            $rows[] = [
                                                'product_id' => (int) $product->id,
                                                'variant_id' => $variantId,
                                                'product_name' => (string) ($product->name ?? ''),
                                                'product_image' => (string) ($product->image ?? ''),
                                                'sku' => (string) ($variant->sku ?? ''),
                                                'option' => (string) ($variant->option1_value ?? ''),
                                                'price' => (float) ($variant->price ?? 0),
                                                'sale_price' => (float) ($ps->price_sale ?? 0),
                                                'qty' => (int) ($ps->number ?? 0),
                                                'phy' => $stock['physical_stock'],
                                                'avail' => $stock['available_stock'],
                                                'sell' => $stock['sellable_stock'],
                                            ];
                                        }
                                    @endphp

                                    @if(empty($rows))
                                        <tr class="js-empty-row">
                                            <td colspan="9" class="text-center text-muted">No items</td>
                                        </tr>
                                    @else
                                        @foreach($rows as $r)
                                            <tr class="js-item-row"
                                                data-key="{{$r['product_id']}}_v{{$r['variant_id']}}"
                                                data-product-id="{{$r['product_id']}}"
                                                data-variant-id="{{$r['variant_id']}}"
                                                data-original-price="{{$r['price']}}">
                                                <td style="text-align:center;">
                                                    <input type="checkbox" class="js-item-check" checked>
                                                </td>
                                                <td>
                                                    <strong class="js-item-name">{{ $r['product_name'] }}</strong>
                                                    <span class="js-item-sub">
                                                        SKU: <span class="js-item-sku">{{ $r['sku'] }}</span>
                                                        | Opt: <span class="js-item-opt">{{ $r['option'] ?: 'Default' }}</span>
                                                    </span>
                                                    <span class="js-err js-item-err"></span>
                                                    <span class="js-warn js-item-warn"></span>
                                                    <input type="hidden" name="productid[]" value="{{$r['product_id']}}_v{{$r['variant_id']}}">
                                                </td>
                                                <td>
                                                    <span class="js-item-price">{{ number_format($r['price'], 0, '.', ',') }}</span>
                                                </td>
                                                <td>
                                                    <div class="js-sale-wrap">
                                                        <input type="number"
                                                               class="form-control js-sale-price"
                                                               name="pricesale[{{$r['product_id']}}][{{$r['variant_id']}}]"
                                                               value="{{ (int) $r['sale_price'] }}"
                                                               min="0"
                                                               placeholder="Sale price">
                                                        <input type="number" class="form-control js-percent" min="0" max="100" placeholder="%">
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number"
                                                           class="form-control js-qty"
                                                           name="numbersale[{{$r['product_id']}}][{{$r['variant_id']}}]"
                                                           value="{{ (int) $r['qty'] }}"
                                                           min="1"
                                                           placeholder="Qty">
                                                </td>
                                                <td class="text-right js-phy" data-phy="{{ $r['phy'] }}">{{ $r['phy'] }}</td>
                                                <td class="text-right js-avail" data-avail="{{ $r['avail'] }}">{{ $r['avail'] }}</td>
                                                <td class="text-right js-sell" data-sell="{{ $r['sell'] }}">{{ $r['sell'] }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-danger js-remove">Del</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
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
.js-warn{color:#b36b00;font-size:12px;display:none;}
.js-err{color:#b30000;font-size:12px;display:none;}
.js-sale-wrap{display:flex;gap:6px;align-items:center;}
.js-sale-wrap .js-percent{width:90px;}
</style>

<script src="/js/flashsale-admin-v2.js"></script>
@endsection
