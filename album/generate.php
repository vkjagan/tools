<?php
// standalone-generator/generate.php

// Setup for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
@ob_implicit_flush(1);
while (ob_get_level() > 0) ob_end_flush();
@set_time_limit(0);

$base_url = $_GET['base_url'] ?? 'http://localhost/';
if (substr($base_url, -1) !== '/') {
    $base_url .= '/';
}

$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$script_dir = str_replace('\\', '/', $script_dir);
if ($script_dir === '/') $script_dir = '';

$base_output_url = $base_url . 'output/';
$base_source_url = $base_url . 'source/';

function generate_grid_pages($output_path, $items, $type, $page_title, $breadcrumbs, $items_per_page = 24) {
    global $base_output_url, $base_source_url, $base_url;
    
    $total_items = count($items);
    $total_pages = max(1, ceil($total_items / $items_per_page));
    
    $generated_files = [];
    
    for ($page = 1; $page <= $total_pages; $page++) {
        $offset = ($page - 1) * $items_per_page;
        $page_items = array_slice($items, $offset, $items_per_page);
        $current_page = $page;
        
        ob_start(); 
        include __DIR__ . '/template_grid.php';
        $html = ob_get_clean();
        
        $filename = ($page === 1) ? 'index.html' : "index-$page.html";
        file_put_contents("$output_path/$filename", $html);
        $generated_files[] = $filename;
    }
    
    return $generated_files;
}

function sse_log($msg, $type = 'log', $percent = null) {
    $data = ['type' => $type, 'msg' => $msg];
    if ($percent !== null) $data['percent'] = $percent;
    echo "data: " . json_encode($data) . "\n\n";
    @flush();
}

$source_dir = __DIR__ . '/source';
$output_dir = __DIR__ . '/output';

// Safety wrapper for mkdir
function safe_mkdir($path) {
    if (!is_dir($path)) {
        if (!@mkdir($path, 0777, true)) {
            sse_log("ERROR: Permission denied creating directory: $path", 'error');
            return false;
        }
    }
    if (!is_writable($path)) {
        if (!@chmod($path, 0777)) {
            sse_log("ERROR: Directory is not writable and chmod failed: $path", 'error');
            return false;
        }
    }
    return true;
}

if (!safe_mkdir($source_dir) || !safe_mkdir($output_dir)) {
    exit;
}

sse_log("Starting generation process.", "progress", 5);

$allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

// First Pass: Gather all data
sse_log("Scanning folder structure...", "log", 10);
$hierarchy = [];

$categories = array_diff(scandir($source_dir), ['.', '..']);
foreach ($categories as $cat) {
    if (is_dir("$source_dir/$cat")) {
        $hierarchy[$cat] = [];
        $albums = array_diff(scandir("$source_dir/$cat"), ['.', '..']);
        foreach ($albums as $alb) {
            if (is_dir("$source_dir/$cat/$alb")) {
                $images = [];
                $files = array_diff(scandir("$source_dir/$cat/$alb"), ['.', '..']);
                foreach ($files as $f) {
                    if (!is_dir("$source_dir/$cat/$alb/$f")) {
                        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                        if (in_array($ext, $allowed_exts)) {
                            $images[] = $f;
                        }
                    }
                }
                natsort($images);
                $hierarchy[$cat][$alb] = array_values($images);
            }
        }
    }
}

if (empty($hierarchy)) {
    sse_log("No data found in source directory.", 'error');
    exit;
}

// 1. Generate Home Page (index.html)
sse_log("Generating Home Page (index.html)...", "log", 15);
$home_items = [];
foreach ($hierarchy as $cat => $albums) {
    $cat_img = '';
    $album_count = count($albums);
    foreach ($albums as $alb => $images) {
        if (!empty($images)) {
            // Validate image existence
            if (file_exists("$source_dir/$cat/$alb/{$images[0]}")) {
                $cat_img = rawurlencode($cat) . "/" . rawurlencode($alb) . "/" . rawurlencode($images[0]);
                break;
            }
        }
    }
    $home_items[] = [
        'url' => rawurlencode($cat) . "/index.html",
        'img' => $cat_img,
        'title' => $cat,
        'subtitle' => "$album_count Albums"
    ];
}

