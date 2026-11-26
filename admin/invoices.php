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

// Create invoice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($_POST['action'] === 'create') {
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $projectId = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;
        $amount = (float) ($_POST['amount'] ?? 0);
        $taxAmount = (float) ($_POST['tax_amount'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $dueDate = $_POST['due_date'] ?? null;
        $notes = sanitize($_POST['notes'] ?? '');
        $terms = sanitize($_POST['terms'] ?? '');
        
        if ($clientId && $amount > 0) {
            $count = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM NOW())")->fetchColumn();
            $invoiceNum = 'INV-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO invoices (client_id, project_id, invoice_number, amount, tax_amount, description, status, due_date, notes, terms, sent_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW(),NOW())");
            $stmt->execute([$clientId, $projectId, $invoiceNum, $amount, $taxAmount, $description, 'sent', $dueDate, $notes, $terms]);
            $success = 'Invoice created: ' . $invoiceNum;
        } else {
            $error = 'Client and valid amount are required.';
        }
        redirect('invoices.php');
    } elseif ($_POST['action'] === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $allowed = ['draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded'];
        
        if ($id && in_array($status, $allowed)) {
            $extra = '';
            $params = [$status];
            if ($status === 'paid') {
                $extra = ', paid_at = NOW()';
            }
            $stmt = $db->prepare("UPDATE invoices SET status = ?{$extra}, updated_at = NOW() WHERE id = ?");
            $params[] = $id;
            $stmt->execute($params);
            $success = 'Invoice status updated to ' . ucfirst($status);
        }
        redirect('invoices.php');
    } elseif ($_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("UPDATE invoices SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Invoice cancelled.';
        }
        redirect('invoices.php');
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
    $where[] = "i.status = ?";
    $params[] = $statusFilter;
}

$countSql = "SELECT COUNT(*) FROM invoices i WHERE " . implode(" AND ", $where);
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalInvoices = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalInvoices / $perPage);

$sql = "SELECT i.*, c.full_name as client_name, c.email as client_email, p.title as project_title 
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    LEFT JOIN projects p ON i.project_id = p.id 
    WHERE " . implode(" AND ", $where) . " 
    ORDER BY i.created_at DESC 
    LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$clients = $db->query("SELECT id, full_name, email FROM clients WHERE status = 'active' ORDER BY full_name")->fetchAll();
$projects = $db->query("SELECT id, title FROM projects WHERE status IN ('planning','in_progress','review') ORDER BY title")->fetchAll();

// Stats
$draftCount = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'draft'")->fetchColumn();
$sentCount = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'sent'")->fetchColumn();
$paidCount = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'")->fetchColumn();
$overdueCount = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'overdue'")->fetchColumn();
$cancelledCount = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'cancelled'")->fetchColumn();

$totalOutstanding = (float) $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status IN ('sent','overdue')")->fetchColumn();

