<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/jwt.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$db = db();

if (!$db) {
    apiResponse(false, null, 'Database unavailable', 503);
}

function apiResponse(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

switch ($method) {
    case 'POST':
        $action = $input['action'] ?? $_POST['action'] ?? '';

        switch ($action) {
            case 'client_login':
                $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $password = $input['password'] ?? '';
                
                if (!$email || !$password) {
                    apiResponse(false, null, 'Email and password required', 400);
                }
                
                $stmt = $db->prepare("SELECT * FROM clients WHERE email = ? AND status = 'active' LIMIT 1");
                $stmt->execute([$email]);
                $client = $stmt->fetch();
                
                if (!$client || !password_verify($password, $client['password'])) {
                    apiResponse(false, null, 'Invalid credentials', 401);
                }
                
                $token = generateJWT([
                    'sub' => $client['id'],
                    'role' => 'client',
                    'email' => $client['email']
                ]);
                
                apiResponse(true, [
                    'token' => $token,
                    'client' => [
                        'id' => $client['id'],
                        'name' => $client['full_name'],
                        'email' => $client['email'],
                        'company' => $client['company_name']
                    ]
                ], 'Login successful');
                break;

            case 'client_register':
                $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
                $password = $input['password'] ?? '';
                $name = sanitize($input['full_name'] ?? '');
                $company = sanitize($input['company_name'] ?? '');
                $phone = sanitize($input['phone'] ?? '');
                
                if (!$email || !$password || strlen($password) < 8 || !$name) {
                    apiResponse(false, null, 'Valid email, name, and password (8+ chars) required', 400);
                }
                
                $exists = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
                $exists->execute([$email]);
                if ($exists->fetch()) {
                    apiResponse(false, null, 'Email already registered', 409);
                }
                
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare("INSERT INTO clients (full_name, email, password, company_name, phone, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW()) RETURNING id");
                $stmt->execute([$name, $email, $hash, $company, $phone]);
                
                $clientId = (int) $stmt->fetchColumn();
                $token = generateJWT(['sub' => $clientId, 'role' => 'client', 'email' => $email]);
                
                apiResponse(true, [
                    'token' => $token,
                    'client' => ['id' => $clientId, 'name' => $name, 'email' => $email]
                ], 'Registration successful', 201);
                break;

            case 'admin_login':
                $username = sanitize($input['username'] ?? '');
                $password = $input['password'] ?? '';
                
                if (!$username || !$password) {
                    apiResponse(false, null, 'Username and password required', 400);
                }
                
                // FIX: Changed 'admin' to 'admins'
                $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $row = $stmt->fetch();
                
                if (!$row || !password_verify($password, $row['password'])) {
                    apiResponse(false, null, 'Invalid credentials', 401);
                }
                
                $token = generateJWT([
                    'sub' => $row['id'],
                    'role' => $row['role'] ?? 'admin',
                    'username' => $row['username']
                ]);
                
                apiResponse(true, [
                    'token' => $token,
                    'admin' => ['id' => $row['id'], 'name' => $row['name'], 'role' => $row['role']]
                ], 'Login successful');
                break;

            case 'refresh':
                $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                $token = str_replace('Bearer ', '', $auth);
                
                if (!$token) {
                    apiResponse(false, null, 'Token required', 401);
                }
                
                $payload = verifyJWT($token);
                if (!$payload) {
                    apiResponse(false, null, 'Invalid or expired token', 401);
                }
                
                $newToken = generateJWT([
                    'sub' => $payload['sub'],
                    'role' => $payload['role'],
                    'email' => $payload['email'] ?? null
                ]);
                
                apiResponse(true, ['token' => $newToken], 'Token refreshed');
                break;

            default:
                apiResponse(false, null, 'Unknown action', 400);
        }
        break;

    case 'GET':
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $auth);
        
        if (!$token) {
            apiResponse(false, null, 'Token required', 401);
        }
        
        $payload = verifyJWT($token);
        if (!$payload) {
            apiResponse(false, null, 'Invalid or expired token', 401);
        }
        
        apiResponse(true, ['payload' => $payload], 'Token valid');
        break;

    default:
        apiResponse(false, null, 'Method not allowed', 405);
}