$type = 'home';
$page_title = 'Digital Photo Portal';
$breadcrumbs = [];
$generated = generate_grid_pages($output_dir, $home_items, $type, $page_title, $breadcrumbs, 24);
sse_log("-> Generated Home Pages: " . implode(", ", $generated), "log");

// 2. Generate Category Pages
$total_cats = count($hierarchy);
$cat_idx = 0;

foreach ($hierarchy as $cat => $albums) {
    $cat_idx++;
    sse_log("Generating Category: $cat ($cat_idx/$total_cats)...", "log");
    safe_mkdir("$output_dir/$cat");
    
    $cat_items = [];
    foreach ($albums as $alb => $images) {
        $img_count = count($images);
        $alb_img = '';
        if ($img_count > 0 && file_exists("$source_dir/$cat/$alb/{$images[0]}")) {
            $alb_img = rawurlencode($alb) . "/" . rawurlencode($images[0]);
        }
        $cat_items[] = [
            'url' => rawurlencode($alb) . "/index.html",
            'img' => $alb_img,
            'title' => $alb,
            'subtitle' => "$img_count Photos"
        ];
    }
    
    $type = 'category';
    $page_title = $cat;
    $breadcrumbs = [
        ['text' => 'Home', 'url' => '../index.html'],
        ['text' => $cat, 'url' => '']
    ];
    $generated = generate_grid_pages("$output_dir/$cat", $cat_items, $type, $page_title, $breadcrumbs, 24);
    sse_log("-> Generated Category Pages: " . implode(", ", $generated), "log");
    
    // 3. Generate Album Pages & Photos
    foreach ($albums as $alb => $images) {
        safe_mkdir("$output_dir/$cat/$alb");

        // Copy images to output folder
        foreach ($images as $img) {
            $source_file = "$source_dir/$cat/$alb/$img";
            $dest_file = "$output_dir/$cat/$alb/$img";
            if (file_exists($source_file)) {
                copy($source_file, $dest_file);
            }
        }

        $category_name = $cat;
        $album_name = $alb;
        $total_images = count($images);

        // Output fallback or pages
        if ($total_images === 0) {
            $current_idx = 0;
            ob_start(); include __DIR__ . '/template.php';
            $html = ob_get_clean();
            file_put_contents("$output_dir/$cat/$alb/page1.html", $html);
            file_put_contents("$output_dir/$cat/$alb/index.html", $html);
            sse_log("-> Album '$alb' (Empty): Generated index.html, page1.html", "log");
        } else {
            $generated_album_files = [];
            for ($i = 0; $i < $total_images; $i++) {
                $current_idx = $i;
                ob_start();
                include __DIR__ . '/template.php';
                $html = ob_get_clean();
                $page_file = "page" . ($i + 1) . ".html";
                file_put_contents("$output_dir/$cat/$alb/$page_file", $html);
                $generated_album_files[] = $page_file;

                // Set index.html as duplication of page1.html
                if ($i === 0) {
                    file_put_contents("$output_dir/$cat/$alb/index.html", $html);
                    $generated_album_files[] = "index.html";
                }
            }
            if (count($generated_album_files) > 10) {
                sse_log("-> Album '$alb': Generated " . count($generated_album_files) . " files (index.html, page1.html to page$total_images.html)", "log");
            } else {
                sse_log("-> Album '$alb': Generated " . implode(", ", $generated_album_files), "log");
            }
        }
    }
    
    $percent = 15 + round(($cat_idx / $total_cats) * 85);
    sse_log("Completed Category $cat_idx/$total_cats", "progress", $percent);
}

sse_log("✨ Setup complete! Generated full site navigation and photos.", "complete", 100);
