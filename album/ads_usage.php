<?php
$config = include __DIR__ . 'ads.php';
include __DIR__ . 'ads_renderer.php';
?>

<!-- Desktop Top Ad -->
<?php render_ad($config['ads']['desktop']['top']); ?>

<!-- Mobile Header -->
<?php render_ad($config['ads']['mobile']['header']); ?>

<!-- Sidebar -->
<?php render_ad($config['ads']['desktop']['sidebar']); ?>