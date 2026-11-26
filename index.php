<?php declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

if ($uri && $uri !== 'index.php' && file_exists($uri . '.php')) {
    include $uri . '.php';
    exit;
}

$pageTitle = 'Home';
$pageDescription = 'Vueports Solutions — Custom Software, Data Engineering & AI Agents. Your IT Solutions Production Partner.';

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Fetch services from database
$services = [];
if (function_exists('getAllServices')) {
    $services = getAllServices('active');
}
if (empty($services) && function_exists('getServices')) {
    $services = getServices(6);
}

// Fetch portfolio items for testimonials
$portfolio_items = [];
if (function_exists('getPortfolioItems')) {
    $portfolio_items = getPortfolioItems(6, 'active');
}

// Stats for counters
$stats = [
    ['count' => 12, 'label' => 'Projects Delivered', 'suffix' => '+'],
    ['count' => 8,  'label' => 'Enterprise Clients', 'suffix' => '+'],
    ['count' => 3,  'label' => 'Core Specializations', 'suffix' => ''],
    ['count' => 5,  'label' => 'Years Experience', 'suffix' => '+'],
];
?>

<!-- Hero Section -->
<section class="vp-hero"><div class="container"><div class="vp-hero-top"><span class="vp-kicker">SOFTWARE / DATA / APPLIED AI</span><span class="vp-location">SOUTH AFRICA ↗ BUILT FOR WHAT’S NEXT</span></div><div class="vp-hero-grid"><div class="vp-hero-copy"><h1>Your business.<br>More capable.<br><span>More connected.</span></h1><p>We connect the systems, information and workflows that move your business. Custom software, data engineering and AI agents — with purpose.</p><div class="vp-hero-actions"><a href="consultation.php" class="btn btn-primary btn-lg">Build what’s next <span class="vp-cta-arrow" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg></span></a><a href="#expertise" class="vp-text-link">Explore our expertise <span aria-hidden="true">↓</span></a></div></div><div class="vp-hero-visual"><img src="images/vueports.png" alt="Vueports software studio artwork" fetchpriority="high"><div class="vp-visual-caption"><span>THE VUEPORTS WAY</span><span>IDEAS INTO WORKING SYSTEMS</span></div></div></div><div class="vp-hero-bottom"><span>YOUR IT SOLUTIONS PRODUCTION PARTNER.</span><span>STRATEGY → ENGINEERING → EVOLUTION</span></div></div></section>

<!-- Logo Bar -->
<section class="logo-bar section-sm">
  <div class="container">
    <p class="logo-bar-label reveal">Trusted by forward-thinking companies</p>
    <div class="logo-bar-grid reveal">
      <div class="logo-bar-item">JPP Municipality</div>
      <div class="logo-bar-item">AlphaDotX</div>
    </div>
  </div>
</section>

