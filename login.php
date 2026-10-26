<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isClient()) {
    redirect('client/dashboard.php');
}

$error = '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $error = 'Invalid security token.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            $db = db();
            if ($db) {
                $stmt = $db->prepare("SELECT id, full_name, email, password, status FROM clients WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = 'Your account is not active. Please contact support.';
                    } else {
                        $stmt = $db->prepare("UPDATE clients SET last_login_at = NOW(), last_login_ip = INET6_ATON(?) WHERE id = ?");
                        $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);

                        session_regenerate_id(true);
                        $_SESSION['client_id'] = $user['id'];
                        $_SESSION['client_name'] = $user['full_name'];
                        $_SESSION['client_email'] = $user['email'];
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['full_name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = 'client';

                        header('Location: client/dashboard.php');
                        exit;
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Database connection failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card reveal">
    <div class="auth-logo">Vueports<span>.</span></div>
    <p class="auth-subtitle">Sign in to your client portal</p>

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

    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-input" placeholder="you@company.com" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6);">
        <label style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--text-sm); color: var(--text-secondary); cursor: pointer;">
          <input type="checkbox" name="remember" style="width: 16px; height: 16px;">
          Remember me
        </label>
        <a href="forgot-password.php" style="font-size: var(--text-sm); color: var(--accent-indigo); font-weight: 500;">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
        Sign In
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </button>
    </form>

    <div class="auth-divider"><span>or</span></div>

    <p class="auth-footer">
      Don't have an account? <a href="register.php">Create one</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
