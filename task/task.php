<?php
// --- SETTINGS ---
$PASSWORD = "gp";
$STORAGE_FILE = 'tasks.json';

session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password']) && $_POST['password'] === $PASSWORD) {
    $_SESSION['authenticated'] = true;
}

// --- BULLETPROOF SAVING ---
if (isset($_SESSION['authenticated']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');

    if (!empty($json)) {
        $decoded = json_decode($json, true);

        // ONLY save if the data is valid and contains an items array
        // This prevents the "0 bytes" wipe-out
        if ($decoded && isset($decoded['items'])) {
            file_put_contents($STORAGE_FILE, json_encode($decoded, JSON_PRETTY_PRINT));
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}

// --- RELIABLE LOAD & REPAIR ---
$data = ["lastId" => 0, "items" => []]; // Default

if (file_exists($STORAGE_FILE) && filesize($STORAGE_FILE) > 0) {
    $content = file_get_contents($STORAGE_FILE);
    $decoded_content = json_decode($content, true);

    // Check if it's the new format
    if (isset($decoded_content['items'])) {
        $data = $decoded_content;
    }
    // If it's the old format (just an array), convert it
    elseif (is_array($decoded_content)) {
        $data['items'] = $decoded_content;
        $data['lastId'] = count($decoded_content);
    }
}
$tasks_json = json_encode($data);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaia Plas Board</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Inter', sans-serif;
        }

        .kanban-col {
            min-height: 400px;
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #0dcaf0;
            margin-bottom: 20px;
        }

        .task-card {
            background: white;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            cursor: grab;
            border-left: 5px solid #0d6efd;
            position: relative;
        }

        .task-num {
            font-weight: 800;
            color: #0d6efd;
            margin-right: 5px;
        }

        /* Bold Red X */
        .delete-btn {
            color: #ff0000;
            cursor: pointer;
            float: right;
            border: none;
            background: none;
            padding: 0;
            font-size: 1.2rem;
        }

        .delete-btn:hover {
            color: #b30000;
        }

        #save-status {
            font-size: 0.75rem;
            display: none;
        }

        .dragging {
            opacity: 0.3;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
            }

            .btn-group {
                margin-top: 10px;
                width: 100%;
            }

            .btn-group .btn {
                flex: 1;
            }
        }
    </style>
</head>

