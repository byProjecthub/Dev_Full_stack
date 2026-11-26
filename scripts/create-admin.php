<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/config/postgres.php';
$options = getopt('', ['email:', 'username:', 'name:', 'password-stdin']);
$email = strtolower(trim((string) ($options['email'] ?? env('ADMIN_EMAIL'))));
$username = trim((string) ($options['username'] ?? env('ADMIN_USERNAME')));
$name = trim((string) ($options['name'] ?? env('ADMIN_NAME', 'Administrator')));
$password = array_key_exists('password-stdin', $options) ? rtrim(stream_get_contents(STDIN), "\r\n") : env('ADMIN_PASSWORD');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9_.-]{3,100}$/D', $username) || strlen($password) < 12) {
    fwrite(STDERR, "Provide --email, --username, optional --name, and ADMIN_PASSWORD (12+ characters) or --password-stdin. Passwords are never accepted as command-line arguments.\n");exit(1);
}
try {
    $db = vueports_connect(true);
    $stmt = $db->prepare("INSERT INTO admins(name,username,email,password,role) VALUES (?,?,?,?,'super_admin') RETURNING id");
    $stmt->execute([$name, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    echo 'Created administrator #' . $stmt->fetchColumn() . ". No existing administrator was overwritten.\n";
} catch (PDOException $error) {
    fwrite(STDERR, $error->getCode() === '23505' ? "That username or email already exists; no account was changed.\n" : "Administrator creation failed. Check the connection and run the schema migration first.\n");exit(1);
}
