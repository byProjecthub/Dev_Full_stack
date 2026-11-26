<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/postgres.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $db = vueports_connect();$db->query('SELECT 1');
    $table = $db->query("SELECT to_regclass('public.vueports_schema_migrations')")->fetchColumn();
    $installed = $table && $db->query("SELECT 1 FROM vueports_schema_migrations WHERE version='001_neon_postgresql'")->fetchColumn();
    if (!$installed) throw new RuntimeException('Schema not installed');
    echo json_encode(['status'=>'healthy','database'=>'postgresql','schema'=>'001_neon_postgresql']);
} catch (Throwable $error) {http_response_code(503);echo json_encode(['status'=>'unhealthy','database'=>'unavailable']);}
