<?php

declare(strict_types=1);

$appConfig = require dirname(__DIR__) . '/config/app.php';
$dbConfig = require dirname(__DIR__) . '/config/database.php';

date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Services/Schema.php';
require_once __DIR__ . '/Services/AuthorService.php';
require_once __DIR__ . '/Services/BookService.php';
require_once __DIR__ . '/Services/CollectionService.php';
require_once __DIR__ . '/Services/StatsService.php';
require_once __DIR__ . '/Services/WishlistService.php';
require_once __DIR__ . '/Services/ImportExportService.php';
require_once __DIR__ . '/Services/GoodreadsService.php';

foreach ([
    'DashboardController',
    'LibraryController',
    'AuthorController',
    'CollectionController',
    'WishlistController',
    'StatisticsController',
    'ImportExportController',
    'SettingsController',
    'SearchController',
    'GoodreadsController',
] as $controller) {
    require_once __DIR__ . '/Controllers/' . $controller . '.php';
}

if (PHP_SAPI !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($appConfig['session_name']);
        session_start();
    }
    if (!headers_sent()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}

try {
    Database::connect($dbConfig);
    if (PHP_SAPI !== 'cli') {
        Schema::ensure();
    }
} catch (Throwable $e) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, 'DB error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
    http_response_code(503);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>BookNest</title></head><body style="font-family:monospace;background:#F4EDDF;color:#403845;padding:2rem">';
    echo '<h1>BOOKNEST</h1><p>The archive could not be opened.</p>';
    if ($appConfig['debug']) {
        echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        echo '<p>Abrí <code>install.php</code> o configurá <code>.env</code>.</p>';
    }
    echo '</body></html>';
    exit;
}

return [
    'app' => $appConfig,
    'db' => $dbConfig,
];
