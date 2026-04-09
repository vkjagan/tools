<?php
// standalone-generator/template.php
// Variables injected:
// $base_url       - e.g. https://dppic.com/
// $category_name  - "Category1"
// $album_name     - "Album1"
// $images         - array of image filenames
// $current_idx    - 0-based index of current image
// $total_images   - total count

$current_page = $current_idx + 1;
$current_image = !empty($images) ? $images[$current_idx] : '';

// Prev / Next Page URLs
$prev_page = ($current_page > 1) ? "page" . ($current_page - 1) . ".html" : null;
$next_page = ($current_page < $total_images) ? "page" . ($current_page + 1) . ".html" : null;

// SEO Titles
$seo_title = htmlspecialchars("{$album_name} - Photo {$current_page} of {$total_images} | {$category_name}");
$seo_desc = htmlspecialchars("View high quality photo {$current_page} from {$album_name} in {$category_name}.");

// Normalize image path
$img_path = !empty($current_image) ? rawurlencode(rawurldecode($current_image)) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- generated-by: <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $seo_title ?></title>
    <meta name="description" content="<?= $seo_desc ?>">
    <meta property="og:title" content="<?= $seo_title ?>">
    <meta property="og:description" content="<?= $seo_desc ?>">
    <?php if (!empty($img_path)): ?>
        <meta property="og:image" content="<?= htmlspecialchars($img_path) ?>">
    <?php endif; ?>

    <?php if (!empty($ads_config['enabled']) && !empty($ads_config['adsense_client'])): ?>
        <script async
            src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($ads_config['adsense_client']) ?>"
            crossorigin="anonymous"></script>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --border: rgba(255, 255, 255, 0.08);
            --text: #f0f0f0;
            --text-muted: #888;
            --accent: #6c63ff;
            --accent2: #a78bfa;
            --nav-h: 60px;
            --header-h: 56px;
        }

        html,
        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            height: 100%;
            overflow: hidden;
        }

        /* ── TOP HEADER ── */
        .header-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-h);
            background: rgba(10, 10, 10, 0.92);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: color 0.2s;
            max-width: 180px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .header-brand:hover {
            color: var(--text);
        }

        .header-brand i {
            font-size: 1rem;
            color: var(--accent2);
            flex-shrink: 0;
        }

        .page-badge {
            background: rgba(108, 99, 255, 0.2);
            border: 1px solid rgba(108, 99, 255, 0.35);
            color: var(--accent2);
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* ── MAIN VIEWPORT ── */
        .page-wrap {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            padding-top: var(--header-h);
            padding-bottom: var(--nav-h);
        }

        /* ── TOP AD STRIP ── */
        .ad-strip-top {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            padding: 6px 12px;
            flex-shrink: 0;
        }

        /* ── IMAGE AREA ── */
        .image-stage {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            padding: 12px 12px;
        }

        .photo-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 8px 48px rgba(0, 0, 0, 0.7);
            display: block;
        }

        .photo-caption {
            position: absolute;
            bottom: 14px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(8px);
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.72rem;
            color: var(--text-muted);
            border: 1px solid var(--border);
            white-space: nowrap;
            max-width: 90%;
            overflow: hidden;
            text-overflow: ellipsis;
            pointer-events: none;
        }

        /* ── BELOW IMAGE AD ── */
        .ad-strip-mid {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60px;
            padding: 6px 12px;
            flex-shrink: 0;
        }

        /* ── BOTTOM NAV BAR ── */
        .nav-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: var(--nav-h);
            background: rgba(10, 10, 10, 0.96);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            z-index: 1000;
        }

        .nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            padding: 0 28px;
            height: 100%;
            text-decoration: none;
            color: var(--text-muted);
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            transition: color 0.18s, background 0.18s;
            border: none;
            background: none;
            cursor: pointer;
            position: relative;
        }

        .nav-btn i {
            font-size: 1.3rem;
            color: var(--text);
            transition: color 0.18s;
        }

        .nav-btn:hover {
            color: var(--accent2);
        }

        .nav-btn:hover i {
            color: var(--accent2);
        }

        .nav-btn.active-pg {
            color: var(--accent2);
        }

        .nav-btn.active-pg i {
            color: var(--accent2);
        }

        .nav-btn.active-pg::after {
            content: '';
            position: absolute;
            top: 0;
            left: 12px;
            right: 12px;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            border-radius: 0 0 3px 3px;
        }

        .nav-btn.disabled {
            opacity: 0.3;
            pointer-events: none;
            cursor: default;
        }

        .nav-divider {
            width: 1px;
            height: 30px;
            background: var(--border);
            flex-shrink: 0;
        }

        /* ── SIDEBAR AD (Desktop only) ── */
        .sidebar-ad {
            display: none;
        }

        @media (min-width: 1200px) {
            .sidebar-ad {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-start;
                position: fixed;
                right: 12px;
                top: calc(var(--header-h) + 80px);
                width: 160px;
                gap: 12px;
                z-index: 900;
            }
        }

        /* ── FALLBACK ── */
        .fallback-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            color: #555;
            padding: 40px;
            border: 2px dashed #333;
            border-radius: 16px;
            text-align: center;
        }

        .fallback-box i {
            font-size: 3rem;
        }

        /* ── AD SLOTS ── */
        .ad-slot {
            text-align: center;
            overflow: hidden;
        }

        .desktop-only {
            display: none;
        }

        .mobile-only {
            display: block;
        }

        @media (min-width: 992px) {
            .desktop-only {
                display: block;
            }

            .mobile-only {
                display: none;
            }
        }

        /* ── Generator Version badge ── */
        .generator-version {
            position: fixed;
            right: 10px;
            bottom: 68px;
            z-index: 1100;
            font-size: 10px;
            color: #555;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 999px;
            padding: 3px 9px;
            pointer-events: none;
        }

        /* ── Swipe touch hint ── */
        @media (hover: none) and (pointer: coarse) {
            .photo-img {
                touch-action: pan-y;
            }
        }

        .nav-btn.disabled {
            opacity: 0.4;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <!-- ══ HEADER ══════════════════════════════════════════════════ -->
    <header class="header-bar">
        <a class="header-brand" href="../index.html" title="Back to Album">
            <i class="bi bi-grid-3x3-gap-fill"></i><?= htmlspecialchars($album_name) ?>
        </a>
        <div class="page-badge" id="counterIndicator"><?= $current_page ?> / <?= $total_images ?></div>
        <a class="header-brand" href="../../index.html" title="All Categories" style="justify-content: flex-end;">
            <i class="bi bi-house-door-fill"></i>
        </a>
    </header>

    <!-- ══ PAGE LAYOUT ════════════════════════════════════════════ -->
    <div class="page-wrap">

        <!-- TOP AD -->
        <div class="ad-strip-top">
            <div class="desktop-only"><?= render_named_ad_slot('album_top_desktop') ?></div>
            <div class="mobile-only"><?= render_named_ad_slot('album_top_mobile') ?></div>
        </div>

        <!-- IMAGE STAGE -->
        <div class="image-stage" id="imageStage">
            <?php if ($total_images === 0): ?>
                <div class="fallback-box">
                    <i class="bi bi-image"></i>
                    <h4>No Images Available</h4>
                    <p style="color:#666;font-size:0.9rem;">This album is currently empty.</p>
                </div>
            <?php else: ?>
                <img src="<?= htmlspecialchars($img_path) ?>" class="photo-img" id="mainPhoto" loading="eager"
                    decoding="async" fetchpriority="high"
                    alt="<?= htmlspecialchars($album_name) ?> - Photo <?= $current_page ?>">
                <div class="photo-caption"><?= htmlspecialchars($current_image) ?></div>
            <?php endif; ?>
        </div>

        <!-- BELOW IMAGE AD (High-CTR zone) -->
        <?php if (!empty($ads_config['enabled'])): ?>
            <div class="ad-strip-mid">
                <div class="desktop-only"><?= render_named_ad_slot('album_mid_desktop') ?></div>
                <div class="mobile-only"><?= render_named_ad_slot('album_mid_mobile') ?></div>
            </div>
        <?php endif; ?>

    </div>

    <!-- ══ SIDEBAR AD (Desktop sticky) ═══════════════════════════ -->
    <aside class="sidebar-ad">
        <?= render_named_ad_slot('album_footer_desktop') ?>
    </aside>

    <!-- ══ BOTTOM NAV BAR ════════════════════════════════════════ -->
    <nav class="nav-bar" aria-label="Photo navigation">
        <?php if (!empty($prev_page)): ?>
            <a id="btnPrev" class="nav-btn" href="<?= htmlspecialchars($prev_page) ?>" aria-label="Previous photo">
                <i class="bi bi-arrow-left-circle-fill"></i>Prev
            </a>
        <?php else: ?>
            <span id="btnPrev" class="nav-btn disabled" aria-disabled="true">
                <i class="bi bi-arrow-left-circle-fill"></i>Prev
            </span>
        <?php endif; ?>

        <div class="nav-divider"></div>

        <a class="nav-btn" href="../index.html" aria-label="Back to album grid">
            <i class="bi bi-grid-fill"></i>Albums
        </a>

        <div class="nav-divider"></div>

        <?php if (!empty($next_page)): ?>
            <a id="btnNext" class="nav-btn active-pg" href="<?= htmlspecialchars($next_page) ?>" aria-label="Next photo">
                <i class="bi bi-arrow-right-circle-fill"></i>Next
            </a>
        <?php else: ?>
            <span id="btnNext" class="nav-btn disabled" aria-disabled="true">
                <i class="bi bi-arrow-right-circle-fill"></i>Next
            </span>
        <?php endif; ?>
    </nav>

    <!-- ══ FOOTER AD (Mobile) ════════════════════════════════════ -->
    <div style="display:none;" id="mobileFooterAdWrap">
        <div class="mobile-only"><?= render_named_ad_slot('album_footer_mobile') ?></div>
    </div>

    <!-- Generator badge -->
    <div class="generator-version" title="Generator Build Version">
        <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?>
    </div>

    <script>
        (function () {
            'use strict';

            var prevUrl = <?= json_encode($prev_page) ?>;
            var nextUrl = <?= json_encode($next_page) ?>;

            /* ── Keyboard navigation ── */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') {
                    if (nextUrl) { e.preventDefault(); location.href = nextUrl; }
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    if (prevUrl) { e.preventDefault(); location.href = prevUrl; }
                }
            });

            /* ── Touch / swipe navigation ── */
            var touchStartX = 0, touchStartY = 0;
            var stage = document.getElementById('imageStage');
            if (stage) {
                stage.addEventListener('touchstart', function (e) {
                    touchStartX = e.changedTouches[0].screenX;
                    touchStartY = e.changedTouches[0].screenY;
                }, { passive: true });
                stage.addEventListener('touchend', function (e) {
                    var dx = e.changedTouches[0].screenX - touchStartX;
                    var dy = e.changedTouches[0].screenY - touchStartY;
                    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
                        if (dx < 0 && nextUrl) location.href = nextUrl;
                        else if (dx > 0 && prevUrl) location.href = prevUrl;
                    }
                }, { passive: true });
            }

            /* ── Image error fallback ── */
            var img = document.getElementById('mainPhoto');
            if (img) {
                img.addEventListener('error', function () {
                    this.style.opacity = '0.2';
                    this.alt = 'Image could not be loaded';
                });
            }
        })();
    </script>
</body>

</html>