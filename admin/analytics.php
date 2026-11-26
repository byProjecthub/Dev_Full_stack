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

/* ========================================
   Helpers
   ======================================== */

function formatDuration(int $seconds): string {
    $m = (int) floor($seconds / 60);
    $s = $seconds % 60;
    return sprintf('%02d:%02d', $m, $s);
}

function csvExport(PDO $db, string $type, string $since, string $until): void {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');
    
    $out = fopen('php://output', 'w');
    
    switch ($type) {
        case 'consultations':
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Subject', 'Service Interest', 'Status', 'Created At']);
            $stmt = $db->prepare("SELECT id, name, email, phone, subject, service_interest, status, created_at 
                                  FROM consultations WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$since, $until]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
            break;
            
        case 'bookings':
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Date', 'Time', 'Service', 'Status', 'Created At']);
            $stmt = $db->prepare("SELECT id, name, email, phone, booking_date, booking_time, service_type, status, created_at 
                                  FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$since, $until]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
            break;
            
        case 'payments':
            fputcsv($out, ['ID', 'Client ID', 'Amount', 'Method', 'Status', 'Reference', 'Created At']);
            $stmt = $db->prepare("SELECT id, client_id, amount, gateway AS payment_method, payment_status, gateway_transaction_id AS reference, created_at FROM payments WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$since, $until]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
            break;
            
        case 'pageviews':
            fputcsv($out, ['ID', 'Page Path', 'Referrer', 'Device', 'IP Hash', 'Created At']);
            $stmt = $db->prepare("SELECT id, page_path, referrer, device_type, ip_hash, created_at 
                                  FROM page_views WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$since, $until]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) fputcsv($out, $row);
            break;
    }
    fclose($out);
    exit;
}

/* ========================================
   Date Range Handling
   ======================================== */

$range = $_GET['range'] ?? '30days';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

switch ($range) {
    case '7days':
        $since = date('Y-m-d', strtotime('-7 days'));
        $until = date('Y-m-d');
        $label = 'Last 7 Days';
        break;
    case '90days':
        $since = date('Y-m-d', strtotime('-90 days'));
        $until = date('Y-m-d');
        $label = 'Last 90 Days';
        break;
    case 'year':
        $since = date('Y-m-01', strtotime('-11 months'));
        $until = date('Y-m-d');
        $label = 'Last 12 Months';
        break;
    case 'custom':
        $since = (!empty($customStart) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $customStart)) ? $customStart : date('Y-m-d', strtotime('-30 days'));
        $until = (!empty($customEnd) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $customEnd)) ? $customEnd : date('Y-m-d');
        $label = date('M j, Y', strtotime($since)) . ' – ' . date('M j, Y', strtotime($until));
        break;
    case '30days':
    default:
        $since = date('Y-m-d', strtotime('-30 days'));
        $until = date('Y-m-d');
        $label = 'Last 30 Days';
        break;
}

/* ========================================
   CSV Export Handler
   ======================================== */

if (!empty($_GET['export']) && in_array($_GET['export'], ['consultations', 'bookings', 'payments', 'pageviews'])) {
    csvExport($db, $_GET['export'], $since, $until);
}

/* ========================================
   Analytics Queries (Prepared Statements)
   ======================================== */

