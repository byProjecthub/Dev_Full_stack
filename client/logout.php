<?php
declare(strict_types=1);
require_once '../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
redirect('login.php');
