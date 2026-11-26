<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();

$db = db();
if (!$db) {
    die('Database connection required');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token';
    } else {
        $settings = [
            'site_title', 'site_tagline', 'contact_email', 'contact_phone',
            'location', 'smtp_from', 'booking_email', 'consultation_email'
        ];
        
        foreach ($settings as $key) {
            if (isset($_POST[$key])) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW()");
                $stmt->execute([$key, sanitize($_POST[$key])]);
            }
        }
        $success = 'Settings saved successfully';
    }
}

// Load current settings
$currentSettings = [];
$rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $row) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'Settings';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Vueports Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-shield-alt"></i> Vueports Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="consultations.php"><i class="fas fa-comments"></i> Consultations</a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            <a href="services.php"><i class="fas fa-briefcase"></i> Services</a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
            <a href="invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div class="admin-user">
            <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            <div class="admin-user-info">
                <div class="admin-user-name"><?= sanitize($_SESSION['username'] ?? 'Admin') ?></div>
                <div class="admin-user-role">Administrator</div>
            </div>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Site Settings</h1>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>

        <section class="admin-section">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                
                <h3 class="admin-section-title" style="margin-bottom:20px;">General</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label>Site Title</label>
                        <input type="text" name="site_title" value="<?= sanitize($currentSettings['site_title'] ?? 'Vueports Solutions') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Site Tagline</label>
                        <input type="text" name="site_tagline" value="<?= sanitize($currentSettings['site_tagline'] ?? 'Your IT Solutions Production Partner') ?>" class="form-input">
                    </div>
                </div>

                <h3 class="admin-section-title" style="margin:24px 0 20px;">Contact Information</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" value="<?= sanitize($currentSettings['contact_email'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Contact Phone</label>
                        <input type="tel" name="contact_phone" value="<?= sanitize($currentSettings['contact_phone'] ?? '') ?>" class="form-input">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Location</label>
                    <input type="text" name="location" value="<?= sanitize($currentSettings['location'] ?? '') ?>" class="form-input">
                </div>

                <h3 class="admin-section-title" style="margin:24px 0 20px;">Email Configuration</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div class="form-group">
                        <label>SMTP From Address</label>
                        <input type="email" name="smtp_from" value="<?= sanitize($currentSettings['smtp_from'] ?? '') ?>" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Booking Notification Email</label>
                        <input type="email" name="booking_email" value="<?= sanitize($currentSettings['booking_email'] ?? '') ?>" class="form-input">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:24px;">
                    <label>Consultation Notification Email</label>
                    <input type="email" name="consultation_email" value="<?= sanitize($currentSettings['consultation_email'] ?? '') ?>" class="form-input">
                </div>

                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </section>
    </main>
</body>
</html>