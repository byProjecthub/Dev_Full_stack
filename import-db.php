<?php
declare(strict_types=1);
// Compatibility command: php import-db.php. Installation is never exposed over HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/scripts/migrate.php';
