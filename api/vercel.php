<?php

/**
 * Vercel / Lambda bootstrap: only /tmp is writable.
 * Call this before Laravel boots (from api/index.php).
 */
$tmpDirs = [
    '/tmp/views',
    '/tmp/storage',
    '/tmp/storage/framework',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/bootstrap',
    '/tmp/bootstrap/cache',
];

foreach ($tmpDirs as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

$cacheMap = [
    'packages.php' => getenv('APP_PACKAGES_CACHE') ?: '/tmp/packages.php',
    'services.php' => getenv('APP_SERVICES_CACHE') ?: '/tmp/services.php',
    'config.php' => getenv('APP_CONFIG_CACHE') ?: '/tmp/config.php',
    'routes-v7.php' => getenv('APP_ROUTES_CACHE') ?: '/tmp/routes-v7.php',
    'events.php' => getenv('APP_EVENTS_CACHE') ?: '/tmp/events.php',
];

$bootstrapCache = dirname(__DIR__).'/bootstrap/cache';

foreach ($cacheMap as $file => $dest) {
    if (! is_file($dest)) {
        $src = $bootstrapCache.'/'.$file;
        if (is_file($src)) {
            @copy($src, $dest);
        }
    }
}

$vercelCacheEnv = [
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes-v7.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
];

foreach ($vercelCacheEnv as $key => $value) {
    if (! getenv($key)) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
