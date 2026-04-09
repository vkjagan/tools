<?php
// standalone-generator/index.php
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <title>Static Photo Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #121212;
            color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .setup-card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 12px;
        }

        .log-window {
            background-color: #0a0a0a;
            color: #20c997;
            font-family: 'Courier New', Courier, monospace;
            overflow-y: auto;
            height: 350px;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #333;
        }

        .log-window p {
            margin: 0;
            padding: 2px 0;
            font-size: 0.9rem;
        }

        .log-error {
            color: #dc3545;
        }

        .log-success {
            color: #198754;
        }
    </style>
</head>

<body>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="mb-4 text-center fw-bold"><i class="bi bi-images me-2 text-primary"></i>High-Performance
                    Static Generator</h2>
                <p class="text-center text-muted mb-5">Converts folder structures into SEO-optimized, infinite-scrolling
                    photo galleries.</p>

                <div class="card setup-card shadow-lg mb-4">
                    <div class="card-body p-4">
                        <form id="genForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Site Home URL (Base Path)</label>
                                    <input type="url" id="siteUrl"
                                        class="form-control bg-dark border-secondary text-light"
                                        value="https://example.com/gallery/" required>
                                    <div class="form-text text-secondary">The public URL where the output folder will be
                                        hosted.</div>
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
                                    <button type="submit" id="startBtn" class="btn btn-primary w-100 py-2 fw-bold">
                                        <i class="bi bi-lightning-charge-fill me-2"></i> Start Generation
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Progress & Logs -->
                <div id="progressArea" style="display: none;">
                    <div class="d-flex justify-content-between mb-2">
                        <span id="statusText" class="fw-bold text-info">Initializing...</span>
                        <span id="percentageText">0%</span>
                    </div>
                    <div class="progress mb-3" style="height: 12px; background-color: #333;">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            style="width: 0%"></div>
                    </div>

                    <div class="log-window shadow-inner" id="logWindow"></div>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('genForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = document.getElementById('startBtn');
            const logs = document.getElementById('logWindow');
            const urlInput = document.getElementById('siteUrl').value;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Generating...';
            document.getElementById('progressArea').style.display = 'block';
            logs.innerHTML = '';

            const appendLog = (msg, type = 'info') => {
                const p = document.createElement('p');
                if (type === 'error') p.classList.add('log-error');
                if (type === 'success') p.classList.add('log-success');
                p.innerHTML = msg;
                logs.appendChild(p);
                logs.scrollTop = logs.scrollHeight;
            };

            appendLog('Connecting to generator process...');

            const timestamp = new Date().getTime();
            const evtSource = new EventSource('generate.php?base_url=' + encodeURIComponent(urlInput) + '&cb=' + timestamp);

            evtSource.onmessage = function (e) {
                const data = JSON.parse(e.data);

                if (data.type === 'log') {
                    appendLog(data.msg);
                } else if (data.type === 'progress') {
                    document.getElementById('progressBar').style.width = data.percent + '%';
                    document.getElementById('percentageText').innerText = data.percent + '%';
                    document.getElementById('statusText').innerText = data.msg;
                    if (data.msg !== document.getElementById('statusText').lastMsg) appendLog(data.msg);
                    document.getElementById('statusText').lastMsg = data.msg;
                } else if (data.type === 'complete') {
                    appendLog('✨ Generation Complete!', 'success');
                    document.getElementById('progressBar').classList.remove('progress-bar-animated');
                    document.getElementById('progressBar').style.width = '100%';
                    document.getElementById('percentageText').innerText = '100%';
                    document.getElementById('statusText').innerText = 'Done.';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Run Again';
                    evtSource.close();
                } else if (data.type === 'error') {
                    appendLog('ERROR: ' + data.msg, 'error');
                    btn.disabled = false;
                    btn.innerText = 'Run Again';
                    evtSource.close();
                }
            };

            evtSource.onerror = function (err) {
                appendLog('Connection to generator lost or finished unexpectedly.', 'error');
                btn.disabled = false;
                btn.innerText = 'Run Again';
                evtSource.close();
            };
        });
    </script>
</body>

</html>