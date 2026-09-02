<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => bn_env('APP_NAME', 'BookNest') ?? 'BookNest',
    'env' => bn_env('APP_ENV', 'local') ?? 'local',
    'debug' => filter_var(bn_env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => bn_env('APP_URL', '') ?? '',
    'timezone' => 'America/Argentina/Buenos_Aires',
    'session_name' => 'booknest_session',
    'version' => '0.1.0',
];
