@extends('Layout::layout')
@section('title','Đồng bộ Media lên R2 (Chạy ngầm)')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Đồng bộ Media lên R2',
])
<section class="content">
    <div class="row">
        <!-- Configuration & Status Column -->
        <div class="col-md-5">
            <div class="box box-primary">
                 <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-cogs"></i> Cấu hình & Điều khiển</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <p><i class="icon fa fa-info-circle"></i> Công cụ chạy ngầm trên server.</p>
                        <p>Bạn có thể tắt trình duyệt sau khi bấm bắt đầu.</p>
                    </div>

                    <div class="form-group">
                        <label>Tùy chọn:</label>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" id="skip-existing" checked> Bỏ qua file đã có trên R2 (Skip Existing)
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Số lượng file xử lý mỗi lần (Batch Size):</label>
                        <select id="batch-size" class="form-control">
                            <option value="5">5 file / lần</option>
                            <option value="10">10 file / lần</option>
                            <option value="25">25 file / lần</option>
                            <option value="50">50 file / lần</option>
                            <option value="100" selected>100 file / lần (Nhanh nhất)</option>
                        </select>
                        <p class="help-block text-muted"><i class="fa fa-bolt"></i> Chọn số lượng lớn để tối ưu tốc độ upload.</p>
                    </div>

                    <hr>

                    <div id="control-buttons">
                        <button id="btn-start-sync" class="btn btn-primary btn-lg btn-block"><i class="fa fa-play"></i> Bắt đầu chạy ngầm</button>
                        <button id="btn-stop-sync" class="btn btn-danger btn-lg btn-block" style="display:none; margin-top: 10px;"><i class="fa fa-stop"></i> Dừng ngay lập tức</button>
                    </div>
                </div>
            </div>

            <!-- Stats Box -->
            <div class="box box-solid" id="stats-box" style="display:none;">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bar-chart"></i> Thống kê thời gian thực</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="info-box bg-green">
                                <span class="info-box-icon"><i class="fa fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Đã xử lý</span>
                                    <span class="info-box-number" id="stat-processed">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="info-box bg-yellow">
                                <span class="info-box-icon"><i class="fa fa-forward"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Đã bỏ qua</span>
                                    <span class="info-box-number" id="stat-skipped">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="info-box bg-red">
                                <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Lỗi</span>
                                    <span class="info-box-number" id="stat-errors">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="info-box bg-aqua">
                                <span class="info-box-icon"><i class="fa fa-files-o"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng file</span>
                                    <span class="info-box-number" id="stat-total">...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log & Monitor Column -->
        <div class="col-md-7">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-terminal"></i> Nhật ký hoạt động (Live Monitor)</h3>
                </div>
                <div class="box-body">
                    <div id="sync-status" style="display:none;">
                        <p><strong>Trạng thái:</strong> <span id="status-text" class="label label-default">Đang chờ...</span></p>
                        <p><strong>Hoạt động gần nhất:</strong> <span id="last-action" class="text-info">...</span></p>
                        
                        <div class="progress active">
                           <div class="progress-bar progress-bar-success progress-bar-striped" id="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                             <span id="progress-percent">0%</span>
                           </div>
                        </div>
                   </div>

                   <div class="well well-sm" style="background: #222; color: #0f0; font-family: monospace; height: 300px; overflow-y: auto;" id="console-log">
                       <div>System ready...</div>
                   </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnStart = document.getElementById('btn-start-sync');
        const btnStop = document.getElementById('btn-stop-sync');
        const consoleLog = document.getElementById('console-log');
        let pollInterval = null;

        // Check status on load
        checkStatus();

        btnStop.addEventListener('click', function() {
            if(!confirm('Dừng ngay lập tức?')) return;
            
            log('Đang gửi lệnh dừng...', 'warning');
            
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');

            fetch('/admin/r2/stop-sync-background', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                log('Đã gửi lệnh dừng.', 'warning');
            });
        });

        btnStart.addEventListener('click', function() {
            if(!confirm('Bạn có chắc chắn muốn bắt đầu? Hành động này sẽ chạy ngầm.')) return;
            
            const batch = document.getElementById('batch-size').value;
            const skip = document.getElementById('skip-existing').checked ? 1 : 0;

            disableControls(true);
            log('Khởi động tiến trình background...', 'info');

            // Use Fetch API instead of jQuery Ajax to avoid extension interference issues
            const formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('batch', batch);
            formData.append('skip', skip);

            fetch('/admin/r2/start-sync-background', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                log('Đã gửi lệnh chạy ngầm thành công.', 'success');
                startPolling();
            })
            .catch(error => {
                log('Lỗi khởi động: ' + error, 'error');
                disableControls(false);
            });
        });

        function startPolling() {
            if (pollInterval) clearInterval(pollInterval);
            pollInterval = setInterval(checkStatus, 2000); 
        }

        function checkStatus() {
            fetch('/admin/r2/get-sync-status', {
                method: 'GET',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                updateUI(data);
            })
            .catch(error => {
                // Silently fail or log sparingly to avoid flooding if network is temporary down
                // console.warn('Status poll failed:', error);
            });
        }

        function updateUI(data) {
            const statusBox = document.getElementById('sync-status');
            const statsBox = document.getElementById('stats-box');
            
            if (data.status === 'processing' || data.status === 'scanning') {
                statusBox.style.display = 'block';
                statsBox.style.display = 'block';
                disableControls(true);
                document.getElementById('btn-stop-sync').style.display = 'block';
                
                document.getElementById('status-text').className = 'label label-warning';
                document.getElementById('status-text').innerText = data.status === 'scanning' ? 'ĐANG QUÉT FILE' : 'ĐANG ĐỒNG BỘ';
                document.getElementById('last-action').innerText = data.last_action || data.message;
                
                // Update Progress
                const percent = data.percent || 0;
                document.getElementById('progress-bar').style.width = percent + '%';
                document.getElementById('progress-percent').innerText = percent + '%';

                // Update Stats
                document.getElementById('stat-processed').innerText = data.processed || 0;
                document.getElementById('stat-skipped').innerText = data.skipped || 0;
                document.getElementById('stat-errors').innerText = data.errors || 0;
                document.getElementById('stat-total').innerText = data.total || 0;

                // Log message if it changed
                if (data.message && window.lastMessage !== data.message) {
                    log(data.message);
                    window.lastMessage = data.message;
                }

                if (!pollInterval) startPolling();

            } else if (data.status === 'done') {
                statusBox.style.display = 'block';
                statsBox.style.display = 'block';
                
                document.getElementById('progress-bar').style.width = '100%';
                document.getElementById('progress-percent').innerText = '100%';
                
                document.getElementById('status-text').className = 'label label-success';
                document.getElementById('status-text').innerText = 'HOÀN TẤT';
                document.getElementById('last-action').innerText = 'Đồng bộ kết thúc.';

                document.getElementById('stat-processed').innerText = data.processed;
                document.getElementById('stat-skipped').innerText = data.skipped;
                document.getElementById('stat-errors').innerText = data.errors;
                
                disableControls(false);
                btnStart.innerText = 'Bắt đầu lại';
                
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                log('Quy trình hoàn tất!', 'success');
            } else if (data.status === 'error') {
                 document.getElementById('status-text').className = 'label label-danger';
                 document.getElementById('status-text').innerText = 'LỖI';
                 log('Có lỗi xảy ra: ' + data.message, 'error');
                 disableControls(false);
                 if (pollInterval) clearInterval(pollInterval);
            }
        }

        function disableControls(disabled) {
            btnStart.disabled = disabled;
            if (disabled) {
                btnStart.innerHTML = '<i class="fa fa-refresh fa-spin"></i> Đang chạy...';
            } else {
                btnStart.innerHTML = '<i class="fa fa-play"></i> Bắt đầu chạy ngầm';
            }
        }

        function log(msg, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const div = document.createElement('div');
            div.style.borderBottom = '1px solid #333';
            div.style.padding = '2px 0';
            
            let color = '#ccc';
            if (type === 'success') color = '#00a65a';
            if (type === 'error') color = '#dd4b39';
            if (type === 'warning') color = '#f39c12';

            div.innerHTML = `<span style="color:#666">[${time}]</span> <span style="color:${color}">${msg}</span>`;
            
            consoleLog.insertBefore(div, consoleLog.firstChild);
            
            // Limit log size
            if (consoleLog.children.length > 50) {
                consoleLog.removeChild(consoleLog.lastChild);
            }
        }
    });
</script>
@endsection
