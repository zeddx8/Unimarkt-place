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

// 1. Direct file match (PHP scripts or uploaded static assets)
if (file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($ext === 'php') {
        require $file;
        exit();
    }

    $mimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'avif' => 'image/avif',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'ico'  => 'image/x-icon',
    ];

    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit();
    }

    return false; // Other static asset served by host
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
