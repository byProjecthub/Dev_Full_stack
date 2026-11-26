<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
require_once dirname(__DIR__) . '/config/postgres.php';
try {
    $db = vueports_connect(true);
    $sessions = $db->exec('DELETE FROM app_sessions WHERE expires_at <= NOW()');
    $limits = $db->exec('DELETE FROM app_rate_limits WHERE expires_at <= NOW()');
    echo "Expired sessions removed: $sessions; expired rate-limit windows removed: $limits.\n";
} catch (Throwable $error) {fwrite(STDERR,"Cleanup failed. Verify the database connection.\n");exit(1);}
