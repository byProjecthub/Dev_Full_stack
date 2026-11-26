<?php
declare(strict_types=1);
// Preserve legacy pricing links and their parameters at the real checkout route.
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: client/pay.php' . ($query !== '' ? '?' . $query : ''), true, 307);
exit;
