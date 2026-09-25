<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// cPanel serves this app from public_html/workout, while this file lives in public/.
// Tell Laravel the site root is /workout so routes and asset URLs stay on that path.
$subdirectory = '/workout';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($requestPath === $subdirectory || str_starts_with($requestPath, $subdirectory.'/')) {
    $_SERVER['SCRIPT_NAME'] = $subdirectory.'/index.php';
    $_SERVER['PHP_SELF'] = $subdirectory.'/index.php';

    if ($requestPath === $subdirectory) {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $_SERVER['REQUEST_URI'] = $subdirectory.'/'.($query !== '' ? '?'.$query : '');
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
