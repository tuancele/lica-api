/* Admin Flash Sale V2 UI (uses Inventory API v2) */
(function ($) {
    const API_BASE = '/api/v2/inventory';
    const $content = $('section.content');
    const apiToken = $content.data('api-token') || $('meta[name="api-token"]').attr('content') || '';
    const warehouseId = $content.data('warehouse-id') || '';

    $.ajaxSetup({
        headers: {
            'Authorization': apiToken ? 'Bearer ' + apiToken : undefined,
            'Accept': 'application/json'
        }
    });

    const $picker = $('#flashsalePicker');
    const $pickerBody = $picker.find('.js-picker-body');
    const $pickerError = $picker.find('.js-picker-error');
    const $pickerMeta = $picker.find('.js-picker-meta');
    const $pickerKeyword = $picker.find('.js-picker-keyword');

    const $itemsBody = $('.js-items-body');
    const $formError = $('.js-form-error');

    let pickerPage = 1;
    let pickerLastPage = 1;
    let pickerKeyword = '';
    let pickerItems = [];

    function money(n) {
        const x = Number(n || 0);
        return x.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }

    function getRowKey(productId, variantId) {
        return String(productId) + '_v' + String(variantId);
    }

    function ensureNotEmptyTable() {
        const hasRows = $itemsBody.find('tr.js-item-row').length > 0;
        $itemsBody.find('tr.js-empty-row').toggle(!hasRows);
    }

    function setRowStock($tr, stock) {
        $tr.find('.js-phy').text(String(stock.physical_stock ?? 0));
        $tr.find('.js-avail').text(String(stock.available_stock ?? 0));
        $tr.find('.js-sell').text(String(stock.sellable_stock ?? stock.available_stock ?? 0));
    }

    function validateRow($tr) {
        const original = Number($tr.data('original-price') || 0);
        const sale = Number($tr.find('.js-sale-price').val() || 0);
        const qty = Number($tr.find('.js-qty').val() || 0);
        const phy = Number($tr.find('.js-phy').text() || 0);
        const avail = Number($tr.find('.js-avail').text() || 0);
        const sell = Number($tr.find('.js-sell').text() || 0);

        const $err = $tr.find('.js-item-err');
        const $warn = $tr.find('.js-item-warn');
        $err.hide().text('');
        $warn.hide().text('');

        if (original > 0 && sale > original) {
            $err.text('Sale price must be <= original (' + money(original) + ')').show();
            return false;
        }
        if (qty < 1) {
            $err.text('Qty must be >= 1').show();
            return false;
        }
        if (phy >= 0 && qty > phy) {
            $err.text('Qty exceeds physical (' + phy + ')').show();
            return false;
        }
        if (avail >= 0 && qty > avail) {
            $warn.text('Warning: qty > available (' + avail + ')').show();
        } else if (sell >= 0 && qty > sell) {
            $warn.text('Warning: qty > sellable (' + sell + ')').show();
        }
        return true;
    }

    function upsertItemRow(item) {
        const variant = item.variant || {};
        const product = variant.product || {};

        const productId = product.id || item.product_id || 0;
        const variantId = item.variant_id || variant.id || 0;
        const key = getRowKey(productId, variantId);

        if (!productId || !variantId) {
            return;
        }

        if ($itemsBody.find('tr[data-key="' + key + '"]').length) {
            return;
        }

        const originalPrice = Number(variant.price || 0);
        const opt = (variant.option1_value || 'Default');

        const $tr = $(`
            <tr class="js-item-row"
                data-key="${key}"
                data-product-id="${productId}"
                data-variant-id="${variantId}"
                data-original-price="${originalPrice}">
                <td style="text-align:center;">
                    <input type="checkbox" class="js-item-check" checked>
                </td>
                <td>
                    <strong class="js-item-name">${product.name || ''}</strong>
                    <span class="js-item-sub">SKU: <span class="js-item-sku">${variant.sku || ''}</span> | Opt: <span class="js-item-opt">${opt}</span></span>
                    <span class="js-err js-item-err"></span>
                    <span class="js-warn js-item-warn"></span>
                    <input type="hidden" name="productid[]" value="${productId}_v${variantId}">
                </td>
                <td><span class="js-item-price">${money(originalPrice)}</span></td>
                <td>
                    <div class="js-sale-wrap">
                        <input type="number"
                               class="form-control js-sale-price"
                               name="pricesale[${productId}][${variantId}]"
                               value="0"
                               min="0"
                               placeholder="Sale price">
                        <input type="number" class="form-control js-percent" min="0" max="100" placeholder="%">
                    </div>
                </td>
                <td>
                    <input type="number"
                           class="form-control js-qty"
                           name="numbersale[${productId}][${variantId}]"
                           value="1"
                           min="1"
                           placeholder="Qty">
                </td>
                <td class="text-right js-phy">0</td>
                <td class="text-right js-avail">0</td>
                <td class="text-right js-sell">0</td>
                <td class="text-center">
                    <button type="button" class="btn btn-xs btn-danger js-remove">Del</button>
                </td>
            </tr>
        `);

        $itemsBody.append($tr);
        ensureNotEmptyTable();
        setRowStock($tr, {
            physical_stock: item.physical_stock ?? 0,
            available_stock: item.available_stock ?? 0,
            sellable_stock: item.sellable_stock ?? item.available_stock ?? 0
        });
        validateRow($tr);
    }

    function renderPickerRows(items) {
        if (!items || !items.length) {
            $pickerBody.html('<tr><td colspan="5" class="text-center text-muted">No results</td></tr>');
            return;
        }
        const html = items.map(function (item) {
            const variant = item.variant || {};
            const product = variant.product || {};
            const productId = product.id || item.product_id || 0;
            const variantId = item.variant_id || variant.id || 0;
            const key = getRowKey(productId, variantId);
            const label = (variant.sku || '') + ' | ' + (product.name || '') + ' | ' + (variant.option1_value || 'Default');
            const phy = item.physical_stock ?? 0;
            const avail = item.available_stock ?? 0;
            const sell = item.sellable_stock ?? item.available_stock ?? 0;

            return `
                <tr data-key="${key}">
                    <td style="text-align:center;">
                        <input type="checkbox" class="js-picker-check" value="${key}">
                    </td>
                    <td>${label}</td>
                    <td class="text-right">${phy}</td>
                    <td class="text-right">${avail}</td>
                    <td class="text-right">${sell}</td>
                </tr>
            `;
        }).join('');
        $pickerBody.html(html);
    }

    function loadPicker(page) {
        pickerPage = page || 1;
        $pickerError.hide().text('');
        $pickerMeta.text('Loading...');
        $pickerBody.html('<tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>');

        const params = {
            keyword: pickerKeyword || '',
            per_page: 20,
            page: pickerPage
        };

        $.getJSON(API_BASE + '/stocks', params)
            .done(function (res) {
                pickerItems = res.data || [];
                pickerLastPage = res.pagination?.last_page || 1;
                renderPickerRows(pickerItems);
                $pickerMeta.text('Page ' + pickerPage + ' / ' + pickerLastPage + ' | ' + (res.pagination?.total || 0) + ' items');
            })
            .fail(function (xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to load stocks';
                $pickerError.text(msg).show();
                $pickerBody.html('<tr><td colspan="5" class="text-center text-muted">Error</td></tr>');
                $pickerMeta.text('');
            });
    }

    function bootstrapStocksForExistingRows() {
        const $rows = $itemsBody.find('tr.js-item-row');
        if (!$rows.length) return;

        const ids = $rows.map(function () {
            return Number($(this).data('variant-id') || 0);
        }).get().filter(Boolean);

        // Fetch in small chunks via /stocks?keyword is not usable for batch; do per row using stockShow.
        function fetchOne($tr, attempt) {
            const variantId = Number($tr.data('variant-id') || 0);
            if (!variantId) return;
            const tries = attempt || 1;
            const params = warehouseId ? { warehouse_id: warehouseId } : {};
            $.getJSON(API_BASE + '/stocks/' + variantId, params)
                .done(function (res) {
                    const d = res.data || {};
                    setRowStock($tr, {
                        physical_stock: d.physicalStock ?? 0,
                        available_stock: d.availableStock ?? 0,
                        sellable_stock: d.sellableStock ?? d.availableStock ?? 0
                    });
                    validateRow($tr);
                })
                .fail(function () {
                    // Fallback: try list search by SKU to recover in case variantId lookup fails.
                    const sku = ($tr.find('.js-item-sku').text() || '').trim();
                    if (sku) {
                        $.getJSON(API_BASE + '/stocks', {
                            keyword: sku,
                            per_page: 1,
                            page: 1,
                            warehouse_id: warehouseId || undefined
                        }).done(function (res) {
                            const item = (res.data || [])[0];
                            if (item) {
                                setRowStock($tr, {
                                    physical_stock: item.physical_stock ?? 0,
                                    available_stock: item.available_stock ?? 0,
                                    sellable_stock: item.sellable_stock ?? item.available_stock ?? 0
                                });
                                validateRow($tr);
                                return;
                            }
                        });
                    }
                    if (tries >= 3) {
                        $tr.find('.js-item-warn').text('Stock refresh failed.').show();
                        return;
                    }
                    const waitMs = tries * 800;
                    setTimeout(function () { fetchOne($tr, tries + 1); }, waitMs);
                });
        }

        $rows.each(function () {
            fetchOne($(this), 1);
        });
    }

    // Open picker
    $(document).on('click', '.js-open-picker', function () {
        if (!apiToken) {
            alert('Missing api token');
            return;
        }
        pickerPage = 1;
        pickerLastPage = 1;
        pickerKeyword = '';
        $pickerKeyword.val('');
        $picker.modal('show');
    });

    $picker.on('shown.bs.modal', function () {
        // Default: show first page (empty keyword is allowed)
        pickerKeyword = ($pickerKeyword.val() || '').trim();
        loadPicker(1);
    });

    // Picker search
    $picker.on('click', '.js-picker-search', function () {
        pickerKeyword = ($pickerKeyword.val() || '').trim();
        loadPicker(1);
    });

    $pickerKeyword.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            pickerKeyword = ($pickerKeyword.val() || '').trim();
            loadPicker(1);
        }
    });

    $picker.on('click', '.js-picker-prev', function () {
        if (pickerPage > 1) loadPicker(pickerPage - 1);
    });
    $picker.on('click', '.js-picker-next', function () {
        if (pickerPage < pickerLastPage) loadPicker(pickerPage + 1);
    });

    $picker.on('click', '.js-picker-check-all', function () {
        const on = $(this).is(':checked');
        $picker.find('.js-picker-check').prop('checked', on);
    });

    $picker.on('click', '.js-picker-add', function () {
        const selectedKeys = $picker.find('.js-picker-check:checked').map(function () { return $(this).val(); }).get();
        if (!selectedKeys.length) {
            alert('Select at least 1 item');
            return;
        }
        const map = {};
        (pickerItems || []).forEach(function (it) {
            const v = it.variant || {};
            const p = v.product || {};
            const pid = p.id || it.product_id || 0;
            const vid = it.variant_id || v.id || 0;
            map[getRowKey(pid, vid)] = it;
        });
        selectedKeys.forEach(function (k) {
            if (map[k]) upsertItemRow(map[k]);
        });
        $picker.modal('hide');
    });

    // Table interactions
    $(document).on('click', '.js-check-all', function () {
        const on = $(this).is(':checked');
        $itemsBody.find('.js-item-check').prop('checked', on);
    });

    $(document).on('click', '.js-remove', function () {
        $(this).closest('tr').remove();
        ensureNotEmptyTable();
    });

    // Percent -> sale price
    $(document).on('input', '.js-percent', function () {
        const $tr = $(this).closest('tr');
        const p = Number($(this).val() || 0);
        const original = Number($tr.data('original-price') || 0);
        if (original <= 0) return;
        if (p < 0 || p > 100) return;
        const sale = Math.round(original - (original * p / 100));
        $tr.find('.js-sale-price').val(sale);
        validateRow($tr);
    });

    $(document).on('input', '.js-sale-price, .js-qty', function () {
        validateRow($(this).closest('tr'));
    });

    // Bulk apply
    $(document).on('click', '.js-bulk-apply', function () {
        const percent = Number($('.js-bulk-percent').val() || 0);
        const qty = Number($('.js-bulk-qty').val() || 0);
        $itemsBody.find('tr.js-item-row').each(function () {
            const $tr = $(this);
            if (!$tr.find('.js-item-check').is(':checked')) return;
            const original = Number($tr.data('original-price') || 0);
            if (original > 0 && percent >= 0 && percent <= 100) {
                const sale = Math.round(original - (original * percent / 100));
                $tr.find('.js-sale-price').val(sale);
                $tr.find('.js-percent').val(percent);
            }
            if (qty >= 1) {
                $tr.find('.js-qty').val(qty);
            }
            validateRow($tr);
        });
    });

    $(document).on('click', '.js-bulk-remove', function () {
        $itemsBody.find('tr.js-item-row').each(function () {
            const $tr = $(this);
            if (!$tr.find('.js-item-check').is(':checked')) return;
            $tr.remove();
        });
        ensureNotEmptyTable();
    });

    // Form submit validation
    $(document).on('submit', '#flashsale-form', function (e) {
        $formError.hide().text('');
        let ok = true;
        $itemsBody.find('tr.js-item-row').each(function () {
            if (!validateRow($(this))) ok = false;
        });
        if (!ok) {
            e.preventDefault();
            $formError.text('Please fix invalid rows before saving.').show();
            return false;
        }
        return true;
    });

    // Bootstrap existing edit rows
    $(function () {
        ensureNotEmptyTable();
        bootstrapStocksForExistingRows();

        // Periodic refresh (best effort) to keep Phy/Avail/Sell accurate.
        setInterval(function () {
            if (document.hidden) return;
            bootstrapStocksForExistingRows();
        }, 30000);
    });
})(jQuery);


