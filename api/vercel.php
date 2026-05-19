<?php

/**
 * Vercel / Lambda bootstrap: only /tmp is writable.
 * Call this before Laravel boots (from api/index.php).
 */
$projectViews = dirname(__DIR__).'/storage/framework/views';
$compiledViews = '/tmp/views';

$tmpDirs = [
    $compiledViews,
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

// Precompiled Blade files from build live under storage/ (read-only on Lambda).
// Copy them into /tmp so Laravel can read and update compiled views at runtime.
if (is_dir($projectViews)) {
    foreach (scandir($projectViews) as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitignore') {
            continue;
        }
        $src = $projectViews.'/'.$file;
        $dest = $compiledViews.'/'.$file;
        if (is_file($src) && (! is_file($dest) || filesize($src) !== filesize($dest))) {
            @copy($src, $dest);
        }
    }
}

$cacheMap = [
    'packages.php' => getenv('APP_PACKAGES_CACHE') ?: '/tmp/packages.php',
    'services.php' => getenv('APP_SERVICES_CACHE') ?: '/tmp/services.php',
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
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes-v7.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => $compiledViews,
    'LARAVEL_STORAGE_PATH' => '/tmp/storage',
];

foreach ($vercelCacheEnv as $key => $value) {
    if (! getenv($key)) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
