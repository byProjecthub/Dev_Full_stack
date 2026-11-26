<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pid = (int) ($_GET['pid'] ?? 0);
if ($pid && db()) {
    db()->prepare("UPDATE payments SET payment_status = 'cancelled' WHERE id = ?")->execute([$pid]);
}

$pageTitle = 'Payment Cancelled';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:140px; text-align:center;">
    <div class="container" style="max-width:600px;">
        <div style="width:80px; height:80px; background:var(--color-danger-light); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
            <i class="fas fa-times" style="font-size:2rem; color:var(--color-danger);"></i>
        </div>
        <h1 style="margin-bottom:12px;">Payment Cancelled</h1>
        <p style="color:var(--text-secondary); margin-bottom:32px;">
            No charges were made. If you encountered an issue, please contact us or try again.
        </p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="pay.php" class="btn btn-primary">Try Again</a>
            <a href="contact.php" class="btn btn-secondary">Contact Support</a>
            <a href="index.php" class="btn btn-outline">Back Home</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>