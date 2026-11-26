<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
if (!isClient()) redirect('login.php');

$client = getClient($_SESSION['client_id']);
if (!$client) redirect('logout.php');

$projects = getClientProjects($client['id']);
$invoices = getClientInvoices($client['id']);

$totalPaid = array_reduce($invoices, fn($c, $i) => $c + ($i['status']==='paid' ? (float)$i['amount'] : 0), 0.0);
$outstanding = array_reduce($invoices, fn($c, $i) => $c + ($i['status']!=='paid' ? (float)$i['amount'] : 0), 0.0);

$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title><?= sanitize($pageTitle) ?> | Client Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-discovery { background:#dbeafe; color:#1e40af; }
        .status-in_progress { background:#fef3c7; color:#92400e; }
        .status-review { background:#e0e7ff; color:#3730a3; }
        .status-completed { background:#dcfce7; color:#166534; }
        .status-on_hold { background:#fee2e2; color:#991b1b; }
        .progress-bar { width:100%; height:8px; background:#e4e4e7; border-radius:999px; overflow:hidden; margin-top:8px; }
        .progress-fill { height:100%; background:#4f46e5; border-radius:999px; }
    </style>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand">&vee;ueports Client</div>
        <nav class="admin-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="projects.php" ><i class="fas fa-envelope"></i> My Projects</a>
            <a href="invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div style="margin-top:auto; padding:16px; font-size:0.75rem; color:#9ca3af; border-top:1px solid #374151;">
            Logged in as<br><strong><?= sanitize($client['full_name']) ?></strong>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Dashboard</h1>
            <div><?= sanitize($client['company_name'] ?: $client['full_name']) ?></div>
        </header>

        <div class="admin-stats">
            <div class="stat-box"><i class="fas fa-folder-open"></i><div class="stat-value"><?= count($projects) ?></div><div class="stat-label">Projects</div></div>
            <div class="stat-box"><i class="fas fa-check-circle"></i><div class="stat-value">R<?= number_format($totalPaid, 0) ?></div><div class="stat-label">Total Paid</div></div>
            <div class="stat-box"><i class="fas fa-clock"></i><div class="stat-value">R<?= number_format($outstanding, 0) ?></div><div class="stat-label">Outstanding</div></div>
        </div>

        <div class="admin-section">
            <h2>Recent Projects</h2>
            <?php if (empty($projects)): ?>
                <p style="color:var(--admin-muted); padding:24px 0;">No projects yet. We will update this once work begins.</p>
            <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Project</th><th>Service</th><th>Status</th><th>Progress</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($projects, 0, 5) as $p): ?>
                    <tr>
                        <td><strong><?= sanitize($p['title']) ?></strong></td>
                        <td><?= sanitize($p['service_type'] ?: '-') ?></td>
                        <td><span class="status-badge status-<?= $p['status'] ?>"><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></span></td>
                        <td style="width:200px;">
                            <div style="font-size:0.75rem; margin-bottom:4px;"><?= $p['progress'] ?>%</div>
                            <div class="progress-bar"><div class="progress-fill" style="width:<?= $p['progress'] ?>%"></div></div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            <a href="projects.php" class="btn btn-sm btn-outline" style="margin-top:12px;">View All Projects</a>
        </div>
    </main>
</body>
</html>