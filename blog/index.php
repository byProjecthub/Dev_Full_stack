<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/emails.php';


$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$category = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$db = db();
$posts = [];
$totalPosts = 0;

if ($db) {
    $where = "WHERE status = 'published' AND published_at <= NOW()";
    $params = [];
    
    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
    }
    if ($search) {
        $where .= " AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM blog_posts $where");
    $countStmt->execute($params);
    $totalPosts = (int) $countStmt->fetchColumn();
    
    $sql = "SELECT * FROM blog_posts $where ORDER BY published_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();
}

$totalPages = (int) ceil($totalPosts / $perPage);

// Categories
$categories = [];
if ($db) {
    $categories = $db->query("SELECT DISTINCT category FROM blog_posts WHERE status = 'published' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Blog';

?>

<section class="section" style="padding-top:140px;">
    <div class="container">
        <div class="section-header center">
            <span class="section-tag">/ Blog</span>
            <h1 class="section-title">Insights & <span class="highlight">Articles</span></h1>
            <p class="section-desc">Thoughts on software engineering, data, and AI.</p>
        </div>

        <!-- Search & Filter -->
        <div style="display:flex; gap:16px; margin-bottom:40px; flex-wrap:wrap; justify-content:center;">
            <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap;">
                <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search articles..." class="form-input" style="min-width:280px;">
                <?php if ($category): ?>
                <input type="hidden" name="category" value="<?= sanitize($category) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                <?php if ($search || $category): ?>
                <a href="index.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
            
            <?php if (!empty($categories)): ?>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat) ?>" class="badge <?= $category === $cat ? 'badge-primary' : '' ?>" style="text-decoration:none;">
                    <?= sanitize($cat) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-newspaper"></i></div>
            <h3 class="empty-state-title">No articles yet</h3>
            <p class="empty-state-desc">Check back soon for insights on software, data, and AI.</p>
        </div>
        <?php else: ?>

        <div class="grid grid-cols-3" style="gap:24px;">
            <?php foreach ($posts as $post): 
                $image = $post['featured_image'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600';
            ?>
            <article class="card card-hover">
                <a href="post.php?slug=<?= sanitize($post['slug']) ?>" style="text-decoration:none; color:inherit;">
                    <div style="height:200px; overflow:hidden; border-radius:var(--border-radius-md); margin-bottom:16px;">
                        <img src="<?= sanitize($image) ?>" alt="<?= sanitize($post['title']) ?>" style="width:100%; height:100%; object-fit:cover; transition:transform var(--transition-slow);" class="hover-scale">
                    </div>
                    <span class="badge badge-primary" style="margin-bottom:12px; display:inline-block;"><?= sanitize($post['category']) ?></span>
                    <h3 style="font-size:var(--font-size-lg); font-weight:700; margin-bottom:8px; line-height:1.3;"><?= sanitize($post['title']) ?></h3>
                    <p style="color:var(--text-secondary); font-size:var(--font-size-sm); line-height:1.6; margin-bottom:12px;"><?= sanitize($post['excerpt'] ?: substr(strip_tags($post['content']), 0, 150) . '...') ?></p>
                    <div style="display:flex; align-items:center; gap:12px; font-size:var(--font-size-xs); color:var(--text-muted);">
                        <span><i class="far fa-calendar"></i> <?= date('M j, Y', strtotime($post['published_at'])) ?></span>
                        <span><i class="far fa-eye"></i> <?= number_format($post['view_count']) ?></span>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top:48px; justify-content:center;">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <?php if ($i == $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="?page=<?= $i ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?><?= $category ? '&category=' . urlencode($category) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>