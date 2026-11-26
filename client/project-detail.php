<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

if (!isClient()) redirect('login.php');

$clientId = (int) $_SESSION['client_id'];
$projectId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$projectId) redirect('projects.php');

$db = db();
if (!$db) die('Database connection required');

// Fetch project (verify ownership)
$stmt = $db->prepare("SELECT p.*, c.full_name as client_name, c.email as client_email 
                       FROM projects p 
                       JOIN clients c ON p.client_id = c.id 
                       WHERE p.id = ? AND p.client_id = ?");
$stmt->execute([$projectId, $clientId]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(403);
    redirect('projects.php?error=unauthorized');
}

// Fetch milestones
$milestones = $db->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC");
$milestones->execute([$projectId]);
$milestones = $milestones->fetchAll();

// Fetch files
$files = $db->prepare("SELECT * FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC");
$files->execute([$projectId]);
$files = $files->fetchAll();

// Fetch comments / updates
$comments = $db->prepare("SELECT pc.*, a.name as admin_name 
                         FROM project_comments pc 
                         LEFT JOIN admins a ON pc.admin_id = a.id 
                         WHERE pc.project_id = ? 
                         ORDER BY pc.created_at DESC");
$comments->execute([$projectId]);
$comments = $comments->fetchAll();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = sanitize($_POST['comment']);
    if (strlen($comment) > 0) {
        $db->prepare("INSERT INTO project_comments (project_id, client_id, comment, created_at) VALUES (?, ?, ?, NOW())")
           ->execute([$projectId, $clientId, $comment]);
        redirect("project-detail.php?id=$projectId#comments");
    }
}

// Progress calculation
$totalMilestones = count($milestones);
$completedMilestones = count(array_filter($milestones, fn($m) => $m['status'] === 'completed'));
$progress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;

// Status styling
$statusColors = [
    'planning' => ['bg' => '#fef3c7', 'color' => '#92400e', 'icon' => 'fa-clipboard-list'],
    'in_progress' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-spinner fa-spin'],
    'review' => ['bg' => '#f3e8ff', 'color' => '#6b21a8', 'icon' => 'fa-eye'],
    'completed' => ['bg' => '#d1fae5', 'color' => '#065f46', 'icon' => 'fa-check-circle'],
    'on_hold' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'fa-pause-circle'],
    'cancelled' => ['bg' => '#f3f4f6', 'color' => '#4b5563', 'icon' => 'fa-times-circle'],
];
$sc = $statusColors[strtolower($project['status'])] ?? $statusColors['planning'];

