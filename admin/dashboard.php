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

$pageTitle = 'Dashboard';

/* ========================================
   Period (default: last 30 days)
   ======================================== */

$range = $_GET['range'] ?? '30days';
switch ($range) {
    case '7days':
        $since = date('Y-m-d', strtotime('-7 days'));
        $label = 'Last 7 Days';
        break;
    case 'today':
        $since = date('Y-m-d');
        $label = 'Today';
        break;
    case '30days':
    default:
        $since = date('Y-m-d', strtotime('-30 days'));
        $label = 'Last 30 Days';
        break;
}
$until = date('Y-m-d');

/* ========================================
   Core Stats
   ======================================== */

$totalMsg       = (int) $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$newMsg         = (int) $db->query("SELECT COUNT(*) FROM messages WHERE status = 'new'")->fetchColumn();
$totalConsults  = (int) $db->query("SELECT COUNT(*) FROM consultations")->fetchColumn();
$newConsults    = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE status = 'new'")->fetchColumn();
$totalBookings  = (int) $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings= (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$totalClients   = (int) $db->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();

$totalRevenue   = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed'")->fetchColumn();
$monthRevenue   = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed' AND created_at >= (NOW() - INTERVAL '30 day')")->fetchColumn();

/* ========================================
   Analytics Data
   ======================================== */

// Traffic
$stmt = $db->prepare("SELECT COUNT(*) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$pageViews = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(DISTINCT ip_hash) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$uniqueVisitors = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT AVG(duration_seconds) FROM page_views WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$avgDuration = (int) round((float) ($stmt->fetchColumn() ?: 0));

// Conversions this period
$stmt = $db->prepare("SELECT COUNT(*) FROM consultations WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$periodConsults = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$periodBookings = (int) $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE payment_status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$since, $until]);
$periodPayments = (int) $stmt->fetchColumn();

// Conversion rates
$consultRate = $pageViews > 0 ? round(($periodConsults / $pageViews) * 100, 2) : 0;
$bookingRate = $periodConsults > 0 ? round(($periodBookings / $periodConsults) * 100, 2) : 0;
$paymentRate = $periodBookings > 0 ? round(($periodPayments / $periodBookings) * 100, 2) : 0;

// Daily trend (last 14 days)
$trendData = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $stmt = $db->prepare("SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = ?");
    $stmt->execute([$d]);
    $trendData[] = ['date' => $d, 'views' => (int) $stmt->fetchColumn()];
}

// Top pages
$stmt = $db->prepare("SELECT page_path, COUNT(*) as views FROM page_views WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY page_path ORDER BY views DESC LIMIT 5");
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
GROUP BY source ORDER BY count DESC LIMIT 5");
$stmt->execute([$since, $until]);
$trafficSources = $stmt->fetchAll();

// Service interest
$stmt = $db->prepare("SELECT service_interest, COUNT(*) as count FROM consultations WHERE DATE(created_at) BETWEEN ? AND ? AND service_interest != '' GROUP BY service_interest ORDER BY count DESC LIMIT 5");
$stmt->execute([$since, $until]);
$serviceInterest = $stmt->fetchAll();

/* ========================================
   Recent Activity (direct queries)
   ======================================== */

$recentMessages = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentConsultations = $db->query("SELECT * FROM consultations ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentBookings = $db->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5")->fetchAll();
$upcomingBookings = $db->query("
    SELECT * FROM bookings
    WHERE booking_date BETWEEN CURRENT_DATE AND (CURRENT_DATE + INTERVAL '7 day')
    AND status IN ('pending', 'confirmed')
    ORDER BY booking_date ASC, booking_time ASC
    LIMIT 5
")->fetchAll();

// Revenue chart data (last 6 months)
$revenueData = $db->query("
    SELECT TO_CHAR(created_at, 'YYYY-MM') as month, COALESCE(SUM(amount), 0) as total
    FROM payments
    WHERE payment_status = 'completed' AND created_at >= (NOW() - INTERVAL '6 month')
    GROUP BY month
    ORDER BY month ASC
")->fetchAll();

$revenueLabels = [];
$revenueValues = [];
foreach ($revenueData as $row) {
    $revenueLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $revenueValues[] = (float) $row['total'];
}

// Booking status colors
$statusColors = [
    'pending'   => '#f59e0b',
    'confirmed' => '#3b82f6',
    'completed' => '#10b981',
    'cancelled' => '#ef4444',
    'no_show'   => '#6b7280'
];

// Consultation status colors
$consultStatusColors = [
    'new'           => '#f59e0b',
    'contacted'     => '#3b82f6',
    'qualified'     => '#8b5cf6',
    'proposal_sent' => '#ec4899',
    'converted'     => '#10b981',
    'closed'        => '#6b7280'
];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.blue { background: #dbeafe; color: #1e40af; }
        .stat-icon.green { background: #d1fae5; color: #065f46; }
        .stat-icon.amber { background: #fef3c7; color: #92400e; }
        .stat-icon.purple { background: #e9d5ff; color: #6b21a8; }
        .stat-icon.red { background: #fee2e2; color: #991b1b; }
        .stat-icon.teal { background: #ccfbf1; color: #0f766e; }
        .stat-info h3 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-primary); }
        .stat-info p { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .dashboard-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
        .dashboard-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .dashboard-card-header h3 { font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin: 0; }
        .dashboard-card-body { padding: 20px; }

        .activity-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
        .activity-content { flex: 1; }
        .activity-content p { margin: 0; font-size: 13px; color: var(--text-primary); }
        .activity-content span { font-size: 12px; color: var(--text-muted); }

        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .quick-action { display: flex; align-items: center; gap: 12px; padding: 16px; border-radius: 8px; background: var(--bg-secondary); text-decoration: none; color: var(--text-primary); transition: background 0.2s; }
        .quick-action:hover { background: var(--border-color); }
        .quick-action i { font-size: 18px; color: var(--color-primary); }
        .quick-action span { font-size: 13px; font-weight: 500; }

        .dash-chart-bar { display: flex; align-items: flex-end; gap: 3px; height: 80px; padding: 8px 0; }
        .dash-chart-item { flex: 1; background: var(--color-primary-200, #c7d2fe); border-radius: 3px 3px 0 0; min-height: 3px; transition: all 0.3s; position: relative; cursor: pointer; }
        .dash-chart-item:hover { background: var(--color-accent, #6366f1); }
        .dash-chart-item::after { content: attr(data-val); position: absolute; top: -18px; left: 50%; transform: translateX(-50%); font-size: 9px; opacity: 0; transition: opacity 0.3s; white-space: nowrap; background: var(--text-primary); color: white; padding: 2px 6px; border-radius: 4px; }
        .dash-chart-item:hover::after { opacity: 1; }

        .mini-donut { width: 80px; height: 80px; border-radius: 50%; position: relative; flex-shrink: 0; }
        .mini-donut-hole { position: absolute; inset: 22px; background: var(--bg-card); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; }

        .funnel-mini { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
        .funnel-mini:last-child { border-bottom: none; }
        .funnel-mini-bar { height: 24px; background: var(--color-primary-100, #e0e7ff); border-radius: 4px; display: flex; align-items: center; padding: 0 8px; font-weight: 600; font-size: 12px; color: var(--color-primary); white-space: nowrap; }

        .source-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; }
        .source-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        .kpi-small { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .analytics-toggle { display: flex; gap: 8px; align-items: center; }
        .analytics-toggle select { padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color); background: var(--bg-card); font-size: 13px; color: var(--text-primary); cursor: pointer; }

        .chart-container { position: relative; height: 260px; }

        .section-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 24px; }
        .section-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px; }

        @media (max-width: 1024px) {
            .dashboard-grid, .section-row, .section-row-2 { grid-template-columns: 1fr; }
        }
    </style>
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-shield-alt"></i> Vueports Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="messages.php"><i class="fas fa-envelope"></i> Messages <?php if($newMsg > 0): ?><span class="admin-nav-badge"><?= $newMsg ?></span><?php endif; ?></a>
            <a href="consultations.php"><i class="fas fa-comments"></i> Consultations <?php if($newConsults > 0): ?><span class="admin-nav-badge"><?= $newConsults ?></span><?php endif; ?></a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings <?php if($pendingBookings > 0): ?><span class="admin-nav-badge"><?= $pendingBookings ?></span><?php endif; ?></a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
            <a href="services.php"><i class="fas fa-briefcase"></i> Services</a>
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
            <h1>Dashboard</h1>
            <div class="admin-header-actions" style="display:flex; gap:16px; align-items:center;">
                <form method="GET" class="analytics-toggle">
                    <select name="range" onchange="this.form.submit()">
                        <option value="7days" <?= $range === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30days" <?= $range === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                    </select>
                </form>
                <span style="font-size:13px; color:var(--text-muted);"><i class="fas fa-clock"></i> <?= date('l, j F Y') ?></span>
            </div>
        </header>

        <!-- Core KPIs -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-envelope"></i></div>
                <div class="stat-info"><h3><?= number_format($newMsg) ?></h3><p>New Messages</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
                <div class="stat-info"><h3><?= number_format($newConsults) ?></h3><p>New Leads</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info"><h3><?= number_format($pendingBookings) ?></h3><p>Pending Bookings</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?= number_format($totalClients) ?></h3><p>Active Clients</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-coins"></i></div>
                <div class="stat-info"><h3>R<?= number_format($monthRevenue, 0) ?></h3><p>Revenue (30d)</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-wallet"></i></div>
                <div class="stat-info"><h3>R<?= number_format($totalRevenue, 0) ?></h3><p>Total Revenue</p></div>
            </div>
        </div>

        <!-- Analytics Row: Traffic + Funnel + Sources -->
        <div class="section-row">
            <!-- Traffic Mini-Chart -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Traffic Trend</h3>
                    <span class="badge badge-primary" style="background:var(--color-primary); color:white; padding:4px 10px; border-radius:6px; font-size:11px;"><?= sanitize($label) ?></span>
                </div>
                <div class="dashboard-card-body">
                    <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                        <div>
                            <div style="font-size:1.5rem; font-weight:800;"><?= number_format($pageViews) ?></div>
                            <div class="kpi-small">Page Views</div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.5rem; font-weight:800;"><?= number_format($uniqueVisitors) ?></div>
                            <div class="kpi-small">Unique Visitors</div>
                        </div>
                    </div>
                    <div class="dash-chart-bar">
                        <?php
                        $maxViews = max(array_column($trendData, 'views')) ?: 1;
                        foreach ($trendData as $t):
                            $h = $t['views'] > 0 ? round(($t['views'] / $maxViews) * 100) : 3;
                        ?>
                        <div class="dash-chart-item" style="height:<?= $h ?>%;" data-val="<?= $t['views'] ?>" title="<?= date('M j', strtotime($t['date'])) ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--text-muted);">
                        <span><?= date('M j', strtotime($trendData[0]['date'])) ?></span>
                        <span><?= date('M j', strtotime($trendData[count($trendData)-1]['date'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Conversion Funnel -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Conversion Funnel</h3>
                    <span class="kpi-small"><?= sanitize($label) ?></span>
                </div>
                <div class="dashboard-card-body">
                    <?php
                    $funnelSteps = [
                        ['label' => 'Views', 'value' => $pageViews, 'max' => $pageViews],
                        ['label' => 'Consults', 'value' => $periodConsults, 'max' => $pageViews, 'rate' => $consultRate],
                        ['label' => 'Bookings', 'value' => $periodBookings, 'max' => $pageViews, 'rate' => $bookingRate],
                        ['label' => 'Payments', 'value' => $periodPayments, 'max' => $pageViews, 'rate' => $paymentRate],
                    ];
                    foreach ($funnelSteps as $i => $step):
                        $width = $step['max'] > 0 ? ($step['value'] / $step['max']) * 100 : 0;
                    ?>
                    <div class="funnel-mini">
                        <div style="width:70px; font-size:12px; color:var(--text-secondary); flex-shrink:0;"><?= $step['label'] ?></div>
                        <div style="flex:1;">
                            <div class="funnel-mini-bar" style="width:<?= max($width, 5) ?>%;"><?= number_format($step['value']) ?></div>
                        </div>
                        <?php if ($i > 0): ?>
                        <div style="width:45px; text-align:right; font-size:11px; color:var(--color-success); font-weight:600;"><?= $step['rate'] ?>%</div>
                        <?php else: ?>
                        <div style="width:45px; text-align:right; font-size:11px; color:var(--text-muted);">—</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Traffic Sources -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Sources</h3>
                    <span class="kpi-small"><?= sanitize($label) ?></span>
                </div>
                <div class="dashboard-card-body" style="display:flex; gap:16px; align-items:center;">
                    <?php
                    $sourceColors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'];
                    $totalSourceCount = array_sum(array_column($trafficSources, 'count')) ?: 1;
                    $gradientParts = [];
                    $currentDeg = 0;
                    foreach ($trafficSources as $i => $s) {
                        $deg = ($s['count'] / $totalSourceCount) * 360;
                        $color = $sourceColors[$i % count($sourceColors)];
                        $gradientParts[] = "$color {$currentDeg}deg " . ($currentDeg + $deg) . "deg";
                        $currentDeg += $deg;
                    }
                    ?>
                    <div class="mini-donut" style="background: conic-gradient(<?= implode(', ', $gradientParts) ?: '#6366f1 0deg 360deg' ?>);">
                        <div class="mini-donut-hole"><?= number_format($totalSourceCount) ?></div>
                    </div>
                    <div style="flex:1;">
                        <?php foreach ($trafficSources as $i => $s):
                            $pct = $totalSourceCount > 0 ? round(($s['count'] / $totalSourceCount) * 100, 1) : 0;
                        ?>
                        <div class="source-row">
                            <span class="source-dot" style="background:<?= $sourceColors[$i % count($sourceColors)] ?>;"></span>
                            <span style="flex:1;"><?= sanitize($s['source']) ?></span>
                            <span style="font-weight:600; font-size:12px;"><?= $pct ?>%</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($trafficSources)): ?>
                        <p style="color:var(--text-muted); font-size:12px;">No source data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Analytics Row: Top Pages + Service Interest -->
        <div class="section-row-2">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Top Pages</h3>
                    <a href="analytics.php" class="admin-btn admin-btn-sm admin-btn-secondary">Full Analytics</a>
                </div>
                <div class="dashboard-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Page</th><th style="text-align:right;">Views</th><th style="text-align:right;">Share</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxPageViews = $topPages[0]['views'] ?? 1;
                            foreach ($topPages as $p):
                                $pct = $pageViews > 0 ? round(($p['views'] / $pageViews) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500; font-size:13px;"><?= sanitize($p['page_path'] ?: '/') ?></div>
                                    <div style="width:100%; height:3px; background:var(--bg-secondary); border-radius:2px; margin-top:4px;">
                                        <div style="width:<?= ($maxPageViews > 0) ? (($p['views'] / $maxPageViews) * 100) : 0 ?>%; height:100%; background:var(--color-accent); border-radius:2px;"></div>
                                    </div>
                                </td>
                                <td style="text-align:right; font-weight:600; font-size:13px;"><?= number_format($p['views']) ?></td>
                                <td style="text-align:right; color:var(--text-muted); font-size:12px;"><?= $pct ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topPages)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">No page view data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Service Interest</h3>
                    <span class="kpi-small"><?= sanitize($label) ?></span>
                </div>
                <div class="dashboard-card-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Service</th><th style="text-align:right;">Leads</th><th style="text-align:right;">Share</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $maxInterest = $serviceInterest[0]['count'] ?? 1;
                            $totalInterest = array_sum(array_column($serviceInterest, 'count')) ?: 1;
                            foreach ($serviceInterest as $si):
                                $pct = $totalInterest > 0 ? round(($si['count'] / $totalInterest) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500; font-size:13px;"><?= sanitize($si['service_interest']) ?></div>
                                    <div style="width:100%; height:3px; background:var(--bg-secondary); border-radius:2px; margin-top:4px;">
                                        <div style="width:<?= ($maxInterest > 0) ? (($si['count'] / $maxInterest) * 100) : 0 ?>%; height:100%; background:var(--color-primary); border-radius:2px;"></div>
                                    </div>
                                </td>
                                <td style="text-align:right; font-weight:600; font-size:13px;"><?= number_format($si['count']) ?></td>
                                <td style="text-align:right; color:var(--text-muted); font-size:12px;"><?= $pct ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($serviceInterest)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:24px; color:var(--text-muted);">No consultation data</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Revenue Chart + Quick Actions -->
        <div class="dashboard-grid" style="margin-top:24px;">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Revenue Overview</h3>
                    <a href="analytics.php" class="admin-btn admin-btn-sm admin-btn-secondary">View Analytics</a>
                </div>
                <div class="dashboard-card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header"><h3>Quick Actions</h3></div>
                <div class="dashboard-card-body">
                    <div class="quick-actions">
                        <a href="bookings.php" class="quick-action"><i class="fas fa-calendar-plus"></i><span>Manage Bookings</span></a>
                        <a href="clients.php" class="quick-action"><i class="fas fa-user-plus"></i><span>Add Client</span></a>
                        <a href="invoices.php" class="quick-action"><i class="fas fa-file-invoice"></i><span>Create Invoice</span></a>
                        <a href="messages.php" class="quick-action"><i class="fas fa-envelope"></i><span>View Messages</span></a>
                        <a href="services.php" class="quick-action"><i class="fas fa-briefcase"></i><span>Edit Services</span></a>
                        <a href="settings.php" class="quick-action"><i class="fas fa-cog"></i><span>Settings</span></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Row -->
        <div class="section-row-2" style="margin-top:24px;">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Recent Messages</h3>
                    <a href="messages.php" class="admin-btn admin-btn-sm admin-btn-secondary">View All</a>
                </div>
                <div class="dashboard-card-body">
                    <?php if (empty($recentMessages)): ?>
                        <p style="text-align:center; color:var(--text-muted); padding:20px;">No recent messages</p>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $m): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: #fef3c7; color: #92400e;"><i class="fas fa-envelope"></i></div>
                            <div class="activity-content">
                                <p><strong><?= sanitize($m['name']) ?></strong> — <?= sanitize($m['subject'] ?: 'No subject') ?></p>
                                <span><i class="fas fa-clock"></i> <?= timeAgo($m['created_at']) ?> &bull; <?= ucfirst($m['status']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3>Recent Leads</h3>
                    <a href="consultations.php" class="admin-btn admin-btn-sm admin-btn-secondary">View All</a>
                </div>
                <div class="dashboard-card-body">
                    <?php if (empty($recentConsultations)): ?>
                        <p style="text-align:center; color:var(--text-muted); padding:20px;">No recent consultations</p>
                    <?php else: ?>
                        <?php foreach ($recentConsultations as $c): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?= $consultStatusColors[$c['status']] ?? '#6b7280' ?>20; color: <?= $consultStatusColors[$c['status']] ?? '#6b7280' ?>;"><i class="fas fa-comments"></i></div>
                            <div class="activity-content">
                                <p><strong><?= sanitize($c['name']) ?></strong> — <?= sanitize($c['service_interest'] ?: 'General inquiry') ?></p>
                                <span><i class="fas fa-clock"></i> <?= timeAgo($c['created_at']) ?> &bull; <?= ucfirst(str_replace('_', ' ', $c['status'])) ?> &bull; <?= sanitize($c['budget_range'] ?: 'No budget') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="dashboard-card" style="margin-top:24px;">
            <div class="dashboard-card-header">
                <h3>Upcoming Bookings (Next 7 Days)</h3>
                <a href="bookings.php?status=confirmed" class="admin-btn admin-btn-sm admin-btn-secondary">View All</a>
            </div>
            <div class="dashboard-card-body">
                <?php if (empty($upcomingBookings)): ?>
                    <p style="text-align:center; color:var(--text-muted); padding:20px;">No upcoming bookings</p>
                <?php else: ?>
                    <?php foreach ($upcomingBookings as $b): ?>
                    <div class="activity-item">
                        <div class="activity-icon" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-clock"></i></div>
                        <div class="activity-content">
                            <p><strong><?= sanitize($b['name']) ?></strong> — <?= sanitize($b['service_type'] ?: 'Consultation') ?></p>
                            <span><?= date('M j, Y', strtotime($b['booking_date'])) ?> at <?= date('g:i A', strtotime($b['booking_time'])) ?> &bull; <?= ucfirst($b['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($revenueLabels) ?>,
                datasets: [{
                    label: 'Revenue (R)',
                    data: <?= json_encode($revenueValues) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
