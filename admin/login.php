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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['username'] ?? $_POST['email'] ?? '';
    $login = is_string($identifier) ? trim($identifier) : '';
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? null;

    if (!is_string($token) || !verifyCsrf($token)) {
        http_response_code(403);
        $error = 'Your session expired. Please try again.';
    } elseif ($login === '' || !is_string($password) || $password === '') {
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
                $error = 'Invalid username, email, or password.';
            } else {
                $ip = inet_pton($_SERVER['REMOTE_ADDR'] ?? '');
                $stmt = $db->prepare("UPDATE admins
                    SET last_login_at = NOW(), last_login_ip = ?,
                        login_attempts = 0, locked_until = NULL
                    WHERE id = ?");
                $stmt->execute([$ip === false ? null : $ip, $admin['id']]);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Vueports Solutions</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-login">
    <main class="login-box">
        <h1>Vueports Solutions</h1>
        <p class="login-subtitle">Sign in to your admin portal</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="username">Username or email</label>
                <input id="username" type="text" name="username"
                    value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password"
                    autocomplete="current-password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
        </form>

        <p style="text-align:center; margin-top:16px; font-size:0.875rem;">
            <a href="../index.php">Back to Website</a>
        </p>
    </main>
</body>
</html>
