@extends('Layout::layout')
@section('title','Cập nhật dữ liệu thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cập nhật dữ liệu',
])
<style>
    /* Disable legacy global ajax loader on this page */
    .img_load_ajax { display: none !important; }
    
    /* Enhanced Crawl UI Styles */
    .crawl-dashboard {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }
    .crawl-stat-card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .crawl-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .crawl-stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #3c8dbc;
        margin: 5px 0;
    }
    .crawl-stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }
    .crawl-stat-card.success .crawl-stat-value { color: #00a65a; }
    .crawl-stat-card.warning .crawl-stat-value { color: #f39c12; }
    .crawl-stat-card.danger .crawl-stat-value { color: #dd4b39; }
    
    .crawl-progress-enhanced {
        background: #f1f1f1;
        border-radius: 10px;
        height: 25px;
        overflow: hidden;
        position: relative;
        margin: 10px 0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    }
    .crawl-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #3c8dbc 0%, #5dade2 50%, #3c8dbc 100%);
        background-size: 200% 100%;
        animation: progress-shimmer 2s infinite;
        transition: width 0.3s ease;
        border-radius: 10px;
        position: relative;
    }
    .crawl-progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: progress-glow 1.5s infinite;
    }
    @keyframes progress-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    @keyframes progress-glow {
        0%, 100% { transform: translateX(-100%); }
        50% { transform: translateX(100%); }
    }
    
    .crawl-activity-feed {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #f9f9f9;
        padding: 10px;
        margin-top: 15px;
    }
    .crawl-activity-item {
        padding: 8px 12px;
        margin: 5px 0;
        background: #fff;
        border-left: 3px solid #3c8dbc;
        border-radius: 4px;
        font-size: 13px;
        animation: slideIn 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .crawl-activity-item.created { border-left-color: #00a65a; }
    .crawl-activity-item.updated { border-left-color: #3c8dbc; }
    .crawl-activity-item.error { border-left-color: #dd4b39; }
    .crawl-activity-time {
        font-size: 11px;
        color: #999;
        margin-left: 10px;
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .crawl-speed-indicator {
        display: inline-block;
        padding: 2px 8px;
        background: #e8f5e9;
        color: #2e7d32;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        margin-left: 10px;
    }
    
    .crawl-status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        margin-left: 10px;
    }
    .crawl-status-badge.running {
        background: #3c8dbc;
        color: white;
        animation: pulse 2s infinite;
    }
    .crawl-status-badge.completed {
        background: #00a65a;
        color: white;
    }
    .crawl-status-badge.error {
        background: #dd4b39;
        color: white;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .crawl-estimate {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }
    
    /* Terminal Style */
    .terminal-box {
        border: 1px solid #333;
        background: #1e1e1e;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        margin-top: 10px;
    }
    .terminal-header {
        background: #2d2d2d;
        padding: 8px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #333;
    }
    .terminal-title {
        color: #ccc;
        font-size: 12px;
        font-weight: 500;
    }
    .terminal-controls {
        display: flex;
        gap: 6px;
    }
    .terminal-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
    .terminal-body {
        padding: 12px;
        max-height: 500px;
        overflow-y: auto;
        overflow-x: hidden;
        background: #1e1e1e;
        color: #d4d4d4;
        font-size: 13px;
        line-height: 1.6;
    }
    .terminal-body::-webkit-scrollbar {
        width: 8px;
    }
    .terminal-body::-webkit-scrollbar-track {
        background: #2d2d2d;
    }
    .terminal-body::-webkit-scrollbar-thumb {
        background: #555;
        border-radius: 4px;
    }
    .terminal-body::-webkit-scrollbar-thumb:hover {
        background: #666;
    }
    .terminal-line {
        margin: 2px 0;
        word-wrap: break-word;
        white-space: pre-wrap;
    }
    .terminal-prompt {
        color: #4ec9b0;
        font-weight: bold;
        margin-right: 8px;
    }
    .terminal-text {
        color: #d4d4d4;
    }
    .terminal-text.success {
        color: #4ec9b0;
    }
    .terminal-text.error {
        color: #f48771;
    }
    .terminal-text.warning {
        color: #dcdcaa;
    }
    .terminal-text.info {
        color: #569cd6;
    }
    .terminal-text.created {
        color: #6a9955;
    }
    .terminal-text.updated {
        color: #4ec9b0;
    }
    .terminal-timestamp {
        color: #808080;
        margin-right: 8px;
    }
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

                        <!-- Real-time Dashboard -->
                        <div id="crawlDashboard" class="crawl-dashboard" style="display:none;">
                            <div class="crawl-stat-card">
                                <div class="crawl-stat-label">Đã xử lý</div>
                                <div class="crawl-stat-value" id="statProcessed">0</div>
                                <div class="crawl-stat-label" id="statProcessedPct">0%</div>
                            </div>
                            <div class="crawl-stat-card">
                                <div class="crawl-stat-label">Tổng số</div>
                                <div class="crawl-stat-value" id="statTotal">0</div>
                                <div class="crawl-stat-label">Items</div>
                            </div>
                            <div class="crawl-stat-card success">
                                <div class="crawl-stat-label">Đã tạo</div>
                                <div class="crawl-stat-value" id="statCreated">0</div>
                                <div class="crawl-stat-label">New items</div>
                            </div>
                            <div class="crawl-stat-card warning">
                                <div class="crawl-stat-label">Đã cập nhật</div>
                                <div class="crawl-stat-value" id="statUpdated">0</div>
                                <div class="crawl-stat-label">Updated items</div>
                            </div>
                            <div class="crawl-stat-card">
                                <div class="crawl-stat-label">Tốc độ</div>
                                <div class="crawl-stat-value" id="statSpeed">0</div>
                                <div class="crawl-stat-label">items/sec</div>
                            </div>
                            <div class="crawl-stat-card">
                                <div class="crawl-stat-label">Thời gian</div>
                                <div class="crawl-stat-value" id="statElapsed">0s</div>
                                <div class="crawl-stat-label" id="statEstimate">Ước tính: --</div>
                            </div>
                        </div>

                        <!-- Status Box -->
                        <div id="crawlStatusBox" class="alert alert-default" style="display:none; margin-top:10px;">
                            <strong>Trạng thái:</strong>
                            <span id="crawlStatusText">Đang chờ thao tác.</span>
                            <span id="crawlStatusBadge" class="crawl-status-badge" style="display:none;"></span>
                            <span id="crawlElapsed" style="margin-left:10px; color:#666; display:none;">(0s)</span>
                        </div>

                        <!-- Enhanced Progress Bar -->
                        <div id="crawlProgressBox" style="display:none; margin-top:10px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:5px;">
                                <strong>Tiến trình:</strong>
                                <span id="crawlProgressText" style="color:#666; font-weight:bold;">0/0</span>
                            </div>
                            <div class="crawl-progress-enhanced">
                                <div id="crawlProgressBar" class="crawl-progress-bar" style="width:0%;"></div>
                            </div>
                            <div class="crawl-estimate" id="crawlEstimate"></div>
                        </div>

                        <!-- Activity Feed -->
                        <div id="crawlActivityBox" style="display:none; margin-top:15px;">
                            <h5 style="margin-bottom:10px;">
                                <i class="fa fa-list"></i> Hoạt động gần đây
                                <span class="crawl-speed-indicator" id="speedIndicator" style="display:none;"></span>
                            </h5>
                            <div id="crawlActivityFeed" class="crawl-activity-feed"></div>
                        </div>

                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-4">
                                <select class="form-control" name="offset" required="" id="crawlOffset">
                                    <option value="">Chon khoang du lieu</option>
                                    @if($page > 0)
                                    @for($i = 0;$i < $page;$i++)
                                    <option value="{{$i*2000}}">{{$i*2000}} - {{($i+1)*2000}}</option>
                                    @endfor
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="button btn btn-primary" type="submit" id="btnStartCrawl">Lay du lieu</button>
                                <button class="button btn btn-danger" type="button" id="btnCancelCrawl" style="display:none; margin-left:10px;">Dừng crawl</button>
                            </div>
                        </div>
                        <!-- Terminal CLI Box -->
                        <div class="result_data" style="margin-top:15px;">
                            <div style="margin-bottom:10px;">
                                <label style="cursor:pointer; display:inline-flex; align-items:center;">
                                    <input type="checkbox" id="showCliBox" style="margin-right:5px; cursor:pointer;">
                                    <strong>Hiển thị log chi tiết (CLI mode)</strong>
                                </label>
                            </div>
                            <div id="crawlCliBox" class="terminal-box" style="display:none;">
                                <div class="terminal-header">
                                    <span class="terminal-title">Terminal - Crawl Log</span>
                                    <span class="terminal-controls">
                                        <span class="terminal-dot" style="background:#ff5f56;"></span>
                                        <span class="terminal-dot" style="background:#ffbd2e;"></span>
                                        <span class="terminal-dot" style="background:#27c93f;"></span>
                                    </span>
                                </div>
                                <div class="terminal-body" id="crawlCliContent">
                                    <div class="terminal-line">
                                        <span class="terminal-prompt">$</span>
                                        <span class="terminal-text">Waiting for crawl to start...</span>
                                    </div>
                                </div>
                            </div>
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
        var $cliContent = $('#crawlCliContent');
        var $btnCancel = $('#btnCancelCrawl');
        var $btnStart = $('#btnStartCrawl');

        var crawlTimer = null;
        var crawlStart = 0;
        var pollTimer = null;
        var nextSince = 0;
        var crawlId = '';
        var currentOffset = parseInt($form.find('select[name="offset"]').val() || '0', 10) || 0;
        var isRunning = false;
        
        // Store all logs for CLI display
        var allLogs = [];
        
        // Statistics tracking
        var stats = {
            processed: 0,
            total: 0,
            created: 0,
            updated: 0,
            errors: 0,
            startTime: 0,
            lastProcessed: 0,
            lastTime: 0,
            speed: 0,
            activityItems: []
        };

        function formatLogLine(line) {
            if (!line || line.trim() === '') return null;
            
            var safe = String(line).replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var timestamp = new Date().toLocaleTimeString('vi-VN');
            var cssClass = 'terminal-text';
            
            // Determine log type by content
            if (line.toLowerCase().indexOf('error') !== -1 || line.toLowerCase().indexOf('lỗi') !== -1) {
                cssClass += ' error';
            } else if (line.toLowerCase().indexOf('created') !== -1 || line.toLowerCase().indexOf('tạo') !== -1) {
                cssClass += ' created';
            } else if (line.toLowerCase().indexOf('updated') !== -1 || line.toLowerCase().indexOf('cập nhật') !== -1) {
                cssClass += ' updated';
            } else if (line.toLowerCase().indexOf('warning') !== -1 || line.toLowerCase().indexOf('cảnh báo') !== -1) {
                cssClass += ' warning';
            } else if (line.toLowerCase().indexOf('info') !== -1 || line.toLowerCase().indexOf('thông tin') !== -1) {
                cssClass += ' info';
            } else if (line.toLowerCase().indexOf('success') !== -1 || line.toLowerCase().indexOf('thành công') !== -1) {
                cssClass += ' success';
            }
            
            return {
                timestamp: timestamp,
                content: safe,
                cssClass: cssClass
            };
        }

        function appendCli(lines) {
            if (!lines || !lines.length) return;
            
            // Always store logs
            lines.forEach(function (line) {
                if (line && line.trim() !== '') {
                    allLogs.push(line);
                }
            });
            
            // Keep only last 2000 lines to avoid memory issues
            if (allLogs.length > 2000) {
                allLogs = allLogs.slice(-2000);
            }
            
            // Display if checkbox is checked
            if ($('#showCliBox').is(':checked')) {
                $cli.show();
                // Append only new lines
                var currentLineCount = $cliContent.find('.terminal-line').length;
                var newLines = allLogs.slice(currentLineCount);
                
                newLines.forEach(function (line) {
                    var formatted = formatLogLine(line);
                    if (formatted) {
                        var $line = $('<div class="terminal-line"></div>');
                        $line.append('<span class="terminal-timestamp">[' + formatted.timestamp + ']</span>');
                        $line.append('<span class="terminal-prompt">$</span>');
                        $line.append('<span class="' + formatted.cssClass + '">' + formatted.content + '</span>');
                        $cliContent.append($line);
                    }
                });
                
                // Auto scroll to bottom
                $cliContent.scrollTop($cliContent[0].scrollHeight);
            }
        }
        
        function refreshCliDisplay() {
            if ($('#showCliBox').is(':checked')) {
                $cli.show();
                // If no logs stored yet but crawl exists (running or completed), fetch all logs
                if (allLogs.length === 0 && crawlId) {
                    // Fetch all logs from beginning
                    $.ajax({
                        type: 'get',
                        url: "{{route('dictionary.ingredient.crawl.status')}}",
                        data: { crawl_id: crawlId, since: 0 },
                        success: function (res) {
                            if (res && res.success && res.data && res.data.logs) {
                                allLogs = res.data.logs || [];
                                $cliContent.html('');
                                
                                if (allLogs.length === 0) {
                                    var $line = $('<div class="terminal-line"></div>');
                                    $line.append('<span class="terminal-prompt">$</span>');
                                    $line.append('<span class="terminal-text info">No logs available yet. Waiting for crawl to start...</span>');
                                    $cliContent.append($line);
                                } else {
                                    allLogs.forEach(function (line) {
                                        var formatted = formatLogLine(line);
                                        if (formatted) {
                                            var $line = $('<div class="terminal-line"></div>');
                                            $line.append('<span class="terminal-timestamp">[' + formatted.timestamp + ']</span>');
                                            $line.append('<span class="terminal-prompt">$</span>');
                                            $line.append('<span class="' + formatted.cssClass + '">' + formatted.content + '</span>');
                                            $cliContent.append($line);
                                        }
                                    });
                                }
                                
                                $cliContent.scrollTop($cliContent[0].scrollHeight);
                            }
                        }
                    });
                } else {
                    // Display stored logs
                    $cliContent.html('');
                    
                    if (allLogs.length === 0) {
                        var $line = $('<div class="terminal-line"></div>');
                        $line.append('<span class="terminal-prompt">$</span>');
                        $line.append('<span class="terminal-text info">No logs available yet. Waiting for crawl to start...</span>');
                        $cliContent.append($line);
                    } else {
                        allLogs.forEach(function (line) {
                            var formatted = formatLogLine(line);
                            if (formatted) {
                                var $line = $('<div class="terminal-line"></div>');
                                $line.append('<span class="terminal-timestamp">[' + formatted.timestamp + ']</span>');
                                $line.append('<span class="terminal-prompt">$</span>');
                                $line.append('<span class="' + formatted.cssClass + '">' + formatted.content + '</span>');
                                $cliContent.append($line);
                            }
                        });
                    }
                    
                    $cliContent.scrollTop($cliContent[0].scrollHeight);
                }
            } else {
                $cli.hide();
            }
        }

        function parseLogLine(line) {
            if (!line) return null;
            var match = line.match(/(\d+)\/(\d+)\s*-\s*(.+?)\s*-\s*(created|updated|error)/i);
            if (match) {
                return {
                    index: parseInt(match[1]),
                    total: parseInt(match[2]),
                    name: match[3].trim(),
                    status: match[4].toLowerCase()
                };
            }
            return null;
        }

        function addActivityItem(item) {
            if (!item) return;
            var $feed = $('#crawlActivityFeed');
            var statusClass = item.status === 'created' ? 'created' : (item.status === 'updated' ? 'updated' : 'error');
            var statusIcon = item.status === 'created' ? '✓' : (item.status === 'updated' ? '↻' : '✗');
            var statusText = item.status === 'created' ? 'Tạo mới' : (item.status === 'updated' ? 'Cập nhật' : 'Lỗi');
            
            var $item = $('<div class="crawl-activity-item ' + statusClass + '">' +
                '<div><strong>' + statusIcon + '</strong> ' + item.name + ' <span class="crawl-activity-time">' + statusText + '</span></div>' +
                '<div class="crawl-activity-time">' + new Date().toLocaleTimeString() + '</div>' +
                '</div>');
            
            $feed.prepend($item);
            
            // Keep only last 50 items
            var items = $feed.find('.crawl-activity-item');
            if (items.length > 50) {
                items.slice(50).remove();
            }
            
            // Update statistics
            if (item.status === 'created') stats.created++;
            else if (item.status === 'updated') stats.updated++;
            else if (item.status === 'error') stats.errors++;
        }

        function updateStatistics(processed, total) {
            processed = processed || 0;
            total = total || 0;
            
            stats.processed = processed;
            stats.total = total;
            
            var now = Date.now();
            if (stats.startTime > 0 && processed > 0) {
                var elapsed = (now - stats.startTime) / 1000; // seconds
                stats.speed = elapsed > 0 ? (processed / elapsed).toFixed(2) : 0;
                
                // Calculate ETA
                if (processed > 0 && total > processed) {
                    var remaining = total - processed;
                    var eta = remaining / (processed / elapsed);
                    var etaText = '';
                    if (eta < 60) {
                        etaText = Math.ceil(eta) + ' giây';
                    } else if (eta < 3600) {
                        etaText = Math.ceil(eta / 60) + ' phút';
                    } else {
                        var hours = Math.floor(eta / 3600);
                        var minutes = Math.ceil((eta % 3600) / 60);
                        etaText = hours + 'h ' + minutes + 'm';
                    }
                    $('#statEstimate').text('Ước tính: ' + etaText);
                    $('#crawlEstimate').text('Còn lại khoảng ' + etaText);
                } else if (processed >= total) {
                    $('#statEstimate').text('Hoàn thành');
                    $('#crawlEstimate').text('Đã hoàn thành');
                }
            }
            
            // Update dashboard
            $('#statProcessed').text(processed.toLocaleString());
            $('#statTotal').text(total.toLocaleString());
            $('#statCreated').text(stats.created.toLocaleString());
            $('#statUpdated').text(stats.updated.toLocaleString());
            $('#statSpeed').text(stats.speed);
            
            var pct = total > 0 ? ((processed / total) * 100).toFixed(1) : 0;
            $('#statProcessedPct').text(pct + '%');
            
            // Update speed indicator
            if (stats.speed > 0) {
                $('#speedIndicator').text(stats.speed + ' items/sec').show();
            }
        }

        function setProgress(processed, total) {
            processed = processed || 0;
            total = total || 0;
            $progressBox.show();
            $progressText.text(processed.toLocaleString() + ' / ' + total.toLocaleString());
            var pct = total > 0 ? Math.floor((processed / total) * 100) : 0;
            if (pct < 0) pct = 0;
            if (pct > 100) pct = 100;
            $('#crawlProgressBar').css('width', pct + '%');
            
            updateStatistics(processed, total);
        }

        function stopTimers() {
            if (crawlTimer) { clearInterval(crawlTimer); crawlTimer = null; }
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            isRunning = false;
            $btnCancel.hide();
        }
        
        function cancelCrawl() {
            if (!crawlId || !confirm('Bạn có chắc chắn muốn dừng crawl này?')) {
                return;
            }
            
            $.ajax({
                type: 'post',
                url: "{{route('dictionary.ingredient.crawl.cancel')}}",
                data: {
                    _token: $form.find('input[name="_token"]').val(),
                    crawl_id: crawlId
                },
                success: function (res) {
                    if (res && res.success) {
                        $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-warning').show();
                        $statusText.text('Đang dừng crawl...');
                        $('#crawlStatusBadge').removeClass('running completed error').addClass('warning').text('Đang dừng').show();
                        
                        // Add cancel message to terminal
                        if ($('#showCliBox').is(':checked')) {
                            var $cancelLine = $('<div class="terminal-line"></div>');
                            $cancelLine.append('<span class="terminal-prompt">$</span>');
                            $cancelLine.append('<span class="terminal-text warning">[CANCELLED] Crawl cancellation requested by user</span>');
                            $cliContent.append($cancelLine);
                            $cliContent.scrollTop($cliContent[0].scrollHeight);
                        }
                        
                        // Continue polling to get final status
                        setTimeout(function() {
                            pollStatus();
                        }, 1000);
                    } else {
                        alert(res.message || 'Không thể dừng crawl');
                    }
                },
                error: function (xhr) {
                    var msg = 'Lỗi khi dừng crawl';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        msg += ': ' + xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        }
        
        // Cancel button click handler
        $btnCancel.on('click', function(e) {
            e.preventDefault();
            cancelCrawl();
        });

        function formatTime(seconds) {
            if (seconds < 60) return seconds + 's';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
            var hours = Math.floor(seconds / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = seconds % 60;
            return hours + 'h ' + minutes + 'm ' + secs + 's';
        }

        function startElapsed() {
            crawlStart = Date.now();
            stats.startTime = crawlStart;
            $('#crawlElapsed').show().text('(0s)');
            if (crawlTimer) clearInterval(crawlTimer);
            crawlTimer = setInterval(function () {
                var sec = Math.floor((Date.now() - crawlStart) / 1000);
                $('#crawlElapsed').text('(' + formatTime(sec) + ')');
                $('#statElapsed').text(formatTime(sec));
            }, 1000);
        }

        function pollStatus() {
            if (!crawlId) return;
            $.ajax({
                type: 'get',
                url: "{{route('dictionary.ingredient.crawl.status')}}",
                data: { crawl_id: crawlId, since: nextSince },
                success: function (res) {
                    if (!res || !res.success || !res.data) {
                        // If no data but we have crawlId, keep polling
                        if (crawlId && isRunning) {
                            return;
                        }
                        return;
                    }
                    var d = res.data;
                    if (typeof d.next_since === 'number') nextSince = d.next_since;
                    
                    // Set isRunning when we get first valid response
                    if (!isRunning && crawlId) {
                        isRunning = true;
                    }
                    
                    // Ensure UI is visible when we get first response
                    if (d.status === 'queued' || d.status === 'running' || d.status === 'cancelling') {
                        $('#crawlDashboard').show();
                        $('#crawlActivityBox').show();
                        $('#crawlProgressBox').show();
                        if ($('#showCliBox').is(':checked')) {
                            $cli.show();
                        }
                    }
                    
                    // Process logs for activity feed and CLI
                    var logs = d.logs || [];
                    if (logs.length > 0) {
                    appendCli(logs);
                    }
                    
                    // Parse and add to activity feed
                    logs.forEach(function(line) {
                        var item = parseLogLine(line);
                        if (item) {
                            addActivityItem(item);
                        }
                    });
                    
                    // Update status badge based on status
                    if (d.status === 'queued') {
                        $('#crawlStatusBadge').removeClass('running completed error').addClass('running').text('Đang chờ').show();
                        $statusText.text('Đang chờ worker xử lý crawl ' + offsetText + '...');
                    } else if (d.status === 'running') {
                        $('#crawlStatusBadge').removeClass('completed error').addClass('running').text('Đang chạy').show();
                        $statusText.text('Đang crawl ' + offsetText + '...');
                    }
                    
                    // Update progress - show even if total is 0 initially
                    var processed = d.processed || 0;
                    var total = d.total || 0;
                    setProgress(processed, total);
                    
                    // If we have total, update status text
                    if (total > 0 && d.status === 'running') {
                        $statusText.text('Đang crawl ' + offsetText + ' (' + processed + '/' + total + ')...');
                    }
                    
                    // Check if cancelled
                    if (d.cancelled || d.status === 'cancelled' || d.status === 'cancelling') {
                        $statusBox.removeClass('alert-info alert-success alert-danger alert-default').addClass('alert-warning').show();
                        $statusText.text('Crawl đã được dừng.');
                        $('#crawlStatusBadge').removeClass('running completed error').addClass('warning').text('Đã dừng').show();
                        $btn.prop('disabled', false).text('Lay du lieu');
                        $btnCancel.hide();
                        stopTimers();
                        
                        // Add final cancel message to terminal
                        if ($('#showCliBox').is(':checked') && d.cancelled) {
                            var $finalLine = $('<div class="terminal-line"></div>');
                            $finalLine.append('<span class="terminal-prompt">$</span>');
                            $finalLine.append('<span class="terminal-text warning">[CANCELLED] Crawl stopped. Processed: ' + (d.processed || 0) + '/' + (d.total || 0) + '</span>');
                            $cliContent.append($finalLine);
                            $cliContent.scrollTop($cliContent[0].scrollHeight);
                        }
                        return;
                    }
                    
                    if (d.error) {
                        $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                        $statusText.text('Lỗi: ' + d.error);
                        $('#crawlStatusBadge').removeClass('running completed').addClass('error').text('Lỗi').show();
                        
                        // Add error message to terminal
                        if ($('#showCliBox').is(':checked')) {
                            var $errorLine = $('<div class="terminal-line"></div>');
                            $errorLine.append('<span class="terminal-prompt">$</span>');
                            $errorLine.append('<span class="terminal-text error">[ERROR] ' + d.error + '</span>');
                            $cliContent.append($errorLine);
                            $cliContent.scrollTop($cliContent[0].scrollHeight);
                        }
                    } else if (d.done) {
                        $statusBox.removeClass('alert-info alert-danger alert-default').addClass('alert-success').show();
                        $statusText.text('Hoàn thành crawl cho ' + offsetText + '.');
                        $('#crawlStatusBadge').removeClass('running error').addClass('completed').text('Hoàn thành').show();
                        $btn.prop('disabled', false).text('Lay du lieu');
                        $btnCancel.hide();
                        
                        // Add completion message to terminal
                        if ($('#showCliBox').is(':checked')) {
                            var $doneLine = $('<div class="terminal-line"></div>');
                            $doneLine.append('<span class="terminal-prompt">$</span>');
                            $doneLine.append('<span class="terminal-text success">[COMPLETED] Crawl finished successfully. Processed: ' + (d.processed || 0) + '/' + (d.total || 0) + '</span>');
                            $cliContent.append($doneLine);
                            $cliContent.scrollTop($cliContent[0].scrollHeight);
                        }
                        
                        stopTimers();
                    } else {
                        $('#crawlStatusBadge').removeClass('completed error').addClass('running').text('Đang chạy').show();
                        $btnCancel.show();
                    }
                },
                error: function(xhr, status, error) {
                    // Ignore browser extension errors silently
                    if (status === 'error' && (!xhr || xhr.status === 0)) {
                        return;
                    }
                    // Only log real errors
                    console.error('Poll status error:', error);
                }
            });
        }

        // CLI box toggle - show terminal immediately when checked
        $('#showCliBox').on('change', function() {
            if ($(this).is(':checked')) {
                refreshCliDisplay();
            } else {
                $cli.hide();
            }
        });
        
        // Show terminal if checkbox is already checked on page load
        if ($('#showCliBox').is(':checked')) {
            refreshCliDisplay();
        }

        // Reset UI
        allLogs = [];
        $cliContent.html('');
        $cli.hide();
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
                
                // Reset statistics
                stats = {
                    processed: 0,
                    total: 0,
                    created: 0,
                    updated: 0,
                    errors: 0,
                    startTime: 0,
                    lastProcessed: 0,
                    lastTime: 0,
                    speed: 0,
                    activityItems: []
                };
                
                // Reset logs
                allLogs = [];
                
                // Reset UI but keep progress boxes ready
                $('#crawlDashboard').show();
                $('#crawlActivityBox').show();
                $('#crawlProgressBox').show();
                $('#crawlActivityFeed').html('');
                $('#statProcessed').text('0');
                $('#statTotal').text('0');
                $('#statCreated').text('0');
                $('#statUpdated').text('0');
                $('#statSpeed').text('0');
                $('#statElapsed').text('0s');
                $('#statEstimate').text('Ước tính: --');
                $('#crawlEstimate').text('');
                $('#speedIndicator').hide();
                $cliContent.html('');
                if ($('#showCliBox').is(':checked')) {
                    $cli.show();
                }
                
                $btn.prop('disabled', true).text('Đang chuẩn bị...');
                $statusBox.removeClass('alert-success alert-danger alert-default').addClass('alert-info').show();
                $statusText.text('Đang khởi động crawl cho ' + offsetText + ' ...');
                $('#crawlStatusBadge').removeClass('completed error').addClass('running').text('Khởi động').show();
                $btnCancel.hide();
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
                    $statusText.text('Thiếu crawl_id.');
                    stopTimers();
                    return;
                }
                try { localStorage.setItem('dictionary_ingredient_crawl_id', crawlId); } catch (e) {}
                
                // Show dashboard and activity feed immediately
                $('#crawlDashboard').show();
                $('#crawlActivityBox').show();
                $('#crawlProgressBox').show();
                $('#crawlStatusBadge').removeClass('completed error').addClass('running').text('Đang chờ').show();
                
                setProgress(0, 0);
                $btn.text('Đang crawl...');
                $statusText.text('Đang khởi động crawl ' + offsetText + '...');
                $btnCancel.show();
                
                // Initialize CLI if checkbox is checked
                if ($('#showCliBox').is(':checked')) {
                    $cli.show();
                    $cliContent.html('');
                    var $initLine = $('<div class="terminal-line"></div>');
                    $initLine.append('<span class="terminal-prompt">$</span>');
                    $initLine.append('<span class="terminal-text info">[INFO] Crawl job queued. Crawl ID: ' + crawlId + '</span>');
                    $cliContent.append($initLine);
                    var $initLine2 = $('<div class="terminal-line"></div>');
                    $initLine2.append('<span class="terminal-prompt">$</span>');
                    $initLine2.append('<span class="terminal-text info">[INFO] Offset: ' + currentOffset + '. Waiting for worker to start...</span>');
                    $cliContent.append($initLine2);
                    $cliContent.scrollTop($cliContent[0].scrollHeight);
                }

                // Poll logs/progress immediately and then every second for real-time updates
                // Poll more frequently when status is "queued" to catch job start
                // First poll immediately
                setTimeout(function() {
                    pollStatus();
                }, 100);
                
                if (pollTimer) clearInterval(pollTimer);
                var pollInterval = 500; // Poll every 500ms initially to catch job start quickly
                var pollCount = 0;
                pollTimer = setInterval(function() {
                pollStatus();
                    pollCount++;
                    // After 10 polls (5 seconds) or when status changes to running, switch to 1 second interval
                    var currentStatus = $('#crawlStatusBadge').text();
                    if (pollCount >= 10 || (currentStatus === 'Đang chạy' && pollCount >= 2)) {
                        clearInterval(pollTimer);
                        pollInterval = 1000;
                        pollTimer = setInterval(pollStatus, pollInterval);
                    }
                }, pollInterval);
            },
            error: function (xhr, status, error) {
                // Ignore browser extension errors (runtime.lastError)
                if (status === 'error' && (!xhr || xhr.status === 0)) {
                    console.warn('Browser extension error ignored:', error);
                    // Still try to check if request actually succeeded
                    setTimeout(function() {
                        if (crawlId) {
                            pollStatus();
                        }
                    }, 500);
                    return;
                }
                
                $btn.prop('disabled', false).text('Lay du lieu');
                $statusBox.removeClass('alert-info alert-success alert-default').addClass('alert-danger').show();
                var msg = 'Start request error.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    msg += ' ' + xhr.responseJSON.message;
                } else if (xhr && xhr.status) {
                    msg += ' HTTP ' + xhr.status;
                }
                $statusText.text(msg);
                stopTimers();
            }
        });
    });
</script>
@endsection