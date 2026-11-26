<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// Get and clean the slug
$rawSlug = $_GET['slug'] ?? '';
$slug = trim($rawSlug);
$slugLookup = str_replace(' ', '-', strtolower($slug));

if (empty($slug)) {
    header('Location: portfolio.php');
    exit;
}

// Fetch from database
$project = null;
if (function_exists('getPortfolioItemBySlug')) {
    $project = getPortfolioItemBySlug($slugLookup);
}

if (empty($project) && $slug !== $slugLookup) {
    $project = getPortfolioItemBySlug($slug);
}

if (empty($project) && function_exists('getPortfolioItems')) {
    $all = getPortfolioItems(100, 'active');
    foreach ($all as $p) {
        $pSlug = $p['slug'] ?? '';
        if ($pSlug === $slugLookup || $pSlug === $slug) {
            $project = $p;
            break;
        }
    }
}

// Hardcoded fallback
if (empty($project)) {
    $fallbacks = [
        'finlytics-dashboard' => [
            'slug' => 'finlytics-dashboard',
            'title' => 'Finlytics Dashboard',
            'description' => 'Real-time financial analytics with multi-tenant architecture. Processing 11 Analytical Screens.',
            'long_description' => 'Finlytics is a comprehensive financial analytics dashboard built for enterprise clients. It features real-time data processing, multi-tenant architecture, role-based access control, and interactive Power BI-style visualizations. The platform processes over 500K transactions daily with sub-second query response times.',
            'service_type' => 'BI Dashboard',
            'client_name' => 'Finlytics Corp',
            'image' => 'assets/images/Finlytics.png',
            'gallery' => ['assets/images/Finlytics2.png', 'assets/images/Finlytics1.png'],
            'tech_stack' => ['React', 'Node.js', 'PostgreSQL', 'Redis', 'AWS', 'Docker'],
            'live_url' => '#',
            'github_url' => '#',
            'results' => ['56% faster load times', '99.9% uptime', '50+ daily transactions'],
            'testimonial' => 'Vueports Solutions did an amazing work with our web-app, everything he did to optimize our software help us to reduce our loading speed by 56%',
            'year' => '2023',
        ],
        'reloventura-platform' => [
            'slug' => 'reloventura-platform',
            'title' => 'Reloventura Platform',
            'description' => 'Booking engine with payment integration.',
            'long_description' => 'Reloventura is a full-featured booking and reservation platform for the travel industry. It includes an availability calendar, secure payment processing via PayFast, automated email notifications, and an admin dashboard for managing bookings, refunds, and reporting.',
            'service_type' => 'Web App',
            'client_name' => 'Reloventura Pty Ltd',
            'image' => 'assets/images/reloventura1.png',
            'gallery' => ['assets/images/reloventura.png', 'assets/images/reloventura2.png'],
            'tech_stack' => ['PHP', 'MySQL', 'JavaScript', 'PayFast API', 'Tailwind CSS', 'Laravel'],
            'live_url' => '#',
            'github_url' => '#',
            'results' => ['3x booking conversion', 'R50K processed', 'Zero downtime deployment'],
            'testimonial' => 'We\'ve never had come this far without Vueports Solutions\'s great attention to detail and care for the final product',
            'year' => '2022',
        ],
    ];
    $project = $fallbacks[$slugLookup] ?? $fallbacks[$slug] ?? null;
}

