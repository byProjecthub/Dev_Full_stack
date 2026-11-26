<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (isAdmin()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $pass  = $_POST['password'] ?? '';

    $db = db();
    if ($db) {
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ? AND role ='super_admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id']    = $admin['admin_id'];
            $_SESSION['name']  = $admin['name'];
            $_SESSION['email'] = $admin['email'];
            redirect('dashboard.php');
        }
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Login | Vueports Solutions</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-login">
    <div class="login-box" style="max-width:420px;">
        <h2><i class="fas fa-user-circle"></i> Client Portal</h2>
        <p style="text-align:center; color:var(--admin-muted); margin-bottom:24px;">Track projects, invoices, and payments.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
        </form>
        <p style="text-align:center; margin-top:16px; font-size:0.875rem;">
            No account? <a href="register.php">Register</a> &bull; <a href="../index.php">Back to site</a>
        </p>
    </div>
</body>
</html>