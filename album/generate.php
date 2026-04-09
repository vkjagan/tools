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

function generate_grid_pages($output_path, $items, $type, $page_title, $breadcrumbs, $items_per_page = 24) {
    global $ads_config, $generator_version;

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

function encode_path_segments(...$segments) {
    return implode('/', array_map(function ($segment) {
        // Prevent double-encoding if names already contain URL-encoded values (e.g. "%20").
        return rawurlencode(rawurldecode($segment));
    }, $segments));
}

function copy_album_images($source_album_dir, $output_album_dir, $images) {
    $copied_count = 0;

    foreach ($images as $img) {
        $source_file = "$source_album_dir/$img";
        $dest_file = "$output_album_dir/$img";

        if (!file_exists($source_file)) {
            sse_log("WARNING: Missing image file skipped: $source_file");
            continue;
        }

        if (!is_readable($source_file)) {
            sse_log("WARNING: Unreadable image file skipped: $source_file");
            continue;
        }

        // Skip copy when destination is already up-to-date.
        if (file_exists($dest_file) && filesize($source_file) === filesize($dest_file) && filemtime($source_file) <= filemtime($dest_file)) {
            $copied_count++;
            continue;
        }

        if (!@copy($source_file, $dest_file)) {
            sse_log("WARNING: Failed to copy image file: $source_file", 'log');
            continue;
        }

        $copied_count++;
    }

    return $copied_count;
}

function load_ads_config($ads_config_file) {
    $defaults = [
        'enabled' => false,
        'adsense_client' => '',
        'slots' => [
            'grid_top_desktop' => '',
            'grid_top_mobile' => '',
            'grid_bottom_desktop' => '',
            'grid_bottom_mobile' => '',
            'album_top_desktop' => '',
            'album_top_mobile' => '',
            'album_mid_desktop' => '',
            'album_mid_mobile' => '',
            'album_footer_desktop' => '',
            'album_footer_mobile' => '',
        ],
    ];

    if (!file_exists($ads_config_file)) {
        return $defaults;
    }

    $loaded = include $ads_config_file;
    if (!is_array($loaded)) {
        return $defaults;
    }

    return array_replace_recursive($defaults, $loaded);
}

function render_ad_slot($slot_id, $extra_classes = '', $options = []) {
    global $ads_config;

    if (empty($ads_config['enabled']) || empty($ads_config['adsense_client']) || empty($slot_id)) {
        return '';
    }

    $safe_classes = trim('ad-slot ' . $extra_classes);
    $client = htmlspecialchars($ads_config['adsense_client']);
    $slot = htmlspecialchars($slot_id);
    $style = htmlspecialchars($options['style'] ?? 'display:block');
    $format = $options['format'] ?? 'auto';
    $full_width = array_key_exists('full_width_responsive', $options) ? (bool) $options['full_width_responsive'] : true;
    $format_attr = $format !== null ? ' data-ad-format="' . htmlspecialchars($format) . '"' : '';
    $full_width_attr = $full_width ? ' data-full-width-responsive="true"' : '';

    return <<<HTML
<div class="$safe_classes" aria-label="Advertisement">
  <ins class="adsbygoogle"
       style="$style"
       data-ad-client="$client"
       data-ad-slot="$slot"
       $format_attr
       $full_width_attr></ins>
</div>
<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
HTML;
}

function render_named_ad_slot($slot_key, $extra_classes = '') {
    global $ads_config;

    if (empty($ads_config['enabled'])) {
        return "<!-- ad-slot:$slot_key skipped: ads_disabled -->";
    }

    if (empty($ads_config['adsense_client'])) {
        return "<!-- ad-slot:$slot_key skipped: missing_adsense_client -->";
    }

    $slot_config = $ads_config['slots'][$slot_key] ?? '';
    $slot_id = is_array($slot_config) ? ($slot_config['id'] ?? '') : $slot_config;
    if (empty($slot_id)) {
        return "<!-- ad-slot:$slot_key skipped: missing_slot_id -->";
    }

    $slot_options = is_array($slot_config) ? $slot_config : [];
    return render_ad_slot($slot_id, $extra_classes, $slot_options);
}

$source_dir = __DIR__ . '/source';
$output_dir = __DIR__ . '/output';
$generator_version = 'album-generator-v2026.04.09-ads-v3';
$ads_config = load_ads_config(__DIR__ . '/ads.php');

if (empty($ads_config['enabled'])) {
    sse_log("Ads are disabled in album/ads.php (enabled=false). Generated files will not contain ad markup.");
} elseif (empty($ads_config['adsense_client'])) {
    sse_log("Ads enabled but adsense_client is empty in album/ads.php. Generated files will not contain ad markup.");
} else {
    sse_log("Ads are enabled. Rendering configured ad slots into generated files.");
}

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
                $cat_img = encode_path_segments($cat, $alb, $images[0]);
                break;
            }
        }
    }
    $home_items[] = [
        'url' => encode_path_segments($cat) . "/index.html",
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
            $alb_img = encode_path_segments($alb, $images[0]);
        }
        $cat_items[] = [
            'url' => encode_path_segments($alb) . "/index.html",
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
        $album_output_dir = "$output_dir/$cat/$alb";
        $album_source_dir = "$source_dir/$cat/$alb";
        safe_mkdir($album_output_dir);

        // Copy images to output folder so album pages remain fully self-contained.
        $copied_images = copy_album_images($album_source_dir, $album_output_dir, $images);

        $category_name = $cat;
        $album_name = $alb;
        $total_images = count($images);
        sse_log("-> Album '$alb': Prepared $copied_images/$total_images image files in output folder", 'log');

        // Output fallback or pages
        if ($total_images === 0) {
            $current_idx = 0;
            ob_start(); include __DIR__ . '/template.php';
            $html = ob_get_clean();
            file_put_contents("$album_output_dir/page1.html", $html);
            file_put_contents("$album_output_dir/index.html", $html);
            sse_log("-> Album '$alb' (Empty): Generated index.html, page1.html", "log");
        } else {
            $generated_album_files = [];
            for ($i = 0; $i < $total_images; $i++) {
                $current_idx = $i;
                ob_start();
                include __DIR__ . '/template.php';
                $html = ob_get_clean();
                $page_file = "page" . ($i + 1) . ".html";
                file_put_contents("$album_output_dir/$page_file", $html);
                $generated_album_files[] = $page_file;

                // Set index.html as duplication of page1.html
                if ($i === 0) {
                    file_put_contents("$album_output_dir/index.html", $html);
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
