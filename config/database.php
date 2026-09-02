<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => bn_env('DB_HOST', '127.0.0.1') ?? '127.0.0.1',
    'port' => (int) (bn_env('DB_PORT', '3306') ?? '3306'),
    'name' => bn_env('DB_NAME', 'booknest') ?? 'booknest',
    'user' => bn_env('DB_USER', 'root') ?? 'root',
    'pass' => bn_env('DB_PASS', '') ?? '',
    'charset' => 'utf8mb4',
];
