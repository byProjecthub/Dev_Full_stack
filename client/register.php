<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/emails.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isClient()) redirect('dashboard.php');

$error = ''; 
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    $error = 'Invalid security token.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['full_name'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone   = sanitize($_POST['phone'] ?? '');
    $company = sanitize($_POST['company_name'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = db();
        if ($db) {
            $check = $db->prepare("SELECT id FROM clients WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO clients (full_name, email, phone, company_name, password) VALUES (?,?,?,?,?)");
                $stmt->execute([$name, $email, $phone, $company, $hash]);

                $clientId = (int) $db->lastInsertId();
                sendWelcomeEmail(['id'=>$clientId, 'full_name'=>$name, 'email'=>$email]);
                $success = 'Account created! Please log in below.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Registration | Vueports Solutions</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-login">
    <div class="login-box" style="max-width:480px;">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert" style="background:#dcfce7; color:#166534; border:1px solid #22c55e;"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
            <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group"><label>Phone</label><input type="tel" name="phone"></div>
                <div class="form-group"><label>Company</label><input type="text" name="company_name"></div>
            </div>
            <div class="form-group"><label>Password * (min 8)</label><input type="password" name="password" required minlength="8"></div>
            <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Create Account</button>
        </form>
        <p style="text-align:center; margin-top:16px; font-size:0.875rem;">
            Already have an account? <a href="login.php">Sign in</a>
        </p>
    </div>
</body>
</html>
