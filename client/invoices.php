<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
if (!isClient()) redirect('login.php');

$client = getClient($_SESSION['client_id']);
$invoices = getClientInvoices($client['id']);
$pageTitle = 'Invoices';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($pageTitle) ?> | Client Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
<aside class="admin-sidebar">
        <div class="admin-brand">&vee;ueports Client</div>
        <nav class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="projects.php" ><i class="fas fa-envelope"></i> My Projects</a>
            <a href="invoices.php" class="active"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div style="margin-top:auto; padding:16px; font-size:0.75rem; color:#9ca3af; border-top:1px solid #374151;">
            Logged in as<br><strong><?= sanitize($client['full_name']) ?></strong>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header"><h1>Invoices & Payments</h1><div><?= sanitize($client['company_name'] ?: $client['full_name']) ?></div></header>
        <section class="admin-section">
            <?php if (empty($invoices)): ?>
                <p style="color:var(--admin-muted);">No invoices yet.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Invoice #</th><th>Description</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= sanitize($inv['invoice_number']) ?></td>
                        <td><?= sanitize($inv['description'] ?: '-') ?></td>
                        <td><strong>R<?= number_format((float)$inv['amount'], 2) ?></strong></td>
                        <td>
                            <?php if ($inv['status'] === 'paid'): ?>
                                <span style="color:#22c55e; font-weight:600;"><i class="fas fa-check"></i> Paid</span>
                            <?php elseif ($inv['status'] === 'overdue'): ?>
                                <span style="color:#ef4444; font-weight:600;">Overdue</span>
                            <?php else: ?>
                                <span style="color:#f59e0b; font-weight:600;"><?= ucfirst($inv['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $inv['due_date'] ? date('M j, Y', strtotime($inv['due_date'])) : '-' ?></td>
                        <td>
                            <?php if ($inv['status'] !== 'paid'): ?>
                                <a href="pay.php?invoice_id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary">Pay Now</a>
                            <?php else: ?>
                                <span style="font-size:0.875rem; color:var(--admin-muted);">Paid <?= $inv['paid_at'] ? date('M j, Y', strtotime($inv['paid_at'])) : '' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>