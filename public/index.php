<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Fresh clone: create .env from .env.example with its own APP_KEY, in production mode,
// so the /instalar wizard opens without touching the server by hand.
if (! file_exists($env = __DIR__.'/../.env') && is_readable($example = __DIR__.'/../.env.example')) {
    $values = [
        'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_INSTALLED' => 'false',
        'APP_URL' => '', 'LOG_LEVEL' => 'error', 'DB_HOST' => 'localhost', 'DB_DATABASE' => '',
        'DB_USERNAME' => '', 'DB_PASSWORD' => '', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    ];
    $contents = file_get_contents($example);
    foreach ($values as $key => $value) {
        $contents = preg_replace('/^'.$key.'=.*$/m', addcslashes($key.'='.$value, '\$'), $contents);
    }
    @file_put_contents($env, $contents);
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
