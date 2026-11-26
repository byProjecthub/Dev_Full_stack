<?php
declare(strict_types=1);
require_once __DIR__ . '/postgres.php';
try {
    $pdo = vueports_connect();
} catch (Throwable $error) {
    // Do not expose a database URL, host, password or driver error in an HTTP response.
    error_log('Vueports database initialization failed (' . get_class($error) . ').');
    if (PHP_SAPI === 'cli') throw $error;
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit('The service is temporarily unavailable. Please try again shortly.');
}
