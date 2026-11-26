<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Portfolio';
$pageDescription = 'View our featured projects and case studies.';

require_once __DIR__ . '/includes/header.php';

// Fetch all active portfolio items
$projects = [];
if (function_exists('getPortfolioItems')) {
    $projects = getPortfolioItems(50, 'active');
}

// Fallback if DB is empty
if (empty($projects)) {
    $projects = [
        [
            'slug' => 'finlytics-dashboard',
            'title' => 'Finlytics Dashboard',
            'description' => 'Real-time financial analytics with multi-tenant architecture. Processing 11 analytical screens.',
            'service_type' => 'SaaS / BI Dashboard',
            'image' => 'assets/images/Finlytics.png',
        ],
        [
            'slug' => 'reloventura-platform',
            'title' => 'Reloventura Platform',
            'description' => 'Booking engine with payment integration.',
            'service_type' => 'Web App',
            'image' => 'assets/images/reloventura1.png',
        ],
    ];
}
?>

<section class="page-header">
    <div class="container">
        <span class="section-tag">/ Portfolio</span>
        <h1 class="page-header-title">Our <span class="highlight">Work</span></h1>
        <p class="page-header-desc">Projects that deliver real business results.</p>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="portfolio-grid">
            <?php foreach ($projects as $project): ?>
            <div class="portfolio-card animate-on-scroll">
                <div class="portfolio-image">
                    <img src="<?= sanitize($project['image'] ?? 'assets/images/placeholder.svg') ?>" 
                         alt="<?= sanitize($project['title'] ?? 'Project') ?>" 
                         loading="lazy"
                         onerror="this.src='assets/images/placeholder.svg'">
                    <div class="portfolio-overlay">
                        <a href="portfolio-detail.php?slug=<?= sanitize($project['slug'] ?? '') ?>" class="btn btn-primary">View Project</a>
                    </div>
                </div>
                <div class="portfolio-info">
                    <span class="portfolio-tag"><?= sanitize($project['service_type'] ?? 'Project') ?></span>
                    <h3><?= sanitize($project['title'] ?? 'Untitled') ?></h3>
                    <p><?= sanitize($project['description'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
