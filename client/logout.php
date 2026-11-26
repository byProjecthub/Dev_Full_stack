<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
if (session_status() === PHP_SESSION_NONE) vueports_start_session();
unset($_SESSION['client_id'], $_SESSION['client_name'], $_SESSION['client_email'], $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
session_destroy();
redirect('login.php');