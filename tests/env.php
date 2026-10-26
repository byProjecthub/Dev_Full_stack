<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$directory = sys_get_temp_dir() . '/vueports-env-' . bin2hex(random_bytes(8));
$prefix = 'VUEPORTS_TEST_' . strtoupper(bin2hex(random_bytes(8)));
$existing = $prefix . '_EXISTING';
$missing = $prefix . '_MISSING';
mkdir($directory . '/config', 0700, true);

try {
    copy(__DIR__ . '/../config/env.php', $directory . '/config/env.php');
    file_put_contents($directory . '/.env', "# Comment\n\n$existing=from-file\n$missing=from-file\ninvalid line\n");
    putenv($existing . '=from-process');
    require $directory . '/config/env.php';

    if (env($existing) !== 'from-process' || env($missing) !== 'from-file'
        || env($prefix . '_UNSET', 'fallback') !== 'fallback') {
        throw new RuntimeException('Environment values or fallback precedence changed.');
    }
    echo "PASS exported values take precedence over dotenv; missing values and fallbacks load.\n";
} finally {
    putenv($existing);
    putenv($missing);
    unlink($directory . '/.env');
    unlink($directory . '/config/env.php');
    rmdir($directory . '/config');
    rmdir($directory);
}
