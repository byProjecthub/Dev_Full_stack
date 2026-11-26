<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/frontend.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = !empty($_SESSION['client_id']) || !empty($_SESSION['user_id']);
$userName = $_SESSION['client_name'] ?? $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['client_email'] ?? $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'client';
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '/index.php', PHP_URL_PATH) ?: '/index.php', '.php');
if ($currentPage === '') $currentPage = 'index';
function isActive($page, $current) { return $page === $current ? 'active' : ''; }
$vpUrl = static fn(string $path): string => htmlspecialchars(vueports_url($path), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#f7f9fc">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?>Vueports Solutions</title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Vueports Solutions — Custom Software, Data Engineering & AI Agents', ENT_QUOTES, 'UTF-8') ?>">
  <link rel="icon" href="<?= $vpUrl('favicon.ico') ?>">
  <link rel="stylesheet" href="<?= $vpUrl('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= $vpUrl('assets/css/style.css') ?>?v=20261126-faq">
  <link rel="stylesheet" href="<?= $vpUrl('assets/css/vueports-light.css') ?>?v=20261126">
  <link rel="stylesheet" href="<?= $vpUrl('assets/css/vueports-navigation.css') ?>?v=20261126-nav">
  <script src="<?= $vpUrl('assets/js/vueports-navigation.js') ?>?v=20261126-nav" defer></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css">
  <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js" defer></script>
</head>
<body class="vueports-light">
<a class="vp-skip-link" href="#page-content">Skip to content</a>
<?php if ($flashMessage): ?>
<div id="flashMessage" class="alert alert-<?= htmlspecialchars($flashType) ?>" role="status" style="position:fixed;top:120px;left:50%;transform:translateX(-50%);z-index:1000;max-width:500px;width:90%;text-align:center;box-shadow:var(--shadow-lg)"><?= htmlspecialchars($flashMessage) ?></div>
<?php endif; ?>
<?php require __DIR__ . '/navigation.php'; ?>
<div id="page-content" tabindex="-1"></div>