// 404 page
if (empty($project)) {
    $pageTitle = 'Project Not Found';
    $pageDescription = 'The requested project could not be found.';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="page-header" style="padding-top: 200px; padding-bottom: 120px;">
        <div class="container">
            <span class="section-tag">/ Error</span>
            <h1 class="page-header-title">Project Not <span class="highlight">Found</span></h1>
            <p class="page-header-desc">The project "<?= sanitize($slug) ?>" doesn't exist or has been removed.</p>
            <div style="margin-top: var(--space-8);">
                <a href="portfolio.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                    Back to Portfolio
                </a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Success
$pageTitle = sanitize($project['title'] ?? 'Project');
$pageDescription = sanitize($project['description'] ?? 'Portfolio project by Vueports Solutions.');

require_once __DIR__ . '/includes/header.php';

// Fetch related projects
$related = [];
if (function_exists('getRelatedProjects')) {
    $related = getRelatedProjects($project['slug'], 3);
} elseif (function_exists('getPortfolioItems')) {
    $all = getPortfolioItems(4, 'active');
    foreach ($all as $p) {
        if (($p['slug'] ?? '') !== $project['slug']) {
            $related[] = $p;
            if (count($related) >= 3) break;
        }
    }
}

// Ensure arrays
$techStack = $project['tech_stack'] ?? [];
if (is_string($techStack)) $techStack = json_decode($techStack, true) ?: [];
$results = $project['results'] ?? [];
if (is_string($results)) $results = json_decode($results, true) ?: [];
$gallery = $project['gallery'] ?? [];
if (is_string($gallery)) $gallery = json_decode($gallery, true) ?: [];
?>

<!-- Project Hero -->
<section class="page-header" style="padding-bottom: var(--space-12);">
    <div class="container">
        <span class="section-tag">/ Portfolio</span>
        <h1 class="page-header-title"><?= sanitize($project['title'] ?? 'Project') ?></h1>
        <p class="page-header-desc"><?= sanitize($project['description'] ?? '') ?></p>
    </div>
</section>

<!-- Project Detail -->
<section class="section section-alt">
    <div class="container">
        <div class="project-layout">

            <!-- Main Content -->
            <div class="project-main">
                <!-- Featured Image -->
                <div class="project-image">
                    <img src="<?= sanitize($project['image'] ?? 'assets/images/placeholder.svg') ?>" 
                         alt="<?= sanitize($project['title'] ?? 'Project') ?>"
                         onerror="this.src='assets/images/placeholder.svg'">
                </div>

                <!-- Description -->
                <div class="project-about">
                    <h2>About the Project</h2>
                    <p><?= sanitize($project['long_description'] ?? $project['description'] ?? 'No description available.') ?></p>
                </div>

                <!-- Results -->
                <?php if (!empty($results)): ?>
                <div class="project-results">
                    <h3>Key Results</h3>
                    <div class="results-grid">
                        <?php foreach ($results as $result): ?>
                        <div class="card result-card">
                            <div class="result-text"><?= sanitize($result) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Gallery -->
                <?php if (!empty($gallery)): ?>
                <div class="project-gallery">
                    <h3>Project Gallery</h3>
                    <div class="gallery-grid">
                        <?php foreach ($gallery as $img): ?>
                        <div class="gallery-item">
                            <img src="<?= sanitize($img) ?>" alt="Project screenshot" 
                                 onerror="this.src='assets/images/placeholder.svg'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Testimonial -->
                <?php if (!empty($project['testimonial'])): ?>
                <div class="card project-testimonial">
                    <div class="quote-icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    </div>
                    <p class="testimonial-text">"<?= sanitize($project['testimonial']) ?>"</p>
                    <?php if (!empty($project['client_name'])): ?>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            <?= strtoupper(substr($project['client_name'], 0, 1)) ?>
                        </div>
                        <div class="testimonial-info">
                            <div class="testimonial-name"><?= sanitize($project['client_name']) ?></div>
                            <div class="testimonial-role"><?= sanitize($project['service_type'] ?? 'Client') ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="project-sidebar">

                <!-- Project Meta -->
                <div class="card sidebar-card">
                    <h3>Project Details</h3>
                    <div class="meta-list">
                        <?php if (!empty($project['service_type'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Category</span>
                            <span class="meta-value"><?= sanitize($project['service_type']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($project['client_name'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Client</span>
                            <span class="meta-value"><?= sanitize($project['client_name']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($project['year'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Year</span>
                            <span class="meta-value"><?= sanitize((string)$project['year']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tech Stack -->
                <?php if (!empty($techStack)): ?>
                <div class="card sidebar-card">
                    <h3>Tech Stack</h3>
                    <div class="tech-tags">
                        <?php foreach ($techStack as $tech): ?>
                        <span class="skill-tag"><?= sanitize($tech) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="card sidebar-card sidebar-actions">
                    <?php if (!empty($project['live_url']) && $project['live_url'] !== '#'): ?>
                    <a href="<?= sanitize($project['live_url']) ?>" target="_blank" rel="noopener" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        View Live
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($project['github_url']) && $project['github_url'] !== '#'): ?>
                    <a href="<?= sanitize($project['github_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
                        View Source
                    </a>
                    <?php endif; ?>
                    <a href="consultation.php?project=<?= sanitize($project['slug'] ?? '') ?>" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Start Similar Project
                    </a>
                </div>

                <!-- Navigation -->
                <div class="sidebar-nav">
                    <a href="portfolio.php" class="btn btn-outline">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
                        All Projects
                    </a>
                    <a href="booking.php" class="btn btn-primary">
                        Hire Us
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: 6px;"><path d="M5 12h14M12 5l7 7-7 7"></path></svg>
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Related Projects -->
<?php if (!empty($related)): ?>
<section class="section">
    <div class="container">
        <div class="section-header center">
            <span class="section-tag">/ More Work</span>
            <h2 class="section-title">Related <span class="highlight">Projects</span></h2>
        </div>
        <div class="portfolio-grid related-grid">
            <?php foreach ($related as $r): ?>
            <div class="portfolio-card animate-on-scroll">
                <div class="portfolio-image">
                    <img src="<?= sanitize($r['image'] ?? 'assets/images/placeholder.svg') ?>" 
                         alt="<?= sanitize($r['title'] ?? 'Project') ?>" 
                         loading="lazy" 
                         onerror="this.src='assets/images/placeholder.svg'">
                    <div class="portfolio-overlay">
                        <a href="portfolio-detail.php?slug=<?= sanitize($r['slug'] ?? '') ?>" class="btn btn-primary">View Project</a>
                    </div>
                </div>
                <div class="portfolio-info">
                    <span class="portfolio-tag"><?= sanitize($r['service_type'] ?? 'Project') ?></span>
                    <h3><?= sanitize($r['title'] ?? 'Untitled') ?></h3>
                    <p><?= sanitize($r['description'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