<body>

    <?php if (!isset($_SESSION['authenticated'])): ?>
        <div class="container mt-5 pt-5" style="max-width: 400px;">
            <div class="card p-4 shadow-lg border-0 text-center">
                <h5 class="fw-bold mb-3"><i class="fas fa-lock"></i> GP Production Access</h5>
                <form method="POST">
                    <input type="password" name="password" class="form-control mb-3 text-center" placeholder="Password"
                        required autofocus>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="container-fluid py-3 px-3">
            <header class="bg-white p-3 rounded shadow-sm mb-4">
                <div class="d-flex header-content justify-content-between align-items-center">
                    <h4 class="fw-bold text-primary m-0">
                        Production Board <span id="save-status"
                            class="badge bg-success animate__animated animate__fadeIn">SAVED</span>
                    </h4>
                    <div class="btn-group btn-group-sm shadow-sm">
                        <button class="btn btn-dark"
                            onclick="new bootstrap.Modal(document.getElementById('bulkModal')).show()">
                            <i class="fas fa-list-ol"></i> List
                        </button>
                        <label class="btn btn-outline-dark m-0">
                            <i class="fas fa-file-import"></i> <input type="file" id="importFile" hidden
                                onchange="importJson(event)">
                        </label>
                        <button class="btn btn-outline-secondary" onclick="exportData()">
                            <i class="fas fa-file-export"></i>
                        </button>
                        <a href="?logout=1" class="btn btn-danger"><i class="fas fa-power-off"></i></a>
                    </div>
                </div>
            </header>

            <div class="row mb-4 justify-content-center">
                <div class="col-12 col-md-5">
                    <div class="input-group shadow-sm">
                        <input type="text" id="taskInput" class="form-control" placeholder="New Task...">
                        <button class="btn btn-primary fw-bold" onclick="addTask()">ADD</button>
                    </div>
                </div>
            </div>

            <div class="row">
                <?php foreach (['todo' => 'TO DO', 'inprogress' => 'IN PROGRESS', 'done' => 'DONE'] as $k => $v): ?>
                    <div class="col-12 col-md-4">
                        <div class="kanban-col shadow-sm" data-col="<?= $k ?>">
                            <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2"><?= $v ?></h6>
                            <div id="<?= $k ?>-list" style="min-height: 150px;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="modal fade" id="bulkModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-header bg-dark text-white">
                        <h5>Bulk Update</h5>
                    </div>
                    <div class="modal-body text-start">
                        <p class="small text-muted">Paste your JSON items here.</p>
                        <textarea id="bulkText" class="form-control" rows="10"
                            placeholder='[{"id":1, "text":"Example", "column":"todo"}]'></textarea>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary w-100" onclick="applyBulk()">Replace Board</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            let boardData = <?php echo $tasks_json; ?>;
            let draggedTask = null;

            function render() {
                document.querySelectorAll('[id$="-list"]').forEach(el => el.innerHTML = '');
                boardData.items.forEach(t => {
                    const card = document.createElement('div');
                    card.className = 'task-card';
                    card.draggable = true;
                    card.innerHTML = `
                    <button class="delete-btn" onclick="deleteTask(${t.id})">
                        <i class="fas fa-times-circle"></i>
                    </button>
                    <span class="task-num">#${t.id}</span> <span>${t.text}</span>
                `;

                    card.ondragstart = () => { draggedTask = t; card.classList.add('dragging'); };
                    card.ondragend = () => card.classList.remove('dragging');

                    // Mobile Move
                    card.onclick = (e) => {
                        if (window.innerWidth < 768 && !e.target.closest('.delete-btn')) {
                            const seq = ['todo', 'inprogress', 'done', 'todo'];
                            t.column = seq[seq.indexOf(t.column) + 1];
                            render(); save();
                        }
                    };

                    const col = document.getElementById(t.column + '-list');
                    if (col) col.appendChild(card);
                });
            }

            async function save() {
                const status = document.getElementById('save-status');
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(boardData)
                    });
                    if (response.ok) {
                        status.style.display = 'inline-block';
                        setTimeout(() => status.style.display = 'none', 1500);
                    }
                } catch (e) { console.error("Save failed", e); }
            }

            function addTask() {
                const val = document.getElementById('taskInput').value.trim();
                if (!val) return;
                boardData.lastId++;
                boardData.items.push({ id: boardData.lastId, text: val, column: 'todo' });
                document.getElementById('taskInput').value = '';
                render(); save();
            }

            function deleteTask(id) {
                if (confirm("Delete Task #" + id + "?")) {
                    boardData.items = boardData.items.filter(item => item.id !== id);
                    render(); save();
                }
            }

            function applyBulk() {
                try {
                    const items = JSON.parse(document.getElementById('bulkText').value);
                    if (Array.isArray(items)) {
                        boardData.items = items;
                        boardData.lastId = items.reduce((max, obj) => obj.id > max ? obj.id : max, 0);
                        render(); save();
                        bootstrap.Modal.getInstance(document.getElementById('bulkModal')).hide();
                    }
                } catch (e) { alert("Invalid JSON!"); }
            }

            function importJson(e) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    try {
                        const items = JSON.parse(event.target.result);
                        if (Array.isArray(items)) {
                            boardData.items = items;
                            boardData.lastId = items.reduce((max, obj) => obj.id > max ? obj.id : max, 0);
                            render(); save();
                        }
                    } catch (e) { alert("Invalid JSON File!"); }
                };
                reader.readAsText(e.target.files[0]);
            }

            document.querySelectorAll('.kanban-col').forEach(col => {
                col.ondragover = e => e.preventDefault();
                col.ondrop = () => {
                    if (draggedTask) {
                        draggedTask.column = col.dataset.col;
                        render(); save();
                    }
                };
            });

            document.getElementById('taskInput').onkeypress = e => { if (e.key === 'Enter') addTask(); };
            render();
        </script>
    <?php endif; ?>
</body>

</html>