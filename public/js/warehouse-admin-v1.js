/* Warehouse Admin V1 (jQuery + fetch)
 * - No page reload
 * - Uses Admin API V1: /admin/api/v1/warehouse/inventory
 * - Sends X-CSRF-TOKEN
 */

(function () {
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function buildQuery(params) {
    var qs = [];
    Object.keys(params).forEach(function (k) {
      var v = params[k];
      if (v === null || v === undefined || v === '') return;
      qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
    });
    return qs.length ? ('?' + qs.join('&')) : '';
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function fmtInt(n) {
    var x = parseInt(n, 10);
    if (isNaN(x)) return '0';
    return String(x);
  }

  function setLoading(isLoading) {
    var $btn = $('#warehouse-filter-submit');
    $btn.prop('disabled', !!isLoading);
    $('#warehouse-loading').toggle(!!isLoading);
  }

  function renderRows(items) {
    var $tbody = $('#warehouse-table-body');
    $tbody.empty();

    if (!items || !items.length) {
      $tbody.append('<tr><td colspan="8" class="text-center">No data</td></tr>');
      return;
    }

    items.forEach(function (row) {
      var productName = escapeHtml(row.product_name || '');
      var productId = row.product_id || '';
      var sku = escapeHtml(row.variant_sku || '');
      var opt = escapeHtml(row.variant_option || 'Mac dinh');
      var physical = fmtInt(row.physical_stock);
      var fs = fmtInt(row.flash_sale_stock);
      var deal = fmtInt(row.deal_stock);
      var avail = fmtInt(row.available_stock);

      var badge = '';
      if (parseInt(avail, 10) <= 0) {
        badge = ' <span class="label label-danger">Het hang kha dung</span>';
      }

      var productLink = '/admin/product/edit/' + productId;
      var productCell = '<a href="' + productLink + '" target="_blank">' + productName + '</a>';

        var tr = ''
            + '<tr data-variant-id="' + row.variant_id + '">'
        + '<td>' + productCell + '</td>'
        + '<td>' + sku + '</td>'
        + '<td>' + opt + '</td>'
        + '<td class="text-right">' + physical + '</td>'
        + '<td class="text-right">' + fs + '</td>'
        + '<td class="text-right">' + deal + '</td>'
        + '<td class="text-right">' + avail + badge + '</td>'
        + '</tr>';

      $tbody.append(tr);
    });
  }

  function renderPagination(pagination, currentParams) {
    var $wrap = $('#warehouse-pagination');
    $wrap.empty();

    if (!pagination || !pagination.total) return;

    var current = parseInt(pagination.current_page || 1, 10);
    var last = parseInt(pagination.last_page || 1, 10);

    function pageBtn(page, label, disabled, active) {
      var cls = 'btn btn-default btn-sm';
      if (active) cls += ' btn-primary';
      var dis = disabled ? ' disabled="disabled"' : '';
      return '<button type="button" class="' + cls + '" data-page="' + page + '"' + dis + '>' + label + '</button>';
    }

    var html = '';
    html += pageBtn(Math.max(1, current - 1), 'Prev', current <= 1, false) + ' ';
    html += '<span style="margin:0 8px;">Page ' + current + ' / ' + last + '</span>';
    html += pageBtn(Math.min(last, current + 1), 'Next', current >= last, false);
    $wrap.append(html);

    $wrap.find('button[data-page]').on('click', function () {
      var p = parseInt($(this).attr('data-page'), 10);
      if (isNaN(p)) return;
      if (p < 1 || p > last) return;
      loadInventory($.extend({}, currentParams, { page: p }));
    });
  }

  function getFilterParams() {
    return {
      keyword: $('#warehouse-keyword').val(),
      min_stock: $('#warehouse-min-stock').val(),
      max_stock: $('#warehouse-max-stock').val(),
      limit: $('#warehouse-limit').val(),
      page: 1,
      sort_by: $('#warehouse-sort-by').val(),
      sort_order: $('#warehouse-sort-order').val()
    };
  }

  function loadInventory(params) {
    setLoading(true);
    $('#warehouse-error').hide().text('');

    var url = '/admin/api/v1/warehouse/inventory' + buildQuery(params);

    fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken()
      },
      credentials: 'same-origin'
    })
      .then(function (res) { return res.json().then(function (data) { return { status: res.status, data: data }; }); })
      .then(function (r) {
        if (!r.data || r.data.success !== true) {
          throw new Error((r.data && r.data.message) ? r.data.message : 'API error');
        }
        renderRows(r.data.data || []);
        renderPagination(r.data.pagination || null, params);
      })
      .catch(function (e) {
        $('#warehouse-error').show().text(e && e.message ? e.message : 'Load failed');
        renderRows([]);
        $('#warehouse-pagination').empty();
      })
      .finally(function () {
        setLoading(false);
      });
  }

  $(function () {
    $('#warehouse-filter-form').on('submit', function (e) {
      e.preventDefault();
      loadInventory(getFilterParams());
    });

    // Initial load
    loadInventory(getFilterParams());
  });
})();

