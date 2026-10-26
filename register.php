<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $error = 'Invalid security token.';
    } else {
        $name     = sanitize($_POST['full_name'] ?? '');
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone    = sanitize($_POST['phone'] ?? '');
        $company  = sanitize($_POST['company_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db = db();
            if ($db) {
                $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email already exists. Please sign in instead.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO clients (full_name, email, phone, company_name, password, status, created_at) VALUES (?,?,?,?,?, 'active', NOW())");
                    $stmt->execute([$name, $email, $phone, $company, $hash]);
                    
                    $_SESSION['flash_success'] = 'Account created successfully! Please sign in.';
                    header('Location: login.php');
                    exit;
                }
            } else {
                $error = 'Database connection failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card reveal">
    <div class="auth-logo">Vueports<span>.</span></div>
    <p class="auth-subtitle">Create your client portal account</p>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom: var(--space-6);">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-input" placeholder="John Doe" required>
      </div>

      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-input" placeholder="you@company.com" required>
      </div>

      <div class="grid-2" style="gap: var(--space-4);">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-input" placeholder="+27 68 826 1507">
        </div>
        <div class="form-group">
          <label class="form-label">Company</label>
          <input type="text" name="company_name" class="form-input" placeholder="Your company">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <input type="password" name="password_confirm" class="form-input" placeholder="Repeat password" required>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
        Create Account
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
      </button>
    </form>

    <div class="auth-divider"><span>or</span></div>

    <p class="auth-footer">
      Already have an account? <a href="login.php">Sign in</a>
    </p>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
