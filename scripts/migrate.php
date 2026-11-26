<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/postgres.php';
try {
    $db = vueports_connect(true);
    $ledger = $db->query("SELECT to_regclass('public.vueports_schema_migrations')")->fetchColumn();
    if ($ledger) {
        $stmt = $db->prepare('SELECT 1 FROM vueports_schema_migrations WHERE version = ?');
        $stmt->execute(['001_neon_postgresql']);
        if ($stmt->fetchColumn()) { echo "Schema is already current (001_neon_postgresql).\n"; exit(0); }
    }
    $existing = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('clients','admins','projects','settings')")->fetchColumn();
    if ((int) $existing !== 0) throw new RuntimeException('Vueports tables already exist without this migration version. Use a fresh database/branch or review the existing schema first; nothing was changed.');
    $sql = file_get_contents(dirname(__DIR__) . '/database/001_neon_postgresql.sql');
    if ($sql === false) throw new RuntimeException('Migration file is missing.');
    $db->exec($sql);
    echo "Installed PostgreSQL schema, public content and templates.\nCreate your administrator with scripts/create-admin.php.\n";
} catch (Throwable $error) {
    fwrite(STDERR, "Migration failed. Check the database connection and server logs.\n");
    if ($error instanceof RuntimeException && !($error instanceof PDOException)) fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
