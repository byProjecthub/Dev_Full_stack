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
    } elseif ($_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("UPDATE clients SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Client deactivated';
        }
        redirect('clients.php');
    } elseif ($_POST['action'] === 'create_invoice') {
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $amount = (float) ($_POST['amount'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        
        if ($clientId && $amount > 0) {
            $count = (int) $db->query("SELECT COUNT(*) FROM invoices WHERE EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM NOW())")->fetchColumn();
            $invoiceNum = 'INV-' . date('Y') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
            
            $stmt = $db->prepare("INSERT INTO invoices (client_id, invoice_number, amount, description, status, due_date, created_at, updated_at) VALUES (?,?,?,?,?,(NOW() + INTERVAL '14 day'),NOW(),NOW())");
            $stmt->execute([$clientId, $invoiceNum, $amount, $description, 'sent']);
            $success = "Invoice {$invoiceNum} created";
        } else {
            $error = 'Client and valid amount are required.';
        }
        redirect('clients.php');
    }
}

// Pagination & Search
$search = sanitize($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR company_name LIKE ? OR phone LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM clients WHERE " . implode(" AND ", $where));
$countStmt->execute($params);
$totalClients = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalClients / $perPage);

$sql = "SELECT * FROM clients WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

$pageTitle = 'Clients';
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
        .status-active { background: #d1fae5; color: #065f46; }
        .status-suspended { background: #fef3c7; color: #92400e; }
        .status-inactive { background: #f3f4f6; color: #6b7280; }
        .admin-search { position: relative; }
        .admin-search input { padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); font-size: 13px; width: 240px; }
        .admin-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 12px; }
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
            <a href="clients.php" class="active"><i class="fas fa-users"></i> Clients</a>
            <a href="invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
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
            <h1>Clients</h1>
            <div class="admin-header-actions" style="display:flex; gap:12px; align-items:center;">
                <form method="GET" style="display:flex; gap:8px;">
                    <div class="admin-search">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search clients..." value="<?= sanitize($search) ?>" id="searchClients">
                    </div>
                    <?php if ($search): ?>
                    <a href="clients.php" class="admin-btn admin-btn-sm admin-btn-secondary"><i class="fas fa-times"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </header>

        <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-check-circle"></i> <?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>

        <section class="admin-section">
            <table class="admin-table" id="clientsTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Company</th><th>Phone</th>
                        <th>Projects</th><th>Status</th><th>Joined</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): 
                        $stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
                        $stmt->execute([$c['id']]);
                        $projectCount = (int) $stmt->fetchColumn();
                    ?>
                    <tr>
                        <td>#<?= $c['id'] ?></td>
                        <td><strong><?= sanitize($c['full_name']) ?></strong></td>
                        <td><a href="mailto:<?= sanitize($c['email']) ?>" style="color:var(--color-primary); text-decoration:none;"><?= sanitize($c['email']) ?></a></td>
                        <td><?= sanitize($c['company_name'] ?: '-') ?></td>
                        <td><?= sanitize($c['phone'] ?: '-') ?></td>
                        <td><?= $projectCount ?></td>
                        <td><span class="status-badge status-<?= sanitize($c['status']) ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                        <td>
                            <button onclick="showInvoiceModal(<?= $c['id'] ?>, '<?= sanitize($c['full_name']) ?>')" class="admin-btn admin-btn-sm admin-btn-secondary" title="Create Invoice">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </button>
                            <?php if ($c['status'] !== 'inactive'): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this client?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="Deactivate">
                                    <i class="fas fa-user-slash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clients)): ?>
                    <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--text-muted);">No clients found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination" style="display:flex; justify-content:center; gap:4px; margin-top:24px;">
                <?php if ($page > 1): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="admin-btn admin-btn-sm admin-btn-secondary"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                <span style="padding:8px 14px; border-radius:6px; font-size:13px; background:var(--color-primary); color:white;"><?= $i ?></span>
                <?php else: ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $i ?>" style="padding:8px 14px; border-radius:6px; font-size:13px; text-decoration:none; background:var(--bg-card); color:var(--text-primary); border:1px solid var(--border-color);"><?= $i ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="admin-btn admin-btn-sm admin-btn-secondary"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Invoice Modal -->
    <div class="admin-modal-overlay" id="invoiceModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
        <div class="admin-modal" style="background:var(--bg-card); border-radius:12px; max-width:500px; width:90%; max-height:90vh; overflow-y:auto;">
            <div class="admin-modal-header" style="padding:16px 20px; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
                <h3>Create Invoice</h3>
                <button onclick="closeInvoiceModal()" class="admin-modal-close" style="background:none; border:none; cursor:pointer; font-size:18px; color:var(--text-muted);"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div class="admin-modal-body" style="padding:20px;">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="create_invoice">
                    <input type="hidden" name="client_id" id="invoiceClientId">
                    
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Client</label>
                        <input type="text" id="invoiceClientName" readonly style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-secondary); color:var(--text-muted); font-size:14px;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Amount (ZAR) *</label>
                        <input type="number" name="amount" step="0.01" min="0" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px;">
                    </div>
                    <div class="form-group" style="margin-bottom:16px;">
                        <label style="display:block; font-size:13px; font-weight:500; margin-bottom:6px; color:var(--text-secondary);">Description</label>
                        <textarea name="description" rows="3" style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); font-size:14px; resize:vertical;" placeholder="e.g., Professional Services - Web Development"></textarea>
                    </div>
                </div>
                <div class="admin-modal-footer" style="padding:16px 20px; border-top:1px solid var(--border-color); display:flex; gap:8px; justify-content:flex-end;">
                    <button type="button" onclick="closeInvoiceModal()" class="admin-btn admin-btn-secondary">Cancel</button>
                    <button type="submit" class="admin-btn admin-btn-primary">Create Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showInvoiceModal(clientId, clientName) {
        document.getElementById('invoiceClientId').value = clientId;
        document.getElementById('invoiceClientName').value = clientName;
        document.getElementById('invoiceModal').style.display = 'flex';
    }
    function closeInvoiceModal() {
        document.getElementById('invoiceModal').style.display = 'none';
    }
    document.getElementById('invoiceModal').addEventListener('click', function(e) {
        if (e.target === this) closeInvoiceModal();
    });
    </script>
</body>
</html>