// ----- Import / Export UI -----
(function () {
  var csrf = (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  })();

  function updateTableRow(stock) {
    if (!stock || !stock.variant_id) return;
    var $row = $('#warehouse-table-body tr[data-variant-id="' + stock.variant_id + '"]');
    if (!$row.length) return;
    $row.find('td').eq(3).text(fmtInt(stock.physical_stock)); // Physical
    $row.find('td').eq(4).text(fmtInt(stock.flash_sale_stock)); // Flash
    $row.find('td').eq(5).text(fmtInt(stock.deal_stock)); // Deal
    var avail = fmtInt(stock.available_stock);
    var badge = parseInt(avail, 10) <= 0 ? ' <span class="label label-danger">Het hang kha dung</span>' : '';
    $row.find('td').eq(6).html(avail + badge);
  }

  function initProductSelect($el, $dropdownParent) {
    if (!$el.select2) {
      // Fallback if select2 is not loaded
      console.warn('Select2 not loaded; product search disabled');
      return;
    }
    $el.select2({
      width: '100%',
      minimumInputLength: 2,
      ajax: {
        url: '/admin/api/v1/warehouse/products/search',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term || '', page: params.page || 1 };
        },
        processResults: function (data) {
          var results = [];
          (data.data || []).forEach(function (item) {
            results.push({ id: item.id, text: item.name || item.text || ('SP #' + item.id) });
          });
          return { results: results };
        },
        error: function (xhr) {
          var msg = 'Search failed';
          if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          }
          console.warn(msg);
        }
      },
      dropdownParent: $dropdownParent || undefined,
      placeholder: 'Chon san pham'
    });
  }

  function loadVariants(productId, $variantSelect, $infoBox) {
    $variantSelect.empty();
    $infoBox.text('Loading...');
    fetch('/admin/api/v1/warehouse/inventory/by-product/' + productId, {
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      credentials: 'same-origin'
    })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json.success) throw new Error(json.message || 'Load variants failed');
        var opts = '<option value="">-- Chon variant --</option>';
        (json.data || []).forEach(function (row) {
          opts += '<option value="' + row.variant_id + '" data-physical="' + row.physical_stock + '" data-available="' + row.available_stock + '">' + (row.variant_sku || row.variant_id) + ' - ' + (row.variant_option || '') + '</option>';
        });
        $variantSelect.html(opts);
        $infoBox.text('Chon variant de xem ton');
      })
      .catch(function (e) {
        $infoBox.text(e.message || 'Load variants error');
      });
  }

  function bindModal(modalId, type) {
    var $modal = $(modalId);
    var $product = $modal.find('.js-product-select');
    var $variant = $modal.find('.js-variant-select');
    var $qty = $modal.find('.js-qty');
    var $info = $modal.find('.js-stock-info');
    var submitUrl = type === 'import' ? '/admin/api/v1/warehouse/import-receipts' : '/admin/api/v1/warehouse/export-receipts';

    initProductSelect($product, $modal);

    $product.on('change', function () {
      var pid = $(this).val();
      if (!pid) {
        $variant.empty();
        $info.text('');
        return;
      }
      loadVariants(pid, $variant, $info);
    });

    $variant.on('change', function () {
      var opt = $(this).find('option:selected');
      var physical = opt.data('physical') || 0;
      var available = opt.data('available') || 0;
      $info.text('Physical: ' + physical + ' | Available: ' + available);
      if (type === 'export') {
        var q = parseInt($qty.val(), 10);
        if (q > available) {
          $qty.val(available);
        }
        $qty.attr('max', available);
      }
    });

    $qty.on('input', function () {
      if (type !== 'export') return;
      var opt = $variant.find('option:selected');
      var available = parseInt(opt.data('available') || 0, 10);
      var val = parseInt($(this).val() || 0, 10);
      if (val > available) {
        $(this).val(available);
      }
    });

    $modal.find('.js-submit').on('click', function () {
      var variantId = $variant.val();
      var quantity = parseInt($qty.val() || 0, 10);
      if (!variantId || !quantity || quantity <= 0) {
        $info.text('Variant va so luong bat buoc');
        return;
      }
      if (type === 'export') {
        var available = parseInt($variant.find('option:selected').data('available') || 0, 10);
        if (quantity > available) {
          $info.text('Quantity > available_stock');
          return;
        }
      }

      var payload = { items: [{ variant_id: parseInt(variantId, 10), quantity: quantity }] };
      fetch(submitUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      })
        .then(function (res) { return res.json().then(function (d) { return { status: res.status, body: d }; }); })
        .then(function (r) {
          if (!r.body || r.body.success !== true) {
            throw new Error((r.body && r.body.message) ? r.body.message : 'Submit failed');
          }
          (r.body.data || []).forEach(updateTableRow);
          $modal.modal('hide');
        })
        .catch(function (e) {
          $info.text(e.message || 'Submit error');
        });
    });

    $modal.on('shown.bs.modal', function () {
      $product.val(null).trigger('change');
      $variant.empty();
      $qty.val('');
      $info.text('');
    });
  }

  $(function () {
    bindModal('#modalImport', 'import');
    bindModal('#modalExport', 'export');
    $('#btn-open-import').on('click', function () { $('#modalImport').modal('show'); });
    $('#btn-open-export').on('click', function () { $('#modalExport').modal('show'); });
  });
})();
