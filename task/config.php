<?php
// ═══════════════════════════════════════════════════════════════
//  KANBAN BOARD — CONFIGURATION FILE
//  Edit this file to customise the board for each application.
// ═══════════════════════════════════════════════════════════════

return [

    // ── ACCESS CONTROL ──────────────────────────────────────────
    'password' => 'gaia',                  // Login password

    // ── WHATSAPP ─────────────────────────────────────────────────
    'whatsapp_number' => '9566132900',   // Recipient number with country code, no + or spaces
    // e.g. Malaysia: 601XXXXXXXX  India: 91XXXXXXXXXX
    // Set to '' to hide the WhatsApp button entirely

    // ── TEAM MEMBERS ─────────────────────────────────────────────
    // Names shown in the "Assignee" dropdown. Add/remove as needed.
    'team_members' => ['JK', 'Omar', 'Dato', 'GP'],

    // ── TAGS ─────────────────────────────────────────────────────
    // Labels available in the tag picker. Keep to ~8 for best display.
    'tags' => ['Finance', 'Legal', 'Operations', 'Follow-up', 'Meeting', 'Urgent', 'Pending'],

    // ── COLOR LABELS ─────────────────────────────────────────────
    // Colour swatches users can pin to tasks. Add/remove hex colours as needed.
    'color_labels' => [
        '#ef4444',  // Red
        '#f97316',  // Orange
        '#eab308',  // Yellow
        '#10b981',  // Green
        '#06b6d4',  // Cyan
        '#3b82f6',  // Blue
        '#6366f1',  // Indigo
        '#8b5cf6',  // Purple
        '#ec4899',  // Pink
        '#64748b',  // Slate
    ],

    // ── BRANDING ────────────────────────────────────────────────
    'title' => 'Task Board',    // Main heading shown in header
    'subtitle' => 'Aishwaryam Trust',           // Sub-label shown after ·
    'icon' => 'fa-layer-group',      // FontAwesome icon (header logo)

    // ── STORAGE ─────────────────────────────────────────────────
    'storage_file' => __DIR__ . '/tasks.json',  // Absolute path to JSON data file
    'session_name' => 'kanban_gaiaplas',        // Unique per instance — prevents
    // session collisions across apps

    // ── BACKUPS ─────────────────────────────────────────────────
    // Auto-backup is created on every save, stored in backup_dir.
    'backup_dir'  => __DIR__ . '/backups',   // Folder to store timestamped backups
    'backup_keep' => 20,                     // Max number of backups to retain

    // ── COLUMNS ─────────────────────────────────────────────────
    // Add, remove, or rename columns here.
    // 'key' must be a valid HTML id (no spaces).
    // 'color' is any CSS hex colour.
    'columns' => [
        'todo' => ['label' => 'TO DO', 'icon' => 'fa-circle-dot', 'color' => '#3b82f6'],
        'inprogress' => ['label' => 'IN PROGRESS', 'icon' => 'fa-spinner', 'color' => '#f59e0b'],
        'done' => ['label' => 'DONE', 'icon' => 'fa-circle-check', 'color' => '#10b981'],
        'archive' => ['label' => 'ARCHIVE', 'icon' => 'fa-box-archive', 'color' => '#94a3b8'],
    ],

    // ── THEME ───────────────────────────────────────────────────
    'theme' => [
        'accent' => '#3b82f6',   // Primary button / link colour
        'header_from' => '#0f172a',   // Header gradient start colour
        'header_to' => '#1a3558',   // Header gradient end colour
    ],

];
