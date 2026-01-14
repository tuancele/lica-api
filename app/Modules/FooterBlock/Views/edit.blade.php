@extends('Layout::layout')
@section('title','Sửa Block Footer')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa Block Footer',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/footer-block/edit">
        @csrf
        <input type="hidden" value="{{$detail->id}}" name="id">
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Tiêu đề (tùy chọn):</label>
                            <input type="text" name="title" value="{{$detail->title}}" class="form-control" placeholder="Nhập tiêu đề block">
                        </div>
                    </div>
                </div>
                
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title">Tags (Thẻ mây)</h3>
                        <p>Thêm các thẻ tag để hiển thị dạng mây thẻ</p>
                        <hr/>
                        <div id="tags-container">
                            @if(count($detail->tags) > 0)
                                @foreach($detail->tags as $tag)
                                <div class="tag-item form-group" style="margin-bottom: 10px;">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="text" name="tag_names[]" value="{{$tag['name']}}" class="form-control" placeholder="Tên tag">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="tag_urls[]" value="{{$tag['url']}}" class="form-control" placeholder="URL">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-tag"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="tag-item form-group" style="margin-bottom: 10px;">
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" name="tag_names[]" class="form-control" placeholder="Tên tag">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="tag_urls[]" class="form-control" placeholder="URL">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-tag"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-tag"><i class="fa fa-plus"></i> Thêm tag</button>
                    </div>
                </div>
                
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title">Links (Liên kết văn bản)</h3>
                        <p>Thêm các liên kết văn bản. Có thể nhập từng link hoặc import hàng loạt.</p>
                        <hr/>
                        <div class="form-group">
                            <label class="control-label">Import nhanh (tùy chọn):</label>
                            <textarea id="quick-import" class="form-control" rows="5" placeholder="Nhập danh sách links, mỗi dòng một link. Format: Text|URL hoặc Text,URL&#10;Ví dụ:&#10;Sản phẩm mới|/san-pham-moi&#10;Khuyến mãi,/khuyen-mai"></textarea>
                            <button type="button" class="btn btn-info btn-sm mt-2" id="import-links"><i class="fa fa-upload"></i> Import từ text</button>
                            <small class="help-block">Mỗi dòng một link, format: Text|URL hoặc Text,URL</small>
                        </div>
                        <div id="links-container">
                            @if(count($detail->links) > 0)
                                @foreach($detail->links as $link)
                                <div class="link-item form-group" style="margin-bottom: 10px;">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="text" name="link_texts[]" value="{{$link['text']}}" class="form-control" placeholder="Text link">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="link_urls[]" value="{{$link['url']}}" class="form-control" placeholder="URL">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-link"><i class="fa fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            @else
                            <div class="link-item form-group" style="margin-bottom: 10px;">
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" name="link_texts[]" class="form-control" placeholder="Text link">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="link_urls[]" class="form-control" placeholder="URL">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-link"><i class="fa fa-times"></i></button>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-link"><i class="fa fa-plus"></i> Thêm link</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Trạng thái:</label>
                            <select class="form-control" name="status">
                                <option value="1" @if($detail->status == 1) selected @endif>Hiển thị</option>
                                <option value="0" @if($detail->status == 0) selected @endif>Ẩn</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Thứ tự:</label>
                            <input type="number" name="sort" value="{{$detail->sort}}" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                <a href="/admin/footer-block" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
            </div>
        </div>
    </form>
</section>
<script>
$(document).ready(function() {
    // Thêm tag
    $('#add-tag').click(function() {
        var html = '<div class="tag-item form-group" style="margin-bottom: 10px;">' +
            '<div class="row">' +
            '<div class="col-md-5"><input type="text" name="tag_names[]" class="form-control" placeholder="Tên tag"></div>' +
            '<div class="col-md-6"><input type="text" name="tag_urls[]" class="form-control" placeholder="URL"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-tag"><i class="fa fa-times"></i></button></div>' +
            '</div></div>';
        $('#tags-container').append(html);
    });
    
    // Xóa tag
    $(document).on('click', '.remove-tag', function() {
        if($('.tag-item').length > 1) {
            $(this).closest('.tag-item').remove();
        } else {
            alert('Phải có ít nhất một tag');
        }
    });
    
    // Thêm link
    $('#add-link').click(function() {
        var html = '<div class="link-item form-group" style="margin-bottom: 10px;">' +
            '<div class="row">' +
            '<div class="col-md-5"><input type="text" name="link_texts[]" class="form-control" placeholder="Text link"></div>' +
            '<div class="col-md-6"><input type="text" name="link_urls[]" class="form-control" placeholder="URL"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-link"><i class="fa fa-times"></i></button></div>' +
            '</div></div>';
        $('#links-container').append(html);
    });
    
    // Xóa link
    $(document).on('click', '.remove-link', function() {
        if($('.link-item').length > 1) {
            $(this).closest('.link-item').remove();
        } else {
            alert('Phải có ít nhất một link');
        }
    });
    
    // Import links từ text
    $('#import-links').click(function() {
        var text = $('#quick-import').val().trim();
        if (!text) {
            alert('Vui lòng nhập danh sách links');
            return;
        }
        
        var lines = text.split('\n');
        var imported = 0;
        
        // Xóa các link hiện tại (trừ link đầu tiên)
        $('.link-item').not(':first').remove();
        
        lines.forEach(function(line) {
            line = line.trim();
            if (!line) return;
            
            var parts = [];
            if (line.indexOf('|') > -1) {
                parts = line.split('|');
            } else if (line.indexOf(',') > -1) {
                parts = line.split(',');
            } else {
                // Nếu không có separator, coi như chỉ có text, URL sẽ là #
                parts = [line, '#'];
            }
            
            if (parts.length >= 2) {
                var text = parts[0].trim();
                var url = parts[1].trim();
                
                if (text) {
                    // Nếu là link đầu tiên, cập nhật
                    if ($('.link-item').length === 1 && !$('.link-item:first input[name="link_texts[]"]').val()) {
                        $('.link-item:first input[name="link_texts[]"]').val(text);
                        $('.link-item:first input[name="link_urls[]"]').val(url);
                    } else {
                        // Thêm link mới
                        var html = '<div class="link-item form-group" style="margin-bottom: 10px;">' +
                            '<div class="row">' +
                            '<div class="col-md-5"><input type="text" name="link_texts[]" class="form-control" placeholder="Text link" value="' + text + '"></div>' +
                            '<div class="col-md-6"><input type="text" name="link_urls[]" class="form-control" placeholder="URL" value="' + url + '"></div>' +
                            '<div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-link"><i class="fa fa-times"></i></button></div>' +
                            '</div></div>';
                        $('#links-container').append(html);
                    }
                    imported++;
                }
            }
        });
        
        if (imported > 0) {
            alert('Đã import ' + imported + ' link(s)');
            $('#quick-import').val('');
        } else {
            alert('Không thể import. Vui lòng kiểm tra format: Text|URL hoặc Text,URL');
        }
    });
});
</script>
@endsection
