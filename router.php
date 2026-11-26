<?php
declare(strict_types=1);
// Local development router with the same private-file boundaries as Apache.
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~(?:^|/)(?:config|includes|vendor|database|scripts|cron|conf\.d|sessions|logs|cache|backups|websocket|deploy|tests|docs)(?:/|$)|(?:^|/)\.|\.(?:sql|env|ini|log|dump|bak|zip|md|ya?ml)$|/(?:import-db\.php|test-email\.php|process-email-queue\.php|vercel\.json|composer\.(?:json|lock)|Dockerfile[^/]*)$~i', $uri)) {
    http_response_code(403);exit('Forbidden');
}
if (preg_match('~\.txt$~i', $uri) && strcasecmp($uri, '/robots.txt') !== 0) {http_response_code(403);exit('Forbidden');}
$filename = realpath(__DIR__ . $uri);
if ($filename && str_starts_with($filename, __DIR__ . DIRECTORY_SEPARATOR) && is_file($filename)) return false;
require __DIR__ . '/index.php';
