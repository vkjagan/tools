<?php
// Temporary regeneration trigger - run via CLI on the server
$_SERVER['SCRIPT_NAME'] = '/home/admin/standalone-generator/generate.php';
$_GET['base_url'] = 'https://dppic.com/';

// Capture SSE output
ob_start();
include __DIR__ . '/generate.php';
$output = ob_get_clean();

echo $output;
