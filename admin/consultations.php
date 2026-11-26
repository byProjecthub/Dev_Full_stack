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

/* ========================================
   Actions
   ======================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif ($_POST['action'] === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = sanitize($_POST['status'] ?? '');
        $allowed = ['new', 'contacted', 'qualified', 'proposal_sent', 'converted', 'closed'];
        
        if ($id && in_array($status, $allowed)) {
            $stmt = $db->prepare("UPDATE consultations SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = 'Lead status updated to ' . ucfirst(str_replace('_', ' ', $status));
        } else {
            $error = 'Invalid status.';
        }
        redirect('consultations.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : '') . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
    } elseif ($_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM consultations WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Lead deleted.';
        }
        redirect('consultations.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    } elseif ($_POST['action'] === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $company = sanitize($_POST['company'] ?? '');
        $serviceInterest = sanitize($_POST['service_interest'] ?? '');
        $budgetRange = sanitize($_POST['budget_range'] ?? '');
        $timeline = sanitize($_POST['timeline'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
        if ($id && $name && $email) {
            $stmt = $db->prepare("UPDATE consultations SET name = ?, email = ?, phone = ?, company = ?, service_interest = ?, budget_range = ?, timeline = ?, message = ?, notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $company, $serviceInterest, $budgetRange, $timeline, $message, $notes, $id]);
            $success = 'Lead updated successfully.';
        } else {
            $error = 'Name and email are required.';
        }
        redirect('consultations.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : '') . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
    } elseif ($_POST['action'] === 'add_note') {
        $id = (int) ($_POST['id'] ?? 0);
        $noteText = sanitize($_POST['note_text'] ?? '');
        
        if ($id && $noteText) {
            // Append to existing notes with timestamp
            $existing = $db->prepare("SELECT notes FROM consultations WHERE id = ?");
            $existing->execute([$id]);
            $current = $existing->fetchColumn() ?? '';
            $newNote = ($current ? $current . "\n\n" : '') . '[' . date('Y-m-d H:i') . '] ' . sanitize($_SESSION['username'] ?? 'Admin') . ': ' . $noteText;
            
            $stmt = $db->prepare("UPDATE consultations SET notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newNote, $id]);
            $success = 'Note added.';
        }
        redirect('consultations.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : '') . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
    }
}

/* ========================================
   Pagination & Filtering
   ======================================== */

$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ["1=1"];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) FROM consultations WHERE " . implode(" AND ", $where));
$countStmt->execute($params);
$totalConsultations = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalConsultations / $perPage);

// Fetch consultations
$sql = "SELECT * FROM consultations WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$consultations = $stmt->fetchAll();

// Stats
$newCount         = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'new'")->fetchColumn();
$contactedCount   = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'contacted'")->fetchColumn();
$qualifiedCount   = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'qualified'")->fetchColumn();
$proposalCount    = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'proposal_sent'")->fetchColumn();
$convertedCount   = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'converted'")->fetchColumn();
$closedCount      = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'closed'")->fetchColumn();

// Status workflow map
$nextStatus = [
    'new'          => 'contacted',
    'contacted'    => 'qualified',
    'qualified'    => 'proposal_sent',
    'proposal_sent'=> 'converted'
];
$nextStatusLabel = [
    'new'          => 'Contact',
    'contacted'    => 'Qualify',
    'qualified'    => 'Send Proposal',
    'proposal_sent'=> 'Convert'
];

