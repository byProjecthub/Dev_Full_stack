<?php
declare(strict_types=1);

/** Load local settings without overriding hosting-provided environment variables. */
foreach ([dirname(__DIR__) . '/.env', dirname(__DIR__) . '/.env.local'] as $environmentFile) {
    if (!is_readable($environmentFile)) continue;
    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', preg_replace('/^export\s+/', '', $line), 2);
        $key = trim($key); $value = trim($value);
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) continue;
        if (getenv($key) !== false || array_key_exists($key, $_ENV)) continue;
        if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && substr($value, -1) === $value[0]) $value = substr($value, 1, -1);
        putenv($key . '=' . $value); $_ENV[$key] = $value;
    }
}
unset($environmentFile, $line, $key, $value);

function env(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value !== false ? $value : (string) ($_ENV[$key] ?? $default);
}

// Keep legacy helpers compatible with the hosting configuration.
foreach (getenv() ?: [] as $key => $value) if (!array_key_exists($key, $_ENV)) $_ENV[$key] = $value;
if (!defined('BASE_URL')) define('BASE_URL', rtrim(env('APP_URL', env('BASE_URL', 'http://localhost:8080')), '/') . '/');
if (!defined('SITE_NAME')) define('SITE_NAME', env('SITE_NAME', 'Vueports Solutions'));
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Johannesburg'));

/** Writable scratch space for logs/cache; durable uploads need an external store or mounted volume. */
function vueports_runtime_path(string $relative = ''): string {
    $base = rtrim(env('VUEPORTS_RUNTIME_DIR', sys_get_temp_dir() . '/vueports'), '/\\');
    $path = $base . ($relative !== '' ? '/' . ltrim($relative, '/') : '');
    $directory = pathinfo($path, PATHINFO_EXTENSION) === '' ? $path : dirname($path);
    if (!is_dir($directory)) mkdir($directory, 0700, true);
    return $path;
}