// Traffic
$stmt = $db->prepare("SELECT COUNT(*) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$totalViews = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT ip_hash) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$uniqueVisitors = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT AVG(duration_seconds) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$avgDuration = (int) round((float) ($stmt->fetchColumn() ?: 0));

// Top pages
$stmt = $db->prepare("SELECT page_path, COUNT(*) as views FROM page_views WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY page_path ORDER BY views DESC LIMIT 10");
$stmt->execute([$since, $until]);
$topPages = $stmt->fetchAll();

// Traffic sources
$stmt = $db->prepare("SELECT CASE 
    WHEN referrer = '' OR referrer IS NULL THEN 'Direct' 
    WHEN referrer LIKE '%google%' THEN 'Google' 
    WHEN referrer LIKE '%facebook%' THEN 'Facebook' 
    WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn' 
    WHEN referrer LIKE '%twitter%' OR referrer LIKE '%x.com%' THEN 'Twitter/X' 
    ELSE 'Other' 
END as source, COUNT(*) as count 
FROM page_views WHERE DATE(created_at) BETWEEN ? AND ? 
GROUP BY source ORDER BY count DESC");
$stmt->execute([$since, $until]);
$sources = $stmt->fetchAll();

// Device types
$stmt = $db->prepare("SELECT device_type, COUNT(*) as count FROM page_views WHERE DATE(created_at) BETWEEN ? AND ? AND device_type IS NOT NULL GROUP BY device_type ORDER BY count DESC");
$stmt->execute([$since, $until]);
$devices = $stmt->fetchAll();

// Conversions
$stmt = $db->prepare("SELECT COUNT(*) FROM consultations WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$consultations = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM consultations WHERE status = 'new' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$newConsultations = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$bookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$confirmedBookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$messages = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM calculator_leads WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$calcLeads = (int) $stmt->fetchColumn();

// Payments
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$paymentStats = $stmt->fetch();
$totalPayments = (int) ($paymentStats['count'] ?? 0);
$revenue = (float) ($paymentStats['total'] ?? 0);

// Invoices
$stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM invoices WHERE status = 'paid' AND DATE(paid_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$invoiceStats = $stmt->fetch();
$paidInvoices = (int) ($invoiceStats['count'] ?? 0);
$invoiceRevenue = (float) ($invoiceStats['total'] ?? 0);

// Outstanding
$outstanding = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status IN ('sent', 'overdue')")->fetchColumn();

// Clients
$totalClients = (int) $db->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM clients WHERE status = 'active' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$newClients = (int) $stmt->fetchColumn();

// Projects
$projectStats = $db->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status")->fetchAll();
$totalProjects = array_sum(array_column($projectStats, 'count'));

// Daily trend
$trendDays = min(30, (int) ((strtotime($until) - strtotime($since)) / 86400) + 1);
$trendData = [];
for ($i = $trendDays - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days", strtotime($until)));
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = ?");
    $stmt->execute([$d]);
    $views = (int) $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM consultations WHERE DATE(created_at) = ?");
    $stmt->execute([$d]);
    $cons = (int) $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND DATE(created_at) = ?");
    $stmt->execute([$d]);
    $pays = (float) $stmt->fetchColumn();
    
    $trendData[] = ['date' => $d, 'views' => $views, 'consultations' => $cons, 'revenue' => $pays];
}

// Service interest breakdown
$stmt = $db->prepare("SELECT service_interest, COUNT(*) as count FROM consultations WHERE DATE(created_at) BETWEEN ? AND ? AND service_interest != '' GROUP BY service_interest ORDER BY count DESC");
$stmt->execute([$since, $until]);
$serviceInterest = $stmt->fetchAll();

// Email performance
$stmt = $db->prepare("SELECT status, COUNT(*) as count FROM email_logs WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$since, $until]);
$emailStats = $stmt->fetchAll();

$pageTitle = 'Analytics';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($pageTitle) ?> | Vueports Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chart-bar { display:flex; align-items:flex-end; gap:4px; height:120px; padding:16px 0; }
        .chart-bar-item { flex:1; background:var(--color-primary-200); border-radius:4px 4px 0 0; min-height:4px; transition:all 0.3s; position:relative; }
        .chart-bar-item:hover { background:var(--color-accent); }
        .chart-bar-item::after { content:attr(data-value); position:absolute; top:-20px; left:50%; transform:translateX(-50%); font-size:10px; opacity:0; transition:opacity 0.3s; }
        .chart-bar-item:hover::after { opacity:1; }
        .donut-chart { width:120px; height:120px; border-radius:50%; position:relative; }
        .donut-hole { position:absolute; inset:30px; background:var(--bg-card); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; }
        .progress-ring { transform:rotate(-90deg); }
        .mini-chart { height:40px; display:flex; align-items:flex-end; gap:2px; }
        .mini-chart span { flex:1; background:var(--color-accent); border-radius:2px 2px 0 0; opacity:0.6; }
        .comparison { display:flex; align-items:center; gap:8px; font-size:13px; }
        .comparison.up { color:var(--color-success); }
        .comparison.down { color:var(--color-danger); }
        .funnel-step { display:flex; align-items:center; gap:16px; padding:12px 0; border-bottom:1px solid var(--border-color); }
        .funnel-step:last-child { border-bottom:none; }
        .funnel-bar { height:32px; background:var(--color-primary-100); border-radius:6px; display:flex; align-items:center; padding:0 12px; font-weight:600; font-size:13px; color:var(--color-primary); transition:width 0.5s ease; }
        .funnel-label { width:140px; font-weight:500; font-size:14px; color:var(--text-secondary); flex-shrink:0; }
        .funnel-value { width:60px; text-align:right; font-weight:700; font-size:14px; flex-shrink:0; }
        .funnel-pct { width:50px; text-align:right; font-size:12px; color:var(--text-muted); flex-shrink:0; }
    </style>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
<aside class="admin-sidebar">
    <div class="admin-brand"><i class="fas fa-chart-line"></i> Vueports Admin</div>
    <nav class="admin-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="consultations.php"><i class="fas fa-comments"></i> Consultations</a>
        <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
        <a href="services.php"><i class="fas fa-briefcase"></i> Services</a>
        <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
        <a href="invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
        <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="analytics.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a>
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
        <h1>Analytics Dashboard</h1>
        <div style="display:flex; align-items:center; gap:16px;">
            <form method="GET" style="display:flex; gap:8px; align-items:center;">
                <select name="range" onchange="this.form.submit()" class="form-select" style="width:auto; padding:8px 12px;">
                    <option value="7days" <?= $range === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="30days" <?= $range === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90days" <?= $range === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="year" <?= $range === 'year' ? 'selected' : '' ?>>Last 12 Months</option>
                    <option value="custom" <?= $range === 'custom' ? 'selected' : '' ?>>Custom</option>
                </select>
                <?php if ($range === 'custom'): ?>
                <input type="date" name="start" value="<?= sanitize($since) ?>" class="form-input" style="width:auto; padding:8px 12px;">
                <input type="date" name="end" value="<?= sanitize($until) ?>" class="form-input" style="width:auto; padding:8px 12px;">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm">Apply</button>
                <?php endif; ?>
            </form>
            <span class="badge badge-primary"><?= sanitize($label) ?></span>
        </div>
    </header>

    <!-- KPI Cards -->
    <div class="admin-stats">
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-eye"></i></div>
            <div class="admin-stat-value"><?= number_format($totalViews) ?></div>
            <div class="admin-stat-label">Page Views</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-user"></i></div>
            <div class="admin-stat-value"><?= number_format($uniqueVisitors) ?></div>
            <div class="admin-stat-label">Unique Visitors</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-clock"></i></div>
            <div class="admin-stat-value"><?= formatDuration($avgDuration) ?></div>
            <div class="admin-stat-label">Avg. Duration</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-comments"></i></div>
            <div class="admin-stat-value"><?= number_format($consultations) ?></div>
            <div class="admin-stat-label">Consultations</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="admin-stat-value"><?= number_format($bookings) ?></div>
            <div class="admin-stat-label">Bookings</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-icon"><i class="fas fa-rand-sign"></i></div>
            <div class="admin-stat-value">R<?= number_format($revenue + $invoiceRevenue, 0) ?></div>
            <div class="admin-stat-label">Revenue</div>
        </div>
    </div>

    <!-- Traffic Trend Chart -->
    <div class="admin-section">
        <div class="admin-section-header">
            <h3 class="admin-section-title">Traffic Trend</h3>
            <span class="text-muted" style="font-size:12px;">Page views per day</span>
        </div>
        <div class="chart-bar">
            <?php 
            $maxViews = max(array_column($trendData, 'views')) ?: 1;
            foreach ($trendData as $t): 
                $height = $t['views'] > 0 ? round(($t['views'] / $maxViews) * 100) : 4;
            ?>
            <div class="chart-bar-item" style="height:<?= $height ?>%;" data-value="<?= $t['views'] ?>" title="<?= date('M j', strtotime($t['date'])) ?>: <?= $t['views'] ?> views"></div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex; justify-content:space-between; font-size:11px; color:var(--text-muted); margin-top:8px;">
            <span><?= !empty($trendData) ? date('M j', strtotime($trendData[0]['date'])) : '-' ?></span>
            <span><?= !empty($trendData) ? date('M j', strtotime($trendData[count($trendData)-1]['date'])) : '-' ?></span>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <!-- Top Pages -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Top Pages</h3>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>Page</th><th style="text-align:right;">Views</th><th style="text-align:right;">%</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $maxPageViews = $topPages[0]['views'] ?? 1;
                    foreach (array_slice($topPages, 0, 8) as $p): 
                        $pct = $totalViews > 0 ? round(($p['views'] / $totalViews) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;"><?= sanitize($p['page_path'] ?: '/') ?></div>
                            <div style="width:100%; height:4px; background:var(--bg-secondary); border-radius:2px; margin-top:4px;">
                                <div style="width:<?= ($maxPageViews > 0) ? (($p['views'] / $maxPageViews) * 100) : 0 ?>%; height:100%; background:var(--color-accent); border-radius:2px;"></div>
                            </div>
                        </td>
                        <td style="text-align:right; font-weight:600;"><?= number_format($p['views']) ?></td>
                        <td style="text-align:right; color:var(--text-muted);"><?= $pct ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($topPages)): ?>
                    <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">No data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Traffic Sources -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Traffic Sources</h3>
            </div>
            <div style="display:flex; gap:24px; align-items:center; padding:16px 0;">
                <div class="donut-chart" style="background: conic-gradient(
                    <?php 
                    $sourceColors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#6b7280'];
                    $totalSourceCount = array_sum(array_column($sources, 'count')) ?: 1;
                    $gradientParts = [];
                    $currentDeg = 0;
                    foreach ($sources as $i => $s) {
                        $deg = ($s['count'] / $totalSourceCount) * 360;
                        $color = $sourceColors[$i % count($sourceColors)];
                        $gradientParts[] = "$color {$currentDeg}deg " . ($currentDeg + $deg) . "deg";
                        $currentDeg += $deg;
                    }
                    echo implode(', ', $gradientParts) ?: '#6366f1 0deg 360deg';
                    ?>
                );">
                    <div class="donut-hole"><?= number_format($totalSourceCount) ?></div>
                </div>
                <div style="flex:1;">
                    <?php foreach ($sources as $i => $s): 
                        $pct = $totalSourceCount > 0 ? round(($s['count'] / $totalSourceCount) * 100, 1) : 0;
                        $color = $sourceColors[$i % count($sourceColors)];
                    ?>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                        <span style="width:10px; height:10px; border-radius:50%; background:<?= $color ?>; display:inline-block;"></span>
                        <span style="flex:1; font-size:13px;"><?= sanitize($s['source']) ?></span>
                        <span style="font-weight:600; font-size:13px;"><?= number_format($s['count']) ?></span>
                        <span style="color:var(--text-muted); font-size:12px; width:40px; text-align:right;"><?= $pct ?>%</span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($sources)): ?>
                    <p style="color:var(--text-muted); font-size:13px;">No traffic source data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">
        <!-- Device Types -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Devices</h3>
            </div>
            <div style="padding:8px 0;">
                <?php 
                $deviceIcons = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-alt', 'tablet' => 'fa-tablet-alt'];
                $totalDevices = array_sum(array_column($devices, 'count')) ?: 1;
                foreach ($devices as $d): 
                    $pct = $totalDevices > 0 ? round(($d['count'] / $totalDevices) * 100, 1) : 0;
                    $icon = $deviceIcons[strtolower($d['device_type'])] ?? 'fa-laptop';
                ?>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <div style="width:40px; height:40px; background:var(--bg-secondary); border-radius:10px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas <?= $icon ?>" style="color:var(--color-primary);"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-weight:500; font-size:14px;"><?= ucfirst(sanitize($d['device_type'])) ?></span>
                            <span style="font-weight:600; font-size:14px;"><?= $pct ?>%</span>
                        </div>
                        <div style="width:100%; height:6px; background:var(--bg-secondary); border-radius:3px;">
                            <div style="width:<?= $pct ?>%; height:100%; background:var(--color-accent); border-radius:3px; transition:width 0.5s;"></div>
                        </div>
                    </div>
                    <span style="font-size:13px; color:var(--text-muted); width:50px; text-align:right;"><?= number_format($d['count']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($devices)): ?>
                <p style="color:var(--text-muted); text-align:center; padding:24px;">No device data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Conversion Funnel -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Conversion Funnel</h3>
            </div>
            <div style="padding:8px 0;">
                <?php
                $funnelSteps = [
                    ['label' => 'Page Views', 'value' => $totalViews, 'max' => $totalViews],
                    ['label' => 'Consultations', 'value' => $consultations, 'max' => $totalViews],
                    ['label' => 'Bookings', 'value' => $bookings, 'max' => $totalViews],
                    ['label' => 'Payments', 'value' => $totalPayments, 'max' => $totalViews],
                ];
                foreach ($funnelSteps as $index => $step):
                    $width = $step['max'] > 0 ? ($step['value'] / $step['max']) * 100 : 0;
                    $prev = $funnelSteps[$index - 1]['value'] ?? $step['value'];
                    $conversion = ($prev > 0 && $index > 0) ? round(($step['value'] / $prev) * 100, 1) : 0;
                ?>
                <div class="funnel-step">
                    <div class="funnel-label"><?= sanitize($step['label']) ?></div>
                    <div style="flex:1;">
                        <div class="funnel-bar" style="width:<?= max($width, 5) ?>%;"><?= number_format($step['value']) ?></div>
                    </div>
                    <div class="funnel-pct"><?= $index > 0 ? $conversion . '%' : '—' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:24px; margin-top:24px;">
        <!-- Service Interest -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Service Interest</h3>
            </div>
            <table class="admin-table">
                <thead>
                    <tr><th>Service</th><th style="text-align:right;">Leads</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $maxInterest = $serviceInterest[0]['count'] ?? 1;
                    foreach ($serviceInterest as $si): 
                        $pct = $maxInterest > 0 ? round(($si['count'] / $maxInterest) * 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:500; font-size:13px;"><?= sanitize($si['service_interest']) ?></div>
                            <div style="width:100%; height:3px; background:var(--bg-secondary); border-radius:2px; margin-top:4px;">
                                <div style="width:<?= $pct ?>%; height:100%; background:var(--color-primary); border-radius:2px;"></div>
                            </div>
                        </td>
                        <td style="text-align:right; font-weight:700;"><?= number_format($si['count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($serviceInterest)): ?>
                    <tr><td colspan="2" style="text-align:center; padding:24px; color:var(--text-muted);">No data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Revenue Breakdown -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Revenue</h3>
            </div>
            <div style="padding:16px;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="font-size:2rem; font-weight:800; color:var(--color-accent);">R<?= number_format($revenue + $invoiceRevenue, 0) ?></div>
                    <div style="font-size:13px; color:var(--text-muted);">Total Revenue</div>
                </div>
                <div style="display:flex; flex-direction:column; gap:16px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--bg-secondary); border-radius:8px;">
                        <span style="font-size:13px;"><i class="fas fa-credit-card" style="color:var(--color-success); margin-right:8px;"></i>PayFast Payments</span>
                        <span style="font-weight:700;">R<?= number_format($revenue, 0) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--bg-secondary); border-radius:8px;">
                        <span style="font-size:13px;"><i class="fas fa-file-invoice" style="color:var(--color-primary); margin-right:8px;"></i>Invoice Payments</span>
                        <span style="font-weight:700;">R<?= number_format($invoiceRevenue, 0) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; background:var(--color-danger-light); border-radius:8px;">
                        <span style="font-size:13px;"><i class="fas fa-exclamation-circle" style="color:var(--color-danger); margin-right:8px;"></i>Outstanding</span>
                        <span style="font-weight:700; color:var(--color-danger);">R<?= number_format($outstanding, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Performance -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Emails</h3>
            </div>
            <div style="padding:16px;">
                <?php
                $emailTotal = array_sum(array_column($emailStats, 'count')) ?: 1;
                $emailColors = ['sent' => 'var(--color-primary)', 'delivered' => 'var(--color-success)', 'failed' => 'var(--color-danger)', 'bounced' => 'var(--color-warning)'];
                foreach ($emailStats as $es): 
                    $pct = $emailTotal > 0 ? round(($es['count'] / $emailTotal) * 100, 1) : 0;
                    $color = $emailColors[strtolower($es['status'])] ?? 'var(--text-muted)';
                ?>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:16px;">
                    <div style="width:12px; height:12px; border-radius:50%; background:<?= $color ?>;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-weight:500; font-size:13px; text-transform:capitalize;"><?= sanitize($es['status']) ?></span>
                            <span style="font-weight:600; font-size:13px;"><?= number_format($es['count']) ?></span>
                        </div>
                        <div style="width:100%; height:5px; background:var(--bg-secondary); border-radius:3px;">
                            <div style="width:<?= $pct ?>%; height:100%; background:<?= $color ?>; border-radius:3px;"></div>
                        </div>
                    </div>
                    <span style="font-size:12px; color:var(--text-muted); width:40px; text-align:right;"><?= $pct ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($emailStats)): ?>
                <p style="color:var(--text-muted); text-align:center; padding:24px;">No email data</p>
                <?php endif; ?>
                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-color); text-align:center;">
                    <span style="font-size:1.25rem; font-weight:700;"><?= number_format($emailTotal) ?></span>
                    <span style="font-size:12px; color:var(--text-muted); display:block;">Total Emails</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Client & Project Overview -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;">
        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Clients</h3>
            </div>
            <div style="display:flex; gap:24px; padding:16px; align-items:center;">
                <div style="text-align:center;">
                    <div style="font-size:2.5rem; font-weight:800; color:var(--color-primary);"><?= number_format($totalClients) ?></div>
                    <div style="font-size:12px; color:var(--text-muted);">Active Clients</div>
                </div>
                <div style="width:1px; height:60px; background:var(--border-color);"></div>
                <div style="text-align:center;">
                    <div style="font-size:2.5rem; font-weight:800; color:var(--color-success);">+<?= number_format($newClients) ?></div>
                    <div style="font-size:12px; color:var(--text-muted);">New this period</div>
                </div>
                <div style="width:1px; height:60px; background:var(--border-color);"></div>
                <div style="text-align:center;">
                    <div style="font-size:2.5rem; font-weight:800; color:var(--color-accent);">R<?= number_format($outstanding, 0) ?></div>
                    <div style="font-size:12px; color:var(--text-muted);">Outstanding</div>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <div class="admin-section-header">
                <h3 class="admin-section-title">Projects</h3>
            </div>
            <div style="display:flex; gap:16px; padding:16px; flex-wrap:wrap;">
                <?php 
                $projectColors = ['active' => 'var(--color-primary)', 'completed' => 'var(--color-success)', 'on_hold' => 'var(--color-warning)', 'cancelled' => 'var(--color-danger)'];
                foreach ($projectStats as $ps): 
                    $color = $projectColors[strtolower($ps['status'])] ?? 'var(--text-muted)';
                ?>
                <div style="flex:1; min-width:120px; text-align:center; padding:16px; background:var(--bg-secondary); border-radius:12px;">
                    <div style="font-size:2rem; font-weight:800; color:<?= $color ?>;"><?= number_format($ps['count']) ?></div>
                    <div style="font-size:12px; color:var(--text-muted); text-transform:capitalize;"><?= str_replace('_', ' ', sanitize($ps['status'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($projectStats)): ?>
                <div style="flex:1; text-align:center; padding:24px; color:var(--text-muted);">No projects</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Raw Data Export -->
    <div class="admin-section" style="margin-top:24px;">
        <div class="admin-section-header">
            <h3 class="admin-section-title">Export Data</h3>
        </div>
        <div style="padding:16px; display:flex; gap:12px; flex-wrap:wrap;">
            <a href="?range=<?= urlencode($range) ?>&export=consultations&start=<?= urlencode($since) ?>&end=<?= urlencode($until) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="fas fa-download"></i> Consultations CSV</a>
            <a href="?range=<?= urlencode($range) ?>&export=bookings&start=<?= urlencode($since) ?>&end=<?= urlencode($until) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="fas fa-download"></i> Bookings CSV</a>
            <a href="?range=<?= urlencode($range) ?>&export=payments&start=<?= urlencode($since) ?>&end=<?= urlencode($until) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="fas fa-download"></i> Payments CSV</a>
            <a href="?range=<?= urlencode($range) ?>&export=pageviews&start=<?= urlencode($since) ?>&end=<?= urlencode($until) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="fas fa-download"></i> Page Views CSV</a>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.funnel-bar').forEach(bar => {
            const target = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => bar.style.width = target, 100);
        });
    });
</script>
</body>
</html>