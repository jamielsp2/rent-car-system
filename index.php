<?php

/**
 * Front controller for hosts whose document root is the project folder and that ignore
 * .htaccess (some Hostinger plans send every unknown URL to /index.php). Files that exist
 * in public/ (build, media, favicon...) are served from here; everything else is Laravel.
 * When the host's document root is already public/, this file is not used.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$public = realpath(__DIR__.DIRECTORY_SEPARATOR.'public');
$file = realpath($public.$path);

if ($path !== '/' && $file !== false && is_file($file) && str_starts_with($file, $public.DIRECTORY_SEPARATOR)
    && ! str_starts_with(basename($file), '.') && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'php') {
    $types = [
        'css' => 'text/css; charset=utf-8', 'js' => 'text/javascript; charset=utf-8', 'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json', 'map' => 'application/json', 'txt' => 'text/plain; charset=utf-8',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'ico' => 'image/x-icon', 'avif' => 'image/avif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'pdf' => 'application/pdf',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
    header('Content-Length: '.filesize($file));
    header('Cache-Control: public, max-age='.(str_contains($path, '/build/') ? '31536000' : '3600'));
    readfile($file);
    exit;
}

$_SERVER['SCRIPT_FILENAME'] = $public.DIRECTORY_SEPARATOR.'index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $public.DIRECTORY_SEPARATOR.'index.php';
