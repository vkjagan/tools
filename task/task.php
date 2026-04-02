<?php
// ── Load configuration ──────────────────────────────────────────
$cfg          = require __DIR__ . '/config.php';
$PASSWORD     = $cfg['password'];
$STORAGE_FILE = $cfg['storage_file'];
$SESSION_NAME = $cfg['session_name'];
$COLUMNS      = $cfg['columns'];
$THEME        = $cfg['theme'];
$WA_NUMBER    = $cfg['whatsapp_number'] ?? '';
$TEAM_MEMBERS = $cfg['team_members'] ?? [];
$TAGS_LIST    = $cfg['tags'] ?? [];
$TITLE        = htmlspecialchars($cfg['title']);
$SUBTITLE     = htmlspecialchars($cfg['subtitle']);
$ICON         = htmlspecialchars($cfg['icon']);

// ── Session ─────────────────────────────────────────────────────
session_name($SESSION_NAME);
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['password']) && $_POST['password'] === $PASSWORD) {
    $_SESSION['authenticated'] = true;
}

// ── Backup AJAX endpoints (must be before save handler) ────────
$BACKUP_DIR  = $cfg['backup_dir']  ?? __DIR__ . '/backups';
$BACKUP_KEEP = $cfg['backup_keep'] ?? 20;

if (isset($_SESSION['authenticated']) && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'list_backups') {
        $files = is_dir($BACKUP_DIR) ? glob($BACKUP_DIR . '/tasks_*.json') : [];
        rsort($files); // newest first
        $list  = array_map(fn($f) => [
            'file'  => basename($f),
            'size'  => filesize($f),
            'mtime' => filemtime($f),
        ], $files);
        header('Content-Type: application/json');
        echo json_encode($list);
        exit;
    }

    if ($action === 'get_backup') {
        $file = $_GET['file'] ?? '';
        if (!preg_match('/^tasks_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.json$/', $file)) {
            http_response_code(400); exit;
        }
        $path = $BACKUP_DIR . '/' . $file;
        if (!file_exists($path)) { http_response_code(404); exit; }
        header('Content-Type: application/json');
        echo file_get_contents($path);
        exit;
    }
}

// ── Bulletproof save (with auto-backup) ─────────────────────────
if (isset($_SESSION['authenticated']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $decoded = json_decode($json, true);
        if ($decoded && isset($decoded['items'])) {
            // 1. Write main file
            file_put_contents($STORAGE_FILE, json_encode($decoded, JSON_PRETTY_PRINT));

            // 2. Create timestamped backup
            if (!is_dir($BACKUP_DIR)) {
                mkdir($BACKUP_DIR, 0755, true);
                // Protect directory from direct web access
                file_put_contents($BACKUP_DIR . '/.htaccess', "Order Allow,Deny\nDeny from all\n");
            }
            $ts   = date('Y-m-d_H-i-s');
            file_put_contents($BACKUP_DIR . '/tasks_' . $ts . '.json', json_encode($decoded, JSON_PRETTY_PRINT));

            // 3. Prune old backups (keep newest $BACKUP_KEEP)
            $all = glob($BACKUP_DIR . '/tasks_*.json');
            sort($all); // oldest first
            while (count($all) > $BACKUP_KEEP) { @unlink(array_shift($all)); }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success']);
            exit;
        }
    }
}

// ── Reliable load & repair ──────────────────────────────────────
$data = ["lastId" => 0, "items" => []];
if (file_exists($STORAGE_FILE) && filesize($STORAGE_FILE) > 0) {
    $content = file_get_contents($STORAGE_FILE);
    $decoded_content = json_decode($content, true);
    if (isset($decoded_content['items'])) {
        $data = $decoded_content;
    } elseif (is_array($decoded_content)) {
        $data['items'] = $decoded_content;
        $data['lastId'] = count($decoded_content);
    }
}
$tasks_json       = json_encode($data);
$columns_json     = json_encode($COLUMNS);
$team_json        = json_encode($TEAM_MEMBERS);
$tags_list_json   = json_encode($TAGS_LIST);
$color_labels     = $cfg['color_labels'] ?? [];
$color_labels_json = json_encode($color_labels);

// Build column options
$col_options = '';
foreach ($COLUMNS as $k => $v) {
    $col_options .= '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v['label']) . '</option>';
}

