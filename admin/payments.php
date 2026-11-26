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

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($_POST['action'] === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $allowed = ['pending', 'completed', 'failed', 'cancelled', 'refunded'];
        
        if ($id && in_array($status, $allowed)) {
            $stmt = $db->prepare("UPDATE payments SET payment_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = 'Payment status updated to ' . ucfirst($status);
        }
        redirect('payments.php');
    }
}

// Filtering & Pagination
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "p.payment_status = ?";
    $params[] = $statusFilter;
}

$countSql = "SELECT COUNT(*) FROM payments p WHERE " . implode(" AND ", $where);
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalPayments = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalPayments / $perPage);

$sql = "SELECT p.*, c.full_name as client_name, c.email as client_email, i.invoice_number, i.total_amount as invoice_total 
    FROM payments p 
    LEFT JOIN clients c ON p.client_id = c.id 
    LEFT JOIN invoices i ON p.invoice_id = i.id 
    WHERE " . implode(" AND ", $where) . " 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Revenue stats
$totalRevenue = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed'")->fetchColumn();
$thisMonth = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND created_at >= (NOW() - INTERVAL '30 day')")->fetchColumn();
$thisWeek = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND created_at >= (NOW() - INTERVAL '7 day')")->fetchColumn();
$pendingRevenue = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'pending'")->fetchColumn();

// Status counts
$pendingCount   = (int) $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn();
$completedCount = (int) $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'completed'")->fetchColumn();
$failedCount    = (int) $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'failed'")->fetchColumn();
$cancelledCount = (int) $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'cancelled'")->fetchColumn();
$refundedCount  = (int) $db->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'refunded'")->fetchColumn();

$pageTitle = 'Payments';
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
    <style>
        .filter-tabs { display: flex; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .filter-tab:hover { background: var(--bg-secondary); }
        .filter-tab.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .filter-tab .badge { margin-left: 6px; font-size: 10px; padding: 2px 6px; border-radius: 10px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
        .status-refunded { background: #fef3c7; color: #92400e; }
        .revenue-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .revenue-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; text-align: center; }
        .revenue-card h4 { font-size: 22px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .revenue-card p { font-size: 12px; color: var(--text-muted); margin: 4px 0 0; }
    </style>
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
            <a href="payments.php" class="active"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
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
            <h1>Payment Transactions</h1>
        </header>

        <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-check-circle"></i> <?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- Revenue Summary -->
        <div class="revenue-grid">
            <div class="revenue-card"><h4>R<?= number_format($totalRevenue, 0) ?></h4><p>Total Revenue</p></div>
            <div class="revenue-card"><h4>R<?= number_format($thisMonth, 0) ?></h4><p>This Month</p></div>
            <div class="revenue-card"><h4>R<?= number_format($thisWeek, 0) ?></h4><p>This Week</p></div>
            <div class="revenue-card"><h4>R<?= number_format($pendingRevenue, 0) ?></h4><p>Pending</p></div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge" style="background:var(--text-muted); color:white;"><?= $totalPayments ?></span></a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending <span class="badge status-pending"><?= $pendingCount ?></span></a>
            <a href="?status=completed" class="filter-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed <span class="badge status-completed"><?= $completedCount ?></span></a>
            <a href="?status=failed" class="filter-tab <?= $statusFilter === 'failed' ? 'active' : '' ?>">Failed <span class="badge status-failed"><?= $failedCount ?></span></a>
            <a href="?status=cancelled" class="filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled <span class="badge status-cancelled"><?= $cancelledCount ?></span></a>
            <a href="?status=refunded" class="filter-tab <?= $statusFilter === 'refunded' ? 'active' : '' ?>">Refunded <span class="badge status-refunded"><?= $refundedCount ?></span></a>
        </div>

        <section class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reference</th>
                        <th>Client</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td><code style="font-size:12px; background:var(--bg-secondary); padding:2px 6px; border-radius:4px;"><?= sanitize($p['gateway_transaction_id'] ?: 'N/A') ?></code></td>
                        <td>
                            <div style="font-weight:600;"><?= sanitize($p['client_name'] ?: ($p['payer_name'] ?: 'Guest')) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= sanitize($p['client_email'] ?: ($p['payer_email'] ?: '')) ?></div>
                        </td>
                        <td><?= sanitize($p['invoice_number'] ?: '-') ?></td>
                        <td>
                            <div style="font-weight:600;">R<?= number_format((float)$p['amount'], 2) ?></div>
                            <?php if ($p['amount_fee'] > 0): ?>
                            <div style="font-size:11px; color:var(--text-muted);">Fee: R<?= number_format((float)$p['amount_fee'], 2) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= ucfirst($p['gateway']) ?></td>
                        <td>
                            <span class="status-badge status-<?= sanitize($p['payment_status']) ?>">
                                <?= ucfirst($p['payment_status']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= date('M j, Y H:i', strtotime($p['created_at'])) ?></td>
                        <td>
                            <div style="display:flex; gap:4px;">
                                <?php if ($p['payment_status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-success" title="Mark Completed"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this payment?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="Cancel"><i class="fas fa-times"></i></button>
                                </form>
                                <?php elseif ($p['payment_status'] === 'completed'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Refund this payment?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="status" value="refunded">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-secondary" title="Refund"><i class="fas fa-undo"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-muted);">No payments yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="display:flex; justify-content:center; gap:4px; margin-top:24px;">
                <?php if ($page > 1): ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $page - 1 ?>" class="admin-btn admin-btn-sm admin-btn-secondary"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                <span style="padding:8px 14px; border-radius:6px; font-size:13px; background:var(--color-primary); color:white;"><?= $i ?></span>
                <?php else: ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $i ?>" style="padding:8px 14px; border-radius:6px; font-size:13px; text-decoration:none; background:var(--bg-card); color:var(--text-primary); border:1px solid var(--border-color);"><?= $i ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $statusFilter ?>&page=<?= $page + 1 ?>" class="admin-btn admin-btn-sm admin-btn-secondary"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>