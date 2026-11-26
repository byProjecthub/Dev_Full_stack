<?php
$pageTitle = 'Services';
require_once __DIR__ . '/includes/functions.php';

$services = [];
if (function_exists('getAllServices')) {
    $services = getAllServices('active');
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Services</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Services</span>
      <h1 class="page-header-title">Everything you need<br>to build and scale.</h1>
      <p class="page-header-desc">End-to-end software services designed to accelerate your business growth.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="services-grid">
      <?php if (!empty($services)): ?>
        <?php
        $accentMap = ['accent-cyan','accent-blue','accent-indigo','accent-violet','accent-pink','accent-amber','accent-rose','accent-emerald'];
        $vpUsedServiceAnchors = [];
        foreach ($services as $i => $svc):
          $accent = $accentMap[$i % count($accentMap)];
          $features = json_decode($svc['features'] ?? '[]', true);
          $vpServiceKey = strtolower(($svc['slug'] ?? '') . ' ' . ($svc['title'] ?? ''));
          $vpServiceAnchor = str_contains($vpServiceKey, 'software') ? 'software' : (str_contains($vpServiceKey, 'data') ? 'data' : (preg_match('/\bai\b|ai-agent|artificial|automation/', $vpServiceKey) ? 'ai' : null));
          if ($vpServiceAnchor && isset($vpUsedServiceAnchors[$vpServiceAnchor])) $vpServiceAnchor = null;
          if ($vpServiceAnchor) $vpUsedServiceAnchors[$vpServiceAnchor] = true;
        ?>
        <div class="service-card <?php echo $accent; ?> reveal" id="<?php echo htmlspecialchars($svc['slug'] ?? ''); ?>">
          <?php if ($vpServiceAnchor && $vpServiceAnchor !== ($svc['slug'] ?? '')): ?><span class="vp-service-anchor" id="<?= $vpServiceAnchor ?>" aria-hidden="true"></span><?php endif; ?>
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
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
            Get Started
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="service-card accent-cyan reveal" id="software">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg></div>
          <h3 class="service-title">Custom Software Development</h3>
          <p class="service-desc">Full-stack web and mobile applications built with React, Vue, Node.js, Laravel, and Python. From MVPs to enterprise platforms.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Web Applications</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Mobile Apps</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> API Development</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> System Integration</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-blue reveal" id="data">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg></div>
          <h3 class="service-title">Data Engineering</h3>
          <p class="service-desc">ETL pipelines, data warehouses, and real-time analytics. We turn your data into your most valuable asset.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> ETL Pipelines</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Data Warehousing</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Real-time Analytics</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> BI Dashboards</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-violet reveal" id="ai">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.387-1 1.732V7h1a7 7 0 0 1 7 7v4a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-4a7 7 0 0 1 7-7h1V5.732A2.001 2.001 0 0 1 12 2z"></path><path d="M9 21h6"></path></svg></div>
          <h3 class="service-title">AI Agents & Automation</h3>
          <p class="service-desc">Intelligent agents that handle support, process documents, analyze data, and make decisions autonomously.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Chatbots & Support</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Document Processing</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Predictive Analytics</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Workflow Automation</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-pink reveal" id="cloud">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg></div>
          <h3 class="service-title">Cloud & DevOps</h3>
          <p class="service-desc">Cloud architecture, CI/CD pipelines, and infrastructure as code. Deploy faster with zero downtime.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> AWS/Azure/GCP</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> CI/CD Pipelines</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Kubernetes</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Monitoring & Alerts</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-amber reveal" id="consulting">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></div>
          <h3 class="service-title">Tech Consulting</h3>
          <p class="service-desc">Strategic technology advisory to help you make the right decisions and avoid costly mistakes.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Architecture Review</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Tech Stack Selection</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Digital Strategy</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Security Audits</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
        <div class="service-card accent-emerald reveal" id="support">
          <div class="service-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></div>
          <h3 class="service-title">Ongoing Support</h3>
          <p class="service-desc">24/7 maintenance, monitoring, and continuous improvement. We stay with you long after launch.</p>
          <ul style="display: flex; flex-direction: column; gap: var(--space-2); margin-bottom: var(--space-6);">
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 24/7 Monitoring</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Bug Fixes & Updates</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Performance Tuning</li>
            <li style="font-size: var(--text-sm); color: var(--text-secondary); display: flex; align-items: center; gap: var(--space-2);"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent-emerald)" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Feature Development</li>
          </ul>
          <a href="consultation.php" class="service-link">Get Started <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
