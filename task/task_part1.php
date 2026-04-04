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

// ── Concurrency Check & Save Handler ─────────────────────────
if (isset($_SESSION['authenticated']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $incoming = json_decode($json, true);
        
        // Load current data from disk to check version
        $currentStr = file_exists($STORAGE_FILE) ? file_get_contents($STORAGE_FILE) : '{"items":[],"lastUpdated":0}';
        $current    = json_decode($currentStr, true);
        
        $serverVersion = $current['lastUpdated'] ?? 0;
        $clientVersion = $incoming['lastUpdated'] ?? 0;

        // If client's version is older than server's version, someone else saved!
        if ($clientVersion > 0 && $serverVersion > $clientVersion) {
            header('Content-Type: application/json');
            http_response_code(409); // Conflict
            echo json_encode(['status' => 'conflict', 'message' => 'The board has been updated by someone else. Please refresh to merge changes.']);
            exit;
        }

        if ($incoming && isset($incoming['items'])) {
            // Update the version timestamp before saving
            $incoming['lastUpdated'] = time();
            $finalJson = json_encode($incoming, JSON_PRETTY_PRINT);

            // 1. Write main file
            file_put_contents($STORAGE_FILE, $finalJson);

            // 2. Create timestamped backup
            if (!is_dir($BACKUP_DIR)) {
                mkdir($BACKUP_DIR, 0755, true);
                file_put_contents($BACKUP_DIR . '/.htaccess', "Order Allow,Deny\nDeny from all\n");
            }
            $ts = date('Y-m-d_H-i-s');
            file_put_contents($BACKUP_DIR . '/tasks_' . $ts . '.json', $finalJson);

            // 3. Prune old backups
            $all = glob($BACKUP_DIR . '/tasks_*.json');
            sort($all); 
            while (count($all) > $BACKUP_KEEP) { @unlink(array_shift($all)); }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'lastUpdated' => $incoming['lastUpdated']]);
            exit;
        }
    }
}

// ── Reliable load & repair ──────────────────────────────────────
$data = ["lastId" => 0, "items" => [], "lastUpdated" => time()];
if (file_exists($STORAGE_FILE) && filesize($STORAGE_FILE) > 0) {
    $content = file_get_contents($STORAGE_FILE);
    $decoded_content = json_decode($content, true);
    if (isset($decoded_content['items'])) {
        $data = $decoded_content;
    } elseif (is_array($decoded_content)) {
        $data['items'] = $decoded_content;
        $data['lastId'] = count($decoded_content);
        $data['lastUpdated'] = time();
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
