<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Large Blade views (e.g. super-admin/templates/show) need a higher PCRE backtrack limit
// so <x-app-layout>…</x-app-layout> can be compiled; default ~1M can truncate huge templates.
ini_set('pcre.backtrack_limit', (string) max(100000000, (int) ini_get('pcre.backtrack_limit')));
ini_set('pcre.recursion_limit', (string) max(100000000, (int) ini_get('pcre.recursion_limit')));

// Form details / VPASS roll-ups decode large JSON; default 128M is often too low on Windows dev servers.
@ini_set('memory_limit', '256M');

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
