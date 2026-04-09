<?php
// standalone-generator/template_grid.php
// Variables injected:
// $type        - 'home', 'category'
// $page_title
// $breadcrumbs - array of ['text' => ..., 'url' => ...]
// $page_items  - array of ['url' => ..., 'img' => ..., 'title' => ..., 'subtitle' => ...]
// $current_page
// $total_pages

$is_home = ($type === 'home');

// Meta description
$meta_desc = $is_home
    ? htmlspecialchars("Browse all photo categories in {$page_title}. Thousands of high-quality images organized in beautiful galleries.")
    : htmlspecialchars("Browse all albums in the {$page_title} category. High-quality photo galleries with hundreds of images.");

// Page heading
$h1_text = htmlspecialchars($page_title);

// Columns: home = 4-col grid | category = 5–6-col compact
$home_cols    = 'col-6 col-sm-4 col-md-3 col-xl-3';
$cat_cols     = 'col-6 col-sm-4 col-md-3 col-lg-2';
$grid_cols    = $is_home ? $home_cols : $cat_cols;

// Card image height
$img_h = $is_home ? '220px' : '160px';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- generated-by: <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h1_text ?><?= isset($current_page) && $current_page > 1 ? " — Page {$current_page}" : '' ?></title>
    <meta name="description" content="<?= $meta_desc ?>">

    <?php if (!empty($ads_config['enabled']) && !empty($ads_config['adsense_client'])): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($ads_config['adsense_client']) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0d0d0d;
            --surface:   #161616;
            --surface2:  #1c1c1c;
            --border:    rgba(255,255,255,0.07);
            --border2:   rgba(255,255,255,0.12);
            --text:      #f0f0f0;
            --text-muted:#888;
            --accent:    #6c63ff;
            --accent2:   #a78bfa;
            --accent-glow: rgba(108,99,255,0.25);
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* ══ TOP HEADER ═══════════════════════════════════════════ */
        .header-bar {
            position: sticky; top: 0; left: 0; right: 0; z-index: 1000;
            background: rgba(13,13,13,0.92);
            backdrop-filter: blur(18px) saturate(180%);
            -webkit-backdrop-filter: blur(18px) saturate(180%);
            border-bottom: 1px solid var(--border);
        }
        .header-inner {
            max-width: 1440px; margin: 0 auto;
            padding: 14px 24px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .site-logo {
            display: flex; align-items: center; gap: 8px;
            font-size: 1.05rem; font-weight: 700; color: var(--text);
            text-decoration: none; letter-spacing: -0.02em;
            flex-shrink: 0;
        }
        .site-logo i {
            font-size: 1.2rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .breadcrumb-row {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.82rem; color: var(--text-muted);
            flex-wrap: wrap;
        }
        .breadcrumb-row a {
            color: var(--accent2); text-decoration: none; transition: color 0.15s;
        }
        .breadcrumb-row a:hover { color: #fff; }
        .breadcrumb-sep { color: #444; }
        .breadcrumb-current { color: var(--text-muted); }

        /* ══ TOP AD ════════════════════════════════════════════════ */
        .ad-top-wrap {
            width: 100%; background: rgba(0,0,0,0.25);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            min-height: 66px; padding: 8px 16px;
        }

        /* ══ MAIN LAYOUT ═══════════════════════════════════════════ */
        .page-layout {
            max-width: 1440px; margin: 0 auto;
            padding: 32px 20px 60px;
            display: flex; gap: 28px; align-items: flex-start;
        }

        .content-main { flex: 1; min-width: 0; }

        /* ══ PAGE HEADING ══════════════════════════════════════════ */
        .page-heading {
            margin-bottom: 24px;
        }
        .page-heading h1 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700; letter-spacing: -0.02em;
            color: var(--text);
            line-height: 1.25;
        }
        .page-heading .count-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(108,99,255,0.15);
            border: 1px solid rgba(108,99,255,0.25);
            color: var(--accent2); font-size: 0.78rem; font-weight: 600;
            padding: 3px 10px; border-radius: 30px; margin-top: 8px;
        }

        /* ══ CARD GRID ═════════════════════════════════════════════ */
        .card-grid {
            display: grid;
            gap: 16px;
        }
        /* Home: 4-col desktop, 2-col mobile */
        .card-grid.home-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        @media (min-width: 540px) { .card-grid.home-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 900px) { .card-grid.home-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (min-width: 1300px){ .card-grid.home-grid { gap: 20px; } }

        /* Category: 5–6 col desktop, 2-col mobile */
        .card-grid.cat-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        @media (min-width: 480px) { .card-grid.cat-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (min-width: 700px) { .card-grid.cat-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (min-width: 900px) { .card-grid.cat-grid { grid-template-columns: repeat(5, 1fr); } }
        @media (min-width: 1200px){ .card-grid.cat-grid { grid-template-columns: repeat(6, 1fr); } }

        /* ══ CARD ══════════════════════════════════════════════════ */
        .gc { text-decoration: none; display: block; }
        .gc .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1),
                        box-shadow 0.22s ease, border-color 0.22s ease;
            height: 100%;
        }
        .gc:hover .card {
            transform: translateY(-5px);
            box-shadow: 0 12px 36px rgba(0,0,0,0.55), 0 0 0 1px rgba(108,99,255,0.18);
            border-color: var(--border2);
        }

        .card-thumb {
            position: relative; overflow: hidden;
            background: #1a1a1a;
            display: flex; align-items: center; justify-content: center;
        }
        .card-thumb img {
            width: 100%; height: 100%; object-fit: cover;
            display: block;
            transition: transform 0.35s ease;
        }
        .gc:hover .card-thumb img { transform: scale(1.07); }
        .card-thumb-fallback {
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            color: #333;
        }
        .card-thumb-fallback i { font-size: 2.2rem; }

        /* Gradient overlay on image */
        .card-thumb::after {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0) 55%);
            pointer-events: none;
        }

        /* Overlay text on image */
        .card-overlay-title {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 10px 10px 8px;
            z-index: 1;
        }
        .card-overlay-title .ct { /* card title */
            font-size: 0.78rem; font-weight: 600; color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            line-height: 1.3;
        }
        .card-overlay-title .cs { /* card subtitle */
            font-size: 0.68rem; color: rgba(255,255,255,0.6);
            margin-top: 2px;
            display: flex; align-items: center; gap: 4px;
        }
        .card-overlay-title .cs i { font-size: 0.65rem; }

        /* Home cards: taller image, title below */
        .home-card-body {
            padding: 10px 12px 12px;
            border-top: 1px solid var(--border);
        }
        .home-card-body .ht {
            font-size: 0.88rem; font-weight: 600; color: var(--text);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .home-card-body .hs {
            font-size: 0.74rem; color: var(--text-muted);
            margin-top: 3px; display: flex; align-items: center; gap: 5px;
        }
        .home-card-body .hs i { font-size: 0.72rem; color: var(--accent2); }

        /* ══ AD INJECT ROW ═════════════════════════════════════════ */
        .ad-inject {
            grid-column: 1 / -1;
            display: flex; align-items: center; justify-content: center;
            padding: 4px 0; min-height: 60px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,0.2);
        }

        /* ══ SIDEBAR (Desktop only) ════════════════════════════════ */
        .sidebar {
            display: none;
        }
        @media (min-width: 1200px) {
            .sidebar {
                display: flex; flex-direction: column; gap: 16px;
                width: 160px; flex-shrink: 0; padding-top: 4px;
                position: sticky; top: 100px; align-self: flex-start;
            }
        }
        .sidebar-ad-box {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px; overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            min-height: 120px; padding: 8px;
        }

        /* ══ EMPTY STATE ═══════════════════════════════════════════ */
        .empty-state {
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; gap: 16px;
            padding: 80px 20px; color: #555; text-align: center;
        }
        .empty-state i { font-size: 3rem; }
        .empty-state h4 { color: #666; font-size: 1rem; font-weight: 500; }

        /* ══ PAGINATION ════════════════════════════════════════════ */
        .pagination-wrap {
            display: flex; justify-content: center; gap: 6px;
            padding: 32px 0 8px; flex-wrap: wrap;
        }
        .pg-btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 38px; height: 38px; padding: 0 10px;
            background: var(--surface2); border: 1px solid var(--border2);
            border-radius: 8px; color: var(--text-muted);
            font-size: 0.82rem; font-weight: 500;
            text-decoration: none; transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .pg-btn:hover { background: var(--surface); color: var(--text); border-color: #444; }
        .pg-btn.active {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-color: transparent; color: #fff;
        }
        .pg-btn.disabled { opacity: 0.3; pointer-events: none; }
        .pg-ellipsis { display: inline-flex; align-items: center; color: #555; font-size: 0.82rem; padding: 0 4px; }

        /* ══ BOTTOM AD ═════════════════════════════════════════════ */
        .ad-bottom-wrap {
            width: 100%; background: rgba(0,0,0,0.25);
            border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            min-height: 66px; padding: 8px 16px; margin-top: 16px;
        }

        /* ══ FOOTER ════════════════════════════════════════════════ */
        .site-footer {
            text-align: center; padding: 24px 16px;
            color: #444; font-size: 0.75rem; border-top: 1px solid var(--border);
        }

        /* ══ AD SLOTS ══════════════════════════════════════════════ */
        .ad-slot { text-align: center; overflow: hidden; }
        .desktop-only { display: none; }
        .mobile-only  { display: block; }
        @media (min-width: 992px) {
            .desktop-only { display: block; }
            .mobile-only  { display: none; }
        }

        /* ══ Generator badge ═══════════════════════════════════════ */
        .generator-version {
            position: fixed; right: 10px; bottom: 10px;
            z-index: 1100; font-size: 10px; color: #444;
            background: rgba(0,0,0,0.55); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 999px; padding: 3px 9px; pointer-events: none;
        }

        /* ══ Subtle entrance animation ═════════════════════════════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-grid { animation: fadeUp 0.35s ease both; }
    </style>
</head>
<body>

    <!-- ══ HEADER ══════════════════════════════════════════════════ -->
    <header class="header-bar">
        <div class="header-inner">
            <a class="site-logo" href="<?= $is_home ? '#' : '../index.html' ?>">
                <i class="bi bi-images"></i>
                <?= $h1_text ?>
            </a>

            <?php if (!empty($breadcrumbs)): ?>
            <nav class="breadcrumb-row" aria-label="breadcrumb">
                <?php foreach ($breadcrumbs as $i => $bc): ?>
                    <?php if ($i > 0): ?><span class="breadcrumb-sep">›</span><?php endif; ?>
                    <?php if (!empty($bc['url'])): ?>
                        <a href="<?= htmlspecialchars($bc['url']) ?>"><?= htmlspecialchars($bc['text']) ?></a>
                    <?php else: ?>
                        <span class="breadcrumb-current"><?= htmlspecialchars($bc['text']) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    <!-- ══ TOP AD ══════════════════════════════════════════════════ -->
    <div class="ad-top-wrap">
        <div class="desktop-only"><?= render_named_ad_slot('grid_top_desktop') ?></div>
        <div class="mobile-only"><?= render_named_ad_slot('grid_top_mobile') ?></div>
    </div>

    <!-- ══ MAIN LAYOUT ══════════════════════════════════════════════ -->
    <div class="page-layout">

        <main class="content-main">

            <!-- PAGE HEADING -->
            <div class="page-heading">
                <h1><?= $h1_text ?></h1>
                <?php if (!empty($page_items)): ?>
                <span class="count-pill">
                    <i class="bi bi-<?= $is_home ? 'folder2' : 'images' ?>"></i>
                    <?= count($page_items) ?> <?= $is_home ? 'Categories' : 'Albums' ?>
                    <?php if (!empty($total_pages) && $total_pages > 1): ?>
                        &nbsp;&middot;&nbsp;Page <?= $current_page ?> of <?= $total_pages ?>
                    <?php endif; ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($page_items)): ?>
                <div class="empty-state">
                    <i class="bi bi-folder2-open"></i>
                    <h4>Nothing to show here.</h4>
                </div>
            <?php else: ?>

                <?php
                // We inject ads after every Nth card
                $ad_interval = $is_home ? 8 : 12;
                $grid_class  = $is_home ? 'home-grid' : 'cat-grid';
                $thumb_h     = $is_home ? '210px' : '155px';
                ?>

                <div class="card-grid <?= $grid_class ?>">
                    <?php foreach ($page_items as $idx => $item): ?>

                        <?php
                        // Inject ad row after every $ad_interval items (after item index 7, 15, 23…)
                        if ($idx > 0 && $idx % $ad_interval === 0):
                        ?>
                        <div class="ad-inject">
                            <div class="desktop-only"><?= render_named_ad_slot('grid_top_desktop') ?></div>
                            <div class="mobile-only"><?= render_named_ad_slot('grid_top_mobile') ?></div>
                        </div>
                        <?php endif; ?>

                        <a href="<?= htmlspecialchars($item['url']) ?>" class="gc" title="<?= htmlspecialchars($item['title']) ?>">
                            <div class="card">
                                <div class="card-thumb" style="height:<?= $thumb_h ?>;">
                                    <?php if (!empty($item['img'])): ?>
                                        <img
                                            src="<?= htmlspecialchars($item['img']) ?>"
                                            alt="<?= htmlspecialchars($item['title']) ?>"
                                            loading="<?= $idx < 6 ? 'eager' : 'lazy' ?>"
                                            decoding="async"
                                        >
                                    <?php else: ?>
                                        <div class="card-thumb-fallback">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!$is_home): ?>
                                    <!-- Category cards: overlay title on image -->
                                    <div class="card-overlay-title">
                                        <div class="ct"><?= htmlspecialchars($item['title']) ?></div>
                                        <?php if (!empty($item['subtitle'])): ?>
                                        <div class="cs">
                                            <i class="bi bi-images"></i><?= htmlspecialchars($item['subtitle']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($is_home): ?>
                                <!-- Home cards: title below image -->
                                <div class="home-card-body">
                                    <div class="ht"><?= htmlspecialchars($item['title']) ?></div>
                                    <?php if (!empty($item['subtitle'])): ?>
                                    <div class="hs">
                                        <i class="bi bi-collection-fill"></i><?= htmlspecialchars($item['subtitle']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>

                    <?php endforeach; ?>
                </div>

                <!-- ══ PAGINATION ══════════════════════════════════ -->
                <?php if (isset($total_pages) && $total_pages > 1): ?>
                <nav class="pagination-wrap" aria-label="Page navigation">
                    <?php
                    $prev_url = ($current_page > 2) ? "index-" . ($current_page - 1) . ".html"
                              : ($current_page == 2  ? "index.html" : "#");
                    $next_url = ($current_page < $total_pages) ? "index-" . ($current_page + 1) . ".html" : "#";

                    // Prev button
                    if ($current_page <= 1) {
                        echo '<span class="pg-btn disabled"><i class="bi bi-chevron-left"></i></span>';
                    } else {
                        echo '<a class="pg-btn" href="' . $prev_url . '" aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>';
                    }

                    // Page number buttons (window of ±2)
                    $start_p = max(1, $current_page - 2);
                    $end_p   = min($total_pages, $current_page + 2);

                    if ($start_p > 1) {
                        $url1 = "index.html";
                        echo "<a class=\"pg-btn\" href=\"$url1\">1</a>";
                        if ($start_p > 2) echo '<span class="pg-ellipsis">…</span>';
                    }
                    for ($p = $start_p; $p <= $end_p; $p++) {
                        $p_url   = ($p === 1) ? "index.html" : "index-$p.html";
                        $active  = ($p === $current_page) ? ' active' : '';
                        echo "<a class=\"pg-btn{$active}\" href=\"{$p_url}\" aria-label=\"Page {$p}\">{$p}</a>";
                    }
                    if ($end_p < $total_pages) {
                        if ($end_p < $total_pages - 1) echo '<span class="pg-ellipsis">…</span>';
                        $last_url = "index-$total_pages.html";
                        echo "<a class=\"pg-btn\" href=\"$last_url\">$total_pages</a>";
                    }

                    // Next button
                    if ($current_page >= $total_pages) {
                        echo '<span class="pg-btn disabled"><i class="bi bi-chevron-right"></i></span>';
                    } else {
                        echo '<a class="pg-btn" href="' . $next_url . '" aria-label="Next page"><i class="bi bi-chevron-right"></i></a>';
                    }
                    ?>
                </nav>
                <?php endif; ?>

            <?php endif; ?>

            <!-- ══ BOTTOM AD ══════════════════════════════════════ -->
            <div class="ad-bottom-wrap">
                <div class="desktop-only"><?= render_named_ad_slot('grid_bottom_desktop') ?></div>
                <div class="mobile-only"><?= render_named_ad_slot('grid_bottom_mobile') ?></div>
            </div>

        </main>

        <!-- ══ SIDEBAR ════════════════════════════════════════════ -->
        <aside class="sidebar" aria-label="Advertisements">
            <div class="sidebar-ad-box">
                <?= render_named_ad_slot('grid_top_desktop') ?>
            </div>
            <div class="sidebar-ad-box">
                <?= render_named_ad_slot('grid_bottom_desktop') ?>
            </div>
        </aside>

    </div><!-- /page-layout -->

    <!-- ══ FOOTER ═════════════════════════════════════════════════ -->
    <footer class="site-footer">
        &copy; <?= date('Y') ?> <?= $h1_text ?> &mdash; All rights reserved.
    </footer>

    <!-- Generator badge -->
    <div class="generator-version" title="Generator Build Version">
        <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?>
    </div>

</body>
</html>
