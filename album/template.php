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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= $seo_title ?></title>
    <meta name="description" content="<?= $seo_desc ?>">
    <meta property="og:title" content="<?= $seo_title ?>">
    <meta property="og:description" content="<?= $seo_desc ?>">
    <meta property="og:image" content="<?= htmlspecialchars(rawurlencode($current_image)) ?>">
    
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
            display: flex; flex-direction: column; align-items: center;
        }
        
        .photo-section {
            width: 100%; height: 100vh;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            position: relative; scroll-snap-align: start;
            padding: 60px 20px 80px;
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
        
        html { scroll-snap-type: y mandatory; }
    </style>
</head>
<body>

    <div class="header-bar">
        <div class="page-badge" id="counterIndicator"><?= $current_page ?> / <?= $total_images ?></div>
    </div>

    <!-- Pre-load current page target in a JS variable to anchor scroll immediately -->
    <script>const START_PAGE = <?= $current_page ?>;</script>

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
            <?php foreach ($images as $idx => $img_name): 
                $pgNum = $idx + 1;
                $isCurrent = ($pgNum === $current_page);
                $img_path = rawurlencode($img_name);
                $page_url = "page" . $pgNum . ".html";
            ?>
            <section class="photo-section" id="photo-<?= $pgNum ?>" data-page="<?= $pgNum ?>" data-url="<?= $page_url ?>">
                <img src="<?= htmlspecialchars($img_path) ?>" class="photo-img" loading="<?= $isCurrent ? 'eager' : 'lazy' ?>" alt="Photo <?= $pgNum ?>">
                <div class="photo-info">
                    <?= htmlspecialchars($img_name) ?>
                </div>
            </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="nav-bar">
        <a id="btnPrev" class="nav-btn"><i class="bi bi-arrow-left-circle-fill"></i>Prev</a>
        <a href="../index.html" class="nav-btn"><i class="bi bi-grid-fill"></i>Albums</a>
        <a id="btnNext" class="nav-btn"><i class="bi bi-arrow-right-circle-fill"></i>Next</a>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const sections = document.querySelectorAll('.photo-section');
        const counter = document.getElementById('counterIndicator');
        const total = <?= $total_images ?>;
        const btnPrev = document.getElementById('btnPrev');
        const btnNext = document.getElementById('btnNext');
        
        let isProgrammaticScroll = true;

        // Auto-scroll to current page on load
        const targetSection = document.getElementById('photo-' + START_PAGE);
        if (targetSection) {
            targetSection.scrollIntoView({ behavior: 'auto' });
            setTimeout(() => isProgrammaticScroll = false, 500); 
        } else {
            isProgrammaticScroll = false;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const pageNum = parseInt(entry.target.getAttribute('data-page'));
                    const pageUrl = entry.target.getAttribute('data-url');
                    
                    // Update URL without reloading if user is scrolling
                    if (!isProgrammaticScroll) {
                        window.history.replaceState(null, '', pageUrl);
                        // Update title dynamically could be implemented here
                    }
                    
                    counter.innerText = pageNum + ' / ' + total;
                }
            });
        }, { threshold: 0.6 });

        sections.forEach(sec => observer.observe(sec));

        // Keyboard & Button Navigation
        const moveTo = (offset) => {
            const currentObj = Array.from(sections).find(s => {
                const rect = s.getBoundingClientRect();
                return rect.top >= -50 && rect.top < window.innerHeight / 2;
            });
            if (!currentObj) return;
            
            const currIdx = parseInt(currentObj.getAttribute('data-page'));
            const targetIdx = currIdx + offset;
            const targetEl = document.getElementById('photo-' + targetIdx);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        };

        btnNext.addEventListener('click', () => moveTo(1));
        btnPrev.addEventListener('click', () => moveTo(-1));

        window.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowRight' || e.key === ' ') {
                e.preventDefault(); moveTo(1);
            } else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
                e.preventDefault(); moveTo(-1);
            }
        });
    });
    </script>
</body>
</html>
