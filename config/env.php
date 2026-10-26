<?php
// config/env.php

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) && getenv($name) === false) {
            putenv($name . '=' . trim($value));
        }
    }
}

define('BASE_URL', 'http://localhost/Vueports/');
define('SITE_NAME', 'Vueports Solutions');

/**
 * Get environment variable with fallback
 */
function env(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
