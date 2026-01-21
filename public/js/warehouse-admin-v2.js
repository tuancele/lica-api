/* Admin Warehouse V2 UI (uses Inventory API v2) */
(function ($) {
    const API_BASE = '/api/v2/inventory';
    const apiToken = $('section.content').data('api-token') || $('meta[name="api-token"]').attr('content') || '';

    if (!apiToken) {
        console.warn('Missing api_token for inventory requests.');
    }

    $.ajaxSetup({
        headers: {
            'Authorization': apiToken ? 'Bearer ' + apiToken : undefined,
            'Accept': 'application/json'
        }
    });

    const $tableBody = $('#warehouse-table-body');
    const $pagination = $('#warehouse-pagination');
    const $loading = $('#warehouse-loading');
    const $error = $('#warehouse-error');

    function renderRows(items) {
        if (!items || !items.length) {
            $tableBody.html('<tr><td colspan="7" class="text-center">No data</td></tr>');
            return;
        }
        const rows = items.map(function (item) {
            const variant = item.variant || {};
            const product = variant.product || {};
            return `
                <tr>
                    <td>${product.name || ''}</td>
                    <td>${variant.sku || ''}</td>
                    <td>${variant.option1_value || 'Mặc định'}</td>
                    <td class="text-right">${item.physical_stock ?? item.physicalStock ?? 0}</td>
                    <td class="text-right">${item.flash_sale_hold ?? item.flashSaleHold ?? 0}</td>
                    <td class="text-right">${item.deal_hold ?? item.dealHold ?? 0}</td>
                    <td class="text-right">${item.sellable_stock ?? item.sellableStock ?? item.available_stock ?? item.availableStock ?? 0}</td>
                </tr>
            `;
        }).join('');
        $tableBody.html(rows);
    }

    function renderPagination(p) {
        if (!p) {
            $pagination.html('');
            return;
        }
        const prevDisabled = p.current_page <= 1 ? 'disabled' : '';
        const nextDisabled = p.current_page >= p.last_page ? 'disabled' : '';
        $pagination.html(`
            <div class="btn-group">
                <button class="btn btn-default btn-prev" ${prevDisabled}>Prev</button>
                <button class="btn btn-default" disabled>Page ${p.current_page} / ${p.last_page}</button>
                <button class="btn btn-default btn-next" ${nextDisabled}>Next</button>
            </div>
        `);
        $pagination.find('.btn-prev').on('click', function () {
            if (p.current_page > 1) loadStocks(p.current_page - 1);
        });
        $pagination.find('.btn-next').on('click', function () {
            if (p.current_page < p.last_page) loadStocks(p.current_page + 1);
        });
    }

    function loadStocks(page = 1) {
        $loading.show();
        $error.hide();
        const params = {
            page: page,
            per_page: $('#warehouse-limit').val(),
            keyword: $('#warehouse-keyword').val(),
            low_stock_only: $('#warehouse-low-only').is(':checked') ? 1 : 0,
            out_of_stock_only: $('#warehouse-out-only').is(':checked') ? 1 : 0,
            sort_by: $('#warehouse-sort-by').val(),
            sort_order: $('#warehouse-sort-order').val(),
        };
        $.getJSON(API_BASE + '/stocks', params)
            .done(function (res) {
                renderRows(res.data || []);
                renderPagination(res.pagination || null);
            })
            .fail(function (xhr) {
                $error.text(xhr.responseJSON?.message || 'Failed to load stocks').show();
            })
            .always(function () {
                $loading.hide();
            });
    }

    function setupVariantSelect($select) {
        $select.select2({
            width: '100%',
            ajax: {
                url: API_BASE + '/stocks',
                data: function (params) {
                    return {
                        keyword: params.term,
                        per_page: 20,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    const results = (data.data || []).map(function (item) {
                        const variant = item.variant || {};
                        const product = variant.product || {};
                        return {
                            id: item.variant_id || item.variantId || variant.id,
                            text: (variant.sku || '') + ' | ' + (product.name || ''),
                            stock: item
                        };
                    });
                    return {
                        results: results,
                        pagination: {
                            more: (data.pagination?.current_page || 1) < (data.pagination?.last_page || 1)
                        }
                    };
                }
            }
        });
    }

    function fetchStockDetail(variantId, cb) {
        $.getJSON(API_BASE + '/stocks/' + variantId)
            .done(function (res) { cb(null, res.data || {}); })
            .fail(function (xhr) { cb(xhr.responseJSON?.message || 'Failed to load stock'); });
    }

    function bindModal($modal, type) {
        const $variant = $modal.find('.js-variant-select');
        const $qty = $modal.find('.js-qty');
        const $info = $modal.find('.js-stock-info');

        setupVariantSelect($variant);

        $variant.on('select2:select', function (e) {
            const vid = e.params.data.id;
            $info.text('Đang tải tồn kho...');
            fetchStockDetail(vid, function (err, data) {
                if (err) {
                    $info.text(err);
                } else {
                    $info.text(`Available: ${data.available_stock ?? data.availableStock ?? 0}, Physical: ${data.physical_stock ?? data.physicalStock ?? 0}`);
                }
            });
        });

        $modal.find('.js-submit').on('click', function () {
            const variantId = $variant.val();
            const qty = parseInt($qty.val() || '0', 10);
            if (!variantId || qty <= 0) {
                $info.text('Chọn variant và số lượng > 0');
                return;
            }
            const body = {
                code: (type === 'import' ? 'UI-IMP-' : 'UI-EXP-') + Date.now(),
                subject: type === 'import' ? 'UI import' : 'UI export',
                warehouse_id: 1,
                items: [
                    { variant_id: parseInt(variantId, 10), quantity: qty, unit_price: 0 }
                ]
            };
            $.ajax({
                url: API_BASE + '/receipts/' + type,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(body),
                success: function () {
                    $info.text('Thành công');
                    $modal.modal('hide');
                    loadStocks();
                },
                error: function (xhr) {
                    $info.text(xhr.responseJSON?.message || 'Thất bại');
                }
            });
        });
    }

    $(function () {
        // Optional filters
        $('#warehouse-filter-form').on('submit', function (e) {
            e.preventDefault();
            loadStocks();
        });

        // Add checkboxes for low/out-of-stock if not present
        const filterRow = $('#warehouse-sort-order').closest('.row');
        if ($('#warehouse-low-only').length === 0) {
            filterRow.append('<div class="col-md-2"><label><input type="checkbox" id="warehouse-low-only"> Low stock only</label></div>');
            filterRow.append('<div class="col-md-2"><label><input type="checkbox" id="warehouse-out-only"> Out of stock only</label></div>');
        }

        loadStocks();

        // Bind modals
        bindModal($('#modalImport'), 'import');
        bindModal($('#modalExport'), 'export');

        $('#btn-open-import').on('click', function () { $('#modalImport').modal('show'); });
        $('#btn-open-export').on('click', function () { $('#modalExport').modal('show'); });
    });
})(jQuery);


