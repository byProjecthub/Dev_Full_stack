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
    <title>Admin Login | Vueports Solutions</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-login">
    <div class="auth-page">
  <div class="auth-card reveal">
    <div class="auth-logo">Vueports<span>.</span></div>
    <p class="auth-subtitle">Sign in to your Admin portal</p>
        
       <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom: var(--space-6);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom: var(--space-6);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
        <form action="" method="POST" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

      <div class="form-group">
        <label class="form-label" for="adminUsername">Username or email</label>
        <input id="adminUsername" type="text" name="username" class="form-input" placeholder="Your username or email" autocomplete="username" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="••••••••" autocomplete="current-password" required>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6);">
        <label style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--text-sm); color: var(--text-secondary); cursor: pointer;">
          <input type="checkbox" name="remember" style="width: 16px; height: 16px;">
          Remember me
        </label>
        <a href="../contact.php?subject=admin-access" style="font-size: var(--text-sm); color: var(--accent-indigo); font-weight: 500;">Need access help?</a>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
        Sign In
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </button>
    </form>
       <div class="auth-divider"><span>or</span></div>
        
        <p style="text-align:center; margin-top:16px; font-size:0.875rem;">
            <a href="../index.php">← Back to Website</a>
        </p>
           <p class="auth-footer">
      Looking for the client portal? <a href="../login.php">Client sign-in</a>
    </p>  
    </div>

    </div>
</body>
</html>
