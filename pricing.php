<?php
$pageTitle = 'Pricing';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Pricing</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Pricing</span>
      <h1 class="page-header-title">Transparent pricing.<br>No surprises.</h1>
      <p class="page-header-desc">Choose the plan that fits your needs. All plans include our core commitment: quality, on time, every time.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="pricing-grid">
      <div class="pricing-card reveal">
        <h3 class="pricing-name">Starter</h3>
        <p class="pricing-desc">Perfect for MVPs, small web apps, and single AI agents.</p>
        <div class="pricing-price">R13,000<span>/project</span></div>
        <p class="pricing-period">One-time fee</p>
        <div class="pricing-features">
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            5-Page Website or Simple SaaS MVP
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Basic Data Pipeline Setup
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            1 Custom AI Agent / Chatbot
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            30 Days Support
          </div>
          <div class="pricing-feature" style="color: var(--text-muted);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            Advanced Analytics
          </div>
        </div>
        <a href="calculator.php" class="btn btn-secondary" style="width: 100%;">Get Started</a>
      </div>

      <div class="pricing-card featured reveal">
        <h3 class="pricing-name">Professional</h3>
        <p class="pricing-desc">Growing businesses needing robust platforms and data intelligence.</p>
        <div class="pricing-price">R25,000<span>/project</span></div>
        <p class="pricing-period">One-time fee</p>
        <div class="pricing-features">
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Full-Stack Web Application
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Data Warehouse + BI Dashboard
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Multi-Agent AI Workflow
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            API Development & Integration
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            90 Days Support
          </div>
        </div>
        <a href="calculator.php" class="btn btn-primary" style="width: 100%;">Get Started</a>
      </div>

      <div class="pricing-card reveal">
        <h3 class="pricing-name">Enterprise</h3>
        <p class="pricing-desc">Large-scale systems, enterprise data platforms, and autonomous operations.</p>
        <div class="pricing-price">Custom<span>/retainer</span></div>
        <p class="pricing-period">Monthly or project-based</p>
        <div class="pricing-features">
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Unlimited Scope & Custom Architecture
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Real-time Data Engineering
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Autonomous AI Agent Ecosystems
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Dedicated DevOps & Support
          </div>
          <div class="pricing-feature">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Monthly Retainer Available
          </div>
        </div>
        <a href="contact.php?plan=enterprise" class="btn btn-secondary" style="width: 100%;">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="section" style="background: var(--bg-surface);">
  <div class="container">
    <div class="section-header center reveal">
      <span class="section-label">FAQ</span>
      <h2 class="section-title">Common Questions</h2>
    </div>
    <div class="faq-list">
      <div class="faq-item reveal">
        <button class="faq-question" onclick="this.parentElement.classList.toggle('open')">
          <span>What's included in support?</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="faq-answer"><p>Support includes bug fixes, minor updates, performance monitoring, and email support during business hours. Professional and Enterprise plans include priority response times.</p></div>
      </div>
      <div class="faq-item reveal">
        <button class="faq-question" onclick="this.parentElement.classList.toggle('open')">
          <span>Can I upgrade my plan later?</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="faq-answer"><p>Absolutely. Many clients start with a Starter MVP and upgrade to Professional or Enterprise as their needs grow. We'll credit your initial investment toward the upgrade.</p></div>
      </div>
      <div class="faq-item reveal">
        <button class="faq-question" onclick="this.parentElement.classList.toggle('open')">
          <span>How does payment work?</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="faq-answer"><p>We typically split payments into milestones: 50% upfront to begin work, 25% at the midpoint demo, and 25% upon final delivery. Enterprise retainers are billed monthly.</p></div>
      </div>
      <div class="faq-item reveal">
        <button class="faq-question" onclick="this.parentElement.classList.toggle('open')">
          <span>What if I need something not listed?</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
        <div class="faq-answer"><p>Every project is unique. Book a free consultation and we'll create a custom proposal tailored to your specific requirements and budget.</p></div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
