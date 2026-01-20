@extends('Layout::layout')
@section('title','Danh sách thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách thành phần',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form id="filterForm"> 
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-2 pr-0">
                        <select class="form-control" name="status">
                            <option value="">---Trạng thái---</option>
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="button btn btn-default" type="submit">Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <button class="button btn btn-primary pull-right" id="btnCrawl"><i class="fa fa-download" aria-hidden="true"></i> Crawl dữ liệu</button>
                <a class="button add btn btn-info pull-right" href="{{route('dictionary.ingredient.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="25%">Tiêu đề</th>
                            <th width="15%">Danh mục</th>
                            <th width="15%">Đánh giá</th>
                            <th width="15%">Lợi ích</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="ingredientBody">
                        <tr><td colspan="7">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <select class="form-control" name="action" style="width:50%;float:left;margin-right:5px;">
                        <option value="">---Chọn thao tác---</option>
                        <option value="0">Ẩn</option>
                        <option value="1">Hiển thị</option>
                        <option value="2">Xóa</option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-4">
                    <div id="crawlProgress" style="margin-top:10px; display:none;">
                        <strong>Tiến trình crawl:</strong>
                        <span id="crawlStatusText">Đang chuẩn bị...</span>
                        <div id="crawlLog" style="margin-top:5px; max-height:120px; overflow:auto; font-size:12px; border:1px solid #ddd; padding:5px; background:#fafafa;"></div>
                    </div>
                </div>
                <div class="col-md-4 text-right" id="paginationBox"></div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection
@section('footer')
<script>
(function ($) {
    const API_BASE = '/admin/api';
    const token = $('meta[name="api-token"]').attr('content') || localStorage.getItem('api_token') || '';
    if (!token) {
        console.warn('Missing api_token meta; API calls will fail with 401.');
    }
    const headers = token ? { 'Authorization': 'Bearer ' + token } : {};

    function renderPagination(p) {
        if (!p || !p.total) {
            $('#paginationBox').html('');
            return;
        }
        let html = '<div class="pagination">';
        html += `<span>Trang ${p.current_page}/${p.last_page} - ${p.total} bản ghi</span>`;
        if (p.current_page > 1) {
            html += ` <a href="#" class="btn_page" data-page="${p.current_page - 1}">«</a>`;
        }
        if (p.current_page < p.last_page) {
            html += ` <a href="#" class="btn_page" data-page="${p.current_page + 1}">»</a>`;
        }
        html += '</div>';
        $('#paginationBox').html(html);
    }

    function renderRows(items) {
        if (!items || items.length === 0) {
            $('#ingredientBody').html('<tr><td colspan="7">Không có dữ liệu</td></tr>');
            return;
        }
        const rows = items.map(item => {
            const cats = (item.categories || []).map(c => `<a target="_blank" href="/admin/dictionary/category/edit/${c.id}">${c.name}</a>`).join(', ');
            const bens = (item.benefits || []).map(b => `<a target="_blank" href="/admin/dictionary/benefit/edit/${b.id}">${b.name}</a>`).join(', ');
            const rate = item.rate ? item.rate.name : 'No Rated';
            return `
                <tr>
                    <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="${item.id}"></td>
                    <td><a href="/ingredient-dictionary/${item.slug}" target="_blank">${item.name}</a></td>
                    <td>${cats || ''}</td>
                    <td>${rate}</td>
                    <td>${bens || ''}</td>
                    <td>
                        <select class="select_status form-control" data-id="${item.id}">
                            <option value="1" ${item.status === '1' || item.status === 1 ? 'selected' : ''}>Hiển thị</option>
                            <option value="0" ${item.status === '0' || item.status === 0 ? 'selected' : ''}>Ẩn</option>
                        </select>
                    </td>
                    <td>
                        <a class="btn btn-primary btn-xs" href="/admin/dictionary/ingredient/edit/${item.id}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                        <a class="btn_delete btn btn-danger btn-xs" data-id="${item.id}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                    </td>
                </tr>
            `;
        });
        $('#ingredientBody').html(rows.join(''));
    }

    function fetchList(page = 1) {
        const params = {
            page: page,
            limit: 20,
            keyword: $('input[name="keyword"]').val() || '',
            status: $('select[name="status"]').val() || ''
        };
        $.ajax({
            url: API_BASE + '/ingredients',
            data: params,
            headers: headers,
            method: 'GET',
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Tải danh sách thất bại');
                    return;
                }
                renderRows(res.data);
                renderPagination(res.pagination);
            },
            error: function () {
                alert('Tải danh sách thất bại');
            }
        });
    }

    function updateStatus(id, status) {
        $.ajax({
            url: API_BASE + '/ingredients/' + id + '/status',
            method: 'PATCH',
            headers: Object.assign({'Content-Type': 'application/json'}, headers),
            data: JSON.stringify({ status: status }),
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Cập nhật trạng thái thất bại');
                }
            },
            error: function () {
                alert('Cập nhật trạng thái thất bại');
            }
        });
    }

    function deleteItem(id) {
        if (!confirm('Xóa thành phần này?')) return;
        $.ajax({
            url: API_BASE + '/ingredients/' + id,
            method: 'DELETE',
            headers: headers,
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Xóa thất bại');
                    return;
                }
                fetchList();
            },
            error: function () {
                alert('Xóa thất bại');
            }
        });
    }

    function bulkAction() {
        const action = $('select[name="action"]').val();
        const ids = [];
        $('input[name="checklist[]"]:checked').each(function () {
            ids.push($(this).val());
        });
        if (!action || ids.length === 0) {
            alert('Chọn thao tác và bản ghi');
            return;
        }
        $.ajax({
            url: API_BASE + '/ingredients/bulk-action',
            method: 'POST',
            headers: Object.assign({'Content-Type': 'application/json'}, headers),
            data: JSON.stringify({ action: action, checklist: ids }),
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Thao tác thất bại');
                    return;
                }
                fetchList();
            },
            error: function () {
                alert('Thao tác thất bại');
            }
        });
    }

    function setCrawlState(visible, statusText, appendLog) {
        if (visible) {
            $('#crawlProgress').show();
        } else {
            $('#crawlProgress').hide();
        }
        if (typeof statusText === 'string') {
            $('#crawlStatusText').text(statusText);
        }
        if (typeof appendLog === 'string' && appendLog.length) {
            const box = $('#crawlLog');
            const safe = appendLog.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            box.append((box.html() ? '<br>' : '') + safe);
            box.scrollTop(box[0].scrollHeight);
        }
    }

    function loadCrawlSummary() {
        $.ajax({
            url: API_BASE + '/ingredients/crawl/summary',
            method: 'GET',
            headers: headers,
            success: function (res) {
                if (!res.success) {
                    alert(res.message || 'Lấy summary thất bại');
                    return;
                }
                const offset = prompt(`Tổng ${res.data.total} bản ghi, ~${res.data.pages} page (mỗi 2000). Nhập offset start:`, '0');
                if (offset === null) return;
                setCrawlState(true, `Đang crawl offset ${offset} (mỗi lần tối đa 2000 bản ghi)...`, '');
                runCrawl(offset);
            },
            error: function () {
                alert('Lấy summary thất bại');
            }
        });
    }

    function runCrawl(offset) {
        $.ajax({
            url: API_BASE + '/ingredients/crawl/run',
            method: 'POST',
            headers: Object.assign({'Content-Type': 'application/json'}, headers),
            data: JSON.stringify({ offset: parseInt(offset, 10) || 0 }),
            success: function (res) {
                const msg = res.message || (res.success ? 'Crawl xong' : 'Crawl lỗi');
                alert(msg);
                setCrawlState(true, res.success ? 'Crawl hoàn tất.' : 'Crawl lỗi.', msg.replace(/\n/g, '<br>'));
                if (res.success) {
                    fetchList();
                }
            },
            error: function () {
                alert('Crawl lỗi');
                setCrawlState(true, 'Crawl lỗi (500 từ server).', 'Vui lòng kiểm tra log server để xem chi tiết lỗi.');
            }
        });
    }

    $(document).ready(function () {
        fetchList();

        $('#filterForm').on('submit', function (e) {
            e.preventDefault();
            fetchList(1);
        });

        $('#ingredientBody').on('change', '.select_status', function () {
            const id = $(this).data('id');
            const status = $(this).val();
            updateStatus(id, status);
        });

        $('#ingredientBody').on('click', '.btn_delete', function (e) {
            e.preventDefault();
            deleteItem($(this).data('id'));
        });

        $('#checkall').on('change', function () {
            const checked = $(this).is(':checked');
            $('input[name="checklist[]"]').prop('checked', checked);
        });

        $('.btn_action').on('click', function () {
            bulkAction();
        });

        $('#paginationBox').on('click', '.btn_page', function (e) {
            e.preventDefault();
            const page = $(this).data('page');
            fetchList(page);
        });

        $('#btnCrawl').on('click', function (e) {
            e.preventDefault();
            window.location.href = '/admin/dictionary/ingredient/crawl';
        });
    });
})(jQuery);
</script>
@endsection