<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// When this file lives in public_html/workout/public, keep generated URLs under /workout.
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$scriptFile = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? __FILE__);
$subdirectory = '';

if ($docRoot !== '' && str_starts_with($scriptFile, $docRoot.'/') && str_ends_with($scriptFile, '/public/index.php')) {
    $subdirectory = substr($scriptFile, strlen($docRoot), -strlen('/public/index.php'));
    $subdirectory = rtrim($subdirectory, '/');
}

if ($subdirectory !== '' && $subdirectory !== '/') {
    $_SERVER['SCRIPT_NAME'] = $subdirectory.'/index.php';
    $_SERVER['PHP_SELF'] = $subdirectory.'/index.php';

    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $query = $_SERVER['QUERY_STRING'] ?? '';
    $queryString = $query !== '' ? '?'.$query : '';

    if ($requestPath === '/' || $requestPath === $subdirectory) {
        $_SERVER['REQUEST_URI'] = $subdirectory.'/'.$queryString;
    } elseif (! str_starts_with($requestPath, $subdirectory.'/')) {
        $_SERVER['REQUEST_URI'] = $subdirectory.$requestPath.$queryString;
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
