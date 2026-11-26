<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/postgres.php';
try {
    $db = vueports_connect(in_array('--direct', $argv, true));
    $version = $db->query('SHOW server_version')->fetchColumn();
    $stmt = $db->query("SELECT to_regclass('public.vueports_schema_migrations') IS NOT NULL");
    $installed = (bool) $stmt->fetchColumn();
    if ($installed) $installed = (bool) $db->query("SELECT 1 FROM vueports_schema_migrations WHERE version = '001_neon_postgresql'")->fetchColumn();
    echo json_encode(['connected' => true, 'driver' => $db->getAttribute(PDO::ATTR_DRIVER_NAME), 'postgres_version' => $version, 'schema_installed' => $installed], JSON_PRETTY_PRINT) . "\n";
    exit($installed ? 0 : 2);
} catch (Throwable $error) {
    fwrite(STDERR, "Database check failed. Verify DATABASE_URL, TLS settings and the pdo_pgsql extension. No connection credentials were printed.\n");exit(1);
}
