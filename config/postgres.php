<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';

/** Convert a Neon URI (or standard PG* settings) to a PDO PostgreSQL DSN. */
function vueports_postgres_config(bool $direct = false): array {
    $uri = $direct ? env('DATABASE_URL_UNPOOLED', env('DIRECT_URL', env('DATABASE_URL'))) : env('DATABASE_URL');
    $query = [];
    if ($uri !== '') {
        $parts = parse_url($uri);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true) || empty($parts['host']) || empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL must be a PostgreSQL connection URI.');
        }
        parse_str($parts['query'] ?? '', $query);
        $host = $parts['host']; $port = (string) ($parts['port'] ?? 5432);
        $name = rawurldecode(ltrim($parts['path'], '/'));
        $user = rawurldecode($parts['user'] ?? ''); $password = rawurldecode($parts['pass'] ?? '');
    } else {
        $host = env('PGHOST'); $port = env('PGPORT', '5432'); $name = env('PGDATABASE');
        $user = env('PGUSER'); $password = env('PGPASSWORD');
    }
    if ($host === '' || $name === '' || $user === '') throw new RuntimeException('Set DATABASE_URL or PGHOST, PGDATABASE and PGUSER before starting Vueports.');
    $local = in_array(strtolower(trim($host, '[]')), ['localhost', '127.0.0.1', '::1', 'db'], true);
    $development = in_array(env('APP_ENV', 'production'), ['local', 'development', 'test'], true);
    $sslmode = (string) ($query['sslmode'] ?? env('PGSSLMODE', 'require'));
    if (!in_array($sslmode, ['require', 'verify-ca', 'verify-full'], true) && !($local && $development && $sslmode === 'disable')) {
        throw new RuntimeException('Remote database connections require TLS.');
    }
    $binding = (string) ($query['channel_binding'] ?? env('PGCHANNELBINDING', $local ? 'prefer' : 'require'));
    if (!in_array($binding, ['require', 'prefer', 'disable'], true)) throw new RuntimeException('Invalid channel_binding setting.');
    $parameters = ['host' => $host, 'port' => $port, 'dbname' => $name, 'sslmode' => $sslmode, 'channel_binding' => $binding, 'connect_timeout' => '10', 'application_name' => 'vueports'];
    foreach (['sslrootcert', 'sslcert', 'sslkey', 'connect_timeout', 'target_session_attrs'] as $key) {
        $value = $query[$key] ?? env('PG' . strtoupper($key));
        if ($value !== '') $parameters[$key] = (string) $value;
    }
    $dsn = 'pgsql:';
    foreach ($parameters as $key => $value) {
        // PDO treats semicolons as separators; reject delimiter/control injection.
        if (preg_match('/[;\x00-\x1f]/', $value)) throw new RuntimeException('Invalid database connection setting.');
        $dsn .= $key . "='" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "' ";
    }
    return ['dsn' => rtrim($dsn), 'user' => $user, 'password' => $password, 'host' => $host, 'port' => $port, 'database' => $name, 'parameters' => $parameters];
}

function vueports_connect(bool $direct = false): PDO {
    $config = vueports_postgres_config($direct);
    return new PDO($config['dsn'], $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
    ]);
}
