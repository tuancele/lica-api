@extends('Layout::layout')
@section('title','Cập nhật dữ liệu thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cập nhật dữ liệu',
])
<style>
    /* Disable legacy global ajax loader on this page */
    .img_load_ajax { display: none !important; }
</style>
<section class="content">
    <form role="form" method="post" class="getData" ajax="">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h4 style="margin-top:0;">CRAWL IngredientPaulas (Paula s Choice)</h4>
                        <p>Trang lay du lieu: <strong>https://www.paulaschoice.com/ingredient-dictionary/</strong></p>
                        <p>Tong du lieu tren trang: <strong>{{$total}}</strong></p>
                        <div class="alert alert-info" style="margin-top:10px;">
                            <strong>Huong dan su dung:</strong>
                            <ul style="margin:5px 0 0 18px; padding:0;">
                                <li>Buoc 1: Chon khoang du lieu (0-2000, 2000-4000, ...).</li>
                                <li>Buoc 2: Bam "Lay du lieu" de bat dau CRAWL.</li>
                                <li>He thong se cap nhat lai thanh phan neu da ton tai va them moi neu chua co.</li>
                                <li>Co the chay lai nhieu lan, thao tac la an toan va idempotent.</li>
                                <li>Moi batch co the mat vai phut, vui long khong dong tab cho den khi trang thai thong bao "Hoan tat".</li>
                            </ul>
                        </div>

                        <div id="crawlStatusBox" class="alert alert-default" style="display:none; margin-top:10px;">
                            <strong>Trang thai:</strong>
                            <span id="crawlStatusText">Dang cho thao tac.</span>
                            <span id="crawlElapsed" style="margin-left:10px; color:#666; display:none;">(0s)</span>
                        </div>

                        <div id="crawlProgressBox" style="display:none; margin-top:10px;">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <strong>Tien trinh:</strong>
                                <span id="crawlProgressText" style="color:#666;">0/0</span>
                            </div>
                            <div style="height:10px; background:#f1f1f1; border-radius:6px; overflow:hidden; margin-top:6px;">
                                <div id="crawlProgressBar" style="height:10px; width:0%; background:#3c8dbc;"></div>
                            </div>
                        </div>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-4">
                                <select class="form-control" name="offset" required="">
                                    <option value="">Chon khoang du lieu</option>
                                    @if($page > 0)
                                    @for($i = 0;$i < $page;$i++)
                                    <option value="{{$i*2000}}">{{$i*2000}} - {{($i+1)*2000}}</option>
                                    @endfor
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="button btn btn-primary" type="submit">Lay du lieu</button>
                            </div>
                        </div>
                        <div class="result_data" style="margin-top:15px;">
                            <div id="crawlCliBox" style="display:none; border:1px solid #ddd; background:#111; color:#eee; padding:10px; font-family:monospace; font-size:12px; max-height:360px; overflow:auto;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
<script>
    $(".getData").on("submit", function (e) {
        e.preventDefault();
        var $form = $(this);
        var offsetText = $form.find('select[name="offset"] option:selected').text() || '';
        var $btn = $form.find('button[type="submit"]');
        var $statusBox = $('#crawlStatusBox');
        var $statusText = $('#crawlStatusText');
        var $progressBox = $('#crawlProgressBox');
        var $progressText = $('#crawlProgressText');
        var $progressBar = $('#crawlProgressBar');
        var $cli = $('#crawlCliBox');

        var crawlTimer = null;
        var crawlStart = 0;
        var pollTimer = null;
        var nextSince = 0;
        var crawlId = '';
        var currentOffset = parseInt($form.find('select[name="offset"]').val() || '0', 10) || 0;
        var isRunning = false;

        function appendCli(lines) {
            if (!lines || !lines.length) return;
            $cli.show();
            lines.forEach(function (line) {
                var safe = String(line).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                $cli.append(safe + '<br>');
            });
            $cli.scrollTop($cli[0].scrollHeight);
        }

        function setProgress(processed, total) {
            processed = processed || 0;
            total = total || 0;
            $progressBox.show();
            $progressText.text(processed + '/' + total);
            var pct = total > 0 ? Math.floor((processed / total) * 100) : 0;
            if (pct < 0) pct = 0;
            if (pct > 100) pct = 100;
            $progressBar.css('width', pct + '%');
        }

        function stopTimers() {
            if (crawlTimer) { clearInterval(crawlTimer); crawlTimer = null; }
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            isRunning = false;
        }

        function startElapsed() {
            crawlStart = Date.now();
            $('#crawlElapsed').show().text('(0s)');
            if (crawlTimer) clearInterval(crawlTimer);
            crawlTimer = setInterval(function () {
                var sec = Math.floor((Date.now() - crawlStart) / 1000);
                $('#crawlElapsed').text('(' + sec + 's)');
            }, 1000);
        }

        function pollStatus() {
            if (!isRunning) return;
            $.ajax({
                type: 'get',
                url: "{{route('dictionary.ingredient.crawl.status')}}",
                data: { crawl_id: crawlId, since: nextSince },
                success: function (res) {
                    if (!res || !res.success || !res.data) return;
                    var d = res.data;
                    if (typeof d.next_since === 'number') nextSince = d.next_since;
                    appendCli(d.logs || []);
                    setProgress(d.processed || 0, d.total || 0);
                    if (d.error) {
                        $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                        $statusText.text('Error: ' + d.error);
                    }
                    if (d.done) {
                        $statusBox.removeClass('alert-info alert-danger alert-default').addClass('alert-success').show();
                        $statusText.text('Crawl done for ' + offsetText + '.');
                        $btn.prop('disabled', false).text('Lay du lieu');
                        stopTimers();
                    }
                }
            });
        }

        // Reset UI
        $cli.html('').hide();
        nextSince = 0;
        setProgress(0, 0);

        // Start new crawl job (cache state)
        $.ajax({
            type: 'post',
            url: "{{route('dictionary.ingredient.crawl.start')}}",
            data: {
                _token: $form.find('input[name="_token"]').val(),
                offset: currentOffset
            },
            beforeSend: function () {
                stopTimers();
                $btn.prop('disabled', true).text('Dang chuan bi...');
                $statusBox.removeClass('alert-success alert-danger alert-default').addClass('alert-info').show();
                $statusText.text('Starting crawl for ' + offsetText + ' ...');
                startElapsed();
            },
            success: function (res) {
                if (!res || !res.success || !res.data) {
                    $btn.prop('disabled', false).text('Lay du lieu');
                    $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                    $statusText.text('Start failed.');
                    stopTimers();
                    return;
                }

                isRunning = true;
                crawlId = res.data.crawl_id || '';
                if (!crawlId) {
                    $btn.prop('disabled', false).text('Lay du lieu');
                    $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                    $statusText.text('Missing crawl_id.');
                    stopTimers();
                    return;
                }
                try { localStorage.setItem('dictionary_ingredient_crawl_id', crawlId); } catch (e) {}
                setProgress(0, 0);
                $btn.text('Dang crawl...');

                // Poll logs/progress every second for "CLI" feel
                if (pollTimer) clearInterval(pollTimer);
                pollTimer = setInterval(pollStatus, 1000);
                pollStatus();
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Lay du lieu');
                $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                var msg = 'Start request error.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg += ' ' + xhr.responseJSON.message;
                }
                $statusText.text(msg);
                stopTimers();
            }
        });
    });
</script>
@endsection