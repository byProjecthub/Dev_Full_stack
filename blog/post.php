<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) redirect('index.php');

$db = db();
$post = null;

if ($db) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published' AND published_at <= NOW()");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
}

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Not Found';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <section class="section" style="padding-top:140px; text-align:center;">
        <div class="container">
            <h1>Article Not Found</h1>
            <p style="color:var(--text-secondary); margin:16px 0 32px;">The article you're looking for doesn't exist or has been removed.</p>
            <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Blog</a>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Increment view count
$db->prepare("UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?")->execute([$post['id']]);

// Related posts
$related = [];
$stmt = $db->prepare("SELECT slug, title, excerpt, featured_image, category FROM blog_posts WHERE status = 'published' AND id != ? AND category = ? ORDER BY published_at DESC LIMIT 3");
$stmt->execute([$post['id'], $post['category']]);
$related = $stmt->fetchAll();

$pageTitle = $post['title'];
$metaDesc = $post['meta_description'] ?: substr(strip_tags($post['excerpt']), 0, 160);
require_once __DIR__ . '/../includes/header.php';
?>

<article class="section" style="padding-top:140px;">
    <div class="container" style="max-width:800px;">
        <!-- Breadcrumb -->
        <nav style="margin-bottom:24px; font-size:var(--font-size-sm);">
            <a href="../index.php" style="color:var(--text-muted);">Home</a>
            <span style="color:var(--text-muted); margin:0 8px;">/</span>
            <a href="index.php" style="color:var(--text-muted);">Blog</a>
            <span style="color:var(--text-muted); margin:0 8px;">/</span>
            <span style="color:var(--text-primary);"><?= sanitize($post['category']) ?></span>
        </nav>

        <!-- Header -->
        <span class="badge badge-primary" style="margin-bottom:16px; display:inline-block;"><?= sanitize($post['category']) ?></span>
        <h1 style="font-size:clamp(var(--font-size-3xl), 4vw, var(--font-size-5xl)); font-weight:800; line-height:1.15; margin-bottom:16px;"><?= sanitize($post['title']) ?></h1>
        
        <div style="display:flex; align-items:center; gap:16px; margin-bottom:32px; color:var(--text-muted); font-size:var(--font-size-sm); flex-wrap:wrap;">
            <span><i class="far fa-calendar"></i> <?= date('F j, Y', strtotime($post['published_at'])) ?></span>
            <span><i class="far fa-eye"></i> <?= number_format($post['view_count']) ?> views</span>
            <span><i class="far fa-clock"></i> <?= ceil(str_word_count(strip_tags($post['content'])) / 200) ?> min read</span>
        </div>

        <!-- Featured Image -->
        <?php if ($post['featured_image']): ?>
        <div style="border-radius:var(--border-radius-lg); overflow:hidden; margin-bottom:32px;">
            <img src="<?= sanitize($post['featured_image']) ?>" alt="<?= sanitize($post['title']) ?>" style="width:100%; height:auto;">
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div style="font-size:var(--font-size-lg); line-height:1.8; color:var(--text-secondary);">
            <?= $post['content'] ?>
        </div>

        <!-- Tags -->
        <?php 
        $tags = json_decode($post['tags'] ?? '[]', true);
        if (!empty($tags)): 
        ?>
        <div style="margin-top:40px; padding-top:24px; border-top:1px solid var(--border-color);">
            <span style="font-weight:600; margin-right:12px;">Tags:</span>
            <?php foreach ($tags as $tag): ?>
            <span class="badge" style="background:var(--bg-secondary); margin-right:8px;"><?= sanitize($tag) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Share -->
        <div style="margin-top:24px; display:flex; gap:12px; align-items:center;">
            <span style="font-weight:600; font-size:var(--font-size-sm);">Share:</span>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="social-link" style="width:36px; height:36px;"><i class="fab fa-twitter"></i></a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="social-link" style="width:36px; height:36px;"><i class="fab fa-linkedin-in"></i></a>
        </div>
    </div>
</article>

<!-- Related Posts -->
<?php if (!empty($related)): ?>
<section class="section" style="background:var(--bg-secondary);">
    <div class="container" style="max-width:800px;">
        <h2 style="font-size:var(--font-size-2xl); font-weight:700; margin-bottom:24px;">Related Articles</h2>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <?php foreach ($related as $r): ?>
            <a href="post.php?slug=<?= sanitize($r['slug']) ?>" style="display:flex; gap:16px; padding:16px; background:var(--bg-card); border-radius:var(--border-radius-md); border:1px solid var(--border-color); text-decoration:none; color:inherit; transition:all var(--transition-base);" class="hover-lift">
                <div style="width:120px; height:80px; border-radius:var(--border-radius-sm); overflow:hidden; flex-shrink:0;">
                    <img src="<?= sanitize($r['featured_image'] ?: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=300') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <div>
                    <span style="font-size:var(--font-size-xs); color:var(--color-accent); font-weight:600;"><?= sanitize($r['category']) ?></span>
                    <h4 style="font-size:var(--font-size-base); font-weight:600; margin:4px 0;"><?= sanitize($r['title']) ?></h4>
                    <p style="font-size:var(--font-size-sm); color:var(--text-muted); line-height:1.5;"><?= sanitize($r['excerpt'] ?: substr(strip_tags($r['content']), 0, 80) . '...') ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>