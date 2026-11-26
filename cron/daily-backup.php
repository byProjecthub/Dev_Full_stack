<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/postgres.php';
try {
    $config = vueports_postgres_config(true);
    $directory = env('BACKUP_DIR', sys_get_temp_dir() . '/vueports-backups');
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Backup directory cannot be created.');
    $filename = $directory . '/vueports_' . gmdate('Y-m-d_H-i-s') . '.dump';
    $processEnv = array_merge(getenv() ?: [], ['PGHOST' => $config['host'], 'PGPORT' => $config['port'], 'PGDATABASE' => $config['database'], 'PGUSER' => $config['user'], 'PGPASSWORD' => $config['password']]);
    foreach ($config['parameters'] as $key => $value) $processEnv['PG' . strtoupper($key)] = $value;
    $binary = env('PG_DUMP_BINARY', 'pg_dump');
    $process = proc_open([$binary, '--no-password', '--format=custom', '--file=' . $filename], [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, null, $processEnv, ['bypass_shell' => true]);
    if (!is_resource($process)) throw new RuntimeException('pg_dump could not be started.');
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]);fclose($pipes[1]);$error = stream_get_contents($pipes[2]);fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0 || !is_file($filename) || filesize($filename) === 0) { if (is_file($filename)) unlink($filename);throw new RuntimeException('pg_dump failed. Verify the client version and direct connection.'); }
    chmod($filename,0600);
    echo 'PostgreSQL backup created: ' . $filename . "\nCopy it to durable private storage before the hosting instance is replaced.\n";
} catch (Throwable $error) {fwrite(STDERR, "Backup failed. Verify PG_DUMP_BINARY, BACKUP_DIR and the direct database connection.\n");exit(1);}