// Build assignee options
$assignee_options = '<option value="">— Unassigned</option>';
foreach ($TEAM_MEMBERS as $m) {
    $assignee_options .= '<option value="' . htmlspecialchars($m) . '">' . htmlspecialchars($m) . '</option>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $TITLE ?> · <?= $SUBTITLE ?></title>
    <meta name="description" content="<?= $SUBTITLE ?> task management board">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --accent:      <?= $THEME['accent'] ?>;
            --hdr-from:    <?= $THEME['header_from'] ?>;
            --hdr-to:      <?= $THEME['header_to'] ?>;
            --pri-high:    #ef4444;
            --pri-medium:  #f59e0b;
            --pri-low:     #10b981;
            --bg:          #f1f5f9;
            --card-bg:     #ffffff;
            --surface:     #f8fafc;
        }
        * { box-sizing: border-box; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; min-height: 100vh; }

        /* ── HEADER ── */
        .app-header {
            background: linear-gradient(135deg, var(--hdr-from) 0%, var(--hdr-to) 100%);
            color: white; padding: 13px 20px;
            position: sticky; top: 0; z-index: 200;
            box-shadow: 0 4px 24px rgba(0,0,0,0.35);
        }
        .app-title { font-size: 1.1rem; font-weight: 800; letter-spacing: -0.3px; margin: 0; }
        .app-title span { font-weight: 300; opacity: 0.55; font-size: 0.88rem; }

        /* ── HEADER BUTTONS ── */
        .hbtn {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.18);
            color: white; border-radius: 8px; padding: 6px 12px; font-size: 0.8rem;
            font-family: 'Inter', sans-serif; cursor: pointer; transition: background 0.2s;
            display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .hbtn:hover  { background: rgba(255,255,255,0.2); color: white; }
        .hbtn.danger { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.4); }
        .hbtn.danger:hover { background: rgba(239,68,68,0.35); }
        .hbtn.primary { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.35); }
        .hbtn.primary:hover { background: rgba(255,255,255,0.25); }

        /* ── SEARCH ── */
        .search-wrap { position: relative; }
        .search-wrap .fa-magnifying-glass { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.45); font-size: 0.8rem; pointer-events: none; }
        .search-bar {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.18);
            border-radius: 8px; color: white; padding: 6px 14px 6px 34px;
            font-size: 0.84rem; width: 200px; transition: all 0.25s; font-family: 'Inter', sans-serif;
        }
        .search-bar::placeholder { color: rgba(255,255,255,0.4); }
        .search-bar:focus { outline: none; background: rgba(255,255,255,0.16); border-color: rgba(255,255,255,0.4); width: 250px; }

        /* ── BOARD ── */
        .board-grid { display: grid; grid-template-columns: repeat(var(--num-cols, 4), 1fr); gap: 16px; align-items: start; }
        @media (max-width: 1199px) { .board-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 767px)  { .board-grid { grid-template-columns: 1fr; } }

        /* ── COLUMN ── */
        .kanban-col {
            background: var(--surface); border-radius: 14px; padding: 14px;
            border: 1.5px solid #e2e8f0; min-height: 280px;
            transition: border-color 0.2s, background 0.2s;
        }
        .kanban-col.drag-over { background: #eff6ff; border-color: var(--accent); border-style: dashed; }
        .col-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid var(--col-color, #e2e8f0); }
        .col-title  { font-size: 0.72rem; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; display: flex; align-items: center; gap: 6px; color: var(--col-color, #64748b); }
        .col-count  { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: color-mix(in srgb, var(--col-color, #64748b) 12%, transparent); color: var(--col-color, #64748b); }

        /* ── TASK CARD ── */
        .task-card {
            background: var(--card-bg); padding: 12px 12px 10px; margin-bottom: 10px;
            border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.07), 0 2px 8px rgba(0,0,0,0.04);
            cursor: grab; border-left: 4px solid #e2e8f0; position: relative;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .task-card:hover  { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
        .task-card.pri-high   { border-left-color: var(--pri-high);   }
        .task-card.pri-medium { border-left-color: var(--pri-medium); }
        .task-card.pri-low    { border-left-color: var(--pri-low);    }
        .task-card.dragging   { opacity: 0.35; transform: scale(0.97); }
        .task-card.card-done    { opacity: 0.72; }
        .task-card.card-archive { opacity: 0.58; }

        .task-top  { display: flex; align-items: flex-start; gap: 6px; margin-bottom: 7px; }
        .task-num  { font-size: 0.68rem; font-weight: 700; color: #94a3b8; white-space: nowrap; margin-top: 2px; }
        .task-text { font-size: 0.84rem; font-weight: 500; color: #1e293b; line-height: 1.45; flex: 1; word-break: break-word; }

        .pri-badge { font-size: 0.62rem; font-weight: 700; padding: 2px 7px; border-radius: 20px; white-space: nowrap; text-transform: uppercase; letter-spacing: 0.5px; }
        .pri-badge.high   { background: rgba(239,68,68,0.12);  color: var(--pri-high); }
        .pri-badge.medium { background: rgba(245,158,11,0.12);  color: var(--pri-medium); }
        .pri-badge.low    { background: rgba(16,185,129,0.12);  color: var(--pri-low); }

        .task-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 6px; }
        .due-chip { font-size: 0.7rem; color: #94a3b8; display: flex; align-items: center; gap: 4px; }
        .due-chip.overdue   { color: var(--pri-high);   font-weight: 600; }
        .due-chip.due-today { color: var(--pri-medium); font-weight: 600; }

        .card-actions { display: flex; gap: 3px; opacity: 0; transition: opacity 0.2s; }
        .task-card:hover .card-actions { opacity: 1; }
        .cbtn { background: none; border: none; padding: 4px 6px; border-radius: 6px; cursor: pointer; font-size: 0.76rem; transition: background 0.15s; }
        .cbtn.edit { color: #3b82f6; } .cbtn.edit:hover { background: rgba(59,130,246,0.1); }
        .cbtn.move { color: #8b5cf6; } .cbtn.move:hover { background: rgba(139,92,246,0.1); }
        .cbtn.wa   { color: #25D366; } .cbtn.wa:hover   { background: rgba(37,211,102,0.1); }
        .cbtn.del  { color: #ef4444; } .cbtn.del:hover  { background: rgba(239,68,68,0.1); }

        /* ── ASSIGNEE ── */
        .assignee-avatar { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 0.57rem; font-weight: 800; color: white; flex-shrink: 0; }
        .assignee-chip   { display: flex; align-items: center; gap: 5px; font-size: 0.7rem; color: #64748b; }

        /* ── TAG CHIPS (card) ── */
        .card-tags { display: flex; flex-wrap: wrap; gap: 4px; margin: 5px 0 3px; }
        .tag-chip  { font-size: 0.61rem; font-weight: 600; padding: 2px 7px; border-radius: 20px; }

        /* ── NOTES PREVIEW (card) ── */
        .notes-preview { font-size: 0.73rem; color: #64748b; font-style: italic; margin: 3px 0 2px; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ── CREATED CHIP ── */
        .created-chip { font-size: 0.65rem; color: #cbd5e1; }

        /* ── TAG PICKER (modal) ── */
        .tag-picker { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag-pick-item { font-size: 0.74rem; font-weight: 600; padding: 4px 11px; border-radius: 20px; cursor: pointer; background: #f1f5f9; color: #475569; border: 1.5px solid transparent; transition: all 0.15s; user-select: none; }
        .tag-pick-item.selected { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); }

        /* ── CARD PROGRESS BAR ── */
        .card-progress-wrap { height: 3px; background: #f1f5f9; border-radius: 0 0 7px 7px; margin: 8px -12px -10px; overflow: hidden; }
        .card-progress-fill { height: 100%; border-radius: 0 0 7px 7px; transition: width 0.4s ease, background 0.4s ease; }

        /* ── PROGRESS SLIDER ── */
        .form-range { accent-color: var(--accent); }

        /* ── REMINDER CHIP (on card) ── */
        .reminder-chip { font-size: 0.68rem; font-weight: 600; color: #f59e0b; display: flex; align-items: center; gap: 4px; margin: 3px 0 2px; }
        .reminder-chip.fired { color: #94a3b8; font-weight: 400; }
        @keyframes bellRing { 0%,100%{transform:rotate(-14deg);}50%{transform:rotate(14deg);} }
        .bell-ping { display:inline-block; animation: bellRing 0.7s ease infinite; }

        /* ── COLOR LABELS ── */
        .color-strip { display:flex; height:5px; margin:-12px -12px 10px; border-radius:7px 7px 0 0; overflow:hidden; }
        .color-strip-seg { flex:1; height:100%; }
        .cl-picker { display:flex; flex-wrap:wrap; gap:8px; }
        .cl-swatch {
            width:28px; height:28px; border-radius:50%; cursor:pointer;
            border:2.5px solid transparent; transition:transform 0.15s, box-shadow 0.15s;
            display:flex; align-items:center; justify-content:center;
        }
        .cl-swatch:hover { transform:scale(1.12); }
        .cl-swatch i { font-size:11px; color:white; opacity:0; transition:opacity 0.15s; pointer-events:none; }
        .cl-swatch.selected { border-color:white; box-shadow:0 0 0 2.5px #475569; transform:scale(1.12); }
        .cl-swatch.selected i { opacity:1; }

        /* ── REMINDER MODAL ── */
        #reminderModal .modal-content { border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 64px rgba(0,0,0,0.25); }
        #reminderModal .modal-header  { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; border: none; padding: 18px 22px; }

        /* ── MOVE DROPDOWN ── */
        .move-wrap { position: relative; }
        .move-drop { position: absolute; right: 0; top: 100%; background: white; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.14); z-index: 600; min-width: 148px; padding: 5px; display: none; }
        .move-drop.show { display: block; }
        .move-opt { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 7px; cursor: pointer; font-size: 0.79rem; font-weight: 500; color: #475569; transition: background 0.15s; }
        .move-opt:hover { background: #f1f5f9; }
        .move-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 28px 10px; color: #cbd5e1; }
        .empty-state i { font-size: 1.8rem; display: block; margin-bottom: 8px; }
        .empty-state p { font-size: 0.76rem; margin: 0; }

        /* ── TOAST ── */
        #toast-wrap { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
        .toast-pill { background: #1e293b; color: white; padding: 10px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 500; box-shadow: 0 6px 24px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 10px; max-width: 280px; animation: toastIn 0.3s ease; }
        .toast-pill.success { border-left: 3px solid #10b981; }
        .toast-pill.error   { border-left: 3px solid #ef4444; }
        .toast-pill.info    { border-left: 3px solid #3b82f6; }
        .toast-pill.warning { border-left: 3px solid #f59e0b; }
        @keyframes toastIn  { from { transform: translateX(80px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes toastOut { to   { transform: translateX(60px); opacity: 0; } }

        /* ── MODALS ── */
        .modal-content { border: none; border-radius: 16px; }
        .modal-header  { border-radius: 16px 16px 0 0; padding: 16px 20px; }
        .form-label    { font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
        .form-control, .form-select { font-family: 'Inter', sans-serif; font-size: 0.86rem; border: 1.5px solid #e2e8f0; border-radius: 8px; }
        .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }

        /* ── LOGIN ── */
        .login-bg { min-height: 100vh; background: linear-gradient(135deg, var(--hdr-from) 0%, var(--hdr-to) 60%, var(--hdr-from) 100%); display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-card { background: white; border-radius: 20px; padding: 40px 36px; box-shadow: 0 24px 64px rgba(0,0,0,0.25); max-width: 380px; width: 100%; text-align: center; }
        .login-icon { width: 62px; height: 62px; background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 60%, #000)); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; font-size: 1.5rem; color: white; }
        .btn-login { border-radius: 10px; background: linear-gradient(135deg, var(--accent), color-mix(in srgb, var(--accent) 60%, #000)); border: none; font-family: 'Inter', sans-serif; font-weight: 700; }

        @media (max-width: 767px) { .hide-sm { display: none !important; } }

        /* ── PROGRESS BAR ── */
        .progress-strip {
            height: 4px;
            background: rgba(0,0,0,0.06);
            position: sticky;
            top: 57px;          /* sits flush under the header */
            z-index: 199;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #3b82f6);
            transition: width 0.5s cubic-bezier(.4,0,.2,1);
            position: relative;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            right: 0; top: 0; bottom: 0;
            width: 40px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35));
            animation: shimmer 1.5s infinite;
        }
        @keyframes shimmer { 0%,100%{opacity:0;} 50%{opacity:1;} }
        .progress-label {
            position: absolute;
            right: 10px;
            top: -18px;
            font-size: 0.65rem;
            font-weight: 700;
            color: #64748b;
            white-space: nowrap;
        }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['authenticated'])): ?>
<!-- ═══════════════ LOGIN ═══════════════ -->
<div class="login-bg">
    <div class="login-card animate__animated animate__fadeInUp">
        <div class="login-icon"><i class="fas <?= $ICON ?>"></i></div>
        <h5 class="fw-bold mb-1" style="color:#0f172a; font-size:1.15rem;"><?= $TITLE ?></h5>
        <p class="text-muted mb-1" style="font-size:0.8rem;"><?= $SUBTITLE ?></p>
        <p class="text-muted mb-4" style="font-size:0.82rem;">Enter your password to continue</p>
        <form method="POST">
            <input type="password" name="password" class="form-control mb-3 text-center"
                   placeholder="Enter password" required autofocus style="padding:12px; font-size:0.95rem;">
            <button type="submit" class="btn btn-primary btn-login w-100 py-2">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══════════════ APP ═══════════════ -->

<header class="app-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fas <?= $ICON ?>" style="font-size:1.1rem; opacity:0.85;"></i>
            <p class="app-title mb-0"><?= $TITLE ?> <span>· <?= $SUBTITLE ?></span></p>
        </div>
        <div class="search-wrap d-none d-md-block">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" id="searchInput" class="search-bar" placeholder="Search tasks…">
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button class="hbtn primary" onclick="openAddModal()"><i class="fas fa-plus"></i><span class="hide-sm"> Add Task</span></button>
            <button class="hbtn" onclick="new bootstrap.Modal(document.getElementById('bulkModal')).show()"><i class="fas fa-list-ol"></i><span class="hide-sm"> Bulk</span></button>
            <button class="hbtn" onclick="openBackupModal()" title="Backups"><i class="fas fa-clock-rotate-left"></i><span class="hide-sm"> Backups</span></button>
            <label class="hbtn" style="cursor:pointer;" title="Import JSON">
                <i class="fas fa-file-import"></i>
                <input type="file" id="importFile" hidden onchange="importJson(event)">
            </label>
            <button class="hbtn" onclick="exportData()" title="Export JSON"><i class="fas fa-file-export"></i></button>
            <a href="?logout=1" class="hbtn danger" title="Logout"><i class="fas fa-power-off"></i></a>
        </div>
    </div>
    <div class="d-md-none mt-2 search-wrap">
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" id="searchInputMobile" class="search-bar" placeholder="Search tasks…" style="width:100%;">
    </div>
</header>

<!-- PROGRESS BAR -->
<div class="progress-strip">
    <div class="progress-fill" id="progressFill" style="width:0%">
        <span class="progress-label" id="progressLabel"></span>
    </div>
</div>

<div class="container-fluid py-4 px-3">
    <div class="board-grid" id="board" style="--num-cols:<?= count($COLUMNS) ?>;"></div>
</div>

<div id="toast-wrap"></div>

<!-- ── ADD / EDIT MODAL ── -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,<?= $THEME['header_from'] ?>,<?= $THEME['header_to'] ?>); color:white;">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label">Task Description *</label>
                    <textarea id="taskText" class="form-control" rows="2" placeholder="Enter task description…"></textarea>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">Priority</label>
                        <select id="taskPriority" class="form-select">
                            <option value="none">— None</option>
                            <option value="high">🔴 High</option>
                            <option value="medium">🟡 Medium</option>
                            <option value="low">🟢 Low</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Due Date</label>
                        <input type="date" id="taskDue" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Assignee</label>
                        <select id="taskAssignee" class="form-select"><?= $assignee_options ?></select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Column</label>
                        <select id="taskCol" class="form-select"><?= $col_options ?></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Tags</label>
                        <div class="tag-picker" id="tagPicker"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea id="taskNotes" class="form-control" rows="2" placeholder="Additional context, links, or details…"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-flex justify-content-between">
                            <span>Task Progress</span>
                            <span id="progressVal" class="fw-bold" style="color:var(--accent)">0%</span>
                        </label>
                        <input type="range" id="taskProgress" class="form-range" min="0" max="100" step="5" value="0"
                               oninput="document.getElementById('progressVal').textContent=this.value+'%'">
                        <div class="d-flex justify-content-between" style="font-size:0.62rem;color:#94a3b8;margin-top:1px;">
                            <span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span>
                        </div>
                    </div>
                    <div class="col-7">
                        <label class="form-label"><i class="fas fa-bell me-1" style="color:#f59e0b;"></i>Set Reminder</label>
                        <input type="datetime-local" id="taskReminder" class="form-control">
                    </div>
                    <div class="col-5 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="clearReminderField()" style="border-radius:8px;font-size:0.78rem;">
                            <i class="fas fa-bell-slash me-1"></i>Clear
                        </button>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-palette me-1" style="color:#8b5cf6;"></i>Color Labels</label>
                        <div class="cl-picker" id="colorLabelPicker"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-login fw-bold px-4" onclick="saveTask()">
                    <i class="fas fa-save me-1"></i> Save Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── BACKUP MODAL ── -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0f172a,#1a3558);color:white;">
                <h5 class="modal-title fw-bold"><i class="fas fa-clock-rotate-left me-2"></i>Auto Backups</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="px-4 py-3 border-bottom" style="background:#f8fafc;">
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>A backup is created automatically on every save. Click <strong>Restore</strong> to roll back the board to that point.</small>
                </div>
                <div class="p-3">
                    <table class="table table-hover table-sm mb-0" style="font-size:0.83rem;">
                        <thead style="background:#f1f5f9;">
                            <tr>
                                <th style="width:44px;">#</th>
                                <th>Backup Date &amp; Time</th>
                                <th>Size</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="backupTableBody">
                            <tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto"><i class="fas fa-shield-halved me-1"></i>Backups are stored server-side and protected from direct web access.</small>
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ── BULK MODAL ── -->
<div class="modal fade" id="bulkModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-list-ol me-2"></i>Bulk Update</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-2">Paste a JSON items array to replace the entire board.</p>
                <textarea id="bulkText" class="form-control" rows="10"
                    placeholder='[{"id":1,"text":"Example","column":"todo","priority":"medium","dueDate":""}]'></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary btn-login fw-bold" onclick="applyBulk()">
                    <i class="fas fa-sync me-1"></i> Replace Board
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── REMINDER ALERT MODAL ── -->
<div class="modal fade" id="reminderModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm" style="margin-top:80px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-bell me-2"></i>Reminder</h5>
            </div>
            <div class="modal-body text-center p-4">
                <div style="font-size:2.6rem;margin-bottom:12px;">⏰</div>
                <p class="fw-bold mb-1" id="reminderTaskNum" style="color:#0f172a;font-size:0.95rem;"></p>
                <p class="text-muted mb-0" id="reminderTaskText" style="font-size:0.83rem;line-height:1.45;"></p>
            </div>
            <div class="modal-footer justify-content-center gap-2 border-0 pt-0 pb-3">
                <button class="btn btn-outline-secondary btn-sm" onclick="snoozeReminder(15)">
                    <i class="fas fa-clock me-1"></i>+15 min
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="snoozeReminder(60)">
                    <i class="fas fa-clock me-1"></i>+1 hr
                </button>
                <button class="btn btn-warning btn-sm fw-bold px-4" data-bs-dismiss="modal">Dismiss</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let boardData   = <?= $tasks_json ?>;
    const COLS           = <?= $columns_json ?>;
    const WA_NUMBER      = <?= json_encode($WA_NUMBER) ?>;
    const TEAM           = <?= $team_json ?>;
    const TAGS_CFG       = <?= $tags_list_json ?>;
    const COLOR_LABELS   = <?= $color_labels_json ?>;

    // Color label picker
    function renderColorPicker(selectedColors) {
        const wrap = document.getElementById('colorLabelPicker');
        wrap.innerHTML = '';
        COLOR_LABELS.forEach(color => {
            const s = document.createElement('div');
            s.className = 'cl-swatch' + (selectedColors.includes(color) ? ' selected' : '');
            s.style.background = color;
            s.dataset.color = color;
            s.title = color;
            s.innerHTML = '<i class="fas fa-check"></i>';
            s.onclick = () => s.classList.toggle('selected');
            wrap.appendChild(s);
        });
    }
    function getSelectedColors() {
        return [...document.querySelectorAll('#colorLabelPicker .cl-swatch.selected')].map(s => s.dataset.color);
    }

    // Tag color palette (cycles by tag index)
    const TAG_PAL = [
        {bg:'#dbeafe',text:'#1d4ed8'},{bg:'#ede9fe',text:'#6d28d9'},
        {bg:'#fce7f3',text:'#be185d'},{bg:'#fef3c7',text:'#92400e'},
        {bg:'#d1fae5',text:'#065f46'},{bg:'#fee2e2',text:'#991b1b'},
        {bg:'#e0f2fe',text:'#0369a1'},{bg:'#fef9c3',text:'#854d0e'}
    ];
    function tagColor(tag) { const i = TAGS_CFG.indexOf(tag); return TAG_PAL[Math.abs(i) % TAG_PAL.length]; }

    // Progress colour: red → amber → blue → green
    function progressColor(pct) {
        if (pct >= 100) return '#10b981';
        if (pct >= 67)  return '#3b82f6';
        if (pct >= 34)  return '#f59e0b';
        return '#ef4444';
    }

    // Assignee avatar color
    function avatarColor(name) {
        const p = ['#6366f1','#8b5cf6','#ec4899','#f59e0b','#10b981','#3b82f6','#ef4444','#06b6d4'];
        let h = 0; for (let c of (name||'')) h = ((h<<5)-h)+c.charCodeAt(0);
        return p[Math.abs(h) % p.length];
    }
    function initials(name) { return (name||'?').split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2); }

    // Render tag picker into #tagPicker, mark selected ones
    function renderTagPicker(selectedTags) {
        const picker = document.getElementById('tagPicker');
        picker.innerHTML = '';
        TAGS_CFG.forEach(tag => {
            const s = document.createElement('span');
            s.className = 'tag-pick-item' + (selectedTags.includes(tag) ? ' selected' : '');
            s.textContent = tag;
            s.dataset.tag = tag;
            s.onclick = () => s.classList.toggle('selected');
            picker.appendChild(s);
        });
    }
    function getSelectedTags() {
        return [...document.querySelectorAll('#tagPicker .tag-pick-item.selected')].map(s => s.dataset.tag);
    }

    /* ── REMINDERS ── */
    let reminderTimers = [];
    let snoozedTaskId  = null;

    function requestNotifyPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    function scheduleReminders() {
        reminderTimers.forEach(id => clearTimeout(id));
        reminderTimers = [];
        const now = Date.now();
        boardData.items.forEach(t => {
            if (!t.reminderAt || t.reminderFired) return;
            const fireAt = new Date(t.reminderAt).getTime();
            const delay  = fireAt - now;
            if (delay > 0) {
                reminderTimers.push(setTimeout(() => fireReminder(t.id), delay));
            } else if (delay > -10 * 60 * 1000) {
                // Missed by less than 10 min — fire immediately on load
                reminderTimers.push(setTimeout(() => fireReminder(t.id), 500));
            }
        });
    }

    function fireReminder(id) {
        const t = boardData.items.find(i => i.id === id);
        if (!t || t.reminderFired) return;
        t.reminderFired = true;
        save(); render();
        // Browser notification
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(`⏰ Reminder · Task #${t.id}`, {
                body: t.text.length > 90 ? t.text.slice(0, 90) + '…' : t.text
            });
        }
        showReminderAlert(t);
    }

    function showReminderAlert(t) {
        snoozedTaskId = t.id;
        document.getElementById('reminderTaskNum').textContent  = `Task #${t.id}`;
        document.getElementById('reminderTaskText').textContent = t.text;
        new bootstrap.Modal(document.querySelector('#reminderModal.modal')).show();
    }

    function snoozeReminder(minutes) {
        const el = document.querySelector('#reminderModal.modal');
        const m  = bootstrap.Modal.getInstance(el); if (m) m.hide();
        const t  = boardData.items.find(i => i.id === snoozedTaskId);
        if (!t) return;
        t.reminderAt    = new Date(Date.now() + minutes * 60 * 1000).toISOString().slice(0, 16);
        t.reminderFired = false;
        save(); render(); scheduleReminders();
        toast(`Snoozed for ${minutes < 60 ? minutes + ' min' : (minutes / 60) + ' hr'}`, 'info');
    }

    function clearReminderField() {
        document.getElementById('taskReminder').value = '';
    }
    let draggedTask = null;
    let searchQuery = '';
    let activeDrop  = null;
    let taskModal   = null;

    /* ── TOAST ── */
    function toast(msg, type = 'success', ms = 2400) {
        const icons = { success:'fa-check-circle', error:'fa-circle-xmark', info:'fa-circle-info', warning:'fa-triangle-exclamation' };
        const el = document.createElement('div');
        el.className = `toast-pill ${type}`;
        el.innerHTML = `<i class="fas ${icons[type]}"></i>${msg}`;
        document.getElementById('toast-wrap').appendChild(el);
        setTimeout(() => { el.style.animation='toastOut 0.3s ease forwards'; setTimeout(()=>el.remove(),300); }, ms);
    }

    /* ── HELPERS ── */
    function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function fmtDate(d) {
        if (!d) return null;
        return new Date(d + 'T00:00:00').toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
    }

    function fmtDateTime(dt) {
        if (!dt) return '';
        const d = new Date(dt);
        return d.toLocaleDateString('en-GB', { day:'2-digit', month:'short' }) +
               ' ' + d.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit', hour12:false });
    }

    function dateStatus(d) {
        if (!d) return 'none';
        const today = new Date(); today.setHours(0,0,0,0);
        const due   = new Date(d + 'T00:00:00');
        if (due < today) return 'overdue';
        if (due.getTime() === today.getTime()) return 'due-today';
        return 'upcoming';
    }

    /* ── RENDER ── */
    function render() {
        const board = document.getElementById('board');
        board.innerHTML = '';
        const q = searchQuery.toLowerCase();

        Object.entries(COLS).forEach(([key, meta]) => {
            const items = boardData.items.filter(t =>
                t.column === key && (q === '' || t.text.toLowerCase().includes(q))
            );

            const col = document.createElement('div');
            col.className = 'kanban-col';
            col.dataset.col = key;
            col.style.setProperty('--col-color', meta.color);
            col.innerHTML = `
                <div class="col-header">
                    <span class="col-title"><i class="fas ${meta.icon}"></i>${meta.label}</span>
                    <span class="col-count">${items.length}</span>
                </div>
                <div id="list-${key}" style="min-height:100px;"></div>`;
            board.appendChild(col);

            col.ondragover  = e => { e.preventDefault(); col.classList.add('drag-over'); };
            col.ondragleave = () => col.classList.remove('drag-over');
            col.ondrop = () => {
                col.classList.remove('drag-over');
                if (draggedTask && draggedTask.column !== key) {
                    draggedTask.column = key;
                    render(); save(); toast(`Moved to ${meta.label}`, 'info');
                } else { draggedTask = null; }
            };

            const list = col.querySelector(`#list-${key}`);
            if (items.length === 0) {
                list.innerHTML = `<div class="empty-state"><i class="fas ${meta.icon}"></i><p>No tasks here</p></div>`;
                return;
            }
            items.forEach(t => list.appendChild(buildCard(t)));
        });

        updateProgress();
    }

    /* ── PROGRESS BAR ── */
    function updateProgress() {
        const total = boardData.items.length;
        if (total === 0) {
            document.getElementById('progressFill').style.width = '0%';
            document.getElementById('progressLabel').textContent = '';
            return;
        }
        const doneKeys = Object.keys(COLS).filter(k => k === 'done' || k === 'archive');
        // Weighted: use task.progress if set; else 100 for done/archive cols, 0 otherwise
        const sum = boardData.items.reduce((s, t) => {
            const pct = (t.progress !== undefined && t.progress !== null)
                ? t.progress
                : (doneKeys.includes(t.column) ? 100 : 0);
            return s + pct;
        }, 0);
        const pct = Math.round(sum / total);
        document.getElementById('progressFill').style.width  = pct + '%';
        document.getElementById('progressLabel').textContent = `${pct}% complete`;
    }

    function buildCard(t) {
        const card = document.createElement('div');
        const pri  = t.priority || 'none';
        const ds   = dateStatus(t.dueDate);

        card.className = `task-card${pri !== 'none' ? ' pri-'+pri : ''}${t.column === 'done' ? ' card-done' : ''}${t.column === 'archive' ? ' card-archive' : ''}`;
        card.draggable = true;

        // Color strip at top of card
        const colorStrip = (t.colorLabels && t.colorLabels.length)
            ? `<div class="color-strip">${t.colorLabels.map(c => `<div class="color-strip-seg" style="background:${c}"></div>`).join('')}</div>`
            : '';

        const priBadge = pri !== 'none' ? `<span class="pri-badge ${pri}">${pri}</span>` : '';

        // Tags row
        const tagsHtml = (t.tags && t.tags.length)
            ? `<div class="card-tags">${t.tags.map(tag => {
                const c = tagColor(tag);
                return `<span class="tag-chip" style="background:${c.bg};color:${c.text}">${esc(tag)}</span>`;
              }).join('')}</div>`
            : '';

        // Notes preview
        const notesHtml = t.notes
            ? `<div class="notes-preview"><i class="fas fa-note-sticky me-1" style="opacity:.5"></i>${esc(t.notes)}</div>`
            : '';

        // Reminder indicator
        const reminderHtml = t.reminderAt
            ? `<div class="reminder-chip${t.reminderFired ? ' fired' : ''}">
                <i class="fas fa-bell${t.reminderFired ? '' : ' bell-ping'}"></i>
                ${t.reminderFired ? 'Reminded &check;' : esc(fmtDateTime(t.reminderAt))}
               </div>`
            : '';

        // Due chip
        const dueChip = t.dueDate ? `
            <span class="due-chip ${ds}">
                <i class="fas ${ds==='overdue'?'fa-triangle-exclamation':ds==='due-today'?'fa-bell':'fa-calendar-day'}"></i>
                ${ds==='overdue'?'Overdue · ':ds==='due-today'?'Today · ':''}${fmtDate(t.dueDate)}
            </span>` : '<span></span>';

        // Assignee
        const assigneeHtml = t.assignee
            ? `<span class="assignee-chip">
                <span class="assignee-avatar" style="background:${avatarColor(t.assignee)}">${initials(t.assignee)}</span>
                ${esc(t.assignee)}
               </span>`
            : (t.createdAt ? `<span class="created-chip"><i class="fas fa-clock me-1"></i>${fmtDate(t.createdAt)}</span>` : '<span></span>');

        // Move dropdown
        const moveOpts = Object.entries(COLS)
            .filter(([k]) => k !== t.column)
            .map(([k, v]) => `
                <div class="move-opt" onclick="moveTask(${t.id},'${k}')">
                    <span class="move-dot" style="background:${v.color}"></span>${v.label}
                </div>`).join('');

        const waBtn = WA_NUMBER
            ? `<button class="cbtn wa" onclick="shareWA(${t.id})" title="Send to WhatsApp"><i class="fab fa-whatsapp"></i></button>`
            : '';

        card.innerHTML = `
            ${colorStrip}
            <div class="task-top">
                <span class="task-num">#${t.id}</span>
                <span class="task-text">${esc(t.text)}</span>
                ${priBadge}
            </div>
            ${tagsHtml}
            ${notesHtml}
            ${reminderHtml}
            <div class="task-footer" style="margin-top:6px">
                <div style="display:flex;align-items:center;gap:6px;">
                    ${assigneeHtml}
                    ${dueChip}
                </div>
                <div class="card-actions">
                    ${waBtn}
                    <button class="cbtn edit" onclick="openEdit(${t.id})" title="Edit"><i class="fas fa-pen"></i></button>
                    <div class="move-wrap">
                        <button class="cbtn move" onclick="toggleDrop(event,${t.id})" title="Move to…"><i class="fas fa-arrows-left-right"></i></button>
                        <div class="move-drop" id="drop-${t.id}">${moveOpts}</div>
                    </div>
                    <button class="cbtn del" onclick="deleteTask(${t.id})" title="Delete"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="card-progress-wrap">
                <div class="card-progress-fill" style="width:${t.progress||0}%;background:${progressColor(t.progress||0)}"></div>
            </div>`;

        card.ondragstart = () => { draggedTask = t; card.classList.add('dragging'); };
        card.ondragend   = () => card.classList.remove('dragging');
        return card;
    }

    /* ── MOVE DROPDOWN ── */
    function toggleDrop(e, id) {
        e.stopPropagation();
        const d = document.getElementById(`drop-${id}`);
        if (activeDrop && activeDrop !== d) activeDrop.classList.remove('show');
        d.classList.toggle('show');
        activeDrop = d.classList.contains('show') ? d : null;
    }
    document.addEventListener('click', () => { if (activeDrop) { activeDrop.classList.remove('show'); activeDrop=null; } });

    function moveTask(id, col) {
        const t = boardData.items.find(i => i.id === id);
        if (t) {
            t.column = col;
            // Auto-complete when moved to done/archive
            const doneKeys = Object.keys(COLS).filter(k => k === 'done' || k === 'archive');
            if (doneKeys.includes(col) && (t.progress || 0) < 100) t.progress = 100;
            render(); save(); toast(`Moved to ${COLS[col].label}`, 'info');
        }
    }

    /* ── WHATSAPP SHARE ── */
    function shareWA(id) {
        const t = boardData.items.find(i => i.id === id);
        if (!t) return;
        const col      = COLS[t.column]  ? COLS[t.column].label  : t.column;
        const pri      = t.priority && t.priority !== 'none' ? t.priority.charAt(0).toUpperCase() + t.priority.slice(1) : 'None';
        const due      = t.dueDate ? fmtDate(t.dueDate) : 'No due date';
        const assignee = t.assignee || 'Unassigned';
        const tags     = (t.tags && t.tags.length) ? t.tags.join(', ') : 'None';
        const notes    = t.notes || '';
        let msg =
            `📋 *Task #${t.id}*\n` +
            `${t.text}\n\n` +
            `📌 Status: ${col}\n` +
            `🚦 Priority: ${pri}\n` +
            `👤 Assignee: ${assignee}\n` +
            `🏷️ Tags: ${tags}\n` +
            `📅 Due: ${due}`;
        if (notes) msg += `\n\n📝 Notes: ${notes}`;
        const url = `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(msg)}`;
        window.open(url, '_blank');
    }

    /* ── SAVE ── */
    async function save() {
        try {
            const r = await fetch(window.location.href, {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(boardData)
            });
            if (!r.ok) throw new Error();
        } catch { toast('Save failed!', 'error'); }
    }

    /* ── MODAL ── */
    function getModal() { return taskModal || (taskModal = new bootstrap.Modal(document.getElementById('taskModal'))); }

    function openAddModal() {
        document.getElementById('editId').value         = '';
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Add Task';
        document.getElementById('taskText').value       = '';
        document.getElementById('taskPriority').value   = 'none';
        document.getElementById('taskDue').value        = '';
        document.getElementById('taskAssignee').value   = '';
        document.getElementById('taskNotes').value      = '';
        document.getElementById('taskProgress').value      = 0;
        document.getElementById('progressVal').textContent  = '0%';
        document.getElementById('taskReminder').value       = '';
        renderColorPicker([]);
        document.getElementById('taskCol').selectedIndex = 0;
        renderTagPicker([]);
        getModal().show();
        setTimeout(() => document.getElementById('taskText').focus(), 400);
    }

    function openEdit(id) {
        const t = boardData.items.find(i => i.id === id);
        if (!t) return;
        const pct = t.progress !== undefined ? t.progress : 0;
        document.getElementById('editId').value         = t.id;
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-pen me-2"></i>Edit Task';
        document.getElementById('taskText').value       = t.text;
        document.getElementById('taskPriority').value   = t.priority  || 'none';
        document.getElementById('taskDue').value        = t.dueDate   || '';
        document.getElementById('taskAssignee').value   = t.assignee  || '';
        document.getElementById('taskNotes').value      = t.notes     || '';
        document.getElementById('taskProgress').value      = pct;
        document.getElementById('progressVal').textContent  = pct + '%';
        document.getElementById('taskReminder').value       = t.reminderAt || '';
        renderColorPicker(t.colorLabels || []);
        document.getElementById('taskCol').value            = t.column;
        renderTagPicker(t.tags || []);
        getModal().show();
        setTimeout(() => document.getElementById('taskText').focus(), 400);
    }

    function saveTask() {
        const text     = document.getElementById('taskText').value.trim();
        if (!text) { toast('Description cannot be empty!', 'warning'); return; }
        const id       = document.getElementById('editId').value;
        const pri      = document.getElementById('taskPriority').value;
        const due      = document.getElementById('taskDue').value;
        const col      = document.getElementById('taskCol').value;
        const assignee = document.getElementById('taskAssignee').value;
        const notes    = document.getElementById('taskNotes').value.trim();
        const tags     = getSelectedTags();
        const progress    = parseInt(document.getElementById('taskProgress').value, 10);
        const reminderAt  = document.getElementById('taskReminder').value || null;
        const colorLabels = getSelectedColors();
        if (id) {
            const t = boardData.items.find(i => i.id === parseInt(id));
            if (t) {
                const resetFired = reminderAt && reminderAt !== t.reminderAt;
                Object.assign(t, { text, priority:pri, dueDate:due, column:col, assignee, notes, tags, progress, reminderAt, colorLabels });
                if (resetFired) t.reminderFired = false;
            }
            toast('Task updated!', 'info');
        } else {
            boardData.lastId++;
            const today = new Date().toISOString().split('T')[0];
            boardData.items.push({ id:boardData.lastId, text, column:col, priority:pri, dueDate:due, assignee, notes, tags, progress, reminderAt, reminderFired:false, colorLabels, createdAt:today });
            toast('Task added!', 'success');
        }
        getModal().hide(); render(); save(); scheduleReminders();
    }

    /* ── DELETE ── */
    function deleteTask(id) {
        boardData.items = boardData.items.filter(i => i.id !== id);
        render(); save(); toast(`Task #${id} deleted`, 'error', 3000);
    }

    /* ── BULK ── */
    function applyBulk() {
        try {
            const items = JSON.parse(document.getElementById('bulkText').value);
            if (!Array.isArray(items)) throw new Error();
            boardData.items  = items;
            boardData.lastId = items.reduce((m,o) => o.id>m?o.id:m, 0);
            render(); save();
            bootstrap.Modal.getInstance(document.getElementById('bulkModal')).hide();
            toast('Board replaced!', 'info');
        } catch { toast('Invalid JSON!', 'error'); }
    }

    /* ── IMPORT / EXPORT ── */
    function importJson(e) {
        const reader = new FileReader();
        reader.onload = ev => {
            try {
                const parsed = JSON.parse(ev.target.result);
                const items  = parsed.items || (Array.isArray(parsed) ? parsed : null);
                if (!items) throw new Error();
                boardData.items  = items;
                boardData.lastId = items.reduce((m,o) => o.id>m?o.id:m, 0);
                render(); save(); toast('Imported successfully!');
            } catch { toast('Invalid JSON file!', 'error'); }
        };
        reader.readAsText(e.target.files[0]);
        e.target.value = '';
    }

    function exportData() {
        const blob = new Blob([JSON.stringify(boardData, null, 2)], { type:'application/json' });
        const a    = document.createElement('a');
        a.href     = URL.createObjectURL(blob);
        a.download = `tasks-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        toast('Exported!');
    }

    /* ── BACKUPS ── */
    function parseBkDate(filename) {
        const m = filename.match(/(\d{4})-(\d{2})-(\d{2})_(\d{2})-(\d{2})-(\d{2})/);
        if (!m) return null;
        return new Date(`${m[1]}-${m[2]}-${m[3]}T${m[4]}:${m[5]}:${m[6]}`);
    }
    function fmtBkDate(filename) {
        const d = parseBkDate(filename);
        if (!d) return filename;
        return d.toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false });
    }

    async function openBackupModal() {
        new bootstrap.Modal(document.getElementById('backupModal')).show();
        const tbody = document.getElementById('backupTableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin me-2"></i>Loading…</td></tr>';
        try {
            const res  = await fetch(window.location.href + '?action=list_backups');
            const list = await res.json();
            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No backups yet — they appear after the first save.</td></tr>';
                return;
            }
            tbody.innerHTML = list.map((b, i) => `
                <tr>
                    <td class="text-muted">${i + 1}</td>
                    <td><i class="fas fa-file-code me-2 text-primary" style="opacity:.6"></i>${fmtBkDate(b.file)}</td>
                    <td class="text-muted">${(b.size / 1024).toFixed(1)} KB</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-success fw-bold" onclick="restoreBackup('${b.file}')">
                            <i class="fas fa-rotate-left me-1"></i>Restore
                        </button>
                    </td>
                </tr>`).join('');
        } catch { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load backups.</td></tr>'; }
    }

    async function restoreBackup(filename) {
        const label = fmtBkDate(filename);
        if (!confirm(`Restore board from backup:\n\n⋙ ${label}\n\nThis will replace the current board. Continue?`)) return;
        try {
            const res = await fetch(`${window.location.href}?action=get_backup&file=${encodeURIComponent(filename)}`);
            if (!res.ok) throw new Error('Server error ' + res.status);
            const data = await res.json();
            if (!data.items) throw new Error('Invalid backup format');
            boardData = data;
            render(); save();
            bootstrap.Modal.getInstance(document.getElementById('backupModal')).hide();
            toast(`Board restored from ${label}`, 'success', 4000);
        } catch (e) { toast('Restore failed: ' + e.message, 'error'); }
    }

    /* ── SEARCH ── */
    ['searchInput','searchInputMobile'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', e => {
            searchQuery = e.target.value;
            ['searchInput','searchInputMobile'].forEach(sid => {
                const s = document.getElementById(sid);
                if (s && s !== e.target) s.value = e.target.value;
            });
            render();
        });
    });

    /* ── KEYBOARD ── */
    document.addEventListener('keydown', e => {
        const inField = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName);
        if (!inField && e.key === 'n') openAddModal();
        if (e.ctrlKey && e.key === 'Enter' && document.getElementById('taskModal').classList.contains('show')) saveTask();
    });

    requestNotifyPermission();
    scheduleReminders();
    render();
</script>
<?php endif; ?>
</body>
</html>