$pageTitle = 'Invoices';
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
        .status-draft { background: #f3f4f6; color: #4b5563; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #9ca3af; }
        .status-refunded { background: #fef3c7; color: #92400e; }
        .invoice-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px; text-align: center; }
        .summary-card h4 { font-size: 22px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .summary-card p { font-size: 12px; color: var(--text-muted); margin: 4px 0 0; }
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
            <a href="invoices.php" class="active"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
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
            <h1>Invoice Management</h1>
            <div class="admin-header-actions">
                <button onclick="document.getElementById('invoiceForm').style.display='flex'" class="admin-btn admin-btn-primary admin-btn-sm">
                    <i class="fas fa-plus"></i> Create Invoice
                </button>
            </div>
        </header>
        
        <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-check-circle"></i> <?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="invoice-summary">
            <div class="summary-card"><h4>R<?= number_format($totalOutstanding, 0) ?></h4><p>Outstanding</p></div>
            <div class="summary-card"><h4><?= number_format($sentCount) ?></h4><p>Sent</p></div>
            <div class="summary-card"><h4><?= number_format($paidCount) ?></h4><p>Paid</p></div>
            <div class="summary-card"><h4><?= number_format($overdueCount) ?></h4><p>Overdue</p></div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge" style="background:var(--text-muted); color:white;"><?= $totalInvoices ?></span></a>
            <a href="?status=draft" class="filter-tab <?= $statusFilter === 'draft' ? 'active' : '' ?>">Draft <span class="badge status-draft"><?= $draftCount ?></span></a>
            <a href="?status=sent" class="filter-tab <?= $statusFilter === 'sent' ? 'active' : '' ?>">Sent <span class="badge status-sent"><?= $sentCount ?></span></a>
            <a href="?status=paid" class="filter-tab <?= $statusFilter === 'paid' ? 'active' : '' ?>">Paid <span class="badge status-paid"><?= $paidCount ?></span></a>
            <a href="?status=overdue" class="filter-tab <?= $statusFilter === 'overdue' ? 'active' : '' ?>">Overdue <span class="badge status-overdue"><?= $overdueCount ?></span></a>
            <a href="?status=cancelled" class="filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled <span class="badge status-cancelled"><?= $cancelledCount ?></span></a>
        </div>
        
        <section class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Project</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?= sanitize($inv['invoice_number']) ?></strong></td>
                        <td>
                            <div style="font-weight:600;"><?= sanitize($inv['client_name'] ?: 'Unknown') ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= sanitize($inv['client_email'] ?: '') ?></div>
                        </td>
                        <td><?= sanitize($inv['project_title'] ?: '-') ?></td>
                        <td>
                            <div style="font-weight:600;">R<?= number_format((float)$inv['total_amount'], 2) ?></div>
                            <?php if ((float)$inv['tax_amount'] > 0): ?>
                            <div style="font-size:11px; color:var(--text-muted);">incl. tax R<?= number_format((float)$inv['tax_amount'], 2) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= sanitize($inv['status']) ?>">
                                <?= ucfirst($inv['status']) ?>
                            </span>
                        </td>
                        <td><?= $inv['due_date'] ? date('M j, Y', strtotime($inv['due_date'])) : '-' ?></td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
                        <td>
                            <div style="display:flex; gap:4px;">
                                <?php if ($inv['status'] === 'sent'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                                    <input type="hidden" name="status" value="paid">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-success" title="Mark Paid"><i class="fas fa-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (!in_array($inv['status'], ['cancelled', 'refunded'])): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this invoice?')">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="Cancel"><i class="fas fa-times"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" style="text-align:center; padding:40px; color:var(--text-muted);">No invoices found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display:flex; justify-content:center; gap:4px; margin-top:24px;">
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
        
        <!-- Create Invoice Modal -->
        <div id="invoiceForm" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;" class="admin-modal-overlay" onclick="if(event.target===this)this.style.display='none'">
            <div class="admin-modal" style="background:var(--bg-card); border-radius:12px; max-width:600px; width:90%; max-height:90vh; overflow-y:auto;">
                <div class="admin-modal-header" style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                    <h3>Create Invoice</h3>
                    <button onclick="document.getElementById('invoiceForm').style.display='none'" class="admin-modal-close" style="background:none; border:none; cursor:pointer; font-size:18px; color:var(--text-muted);"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" style="padding:20px;">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Client *</label>
                        <select name="client_id" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                            <option value="">Select client...</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= sanitize($c['full_name']) ?> (<?= sanitize($c['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Project (optional)</label>
                        <select name="project_id" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                            <option value="">None</option>
                            <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= sanitize($p['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                        <div class="form-group">
                            <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Amount (ZAR) *</label>
                            <input type="number" name="amount" step="0.01" min="0" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                        </div>
                        <div class="form-group">
                            <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Tax Amount</label>
                            <input type="number" name="tax_amount" step="0.01" min="0" value="0" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Description</label>
                        <textarea name="description" rows="2" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px; resize:vertical;" placeholder="e.g., Professional Services - Phase 1"></textarea>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                        <div class="form-group">
                            <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Due Date</label>
                            <input type="date" name="due_date" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Notes</label>
                        <textarea name="notes" rows="2" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px; resize:vertical;"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Terms</label>
                        <textarea name="terms" rows="2" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px; resize:vertical;"></textarea>
                    </div>
                    
                    <div style="display:flex; gap:8px; justify-content:flex-end; padding-top:16px; border-top:1px solid var(--border-color);">
                        <button type="button" onclick="document.getElementById('invoiceForm').style.display='none'" class="admin-btn admin-btn-secondary">Cancel</button>
                        <button type="submit" class="admin-btn admin-btn-primary">Create Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>