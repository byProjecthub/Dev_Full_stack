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

// Mark single as read
if (isset($_GET['mark']) && is_numeric($_GET['mark']) && isset($_GET['csrf']) && verifyCsrf($_GET['csrf'])) {
    $stmt = $db->prepare("UPDATE messages SET status = 'read', updated_at = NOW() WHERE id = ?");
    $stmt->execute([(int)$_GET['mark']]);
    $success = 'Message marked as read.';
    redirect('messages.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : ''));
}

// Mark all as read
if (isset($_GET['mark_all']) && isset($_GET['csrf']) && verifyCsrf($_GET['csrf'])) {
    $db->query("UPDATE messages SET status = 'read', updated_at = NOW() WHERE status = 'new'");
    $success = 'All messages marked as read.';
    redirect('messages.php');
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete']) && isset($_GET['csrf']) && verifyCsrf($_GET['csrf'])) {
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    $success = 'Message deleted.';
    redirect('messages.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : ''));
}

// Reply via email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_to'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $replyId = (int) ($_POST['reply_to'] ?? 0);
        $replyMessage = sanitize($_POST['reply_message'] ?? '');
        
        if ($replyId && !empty($replyMessage)) {
            $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$replyId]);
            $original = $stmt->fetch();
            
            if ($original) {
                $to = $original['email'];
                $subject = 'Re: ' . ($original['subject'] ?: 'Your inquiry');
                $body = "Hi " . $original['name'] . ",\n\n" . $replyMessage . "\n\n---\nVueports Solutions\n" . ($currentSettings['contact_email'] ?? 'info@vueports.com');
                $headers = "From: " . ($currentSettings['smtp_from'] ?? 'info@vueports.com') . "\r\nReply-To: " . ($currentSettings['contact_email'] ?? 'info@vueports.com');
                
                @mail($to, $subject, $body, $headers);
                
                $stmt = $db->prepare("UPDATE messages SET status = 'replied', replied_by = ?, replied_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id'] ?? 0, $replyId]);
                
                // Also log to email_queue for audit
                $stmt2 = $db->prepare("INSERT INTO email_queue (to_email, to_name, subject, body_text, from_email, from_name, status, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
                $stmt2->execute([$to, $original['name'], $subject, $replyMessage, $currentSettings['smtp_from'] ?? 'info@vueports.com', 'Vueports Admin', 'sent']);
                
                $success = 'Reply sent to ' . sanitize($to);
            }
        } else {
            $error = 'Reply message is required.';
        }
    }
    redirect('messages.php');
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
$countStmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE " . implode(" AND ", $where));
$countStmt->execute($params);
$totalMessages = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalMessages / $perPage);

// Fetch messages
$sql = "SELECT m.*, a.name as replied_by_name, a.username as replied_by_username 
        FROM messages m 
        LEFT JOIN admins a ON m.replied_by = a.id 
        WHERE " . implode(" AND ", $where) . " 
        ORDER BY m.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Stats
