<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;

function setup2FA(int $userId): ?array {
    $ga = new GoogleAuthenticator();
    $secret = $ga->generateSecret();
    
    $db = db();
    if (!$db) return null;
    
    $db->prepare("UPDATE admins SET totp_secret = ? WHERE id = ?")
       ->execute([$secret, $userId]);
    
    $qrUrl = $ga->getUrl(
        'Vueports Admin',
        parse_url(env('APP_URL', 'vueports.co.za'), PHP_URL_HOST) ?? 'vueports.co.za',
        $secret
    );
    
    return [
        'secret' => $secret,
        'qr_url' => $qrUrl
    ];
}

function verify2FA(int $userId, string $code): bool {
    $db = db();
    if (!$db) return false;
    
    $stmt = $db->prepare("SELECT totp_secret FROM admins WHERE id = ?");
    $stmt->execute([$userId]);
    $secret = $stmt->fetchColumn();
    
    if (!$secret) return false;
    
    $ga = new GoogleAuthenticator();
    return $ga->checkCode($secret, $code);
}

function disable2FA(int $userId): bool {
    $db = db();
    if (!$db) return false;
    
    $stmt = $db->prepare("UPDATE admins SET totp_secret = NULL WHERE id = ?");
    return $stmt->execute([$userId]);
}