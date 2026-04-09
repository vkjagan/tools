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
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <!-- generated-by: <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= $seo_title ?></title>
    <meta name="description" content="<?= $seo_desc ?>">
    <meta property="og:title" content="<?= $seo_title ?>">
    <meta property="og:description" content="<?= $seo_desc ?>">
    <meta property="og:image" content="<?= htmlspecialchars(rawurlencode(rawurldecode($current_image))) ?>">
    <?php if (!empty($ads_config['enabled']) && !empty($ads_config['adsense_client'])): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($ads_config['adsense_client']) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body, html { 
            background-color: #000; 
            color: #fff; 
            margin: 0; padding: 0; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            scroll-behavior: smooth;
        }
        
        .header-bar {
            position: fixed; top: 0; left: 0; right: 0; height: 60px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent);
            z-index: 1000; display: flex; align-items: center; justify-content: center;
        }
        .page-badge {
            background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
            padding: 6px 16px; border-radius: 20px; font-weight: bold; font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .album-container {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 100vh;
        }
        
        .photo-section {
            width: 100%; height: 100vh;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            position: relative; scroll-snap-align: start;
            padding: 150px 20px 190px;
        }
        
        .photo-img {
            max-width: 100%; max-height: 100%; object-fit: contain;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5); border-radius: 8px;
        }
        
        .photo-info {
            position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%);
            background: rgba(0,0,0,0.6); backdrop-filter: blur(12px);
            padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; color: #ccc;
            border: 1px solid rgba(255,255,255,0.1); white-space: nowrap;
        }
        
        .nav-bar {
            position: fixed; bottom: 0; left: 0; right: 0; height: 60px;
            background: rgba(10,10,10,0.95); border-top: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center; gap: 40px; z-index: 1000;
        }
        .nav-btn {
            color: #fff; text-decoration: none; display: flex; flex-direction: column; 
            align-items: center; font-size: 0.75rem; color: #aaa; transition: color 0.2s;
            cursor: pointer;
        }
        .nav-btn i { font-size: 1.4rem; color: #fff; }
        .nav-btn:hover { color: #fff; }
        
        /* Fallback styling for no image */
        .fallback-box {
            width: 80%; max-width: 500px; height: 60vh; border: 2px dashed #444; border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center; color: #666;
        }
        
        html { scroll-snap-type: none; }
        .ad-wrap { width: min(100%, 980px); margin: 0 auto; padding: 6px 20px; }
        .ad-slot { min-height: 50px; margin: 10px 0; text-align: center; overflow: hidden; }
        .ad-top-fixed {
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            z-index: 1001;
            background: linear-gradient(to bottom, rgba(0,0,0,0.75), rgba(0,0,0,0.25));
        }
        .ad-bottom-fixed {
            position: fixed;
            bottom: 60px;
            left: 0;
            right: 0;
            z-index: 1001;
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.25));
        }
        .desktop-only { display: none; }
        .mobile-only { display: block; }
        @media (min-width: 992px) {
            .desktop-only { display: block; }
            .mobile-only { display: none; }
        }
        .generator-version {
            position: fixed;
            right: 10px;
            bottom: 124px;
            z-index: 1100;
            font-size: 11px;
            color: #bbb;
            background: rgba(0, 0, 0, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            padding: 4px 10px;
            pointer-events: none;
        }
        .nav-btn.disabled {
            opacity: 0.4;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="header-bar">
        <div class="page-badge" id="counterIndicator"><?= $current_page ?> / <?= $total_images ?></div>
    </div>

    <div class="ad-wrap ad-top-fixed">
        <div class="desktop-only"><?= render_named_ad_slot('album_top_desktop') ?></div>
        <div class="mobile-only"><?= render_named_ad_slot('album_top_mobile') ?></div>
    </div>

    <div class="ad-wrap ad-top-fixed">
        <div class="desktop-only"><?= render_named_ad_slot('album_top_desktop') ?></div>
        <div class="mobile-only"><?= render_named_ad_slot('album_top_mobile') ?></div>
    </div>

    <div class="album-container" id="albumContainer">
        <?php if ($total_images === 0): ?>
            <section class="photo-section">
                <div class="fallback-box">
                    <i class="bi bi-image text-secondary mb-3" style="font-size: 4rem;"></i>
                    <h4>No Images Available</h4>
                    <p>This album is currently empty.</p>
                </div>
            </section>
        <?php else: ?>
            <?php
                // Render only the current page image to keep ad flow stable and page-specific.
                $img_path = rawurlencode(rawurldecode($current_image));
            ?>
            <section class="photo-section" id="photo-<?= $current_page ?>" data-page="<?= $current_page ?>" data-url="page<?= $current_page ?>.html">
                <img
                    src="<?= htmlspecialchars($img_path) ?>"
                    class="photo-img"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high"
                    alt="<?= htmlspecialchars($album_name) ?> - Photo <?= $current_page ?>">
                <div class="photo-info">
                    <?= htmlspecialchars($current_image) ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="nav-bar">
        <?php if (!empty($prev_page)): ?>
            <a id="btnPrev" class="nav-btn" href="<?= htmlspecialchars($prev_page) ?>"><i class="bi bi-arrow-left-circle-fill"></i>Prev</a>
        <?php else: ?>
            <span id="btnPrev" class="nav-btn disabled"><i class="bi bi-arrow-left-circle-fill"></i>Prev</span>
        <?php endif; ?>
        <a href="../index.html" class="nav-btn"><i class="bi bi-grid-fill"></i>Albums</a>
        <?php if (!empty($next_page)): ?>
            <a id="btnNext" class="nav-btn" href="<?= htmlspecialchars($next_page) ?>"><i class="bi bi-arrow-right-circle-fill"></i>Next</a>
        <?php else: ?>
            <span id="btnNext" class="nav-btn disabled"><i class="bi bi-arrow-right-circle-fill"></i>Next</span>
        <?php endif; ?>
    </div>

    <div class="ad-wrap ad-bottom-fixed">
        <div class="desktop-only"><?= render_named_ad_slot('album_footer_desktop') ?></div>
        <div class="mobile-only"><?= render_named_ad_slot('album_footer_mobile') ?></div>
    </div>

    <div class="generator-version" title="Generator Build Version">
        <?= htmlspecialchars($generator_version ?? 'unknown-generator-version') ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const prevUrl = <?= json_encode($prev_page) ?>;
        const nextUrl = <?= json_encode($next_page) ?>;

        window.addEventListener('keydown', (e) => {
            if ((e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === ' ') && nextUrl) {
                e.preventDefault();
                window.location.href = nextUrl;
            } else if ((e.key === 'ArrowLeft' || e.key === 'ArrowUp') && prevUrl) {
                e.preventDefault();
                window.location.href = prevUrl;
            }
        });
    });
    </script>
</body>
</html>
