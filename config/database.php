<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

// getenv() also works when PHP's variables_order does not populate $_ENV.
$databaseValue = static function (string $name, string $default): string {
    $value = $_ENV[$name] ?? getenv($name);
    return $value === false ? $default : (string) $value;
};

$host = $databaseValue('MYSQLHOST', '127.0.0.1');
$port = $databaseValue('MYSQLPORT', '3306');
$dbname = $databaseValue('MYSQLDATABASE', 'vueports_database');
$username = $databaseValue('MYSQLUSER', 'root');
$password = $databaseValue('MYSQLPASSWORD', '');
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->exec("SET NAMES $charset COLLATE utf8mb4_unicode_ci");
} catch (\PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(503);
    die('Database connection failed.');
}
