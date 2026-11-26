<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) redirect('services.php');

$db = db();
$service = null;
if ($db) {
    $stmt = $db->prepare("SELECT * FROM services WHERE slug = ? AND status = 'active'");
    $stmt->execute([$slug]);
    $service = $stmt->fetch();
}

if (!$service) redirect('services.php');

$pageTitle = $service['title'];
require_once __DIR__ . '/includes/header.php';
$features = json_decode($service['features'] ?? '[]', true);

// Related services
$related = [];
if ($db) {
    $stmt = $db->prepare("SELECT * FROM services WHERE slug != ? AND status = 'active' ORDER BY RANDOM() LIMIT 2");
    $stmt->execute([$slug]);
    $related = $stmt->fetchAll();
}
?>

<section class="page-header">
    <div class="container">
        <span class="section-tag"><?= sanitize($service['title']) ?></span>
        <h1 class="page-header-title"><?= sanitize($service['title']) ?></h1>
        <p class="page-header-desc"><?= sanitize($service['description']) ?></p>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="service-detail-card">
            <div class="service-detail-visual">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
            </div>
            <div class="service-detail-content">
                <h2>Overview</h2>
                <p><?= nl2br(sanitize($service['long_description'])) ?></p>

                <h3 style="margin-top: var(--space-6); margin-bottom: var(--space-4); font-size: var(--text-xl);">What You Get</h3>
                <ul class="feature-list">
                    <?php foreach ($features as $f): ?>
                    <li><?= sanitize($f) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="price-box">
                    <strong>Investment Range:</strong>
                    <span class="price-range">R<?= sanitize($service['price_min']) ?> - R<?= sanitize($service['price_max']) ?></span>
                    <p class="price-note">Final quotes depend on scope, integrations, and timeline.</p>
                </div>

                <div style="display: flex; gap: var(--space-4); flex-wrap: wrap;">
                    <a href="contact.php?service=<?= sanitize($service['slug']) ?>" class="btn btn-primary btn-lg">Discuss Your Project</a>
                    <a href="calculator.php?service=<?= sanitize($service['slug']) ?>" class="btn btn-outline btn-lg">Get Estimate</a>
                </div>
            </div>
        </div>

        <!-- Related Services -->
        <?php if (!empty($related)): ?>
        <div style="margin-top: var(--space-16);">
            <h3 style="text-align: center; margin-bottom: var(--space-8); font-size: var(--text-2xl);">Explore Related Services</h3>
            <div class="grid-2" style="max-width: 800px; margin: 0 auto;">
                <?php foreach ($related as $r): ?>
                <a href="service-detail.php?slug=<?= sanitize($r['slug']) ?>" class="card card-hover" style="text-decoration: none;">
                    <div style="font-size: 2rem; color: var(--accent-indigo); margin-bottom: var(--space-3);">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                    </div>
                    <h4 style="font-size: var(--text-lg); margin-bottom: var(--space-2); color: var(--text-primary);"><?= sanitize($r['title']) ?></h4>
                    <p style="color: var(--text-secondary); font-size: var(--text-sm);"><?= sanitize($r['description']) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
