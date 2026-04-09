<?php
// standalone-generator/template_grid.php
// Variables injected:
// $type - 'home', 'category'
// $page_title
// $breadcrumbs - array of ['text' => ..., 'url' => ...]
// $items - array of ['url' => ..., 'img' => ..., 'title' => ..., 'subtitle' => ...]
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body, html { 
            background-color: #121212; 
            color: #f8f9fa; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .header-bar {
            background: rgba(18,18,18,0.95); backdrop-filter: blur(10px);
            border-bottom: 1px solid #333; position: sticky; top: 0; z-index: 1000;
            padding: 15px 0;
        }
        .breadcrumb { margin-bottom: 0; font-size: 0.9rem; }
        .breadcrumb-item a { color: #0d6efd; text-decoration: none; }
        .breadcrumb-item a:hover { color: #0a58ca; }
        .breadcrumb-item.active { color: #ccc; }
        .breadcrumb-item+.breadcrumb-item::before { color: #666; }
        
        .card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            height: 100%;
        }
        a.text-decoration-none:hover .card {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: #444;
        }
        
        .card-img-top-wrapper {
            height: 220px; overflow: hidden; background: #222;
            display: flex; align-items: center; justify-content: center;
        }
        .card-img-top-wrapper img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .card-img-top-wrapper i { font-size: 4rem; color: #444; }
    </style>
</head>
<body>

    <div class="header-bar">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php if (!empty($breadcrumbs)): ?>
                        <?php foreach($breadcrumbs as $bc): ?>
                            <?php if (!empty($bc['url'])): ?>
                                <li class="breadcrumb-item"><a href="<?= $bc['url'] ?>"><?= htmlspecialchars($bc['text']) ?></a></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($bc['text']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page">Home</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container py-5">
        <h2 class="mb-4 fw-bold"><?= htmlspecialchars($page_title) ?></h2>
        
        <?php if (empty($page_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-folder2-open text-secondary mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">Nothing to show here.</h4>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($page_items as $item): ?>
                    <div class="col">
                        <a href="<?= $item['url'] ?>" class="text-decoration-none">
                            <div class="card">
                                <div class="card-img-top-wrapper">
                                    <?php if (!empty($item['img'])): ?>
                                        <img src="<?= htmlspecialchars($item['img']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="bi bi-image"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title text-light mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                                    <?php if (!empty($item['subtitle'])): ?>
                                        <p class="card-text text-muted small"><i class="bi bi-collection-fill me-1"></i><?= htmlspecialchars($item['subtitle']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($total_pages) && $total_pages > 1): ?>
                <nav class="mt-5" aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php
                        // Prev button
                        $prev_url = ($current_page > 2) ? "index-" . ($current_page - 1) . ".html" : ($current_page == 2 ? "index.html" : "#");
                        $next_url = ($current_page < $total_pages) ? "index-" . ($current_page + 1) . ".html" : "#";
                        ?>
                        <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link text-bg-dark border-secondary" href="<?= $prev_url ?>">Previous</a>
                        </li>
                        
                        <?php
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link text-bg-dark border-secondary" href="index.html">1</a></li>';
                            if ($start_page > 2) echo '<li class="page-item disabled"><a class="page-link text-bg-dark border-secondary" href="#">...</a></li>';
                        }
                        
                        for ($p = $start_page; $p <= $end_page; $p++) {
                            $p_url = ($p === 1) ? "index.html" : "index-$p.html";
                            $active = ($p === $current_page) ? 'active bg-primary border-primary' : 'text-bg-dark border-secondary';
                            echo "<li class=\"page-item\"><a class=\"page-link $active\" href=\"$p_url\">$p</a></li>";
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<li class="page-item disabled"><a class="page-link text-bg-dark border-secondary" href="#">...</a></li>';
                            $last_url = "index-$total_pages.html";
                            echo "<li class=\"page-item\"><a class=\"page-link text-bg-dark border-secondary\" href=\"$last_url\">$total_pages</a></li>";
                        }
                        ?>
                        
                        <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link text-bg-dark border-secondary" href="<?= $next_url ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

</body>
</html>