$newCount     = (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn();
$readCount    = (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'read'")->fetchColumn();
$repliedCount = (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'replied'")->fetchColumn();
$archivedCount= (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'archived'")->fetchColumn();
$spamCount    = (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'spam'")->fetchColumn();

$pageTitle = 'Messages';
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
        .message-row { cursor: pointer; transition: background 0.2s; }
        .message-row:hover { background: var(--bg-secondary); }
        .message-row.new { background: rgba(99, 102, 241, 0.05); }
        .message-detail { display: none; background: var(--bg-secondary); }
        .message-detail.expanded { display: table-row; }
        .message-detail td { padding: 20px; }
        .message-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .filter-tabs { display: flex; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .filter-tab:hover { background: var(--bg-secondary); }
        .filter-tab.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .filter-tab .badge { margin-left: 6px; font-size: 10px; padding: 2px 6px; border-radius: 10px; }
        .status-new { background: #fef3c7; color: #92400e; }
        .status-read { background: #dbeafe; color: #1e40af; }
        .status-replied { background: #d1fae5; color: #065f46; }
        .status-archived { background: #f3f4f6; color: #6b7280; }
        .status-spam { background: #fee2e2; color: #991b1b; }
        .reply-box { background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-top: 12px; }
        .reply-box textarea { width: 100%; min-height: 100px; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical; }
        .reply-box textarea:focus { outline: none; border-color: var(--color-primary); }
    </style>
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-shield-alt"></i> Vueports Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages <?php if($newCount > 0): ?><span class="admin-nav-badge"><?= $newCount ?></span><?php endif; ?></a>
            <a href="consultations.php"><i class="fas fa-comments"></i> Consultations</a>
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
            <h1>Contact Messages</h1>
            <div class="admin-header-actions">
                <?php if ($newCount > 0): ?>
                <a href="?mark_all=1&csrf=<?= csrfToken() ?>" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="return confirm('Mark all <?= $newCount ?> new messages as read?')">
                    <i class="fas fa-check-double"></i> Mark All Read
                </a>
                <?php endif; ?>
                <a href="messages.php" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fas fa-sync"></i> Refresh</a>
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
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge" style="background:var(--text-muted); color:white;"><?= $totalMessages ?></span></a>
            <a href="?status=new" class="filter-tab <?= $statusFilter === 'new' ? 'active' : '' ?>">New <?php if($newCount > 0): ?><span class="badge status-new"><?= $newCount ?></span><?php endif; ?></a>
            <a href="?status=read" class="filter-tab <?= $statusFilter === 'read' ? 'active' : '' ?>">Read <span class="badge status-read"><?= $readCount ?></span></a>
            <a href="?status=replied" class="filter-tab <?= $statusFilter === 'replied' ? 'active' : '' ?>">Replied <span class="badge status-replied"><?= $repliedCount ?></span></a>
            <a href="?status=archived" class="filter-tab <?= $statusFilter === 'archived' ? 'active' : '' ?>">Archived <span class="badge status-archived"><?= $archivedCount ?></span></a>
            <a href="?status=spam" class="filter-tab <?= $statusFilter === 'spam' ? 'active' : '' ?>">Spam <span class="badge status-spam"><?= $spamCount ?></span></a>
        </div>

        <!-- Messages Table -->
        <section class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr class="message-row <?= sanitize($m['status']) ?>" onclick="toggleMessage(<?= $m['id'] ?>)">
                        <td>
                            <div style="font-weight:600;"><?= sanitize($m['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= sanitize($m['email']) ?></div>
                        </td>
                        <td><?= sanitize($m['subject'] ?: '(No subject)') ?></td>
                        <td><span class="status-badge status-<?= sanitize($m['status']) ?>"><?= ucfirst($m['status']) ?></span></td>
                        <td style="font-size:12px; color:var(--text-muted); white-space:nowrap;"><?= timeAgo($m['created_at']) ?></td>
                        <td>
                            <div style="display:flex; gap:4px;">
                                <?php if ($m['status'] === 'new'): ?>
                                <a href="?mark=<?= $m['id'] ?>&page=<?= $page ?>&csrf=<?= csrfToken() ?>" class="admin-btn admin-btn-sm admin-btn-success" onclick="event.stopPropagation()" title="Mark as read"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <a href="mailto:<?= sanitize($m['email']) ?>?subject=Re: <?= urlencode($m['subject'] ?: 'Your inquiry') ?>" class="admin-btn admin-btn-sm admin-btn-primary" onclick="event.stopPropagation()" title="Reply via email"><i class="fas fa-reply"></i></a>
                                <a href="?delete=<?= $m['id'] ?>&page=<?= $page ?>&csrf=<?= csrfToken() ?>" class="admin-btn admin-btn-sm admin-btn-danger" onclick="event.stopPropagation(); return confirm('Delete this message?')" title="Delete"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <tr class="message-detail" id="detail-<?= $m['id'] ?>">
                        <td colspan="5">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                                <div>
                                    <h4 style="margin-bottom:12px; font-size:14px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Message</h4>
                                    <div style="background:white; border:1px solid var(--border-color); border-radius:8px; padding:16px; line-height:1.7; color:var(--text-primary);">
                                        <?= nl2br(sanitize($m['message'])) ?>
                                    </div>
                                    <div style="margin-top:12px; font-size:12px; color:var(--text-muted);">
                                        <i class="fas fa-clock"></i> Received <?= date('F j, Y \\a\\t g:i A', strtotime($m['created_at'])) ?>
                                        <?php if ($m['phone']): ?> | <i class="fas fa-phone"></i> <?= sanitize($m['phone']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:12px; font-size:14px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Quick Reply</h4>
                                    <form method="POST" class="reply-box">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="reply_to" value="<?= $m['id'] ?>">
                                        <textarea name="reply_message" placeholder="Type your reply here..."></textarea>
                                        <div class="message-actions">
                                            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fas fa-paper-plane"></i> Send Reply</button>
                                            <a href="mailto:<?= sanitize($m['email']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="fas fa-external-link-alt"></i> Open in Email Client</a>
                                        </div>
                                    </form>
                                    <?php if ($m['status'] === 'replied' && $m['replied_at']): ?>
                                    <div style="margin-top:16px; padding:12px; background:#ecfdf5; border-radius:8px; font-size:13px;">
                                        <strong><i class="fas fa-check-circle" style="color:#10b981;"></i> Replied <?= timeAgo($m['replied_at']) ?> by <?= sanitize($m['replied_by_name'] ?: $m['replied_by_username'] ?: 'Admin') ?>:</strong><br>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding:60px; color:var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.3;"></i>
                            No messages found
                        </td>
                    </tr>
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

    <script>
        function toggleMessage(id) {
            const detail = document.getElementById('detail-' + id);
            detail.classList.toggle('expanded');
            document.querySelectorAll('.message-detail.expanded').forEach(el => {
                if (el.id !== 'detail-' + id) el.classList.remove('expanded');
            });
        }
    </script>
</body>
</html>