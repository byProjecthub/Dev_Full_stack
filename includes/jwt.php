<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

function generateJWT(array $payload, int $expiryHours = 24): string {
    if (strlen(env('JWT_SECRET')) < 32) throw new RuntimeException('Configure JWT_SECRET with at least 32 characters.');
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    $payload['exp'] = time() + ($expiryHours * 3600);
    $payload['iss'] = env('APP_URL', 'https://vueports.co.za');
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    $signature = hash_hmac('sha256', "$base64Header.$base64Payload", env('JWT_SECRET', ''), true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return "$base64Header.$base64Payload.$base64Signature";
}

function verifyJWT(string $token): ?array {
    if (strlen(env('JWT_SECRET')) < 32) return null;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    $signature = hash_hmac('sha256', "$parts[0].$parts[1]", env('JWT_SECRET', ''), true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if (!hash_equals($base64Signature, $parts[2])) return null;
    
    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) return null;
    
    return $payload;
}

function getBearerToken(): ?string {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
        return $matches[1];
    }
    return null;
}

function requireAuth(): array {
    $token = getBearerToken();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $payload = verifyJWT($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }
    
    return $payload;
}