$pageTitle = 'Consultations';
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
        .consultation-row { cursor: pointer; transition: background 0.2s; }
        .consultation-row:hover { background: var(--bg-secondary); }
        .consultation-row.new { background: rgba(245, 158, 11, 0.05); }
        .consultation-row.contacted { background: rgba(59, 130, 246, 0.05); }
        .consultation-row.qualified { background: rgba(139, 92, 246, 0.05); }
        .consultation-row.proposal_sent { background: rgba(236, 72, 153, 0.05); }
        .consultation-row.converted { background: rgba(16, 185, 129, 0.05); }
        .consultation-detail { display: none; background: var(--bg-secondary); }
        .consultation-detail.expanded { display: table-row; }
        .consultation-detail td { padding: 20px; }
        .filter-tabs { display: flex; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .filter-tab:hover { background: var(--bg-secondary); }
        .filter-tab.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .filter-tab .badge { margin-left: 6px; font-size: 10px; padding: 2px 6px; border-radius: 10px; }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-contacted { background: #dbeafe; color: #1e40af; }
        .status-qualified { background: #e9d5ff; color: #6b21a8; }
        .status-proposal_sent { background: #fce7f3; color: #9d174d; }
        .status-converted { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #4b5563; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .detail-panel { background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; }
        .detail-panel h4 { margin: 0 0 12px; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .detail-field { margin-bottom: 10px; font-size: 14px; }
        .detail-field strong { color: var(--text-muted); display: inline-block; width: 100px; font-weight: 500; }
        .edit-form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .edit-form .form-group { margin-bottom: 0; }
        .edit-form textarea { grid-column: 1 / -1; }
        .action-btns { display: flex; gap: 4px; }
        .notes-log { background: #f8fafc; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 13px; line-height: 1.6; white-space: pre-wrap; max-height: 200px; overflow-y: auto; }
        .note-input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 13px; resize: vertical; min-height: 80px; }
        @media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } .edit-form { grid-template-columns: 1fr; } }
    </style>
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-shield-alt"></i> Vueports Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="consultations.php" class="active"><i class="fas fa-comments"></i> Consultations <?php if($newCount > 0): ?><span class="admin-nav-badge"><?= $newCount ?></span><?php endif; ?></a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            <a href="services.php"><i class="fas fa-briefcase"></i> Services</a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
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
            <h1>Lead Management</h1>
            <div class="admin-header-actions">
                <a href="consultations.php" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fas fa-sync"></i> Refresh</a>
            </div>
        </header>

        <?php if ($success): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            <i class="fas fa-check-circle"></i> <?= sanitize($success) ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger" style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            <i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge" style="background:var(--text-muted); color:white;"><?= $totalConsultations ?></span></a>
            <a href="?status=new" class="filter-tab <?= $statusFilter === 'new' ? 'active' : '' ?>">New <span class="badge status-new"><?= $newCount ?></span></a>
            <a href="?status=contacted" class="filter-tab <?= $statusFilter === 'contacted' ? 'active' : '' ?>">Contacted <span class="badge status-contacted"><?= $contactedCount ?></span></a>
            <a href="?status=qualified" class="filter-tab <?= $statusFilter === 'qualified' ? 'active' : '' ?>">Qualified <span class="badge status-qualified"><?= $qualifiedCount ?></span></a>
            <a href="?status=proposal_sent" class="filter-tab <?= $statusFilter === 'proposal_sent' ? 'active' : '' ?>">Proposal <span class="badge status-proposal_sent"><?= $proposalCount ?></span></a>
            <a href="?status=converted" class="filter-tab <?= $statusFilter === 'converted' ? 'active' : '' ?>">Converted <span class="badge status-converted"><?= $convertedCount ?></span></a>
            <a href="?status=closed" class="filter-tab <?= $statusFilter === 'closed' ? 'active' : '' ?>">Closed <span class="badge status-closed"><?= $closedCount ?></span></a>
        </div>

        <!-- Consultations Table -->
        <section class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Lead</th>
                        <th>Service Interest</th>
                        <th>Budget</th>
                        <th>Timeline</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations as $c): ?>
                    <tr class="consultation-row <?= sanitize($c['status']) ?>" onclick="toggleConsultation(<?= $c['id'] ?>)">
                        <td>#<?= $c['id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= sanitize($c['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= sanitize($c['email']) ?></div>
                            <?php if ($c['company']): ?>
                            <div style="font-size:11px; color:var(--text-muted);"><i class="fas fa-building"></i> <?= sanitize($c['company']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitize($c['service_interest'] ?: '-') ?></td>
                        <td><?= sanitize($c['budget_range'] ?: '-') ?></td>
                        <td><?= sanitize($c['timeline'] ?: '-') ?></td>
                        <td>
                            <span class="status-badge status-<?= sanitize($c['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $c['status'])) ?>
                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= sanitize($c['source']) ?></td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= timeAgo($c['created_at']) ?></td>
                        <td>
                            <div class="action-btns">
                                <?php if (isset($nextStatus[$c['status']])): ?>
                                <form method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $nextStatus[$c['status']] ?>">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-success" title="<?= $nextStatusLabel[$c['status']] ?>"><i class="fas fa-arrow-right"></i> <?= $nextStatusLabel[$c['status']] ?></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($c['status'] !== 'closed'): ?>
                                <form method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="status" value="closed">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-secondary" title="Close" onclick="return confirm('Close this lead?')"><i class="fas fa-archive"></i></button>
                                </form>
                                <?php endif; ?>
                                <button onclick="event.stopPropagation(); toggleConsultation(<?= $c['id'] ?>)" class="admin-btn admin-btn-sm admin-btn-secondary" title="View & Edit"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="consultation-detail" id="detail-<?= $c['id'] ?>">
                        <td colspan="9">
                            <div class="detail-grid">
                                <div>
                                    <div class="detail-panel">
                                        <h4><i class="fas fa-info-circle"></i> Lead Details</h4>
                                        <div class="detail-field"><strong>Name:</strong> <?= sanitize($c['name']) ?></div>
                                        <div class="detail-field"><strong>Email:</strong> <?= sanitize($c['email']) ?></div>
                                        <div class="detail-field"><strong>Phone:</strong> <?= sanitize($c['phone'] ?: 'N/A') ?></div>
                                        <div class="detail-field"><strong>Company:</strong> <?= sanitize($c['company'] ?: 'N/A') ?></div>
                                        <div class="detail-field"><strong>Service:</strong> <?= sanitize($c['service_interest'] ?: 'N/A') ?></div>
                                        <div class="detail-field"><strong>Budget:</strong> <?= sanitize($c['budget_range'] ?: 'N/A') ?></div>
                                        <div class="detail-field"><strong>Timeline:</strong> <?= sanitize($c['timeline'] ?: 'N/A') ?></div>
                                        <div class="detail-field"><strong>Source:</strong> <?= sanitize($c['source']) ?></div>
                                        <div class="detail-field" style="margin-top:12px;"><strong>Message:</strong></div>
                                        <div style="background:#f8fafc; border:1px solid var(--border-color); border-radius:6px; padding:12px; font-size:13px; line-height:1.6;"><?= nl2br(sanitize($c['message'] ?: 'No message')) ?></div>
                                    </div>
                                    
                                    <?php if ($c['notes']): ?>
                                    <div class="detail-panel" style="margin-top:16px;">
                                        <h4><i class="fas fa-sticky-note"></i> Admin Notes</h4>
                                        <div class="notes-log"><?= nl2br(sanitize($c['notes'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="detail-panel" style="margin-top:16px;">
                                        <h4><i class="fas fa-plus-circle"></i> Add Note</h4>
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="add_note">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <textarea name="note_text" class="note-input" placeholder="Type a note and save..." required></textarea>
                                            <button type="submit" class="admin-btn admin-btn-sm admin-btn-primary" style="margin-top:8px;"><i class="fas fa-save"></i> Add Note</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="detail-panel">
                                        <h4><i class="fas fa-edit"></i> Edit Lead</h4>
                                        <form method="POST" class="edit-form">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input type="text" name="name" value="<?= sanitize($c['name']) ?>" required class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="email" value="<?= sanitize($c['email']) ?>" required class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Phone</label>
                                                <input type="tel" name="phone" value="<?= sanitize($c['phone'] ?? '') ?>" class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Company</label>
                                                <input type="text" name="company" value="<?= sanitize($c['company'] ?? '') ?>" class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Service Interest</label>
                                                <input type="text" name="service_interest" value="<?= sanitize($c['service_interest'] ?? '') ?>" class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Budget Range</label>
                                                <input type="text" name="budget_range" value="<?= sanitize($c['budget_range'] ?? '') ?>" class="form-input">
                                            </div>
                                            <div class="form-group">
                                                <label>Timeline</label>
                                                <input type="text" name="timeline" value="<?= sanitize($c['timeline'] ?? '') ?>" class="form-input">
                                            </div>
                                            <div class="form-group" style="grid-column:1/-1;">
                                                <label>Message</label>
                                                <textarea name="message" rows="3" class="form-textarea"><?= sanitize($c['message'] ?? '') ?></textarea>
                                            </div>
                                            <div class="form-group" style="grid-column:1/-1;">
                                                <label>Admin Notes</label>
                                                <textarea name="notes" rows="3" class="form-textarea"><?= sanitize($c['notes'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <div style="grid-column:1/-1; display:flex; gap:8px; justify-content:flex-end;">
                                                <button type="button" onclick="toggleConsultation(<?= $c['id'] ?>)" class="admin-btn admin-btn-secondary">Close</button>
                                                <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color);">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this lead?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger"><i class="fas fa-trash"></i> Delete Lead</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($consultations)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:60px; color:var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.3;"></i>
                            No leads found
                        </td>
                    </tr>
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
    </main>

    <script>
        function toggleConsultation(id) {
            const detail = document.getElementById('detail-' + id);
            detail.classList.toggle('expanded');
            document.querySelectorAll('.consultation-detail.expanded').forEach(el => {
                if (el.id !== 'detail-' + id) el.classList.remove('expanded');
            });
        }
    </script>
</body>
</html>