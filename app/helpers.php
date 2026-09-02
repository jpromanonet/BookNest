<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $appUrl = (string) app_config('url', '');
    if ($appUrl !== '') {
        $urlPath = parse_url($appUrl, PHP_URL_PATH);
        if (is_string($urlPath) && $urlPath !== '' && $urlPath !== '/') {
            $cached = rtrim($urlPath, '/');
            return $cached;
        }
        $cached = '';
        return $cached;
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '\\' || $script === '.') {
        $cached = '';
        return $cached;
    }
    $cached = rtrim($script, '/');
    return $cached;
}

function url(string $path = '/'): string
{
    $extraQuery = [];
    $hash = '';
    if (str_contains($path, '#')) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (str_contains($path, '?')) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $extraQuery);
    }

    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }

    $base = base_path();

    if (str_starts_with($path, '/assets/') || str_starts_with($path, '/storage/') || preg_match('#^/[^/]+\.php$#', $path) === 1) {
        $suffix = $extraQuery ? ('?' . http_build_query($extraQuery)) : '';
        return $base . $path . $suffix . $hash;
    }

    $script = $base . '/index.php';
    $params = $extraQuery;
    if ($path !== '/') {
        $params = array_merge(['r' => $path], $params);
    }
    $suffix = $params ? ('?' . http_build_query($params)) : '';
    return $script . $suffix . $hash;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    $appName = (string) app_config('name', 'BookNest');
    $appVersion = (string) app_config('version', '0.1.0');
    $templateFile = dirname(__DIR__) . '/app/Views/' . $template . '.php';
    if (!is_file($templateFile)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    require dirname(__DIR__) . '/app/Views/layouts/main.php';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function require_csrf(): void
{
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        echo 'Invalid CSRF token';
        exit;
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function nav_active(string $prefix, bool $exact = false): string
{
    $current = $_GET['r'] ?? '/';
    if ($current === '' || $current === false) {
        $current = '/';
    }
    $current = '/' . trim((string) $current, '/');
    if ($current === '//') {
        $current = '/';
    }
    if ($exact) {
        return $current === $prefix ? 'is-active' : '';
    }
    if ($prefix === '/') {
        return $current === '/' ? 'is-active' : '';
    }
    return str_starts_with($current, $prefix) ? 'is-active' : '';
}

function reading_statuses(): array
{
    return [
        'unread' => 'No leído',
        'reading' => 'Leyendo',
        'read' => 'Leído',
        'abandoned' => 'Abandonado',
        'reread' => 'Relectura',
    ];
}

function physical_conditions(): array
{
    return [
        'new' => 'Nuevo',
        'excellent' => 'Excelente',
        'good' => 'Bueno',
        'fair' => 'Regular',
        'damaged' => 'Dañado',
        'restore' => 'Restaurar',
    ];
}

function status_label(string $status): string
{
    return reading_statuses()[$status] ?? $status;
}

function condition_label(string $condition): string
{
    return physical_conditions()[$condition] ?? $condition;
}

function status_badge_class(string $status): string
{
    return match ($status) {
        'read' => 'badge-sage',
        'reading' => 'badge-blue',
        'abandoned' => 'badge-rose',
        'reread' => 'badge-lavender',
        default => 'badge-parchment',
    };
}

function cover_url(?string $cover): string
{
    if ($cover === null || $cover === '') {
        return url('/assets/img/cover-placeholder.svg');
    }
    if (str_starts_with($cover, 'http://') || str_starts_with($cover, 'https://')) {
        return $cover;
    }
    return url('/storage/covers/' . ltrim($cover, '/'));
}

function icon(string $name, int $size = 16): string
{
    $src = url('/assets/icons/' . $name . '.svg');
    return '<img class="px-icon" src="' . e($src) . '" width="' . $size . '" height="' . $size . '" alt="" aria-hidden="true">';
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return str_contains($accept, 'application/json')
        || (isset($_GET['format']) && $_GET['format'] === 'json')
        || (isset($_POST['format']) && $_POST['format'] === 'json');
}

function format_number(int|float|null $n): string
{
    if ($n === null) {
        return '—';
    }
    return number_format((float) $n, 0, ',', '.');
}

function format_money(?float $n): string
{
    if ($n === null) {
        return '—';
    }
    return '$' . number_format($n, 2, ',', '.');
}

function null_if_blank(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    return $value === '' ? null : $value;
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    return (int) $value;
}

function float_or_null(mixed $value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    return (float) $value;
}
