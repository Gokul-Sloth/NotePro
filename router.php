<?php
// router.php for PHP built-in dev server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('/^\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
    $_GET['note'] = $matches[1];
    require 'index.php';
} elseif ($uri == '/') {
    require 'index.php';
} elseif (file_exists(__DIR__ . $uri)) {
    return false; // serve the requested resource as-is.
} else {
    require 'index.php';
}
