@extends('Layout::layout')
@section('title','Danh mục sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh mục sản phẩm',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="{{route('taxonomy')}}"> 
                    <div class="col-md-5 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-3 pr-0">
                        <?php $status = request()->get('status'); ?>
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button add btn btn-info pull-right" href="{{route('taxonomy.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
                <a class="button add btn btn-warning pull-right" href="{{route('taxonomy.sort')}}" style="margin-right:5px;"><i class="fa fa-arrows-v" aria-hidden="true"></i> Sắp xếp</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('taxonomy.delete')}}" action-url="{{route('taxonomy.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="10%">Hình ảnh</th>
                            <th width="30%">Tiêu đề</th>
                            <th width="15%">Ngày tạo</th>
                            <th width="15%">Người tạo</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="taxonomy-body">
                        {!! listTaxonomy($categories,0,'','taxonomy')!!}
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
</section>
<script>
    (function ($) {
        function normalizeMediaUrl(input) {
            var url = (input || '').toString().trim();
            if (!url) return '';

            // Fix "/https://..." => "https://..."
            if (url.indexOf('/http://') === 0 || url.indexOf('/https://') === 0) {
                url = url.substring(1);
            }

            // If string contains multiple absolute URLs, take the last one
            var matches = url.match(/https?:\/\/[^\s"']+/g);
            if (matches && matches.length) {
                return matches[matches.length - 1];
            }

            return url;
        }

        function normalizeExistingImages() {
            $('#taxonomy-body img').each(function () {
                var $img = $(this);
                var src = $img.attr('src') || '';
                var fixed = normalizeMediaUrl(src);
                if (fixed && fixed !== src) {
                    $img.attr('src', fixed);
                }
            });
        }

        function renderTaxonomyRows(items) {
            if (!Array.isArray(items)) {
                return '';
            }
            var html = '';
            items.forEach(function (item) {
                var id = item.id || 0;
                var name = item.name || '';
                var slug = item.slug || '';
                var image = normalizeMediaUrl(item.image || '');
                var status = typeof item.status !== 'undefined' ? item.status : 0;
                var createdAt = item.created_at || '';

                html += '<tr>';
                html += '<td>';
                if (image) {
                    html += '<img src="' + image + '" style="max-width:60px;">';
                }
                html += '</td>';
                html += '<td>' + name + '</td>';
                html += '<td>' + createdAt + '</td>';
                html += '<td></td>';
                html += '<td>' + (parseInt(status, 10) === 1 ? 'Hiển thị' : 'Ẩn') + '</td>';
                html += '<td>';
                html += '<a href="/admin/taxonomy/edit/' + id + '" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i></a>';
                html += '</td>';
                html += '</tr>';
            });
            return html;
        }

        function loadTaxonomies() {
            var $form = $('form[action="{{ route('taxonomy') }}"]');
            var status = $form.find('select[name=status]').val() || '';
            var keyword = $form.find('input[name=keyword]').val() || '';

            $.ajax({
                url: '/admin/api/taxonomies',
                method: 'GET',
                data: {
                    limit: 200,
                    status: status,
                    keyword: keyword
                },
                headers: {
                    'Accept': 'application/json'
                },
                success: function (res) {
                    if (!res || !res.success || !Array.isArray(res.data)) {
                        return;
                    }
                    var html = renderTaxonomyRows(res.data);
                    $('#taxonomy-body').html(html);
                    normalizeExistingImages();
                },
                error: function () {
                    // keep legacy HTML as fallback
                    console.warn('Failed to load taxonomies via API');
                    normalizeExistingImages();
                }
            });
        }

        $(document).ready(function () {
            // Fix legacy rendered images immediately (before API response)
            normalizeExistingImages();

            // Auto-load via API once page is ready
            loadTaxonomies();

            // Re-load when filter form submitted
            $('form[action="{{ route('taxonomy') }}"]').on('submit', function (e) {
                e.preventDefault();
                loadTaxonomies();
            });
        });
    })(jQuery);
</script>
@endsection