<!-- Services Section -->
<section class="section" id="expertise">
  <div class="container">
    <div class="section-header center reveal">
      <span class="section-label">What We Do</span>
      <h2 class="section-title">Everything you need to<br>build and scale.</h2>
      <p class="section-desc">From custom applications to intelligent AI agents — we handle the tech so you can focus on growth.</p>
    </div>

    <div class="services-grid">
      <?php if (!empty($services)): ?>
        <?php 
        $accentMap = ['accent-cyan','accent-blue','accent-indigo','accent-violet','accent-pink','accent-amber','accent-rose','accent-emerald'];
        foreach (array_slice($services, 0, 6) as $i => $svc): 
          $accent = $accentMap[$i % count($accentMap)];
          $features = json_decode($svc['features'] ?? '[]', true);
        ?>
        <div class="service-card <?php echo $accent; ?> reveal">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="16 18 22 12 16 6"></polyline>
              <polyline points="8 6 2 12 8 18"></polyline>
            </svg>
          </div>
          <h3 class="service-title"><?php echo htmlspecialchars($svc['title'] ?? ''); ?></h3>
          <p class="service-desc"><?php echo htmlspecialchars($svc['description'] ?? ''); ?></p>
          <?php if (!empty($features)): ?>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <?php foreach (array_slice($features, 0, 4) as $f): ?>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <?php echo htmlspecialchars($f); ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <a href="service-detail.php?slug=<?php echo urlencode($svc['slug'] ?? ''); ?>" class="service-link">
            Learn More
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"></line>
              <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
          </a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="service-card accent-cyan reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg></div>
          <h3 class="service-title">Custom Software</h3>
          <p class="service-desc">Tailored web and mobile applications built with modern stacks. Scalable, secure, and designed around your workflow.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-blue reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg></div>
          <h3 class="service-title">Data Engineering</h3>
          <p class="service-desc">ETL pipelines, data warehouses, and real-time analytics. Turn raw data into actionable business intelligence.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-violet reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.387-1 1.732V7h1a7 7 0 0 1 7 7v4a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-4a7 7 0 0 1 7-7h1V5.732A2.001 2.001 0 0 1 12 2z"></path><path d="M9 21h6"></path></svg></div>
          <h3 class="service-title">AI Agents</h3>
          <p class="service-desc">Intelligent automation that handles repetitive tasks., answers queries, and makes decisions — 24/7, without breaks.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-pink reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg></div>
          <h3 class="service-title">Cloud Solutions</h3>
          <p class="service-desc">AWS, Azure, and GCP architecture, migration, and DevOps. Infrastructure that scales with your ambition.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-amber reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></div>
          <h3 class="service-title">Tech Consulting</h3>
          <p class="service-desc">Strategic guidance on architecture, tech stack selection, and digital transformation roadmaps.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-emerald reveal">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></div>
          <h3 class="service-title">Ongoing Support</h3>
          <p class="service-desc">Maintenance, monitoring, and continuous improvement. We stay with you long after launch day.</p>
          <a href="services.php" class="service-link">Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- About Section -->
<section class="section" style="background: var(--bg-surface);">
  <div class="container">
    <div class="about-grid">
      <div class="about-visual reveal">
        <div class="visual-accent"></div>
        <div class="visual-box">
          <div class="label" style="margin-bottom: var(--space-6);">Why Vueports?</div>
          <h3 class="heading-lg" style="margin-bottom: var(--space-4);">We build software that actually works.</h3>
          <p class="body-base">No bloated code. No over-engineering. Just clean, scalable solutions that solve real problems and deliver measurable results.</p>
          <div class="about-values">
            <span class="value-tag">Performance First</span>
            <span class="value-tag">Clean Architecture</span>
            <span class="value-tag">Future-Proof</span>
            <span class="value-tag">User-Centric</span>
            <span class="value-tag">Transparent</span>
          </div>
        </div>
      </div>
      <div class="reveal">
        <span class="section-label">About Us</span>
        <h2 class="section-title" style="margin-bottom: var(--space-6);">Technology partners,<br>not just vendors.</h2>
        <p class="body-lg" style="margin-bottom: var(--space-6);">
          Vueports Solutions was founded on a simple belief: technology should make business easier, not harder. We partner with companies to build software that drives growth, reduces friction, and creates competitive advantage.
        </p>
        <p class="body-base" style="margin-bottom: var(--space-8);">
          Our team combines deep technical expertise with business acumen. We don't just write code — we solve problems, optimize processes, and help you make better decisions with data.
        </p>
        <a href="about.php" class="btn-arrow">
          More About Us
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="section-sm">
  <div class="container">
    <div class="stats-grid reveal">
      <?php foreach ($stats as $stat): ?>
      <div class="stat-item">
        <div class="stat-value" data-count="<?php echo (int)$stat['count']; ?>" data-suffix="<?php echo htmlspecialchars($stat['suffix']); ?>">0<?php echo htmlspecialchars($stat['suffix']); ?></div>
        <div class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Feature Cards Section -->