$pageTitle = 'Project: ' . $project['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section" style="padding-top:140px;">
    <div class="container">
        <!-- Breadcrumb -->
        <nav style="margin-bottom:24px; font-size:var(--font-size-sm);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span style="color:var(--text-muted); margin:0 8px;">/</span>
            <a href="projects.php" style="color:var(--text-muted);">Projects</a>
            <span style="color:var(--text-muted); margin:0 8px;">/</span>
            <span style="color:var(--text-primary); font-weight:500;"><?= sanitize($project['title']) ?></span>
        </nav>

        <!-- Project Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:32px;">
            <div>
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; flex-wrap:wrap;">
                    <h1 style="font-size:var(--font-size-3xl); font-weight:800;"><?= sanitize($project['title']) ?></h1>
                    <span class="badge" style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>; font-size:var(--font-size-sm); padding:6px 14px; text-transform:capitalize;">
                        <i class="fas <?= $sc['icon'] ?>" style="margin-right:6px;"></i><?= str_replace('_', ' ', sanitize($project['status'])) ?>
                    </span>
                </div>
                <p style="color:var(--text-secondary); font-size:var(--font-size-base); max-width:600px; line-height:1.6;">
                    <?= sanitize($project['description'] ?: 'No description provided.') ?>
                </p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:var(--font-size-sm); color:var(--text-muted); margin-bottom:4px;">Project Value</div>
                <div style="font-size:var(--font-size-2xl); font-weight:800; color:var(--color-accent);">
                    R<?= number_format((float)$project['budget'], 2) ?>
                </div>
                <div style="font-size:var(--font-size-xs); color:var(--text-muted); margin-top:4px;">
                    Started <?= date('M j, Y', strtotime($project['start_date'])) ?>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="card" style="margin-bottom:24px; padding:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-weight:600; font-size:var(--font-size-sm);">Overall Progress</span>
                <span style="font-weight:700; color:var(--color-primary);"><?= $progress ?>%</span>
            </div>
            <div style="width:100%; height:10px; background:var(--bg-secondary); border-radius:5px; overflow:hidden;">
                <div style="width:<?= $progress ?>%; height:100%; background:linear-gradient(90deg, var(--color-primary), var(--color-accent)); border-radius:5px; transition:width 1s ease;"></div>
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:8px; font-size:var(--font-size-xs); color:var(--text-muted);">
                <span><?= $completedMilestones ?> of <?= $totalMilestones ?> milestones completed</span>
                <span>Due <?= date('M j, Y', strtotime($project['deadline'])) ?></span>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; align-items:start;">
            <!-- Left Column -->
            <div style="display:flex; flex-direction:column; gap:24px;">
                
                <!-- Milestones -->
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                        <h2 style="font-size:var(--font-size-xl); font-weight:700;"><i class="fas fa-flag-checkered" style="color:var(--color-primary); margin-right:10px;"></i>Milestones</h2>
                        <span class="badge badge-primary"><?= $completedMilestones ?>/<?= $totalMilestones ?></span>
                    </div>
                    
                    <?php if (empty($milestones)): ?>
                    <div class="empty-state" style="padding:32px;">
                        <div class="empty-state-icon"><i class="fas fa-flag"></i></div>
                        <h3 class="empty-state-title">No milestones yet</h3>
                        <p class="empty-state-desc">Milestones will appear here once the project is planned.</p>
                    </div>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:0;">
                        <?php foreach ($milestones as $i => $m): 
                            $mStatus = strtolower($m['status']);
                            $mColor = match($mStatus) {
                                'completed' => ['bg' => '#d1fae5', 'border' => '#10b981', 'icon' => 'fa-check'],
                                'in_progress' => ['bg' => '#dbeafe', 'border' => '#6366f1', 'icon' => 'fa-clock'],
                                'pending' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'icon' => 'fa-hourglass-half'],
                                default => ['bg' => '#f3f4f6', 'border' => '#9ca3af', 'icon' => 'fa-circle'],
                            };
                        ?>
                        <div style="display:flex; gap:16px; padding:20px 0; <?= $i < count($milestones)-1 ? 'border-bottom:1px solid var(--border-color);' : '' ?>">
                            <div style="width:40px; height:40px; background:<?= $mColor['bg'] ?>; border:2px solid <?= $mColor['border'] ?>; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fas <?= $mColor['icon'] ?>" style="color:<?= $mColor['border'] ?>; font-size:14px;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px; margin-bottom:6px;">
                                    <h4 style="font-weight:600; font-size:var(--font-size-base);"><?= sanitize($m['title']) ?></h4>
                                    <span class="badge" style="background:<?= $mColor['bg'] ?>; color:<?= $mColor['border'] ?>; font-size:11px; text-transform:capitalize;"><?= str_replace('_', ' ', sanitize($m['status'])) ?></span>
                                </div>
                                <p style="color:var(--text-secondary); font-size:var(--font-size-sm); line-height:1.5; margin-bottom:8px;"><?= sanitize($m['description'] ?: '') ?></p>
                                <div style="display:flex; gap:16px; font-size:var(--font-size-xs); color:var(--text-muted); flex-wrap:wrap;">
                                    <span><i class="far fa-calendar" style="margin-right:4px;"></i>Due <?= date('M j, Y', strtotime($m['due_date'])) ?></span>
                                    <?php if ($m['completed_at']): ?>
                                    <span style="color:var(--color-success);"><i class="far fa-check-circle" style="margin-right:4px;"></i>Completed <?= date('M j, Y', strtotime($m['completed_at'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Comments / Updates -->
                <div class="card" id="comments">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                        <h2 style="font-size:var(--font-size-xl); font-weight:700;"><i class="fas fa-comments" style="color:var(--color-primary); margin-right:10px;"></i>Updates & Discussion</h2>
                        <span class="badge badge-primary"><?= count($comments) ?></span>
                    </div>

                    <?php if (empty($comments)): ?>
                    <p style="color:var(--text-muted); text-align:center; padding:24px;">No updates yet. Check back soon for progress reports.</p>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:20px; margin-bottom:24px;">
                        <?php foreach ($comments as $c): 
                            $isAdmin = !empty($c['admin_name']);
                            $avatar = $isAdmin ? 'V' : substr($project['client_name'], 0, 1);
                            $bg = $isAdmin ? 'var(--bg-secondary)' : 'var(--color-primary-50)';
                            $border = $isAdmin ? 'var(--border-color)' : 'var(--color-primary-200)';
                        ?>
                        <div style="display:flex; gap:12px;">
                            <div style="width:40px; height:40px; background:<?= $isAdmin ? 'var(--color-accent)' : 'var(--color-primary)' ?>; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                                <?= $avatar ?>
                            </div>
                            <div style="flex:1; background:<?= $bg ?>; border:1px solid <?= $border ?>; border-radius:12px; padding:16px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; flex-wrap:wrap; gap:4px;">
                                    <span style="font-weight:600; font-size:var(--font-size-sm);">
                                        <?= $isAdmin ? sanitize($c['admin_name']) . ' <span style="color:var(--color-accent); font-size:11px;">(Vueports Team)</span>' : 'You' ?>
                                    </span>
                                    <span style="font-size:11px; color:var(--text-muted);"><?= timeAgo($c['created_at']) ?></span>
                                </div>
                                <p style="color:var(--text-secondary); font-size:var(--font-size-sm); line-height:1.6; white-space:pre-wrap;"><?= nl2br(sanitize($c['comment'])) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <form method="POST" style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="width:40px; height:40px; background:var(--color-primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px; flex-shrink:0;">
                            <?= substr($project['client_name'], 0, 1) ?>
                        </div>
                        <div style="flex:1;">
                            <textarea name="comment" rows="3" placeholder="Ask a question or leave a comment..." class="form-input" style="resize:vertical; margin-bottom:8px;" required></textarea>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:12px; color:var(--text-muted);">Press Enter to send</span>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Send</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div style="display:flex; flex-direction:column; gap:24px;">
                
                <!-- Project Info Card -->
                <div class="card">
                    <h3 style="font-size:var(--font-size-lg); font-weight:700; margin-bottom:20px;">Project Details</h3>
                    
                    <div style="display:flex; flex-direction:column; gap:16px;">
                        <div>
                            <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Service Type</div>
                            <div style="font-weight:600; font-size:var(--font-size-sm);"><?= sanitize($project['service_type'] ?: 'Custom Project') ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Priority</div>
                            <div style="font-weight:600; font-size:var(--font-size-sm); text-transform:capitalize;"><?= sanitize($project['priority'] ?: 'Normal') ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Project Manager</div>
                            <div style="font-weight:600; font-size:var(--font-size-sm);"><?= sanitize($project['project_manager'] ?: 'TBD') ?></div>
                        </div>
                        <div>
                            <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Timeline</div>
                            <div style="font-weight:600; font-size:var(--font-size-sm);">
                                <?= date('M j', strtotime($project['start_date'])) ?> – <?= date('M j, Y', strtotime($project['deadline'])) ?>
                            </div>
                            <div style="font-size:11px; color:var(--text-muted); margin-top:2px;">
                                <?= ceil((strtotime($project['deadline']) - time()) / 86400) ?> days remaining
                            </div>
                        </div>
                        <div>
                            <div style="font-size:var(--font-size-xs); color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Last Updated</div>
                            <div style="font-weight:600; font-size:var(--font-size-sm);"><?= timeAgo($project['updated_at']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Files -->
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="font-size:var(--font-size-lg); font-weight:700;"><i class="fas fa-folder-open" style="color:var(--color-primary); margin-right:8px;"></i>Files</h3>
                        <span class="badge badge-primary"><?= count($files) ?></span>
                    </div>

                    <?php if (empty($files)): ?>
                    <p style="color:var(--text-muted); font-size:var(--font-size-sm); text-align:center; padding:16px;">No files uploaded yet.</p>
                    <?php else: ?>
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <?php foreach ($files as $f): 
                            $ext = pathinfo($f['filename'], PATHINFO_EXTENSION);
                            $icon = match(strtolower($ext)) {
                                'pdf' => 'fa-file-pdf',
                                'doc', 'docx' => 'fa-file-word',
                                'xls', 'xlsx' => 'fa-file-excel',
                                'zip', 'rar' => 'fa-file-archive',
                                'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image',
                                default => 'fa-file',
                            };
                            $size = $f['file_size'] ?? 0;
                            $sizeLabel = $size > 1048576 ? round($size/1048576, 1).' MB' : ($size > 1024 ? round($size/1024, 1).' KB' : $size.' B');
                        ?>
                        <a href="../uploads/projects/<?= sanitize($f['stored_name']) ?>" target="_blank" download style="display:flex; align-items:center; gap:12px; padding:12px; background:var(--bg-secondary); border-radius:10px; text-decoration:none; color:inherit; transition:all var(--transition-base);" class="hover-lift">
                            <div style="width:40px; height:40px; background:var(--color-primary-100); border-radius:8px; display:flex; align-items:center; justify-content:center;">
                                <i class="fas <?= $icon ?>" style="color:var(--color-primary); font-size:18px;"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-weight:600; font-size:var(--font-size-sm); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitize($f['filename']) ?></div>
                                <div style="font-size:11px; color:var(--text-muted);"><?= $sizeLabel ?> • <?= date('M j', strtotime($f['uploaded_at'])) ?></div>
                            </div>
                            <i class="fas fa-download" style="color:var(--text-muted); font-size:14px;"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <h3 style="font-size:var(--font-size-lg); font-weight:700; margin-bottom:16px;">Actions</h3>
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <a href="invoices.php?project_id=<?= $projectId ?>" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
                            <i class="fas fa-file-invoice"></i> View Invoices
                        </a>
                        <a href="pay.php?project_id=<?= $projectId ?>" class="btn btn-primary btn-sm" style="justify-content:flex-start;">
                            <i class="fas fa-credit-card"></i> Make Payment
                        </a>
                        <a href="mailto:<?= getSetting('contact_email', 'njabulod.hlongwane@gmail.com') ?>?subject=Project: <?= urlencode($project['title']) ?>" class="btn btn-outline btn-sm" style="justify-content:flex-start;">
                            <i class="fas fa-envelope"></i> Email Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>