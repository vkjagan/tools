<?php
$configFile = __DIR__ . '/config.php';
$cfg = require $configFile;

session_name($cfg['session_name'] ?? 'kanban_admin');
session_start();

$admin_password = $cfg['admin_password'] ?? 'admin123';

if (isset($_GET['logout'])) {
    unset($_SESSION['admin_auth']);
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === $admin_password) {
        $_SESSION['admin_auth'] = true;
    } else {
        $error = "Invalid admin password.";
    }
}

if (isset($_SESSION['admin_auth']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    // Read and save form data
    $newConfig = $cfg;
    $newConfig['password'] = $_POST['password'] ?? $newConfig['password'];
    $newConfig['admin_password'] = $_POST['admin_password'] ?? $newConfig['admin_password'];
    $newConfig['title'] = $_POST['title'] ?? $newConfig['title'];
    $newConfig['subtitle'] = $_POST['subtitle'] ?? $newConfig['subtitle'];
    $newConfig['icon'] = $_POST['icon'] ?? $newConfig['icon'];
    $newConfig['whatsapp_number'] = $_POST['whatsapp_number'] ?? $newConfig['whatsapp_number'];
    
    // Server & Storage
    $newConfig['storage_file'] = $_POST['storage_file'] ?? $newConfig['storage_file'];
    $newConfig['session_name'] = $_POST['session_name'] ?? $newConfig['session_name'];
    $newConfig['backup_dir'] = $_POST['backup_dir'] ?? $newConfig['backup_dir'];
    $newConfig['backup_keep'] = (int)($_POST['backup_keep'] ?? $newConfig['backup_keep']);
    
    // Arrays
    $newConfig['team_members'] = array_map('trim', explode(',', $_POST['team_members'] ?? ''));
    $newConfig['tags'] = array_map('trim', explode(',', $_POST['tags'] ?? ''));
    $newConfig['color_labels'] = array_map('trim', explode(',', $_POST['color_labels'] ?? ''));
    
    // Theme
    $newConfig['theme']['accent'] = $_POST['theme_accent'] ?? $newConfig['theme']['accent'];
    $newConfig['theme']['header_from'] = $_POST['theme_header_from'] ?? $newConfig['theme']['header_from'];
    $newConfig['theme']['header_to'] = $_POST['theme_header_to'] ?? $newConfig['theme']['header_to'];
    
    // Columns (we'll keep existing ones for now, maybe just rename labels)
    foreach ($newConfig['columns'] as $key => &$col) {
        if (isset($_POST["col_{$key}_label"])) {
            $col['label'] = $_POST["col_{$key}_label"];
            $col['color'] = $_POST["col_{$key}_color"];
        }
    }
    
    // Save to config.php
    // Use var_export but convert to short array syntax
    $export = var_export($newConfig, true);
    $export = preg_replace(['/array \(/', "/\n\s+\),/"], ['[', "\n    ],"], $export);
    $export = str_replace("),\n  '", "],\n  '", $export);
    $export = str_replace(")\n]", "]\n]", $export);
    if(substr($export, -1) == ")") $export = substr($export, 0, -1) . "]";
    
    $fileContent = "<?php\n// Auto-generated config file\nreturn " . $export . ";\n";
    file_put_contents($configFile, $fileContent);
    $cfg = $newConfig; // update active cfg
    $success = "Configuration updated successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom:0.3rem; }
        .form-control { border-radius: 8px; border: 1.5px solid #e2e8f0; font-size: 0.9rem;}
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        .alert-success { background-color: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 10px; }
    </style>
</head>
<body class="py-5">
<div class="container" style="max-width: 850px;">
    
    <?php if (!isset($_SESSION['admin_auth'])): ?>
    <div class="card p-4 mx-auto mt-5" style="max-width:400px;">
        <div class="text-center mb-4">
            <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                <i class="fas fa-hammer text-white" style="font-size: 1.5rem;"></i>
            </div>
            <h4 class="fw-bold text-dark">Super Admin</h4>
            <p class="text-muted small">Configure application settings</p>
        </div>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <div class="mb-3">
                <input type="password" name="login_password" class="form-control" placeholder="Admin Password" required autofocus>
            </div>
            <button class="btn btn-dark w-100 fw-bold py-2 shadow-sm">Login</button>
        </form>
    </div>
    <?php else: ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0">Configuration Setup</h3>
            <p class="text-muted small mb-0">Manage the global configuration for the Kanban board.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="task.php" class="btn btn-primary btn-sm"><i class="fas fa-layer-group me-1"></i> Go to Board</a>
            <a href="?logout=1" class="btn btn-outline-danger btn-sm"><i class="fas fa-power-off"></i></a>
        </div>
    </div>
    
    <?php if (isset($success)) echo "<div class='alert alert-success fw-bold'><i class='fas fa-check-circle me-2'></i>$success</div>"; ?>
    
    <div class="card p-4">
        <form method="POST">
            <input type="hidden" name="action" value="save_config">
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-shield-halved me-2"></i>Authentication</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">User Password</label>
                    <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($cfg['password']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Super Admin Password</label>
                    <input type="text" name="admin_password" class="form-control" value="<?= htmlspecialchars($admin_password) ?>">
                </div>
            </div>
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-palette me-2"></i>Branding & Features</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($cfg['title'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" class="form-control" value="<?= htmlspecialchars($cfg['subtitle'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Icon (FontAwesome)</label>
                    <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($cfg['icon'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">WhatsApp Number (e.g. 91XXXXXXXXX)</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?= htmlspecialchars($cfg['whatsapp_number'] ?? '') ?>">
                </div>
            </div>
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-server me-2"></i>Server & Storage</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Storage File Path</label>
                    <input type="text" name="storage_file" class="form-control" value="<?= htmlspecialchars($cfg['storage_file'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Session Name</label>
                    <input type="text" name="session_name" class="form-control" value="<?= htmlspecialchars($cfg['session_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Backup Directory Path</label>
                    <input type="text" name="backup_dir" class="form-control" value="<?= htmlspecialchars($cfg['backup_dir'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Backups to Keep</label>
                    <input type="number" name="backup_keep" class="form-control" value="<?= htmlspecialchars($cfg['backup_keep'] ?? '') ?>">
                </div>
            </div>
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-list me-2"></i>Data Lists</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Team Members (Comma separated)</label>
                    <input type="text" name="team_members" class="form-control" value="<?= htmlspecialchars(implode(', ', $cfg['team_members'] ?? [])) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Tags (Comma separated)</label>
                    <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars(implode(', ', $cfg['tags'] ?? [])) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Color Labels (Comma separated hex)</label>
                    <input type="text" name="color_labels" class="form-control" value="<?= htmlspecialchars(implode(', ', $cfg['color_labels'] ?? [])) ?>">
                </div>
            </div>
            
            <h6 class="fw-bold border-bottom pb-2 mb-3 text-primary"><i class="fas fa-fill-drip me-2"></i>Theme & Columns</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Accent Color</label>
                    <input type="color" name="theme_accent" class="form-control form-control-color w-100" value="<?= htmlspecialchars($cfg['theme']['accent'] ?? '#3b82f6') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Header Start Color</label>
                    <input type="color" name="theme_header_from" class="form-control form-control-color w-100" value="<?= htmlspecialchars($cfg['theme']['header_from'] ?? '#0f172a') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Header End Color</label>
                    <input type="color" name="theme_header_to" class="form-control form-control-color w-100" value="<?= htmlspecialchars($cfg['theme']['header_to'] ?? '#1a3558') ?>">
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <?php foreach ($cfg['columns'] as $key => $col): ?>
                <div class="col-md-6">
                    <div class="p-3 border rounded h-100 bg-white shadow-sm">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-secondary mb-0"><?= $key ?></span>
                        </div>
                        <label class="form-label small">Label</label>
                        <input type="text" name="col_<?= $key ?>_label" class="form-control form-control-sm mb-2" value="<?= htmlspecialchars($col['label']) ?>">
                        <label class="form-label small">Color</label>
                        <input type="color" name="col_<?= $key ?>_color" class="form-control form-control-sm form-control-color w-100" value="<?= htmlspecialchars($col['color']) ?>">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="d-flex mt-5 border-top pt-4">
                <button class="btn btn-dark fw-bold px-5 py-2 shadow-sm"><i class="fas fa-save me-2"></i> Save Configuration</button>
            </div>
        </form>
    </div>
    
    <div class="text-center mt-4">
        <small class="text-muted">Changes saved here apply globally across the board.</small>
    </div>
    
    <?php endif; ?>
</div>
</body>
</html>
