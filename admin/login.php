<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isAdmin()) {
    redirect('dashboard.php');
}

$error = '';
$login = '';
$identityInvalid = false;
$passwordInvalid = false;
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['username'] ?? $_POST['email'] ?? '';
    $login = is_string($identifier) ? trim($identifier) : '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verifyCsrf($token)) {
        http_response_code(403);
        $error = 'Your session expired. Please try again.';
    } elseif ($login === '' || !is_string($password) || $password === '') {
        $identityInvalid = $login === '';
        $passwordInvalid = !is_string($password) || $password === '';
        $error = 'Please enter your username or email and password.';
    } else {
        try {
            $db = db();
            if (!$db) {
                throw new RuntimeException('Database unavailable');
            }

            $stmt = $db->prepare("SELECT * FROM admins
                WHERE (username = ? OR email = ?)
                  AND (locked_until IS NULL OR locked_until <= NOW())
                LIMIT 1");
            $stmt->execute([$login, $login]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($password, $admin['password'])) {
                $identityInvalid = true;
                $passwordInvalid = true;
                $error = 'Invalid username, email, or password.';
            } else {
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt = $db->prepare("UPDATE admins
                    SET last_login_at = NOW(), last_login_ip = ?,
                        login_attempts = 0, locked_until = NULL
                    WHERE id = ?");
                $stmt->execute([$ip, $admin['id']]);

                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['admin_role'] = $admin['role'];
                unset($_SESSION['csrf_token']);

                redirect('dashboard.php');
            }
        } catch (PDOException | RuntimeException $exception) {
            error_log('Admin login failed: ' . $exception->getMessage());
            http_response_code(503);
            $error = 'Sign-in is temporarily unavailable. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Vueports Solutions</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
    <link rel="stylesheet" href="../assets/css/admin-login.css?v=20261126">
    <script src="../assets/js/admin-login.js?v=20261126" defer></script>
</head>
<body class="admin-login">
    <div class="auth-page">
        <main class="auth-card vp-admin-auth" aria-labelledby="adminLoginTitle">
            <a class="auth-logo" href="../index.php" aria-label="Vueports home">Vueports<span>.</span></a>
            <p class="vp-admin-auth__eyebrow">ADMINISTRATOR ACCESS</p>
            <h1 id="adminLoginTitle">Sign in to your workspace</h1>
            <p class="auth-subtitle">Use your administrator username or email address and password.</p>

            <?php if ($success !== ''): ?>
                <p class="alert alert-success" role="status"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p id="adminLoginError" class="alert alert-error" role="alert" tabindex="-1"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="on">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="adminUsername">Username or email address</label>
                    <input id="adminUsername" type="text" name="username" class="form-input"
                        value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Enter your username or email"
                        autocomplete="username" autocapitalize="none" spellcheck="false"
                        aria-describedby="adminIdentityHint<?= $identityInvalid ? ' adminLoginError' : '' ?>"
                        aria-invalid="<?= $identityInvalid ? 'true' : 'false' ?>"
                        required <?= $error === '' ? 'autofocus' : '' ?>>
                    <p id="adminIdentityHint" class="vp-admin-auth__hint">Use the administrator account created for this website.</p>
                </div>

                <div class="form-group">
                    <label for="adminPassword">Password</label>
                    <div class="vp-admin-auth__password">
                        <input id="adminPassword" type="password" name="password" class="form-input"
                            placeholder="Enter your password" autocomplete="current-password"
                            aria-invalid="<?= $passwordInvalid ? 'true' : 'false' ?>"
                            <?= $passwordInvalid ? 'aria-describedby="adminLoginError"' : '' ?> required>
                        <button id="adminPasswordToggle" class="vp-admin-auth__toggle" type="button"
                            aria-controls="adminPassword" aria-label="Show password" aria-pressed="false" hidden>Show</button>
                    </div>
                </div>

                <div class="vp-admin-auth__help">
                    <a href="../contact.php?subject=admin-access">Need access help?</a>
                </div>
                <button type="submit" class="btn btn-primary vp-admin-auth__submit">
                    Sign in
                    <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-6-6 6 6-6 6"></path></svg>
                </button>
            </form>

            <p class="auth-footer">Looking for your client account? <a href="../login.php">Client sign-in</a></p>
            <a class="vp-admin-auth__back" href="../index.php">Back to website</a>
        </main>
    </div>
</body>
</html>
