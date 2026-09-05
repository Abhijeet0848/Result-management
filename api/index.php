<?php
// Vercel Serverless Entry Point Router for PHP
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/..' . $uri;

if (file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($ext === 'php') {
        require $file;
        exit;
    }
    
    // Serve static assets (CSS, JS, Images, Fonts)
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'json'  => 'application/json',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'pdf'   => 'application/pdf'
    ];
    
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($file);
    exit;
}

// Fallback to home page
require __DIR__ . '/../index.php';
