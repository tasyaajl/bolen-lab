<?php

declare(strict_types=1);

/**
 * Router untuk PHP built-in server:
 * php -S localhost:8000 -t public public/router.php
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Jangan pernah mengeksekusi file PHP dari folder upload.
if (str_starts_with($path, '/uploads/') && preg_match('/\.(php|phtml|phar)$/i', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
