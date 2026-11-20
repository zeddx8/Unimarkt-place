<?php
// ════════════════════════════════════════════════════════════
// UniMarket Ghana — Vercel Serverless PHP Router
// ════════════════════════════════════════════════════════════

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Normalize path
$root = dirname(__DIR__);

// Root index
if ($uri === '/' || $uri === '') {
    require $root . '/index.php';
    exit();
}

$file = $root . $uri;

// 1. Direct PHP file match (e.g. /dashboard.php, /register.php, /cart.php)
if (file_exists($file) && !is_dir($file)) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        require $file;
        exit();
    }
    return false; // Static asset served by Vercel
}

// 2. Directory index match (e.g. /admin/ -> /admin/index.php)
if (is_dir($file) && file_exists($file . '/index.php')) {
    require $file . '/index.php';
    exit();
}

// 3. Extensionless URL match (e.g. /dashboard -> /dashboard.php)
if (file_exists($file . '.php')) {
    require $file . '.php';
    exit();
}

// Fallback to main storefront
require $root . '/index.php';
