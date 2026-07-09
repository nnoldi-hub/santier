<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Base path when file is served from project /public (local default).
$basePath = dirname(__DIR__);

// Hostico shared-hosting fallback when this file is copied to /public_html.
if (! file_exists($basePath.'/vendor/autoload.php')) {
    $basePath = '/home/rlwrgzez/repositories/modulia-app';
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
