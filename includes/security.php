<?php
declare(strict_types=1);

/**
 * Security Layer - Rate Limiting, Headers, Input Validation
 */

// Security headers (fallback if .htaccess not active)
function sendSecurityHeaders(): void {
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}

// Rate limiting (file-based)
function checkRateLimit(string $key, int $maxAttempts = 5, int $decayMinutes = 1): bool {
    return vueportsRateLimit('security:' . $key, $maxAttempts, $decayMinutes * 60);
}

// Input sanitization
function cleanInput(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Password strength validator
function isStrongPassword(string $password): bool {
    return strlen($password) >= 12 
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

// reCAPTCHA v3 verification
function verifyRecaptcha(string $token): bool {
    $secret = getenv('RECAPTCHA_SECRET_KEY');
    if (!$secret || !$token) return false;
    
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $secret,
        'response' => $token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return ($data['success'] ?? false) && ($data['score'] ?? 0) >= 0.5;
}

// Generate secure random token
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

// Hash password using bcrypt
//function hashPassword(string $password): string {
   // return password_hash($password, PASSWORD_ARGON2ID, [
       // 'memory_cost' => 65536,
       // 'time_cost' => 4,
      //  'threads' => 3
   // ]);
//}

// Verify password
////function verifyPassword(string $password, string $hash): bool {
    //return password_verify($password, $hash);
//}

// Encrypt sensitive data
function encryptData(string $data, string $key): string {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $encrypted);
}

// Decrypt sensitive data
function decryptData(string $data, string $key): ?string {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $ciphertext = substr($data, 32);
    
    $decrypted = openssl_decrypt($ciphertext, 'AES-256-GCM', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $decrypted !== false ? $decrypted : null;
}

// CSRF token for forms
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// IP-based brute force protection
function checkBruteForce(string $identifier, int $maxAttempts = 10, int $lockoutMinutes = 30): bool {
    $db = db();
    if (!$db) return true;
    
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM login_attempts 
        WHERE identifier = ? AND attempted_at > (NOW() - CAST(? AS INTEGER) * INTERVAL '1 minute')
    ");
    $stmt->execute([$identifier, $lockoutMinutes]);
    $attempts = (int) $stmt->fetchColumn();
    
    return $attempts < $maxAttempts;
}

function logLoginAttempt(string $identifier, bool $success): void {
    $db = db();
    if (!$db) return;
    
    $stmt = $db->prepare("INSERT INTO login_attempts (identifier, ip_address, user_agent, success) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $identifier,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $success ? 1 : 0
    ]);
}

// Content Security Policy nonce
function cspNonce(): string {
    if (empty($_SESSION['csp_nonce'])) {
        $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
    }
    return $_SESSION['csp_nonce'];
}

// Secure session initialization
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', 3600);
        session_start();
        
        // Regenerate ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}