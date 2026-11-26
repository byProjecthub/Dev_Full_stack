<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

$pid = (int) ($_GET['pid'] ?? 0);
$payment = null;
$client = null;

if ($pid && db()) {
    $stmt = db()->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$pid]);
    $payment = $stmt->fetch();
    
    if ($payment && $payment['client_id']) {
        $stmt = db()->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$payment['client_id']]);
        $client = $stmt->fetch();
    }
}

$pageTitle = 'Payment Successful';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:140px; text-align:center;">
    <div class="container" style="max-width:600px;">
        <div style="width:80px; height:80px; background:var(--color-success-light); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
            <i class="fas fa-check" style="font-size:2rem; color:var(--color-success);"></i>
        </div>
        <h1 style="margin-bottom:12px;">Payment Successful</h1>
        <p style="color:var(--text-secondary); margin-bottom:32px;">
            Thank you! PayFast has confirmed your payment. A receipt has been emailed to you.
        </p>

        <?php if ($payment): ?>
        <div class="card" style="text-align:left; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                <span style="color:var(--text-muted);">Reference</span>
                <span style="font-weight:600;">#<?= $payment['id'] ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                <span style="color:var(--text-muted);">Amount</span>
                <span style="font-weight:800; color:var(--color-accent);">R<?= number_format((float)$payment['amount'], 2) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between;">
                <span style="color:var(--text-muted);">Status</span>
                <span style="font-weight:600; color:var(--color-success);"><?= ucfirst($payment['payment_status']) ?></span>
            </div>
            <?php if ($payment['gateway_transaction_id']): ?>
            <div style="display:flex; justify-content:space-between; margin-top:12px; padding-top:12px; border-top:1px solid var(--border-color);">
                <span style="color:var(--text-muted);">Transaction ID</span>
                <span style="font-weight:600; font-size:0.875rem;"><?= sanitize($payment['gateway_transaction_id']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="client/login.php" class="btn btn-primary">Client Portal</a>
            <a href="index.php" class="btn btn-secondary">Back Home</a>
            <?php if ($payment && $payment['invoice_id']): ?>
            <a href="client/invoices.php" class="btn btn-outline">View Invoice</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>