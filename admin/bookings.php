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
        $allowed = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
        
        if ($id && in_array($status, $allowed)) {
            $stmt = $db->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = 'Booking status updated to ' . ucfirst($status);
        } else {
            $error = 'Invalid status.';
        }
        redirect('bookings.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : '') . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
    } elseif ($_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Booking deleted.';
        }
        redirect('bookings.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    } elseif ($_POST['action'] === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $company = sanitize($_POST['company'] ?? '');
        $bookingDate = $_POST['booking_date'] ?? '';
        $bookingTime = $_POST['booking_time'] ?? '';
        $serviceType = sanitize($_POST['service_type'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        $duration = (int) ($_POST['duration_minutes'] ?? 60);
        
        if ($id && $name && $email && $bookingDate && $bookingTime) {
            $stmt = $db->prepare("UPDATE bookings SET name = ?, email = ?, phone = ?, company = ?, booking_date = ?, booking_time = ?, service_type = ?, notes = ?, duration_minutes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $company, $bookingDate, $bookingTime, $serviceType, $notes, $duration, $id]);
            $success = 'Booking updated successfully.';
        } else {
            $error = 'Name, email, date and time are required.';
        }
        redirect('bookings.php' . (!empty($_GET['page']) ? '?page=' . (int)$_GET['page'] : '') . (!empty($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''));
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
$countStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE " . implode(" AND ", $where));
$countStmt->execute($params);
$totalBookings = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalBookings / $perPage);

// Fetch bookings
$sql = "SELECT * FROM bookings WHERE " . implode(" AND ", $where) . " ORDER BY booking_date DESC, booking_time DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Stats
$pendingCount   = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmedCount = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$completedCount = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$cancelledCount = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();
$noShowCount    = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'no_show'")->fetchColumn();

$pageTitle = 'Bookings';
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
        .booking-row { cursor: pointer; transition: background 0.2s; }
        .booking-row:hover { background: var(--bg-secondary); }
        .booking-row.pending { background: rgba(245, 158, 11, 0.05); }
        .booking-row.confirmed { background: rgba(59, 130, 246, 0.05); }
        .booking-row.completed { background: rgba(16, 185, 129, 0.05); }
        .booking-row.cancelled { background: rgba(239, 68, 68, 0.05); }
        .booking-row.no_show { background: rgba(107, 114, 128, 0.05); }
        .booking-detail { display: none; background: var(--bg-secondary); }
        .booking-detail.expanded { display: table-row; }
        .booking-detail td { padding: 20px; }
        .filter-tabs { display: flex; gap: 4px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab { padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
        .filter-tab:hover { background: var(--bg-secondary); }
        .filter-tab.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .filter-tab .badge { margin-left: 6px; font-size: 10px; padding: 2px 6px; border-radius: 10px; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-no_show { background: #f3f4f6; color: #4b5563; }
        .edit-form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .edit-form .form-group { margin-bottom: 0; }
        .edit-form textarea { grid-column: 1 / -1; }
        .action-btns { display: flex; gap: 4px; }
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
            <a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> Bookings <?php if($pendingCount > 0): ?><span class="admin-nav-badge"><?= $pendingCount ?></span><?php endif; ?></a>
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
            <h1>Booking Management</h1>
            <div class="admin-header-actions">
                <a href="bookings.php" class="admin-btn admin-btn-primary admin-btn-sm"><i class="fas fa-sync"></i> Refresh</a>
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
            <a href="?status=all" class="filter-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">All <span class="badge" style="background:var(--text-muted); color:white;"><?= $totalBookings ?></span></a>
            <a href="?status=pending" class="filter-tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">Pending <span class="badge status-pending"><?= $pendingCount ?></span></a>
            <a href="?status=confirmed" class="filter-tab <?= $statusFilter === 'confirmed' ? 'active' : '' ?>">Confirmed <span class="badge status-confirmed"><?= $confirmedCount ?></span></a>
            <a href="?status=completed" class="filter-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed <span class="badge status-completed"><?= $completedCount ?></span></a>
            <a href="?status=cancelled" class="filter-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">Cancelled <span class="badge status-cancelled"><?= $cancelledCount ?></span></a>
            <a href="?status=no_show" class="filter-tab <?= $statusFilter === 'no_show' ? 'active' : '' ?>">No Show <span class="badge status-no_show"><?= $noShowCount ?></span></a>
        </div>

        <!-- Bookings Table -->
        <section class="admin-section">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Booked</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr class="booking-row <?= sanitize($b['status']) ?>" onclick="toggleBooking(<?= $b['id'] ?>)">
                        <td>#<?= $b['id'] ?></td>
                        <td>
                            <div style="font-weight:600;"><?= sanitize($b['name']) ?></div>
                            <div style="font-size:12px; color:var(--text-muted);"><?= sanitize($b['email']) ?></div>
                            <?php if ($b['company']): ?><div style="font-size:11px; color:var(--text-muted);"><i class="fas fa-building"></i> <?= sanitize($b['company']) ?></div><?php endif; ?>
                        </td>
                        <td><?= sanitize($b['service_type'] ?: '-') ?></td>
                        <td style="white-space:nowrap;">
                            <i class="fas fa-calendar" style="color:var(--color-primary);"></i> <?= date('M j, Y', strtotime($b['booking_date'])) ?><br>
                            <i class="fas fa-clock" style="color:var(--text-muted);"></i> <?= date('g:i A', strtotime($b['booking_time'])) ?>
                        </td>
                        <td><?= (int)($b['duration_minutes'] ?? 60) ?> min</td>
                        <td>
                            <span class="status-badge status-<?= sanitize($b['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', $b['status'])) ?>
                            </span>
                        </td>
                        <td style="font-size:12px; color:var(--text-muted);"><?= timeAgo($b['created_at']) ?></td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <?php if ($b['status'] === 'pending'): ?>
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-success" title="Confirm"><i class="fas fa-check"></i></button>
                                    <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-primary" title="Complete"><i class="fas fa-check-double"></i></button>
                                    <?php elseif ($b['status'] === 'completed'): ?>
                                    <input type="hidden" name="status" value="no_show">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-secondary" title="Mark No-Show" onclick="return confirm('Mark as no-show?')"><i class="fas fa-user-clock"></i></button>
                                    <?php endif; ?>
                                </form>
                                <?php if (!in_array($b['status'], ['cancelled', 'no_show'])): ?>
                                <form method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="Cancel" onclick="return confirm('Cancel this booking?')"><i class="fas fa-times"></i></button>
                                </form>
                                <?php endif; ?>
                                <button onclick="event.stopPropagation(); toggleBooking(<?= $b['id'] ?>)" class="admin-btn admin-btn-sm admin-btn-secondary" title="Edit"><i class="fas fa-edit"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="booking-detail" id="detail-<?= $b['id'] ?>">
                        <td colspan="8">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                                <div>
                                    <h4 style="margin-bottom:12px; font-size:14px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Booking Details</h4>
                                    <div style="background:white; border:1px solid var(--border-color); border-radius:8px; padding:16px;">
                                        <div style="margin-bottom:8px;"><strong>Phone:</strong> <?= sanitize($b['phone'] ?: 'N/A') ?></div>
                                        <div style="margin-bottom:8px;"><strong>Company:</strong> <?= sanitize($b['company'] ?: 'N/A') ?></div>
                                        <div style="margin-bottom:8px;"><strong>Service:</strong> <?= sanitize($b['service_type'] ?: 'Not specified') ?></div>
                                        <div style="margin-bottom:8px;"><strong>Date:</strong> <?= date('l, F j, Y', strtotime($b['booking_date'])) ?></div>
                                        <div style="margin-bottom:8px;"><strong>Time:</strong> <?= date('g:i A', strtotime($b['booking_time'])) ?> (<?= sanitize($b['timezone'] ?? 'Africa/Johannesburg') ?>)</div>
                                        <div style="margin-bottom:8px;"><strong>Duration:</strong> <?= (int)($b['duration_minutes'] ?? 60) ?> minutes</div>
                                        <div><strong>Notes:</strong> <?= nl2br(sanitize($b['notes'] ?: 'No notes')) ?></div>
                                        <?php if ($b['meeting_link']): ?>
                                        <div style="margin-top:8px;"><strong>Meeting Link:</strong> <a href="<?= sanitize($b['meeting_link']) ?>" target="_blank"><?= sanitize($b['meeting_link']) ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top:12px; font-size:12px; color:var(--text-muted);">
                                        <i class="fas fa-clock"></i> Booked <?= timeAgo($b['created_at']) ?>
                                        <?php if ($b['updated_at'] != $b['created_at']): ?> | <i class="fas fa-pen"></i> Updated <?= timeAgo($b['updated_at']) ?><?php endif; ?>
                                    </div>
                                </div>
                                <div>
                                    <h4 style="margin-bottom:12px; font-size:14px; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px;">Edit Booking</h4>
                                    <form method="POST" class="edit-form">
                                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        
                                        <div class="form-group">
                                            <label>Name</label>
                                            <input type="text" name="name" value="<?= sanitize($b['name']) ?>" required class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" value="<?= sanitize($b['email']) ?>" required class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="tel" name="phone" value="<?= sanitize($b['phone'] ?? '') ?>" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Company</label>
                                            <input type="text" name="company" value="<?= sanitize($b['company'] ?? '') ?>" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Service Type</label>
                                            <input type="text" name="service_type" value="<?= sanitize($b['service_type'] ?? '') ?>" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Duration (min)</label>
                                            <input type="number" name="duration_minutes" value="<?= (int)($b['duration_minutes'] ?? 60) ?>" min="15" max="480" class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Date</label>
                                            <input type="date" name="booking_date" value="<?= $b['booking_date'] ?>" required class="form-input">
                                        </div>
                                        <div class="form-group">
                                            <label>Time</label>
                                            <input type="time" name="booking_time" value="<?= $b['booking_time'] ?>" required class="form-input">
                                        </div>
                                        <div class="form-group" style="grid-column:1/-1;">
                                            <label>Notes</label>
                                            <textarea name="notes" rows="3" class="form-textarea"><?= sanitize($b['notes'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div style="grid-column:1/-1; display:flex; gap:8px; justify-content:flex-end;">
                                            <button type="button" onclick="toggleBooking(<?= $b['id'] ?>)" class="admin-btn admin-btn-secondary">Close</button>
                                            <button type="submit" class="admin-btn admin-btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                                        </div>
                                    </form>
                                    
                                    <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color);">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this booking?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger"><i class="fas fa-trash"></i> Delete Booking</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:60px; color:var(--text-muted);">
                            <i class="fas fa-calendar-times" style="font-size:3rem; margin-bottom:16px; display:block; opacity:0.3;"></i>
                            No bookings found
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
        function toggleBooking(id) {
            const detail = document.getElementById('detail-' + id);
            detail.classList.toggle('expanded');
            document.querySelectorAll('.booking-detail.expanded').forEach(el => {
                if (el.id !== 'detail-' + id) el.classList.remove('expanded');
            });
        }
    </script>
</body>
</html>