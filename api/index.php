<?php
// High-Speed Vercel Serverless Router with Gzip & Edge Asset Caching
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    if (!ob_get_level() && !headers_sent()) {
        @ob_start('ob_gzhandler');
    }
    require __DIR__ . '/../index.php';
    exit;
}

$file = __DIR__ . '/..' . $uri;

if (file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($ext === 'php') {
        if (!ob_get_level() && !headers_sent()) {
            @ob_start('ob_gzhandler');
        }
        require $file;
        exit;
    }
    
    // Static Asset Content Types
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
    
    // Aggressive browser & CDN caching for static assets (1 year immutable cache)
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Vary: Accept-Encoding');
    
    readfile($file);
    exit;
}

// Fallback to home page
if (!ob_get_level() && !headers_sent()) {
    @ob_start('ob_gzhandler');
}
require __DIR__ . '/../index.php';