<section class="section" id="approach">
  <div class="container">
    <div class="section-header center reveal">
      <span class="section-label">Our Approach</span>
      <h2 class="section-title">How we turn ideas<br>into reality.</h2>
    </div>

    <div class="grid-3" style="gap: var(--space-6);">
      <div class="feature-card accent-cyan reveal">
        <h3 class="card-title" style="color: var(--accent-cyan);">Discover</h3>
        <p class="card-desc">We start by understanding your business, your users, and your goals. Deep discovery leads to better solutions.</p>
        <a href="consultation.php" class="card-cta" style="color: var(--accent-cyan);">
          Our Process
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
      </div>
      <div class="feature-card accent-indigo reveal">
        <h3 class="card-title" style="color: var(--accent-indigo);">Build</h3>
        <p class="card-desc">Agile development with weekly demos. You see progress in real-time and can pivot whenever needed.</p>
        <a href="services.php" class="card-cta" style="color: var(--accent-indigo);">
          Tech Stack
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
      </div>
      <div class="feature-card accent-pink reveal">
        <h3 class="card-title" style="color: var(--accent-pink);">Launch</h3>
        <p class="card-desc">Smooth deployment with monitoring, documentation, and training. We don't disappear after go-live.</p>
        <a href="portfolio.php" class="card-cta" style="color: var(--accent-pink);">
          Case Studies
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Testimonials -->
<section class="section" style="background: var(--bg-surface);">
  <div class="container">
    <div class="section-header center reveal">
      <span class="section-label">Testimonials</span>
      <h2 class="section-title">Loved by teams<br>everywhere.</h2>
    </div>

    <div class="testimonials-grid">
      <?php 
      $hasTestimonials = false;
      if (!empty($portfolio_items)):
        foreach ($portfolio_items as $t):
          if (empty($t['testimonial'])) continue;
          $hasTestimonials = true;
      ?>
      <div class="testimonial-card reveal">
        <p class="testimonial-quote">"<?php echo htmlspecialchars($t['testimonial']); ?>"</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar"><?php echo strtoupper(substr($t['client_name'] ?? 'C', 0, 1)); ?></div>
          <div>
            <div class="testimonial-name"><?php echo htmlspecialchars($t['client_name'] ?? 'Client'); ?></div>
            <div class="testimonial-role"><?php echo htmlspecialchars($t['title'] ?? ''); ?></div>
          </div>
        </div>
      </div>
      <?php 
        endforeach;
      endif;
      
      if (!$hasTestimonials):
      ?>
      <div class="testimonial-card reveal">
        <p class="testimonial-quote">"Vueports transformed our legacy system into a modern platform in just 8 weeks. The team's technical depth and communication were exceptional."</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar">UD</div>
          <div>
            <div class="testimonial-name">Usanda Dakuda</div>
            <div class="testimonial-role">IT MANAGER, JPP Municipality</div>
          </div>
        </div>
      </div>
      <div class="testimonial-card reveal">
        <p class="testimonial-quote">"Their AI chatbot reduced our customer support workload by 60%. It's like having a full-time employee that never sleeps."</p>
        <div class="testimonial-author">
          <div class="testimonial-avatar">TM</div>
          <div>
            <div class="testimonial-name">Tebogo Madimeng</div>
            <div class="testimonial-role">Founder, AlphaDotX</div>
          </div>
        </div>
      </div>
       <?php endif; ?>
    </div>
  </div>
</section>

<!-- CTA Section -->
<section class="section cta-section">
  <div class="container">
    <div class="cta-section-inner reveal">
      <div class="cta-section-bg-text">Build.</div>
      <div class="cta-section-content">
        <h2 class="cta-section-title">Ready to build<br>something great?</h2>
        <p class="cta-section-desc">Book a free consultation and let's discuss how we can help your business grow with technology.</p>
        <div style="display: flex; gap: var(--space-4); justify-content: center; flex-wrap: wrap;">
          <a href="consultation.php" class="btn btn-primary btn-lg">
            Book a Call
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
          <a href="calculator.php" class="btn btn-secondary btn-lg">Price Calculator</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Large CTA Cards -->
<section class="section-sm">
  <div class="container">
    <div class="grid-3" style="gap: var(--space-6);">
      <a href="consultation.php" class="cta-card-large reveal">
        <h3 class="cta-title">Book a Call</h3>
      </a>
      <a href="calculator.php" class="cta-card-large reveal">
        <h3 class="cta-title">Get a Quote</h3>
      </a>
      <a href="contact.php" class="cta-card-large reveal">
        <h3 class="cta-title">Send a Message</h3>
      </a>
    </div>
  </div>
</section>

<script>
const statItems = document.querySelectorAll('.stat-item .stat-value');
const animateCounter = (el) => {
  const target = parseInt(el.dataset.count || '0');
  const suffix = el.dataset.suffix || '';
  const duration = 2000;
  const start = performance.now();
  const step = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(ease * target) + suffix;
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
};
const statObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounter(entry.target);
      statObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });
statItems.forEach(el => statObserver.observe(el));
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
