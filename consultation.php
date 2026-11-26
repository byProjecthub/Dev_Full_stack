<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $data = [
            'name'            => sanitize($_POST['name'] ?? ''),
            'email'           => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'phone'           => sanitize($_POST['phone'] ?? ''),
            'company'         => sanitize($_POST['company'] ?? ''),
            'service_interest'=> sanitize($_POST['service_interest'] ?? ''),
            'budget_range'    => sanitize($_POST['budget_range'] ?? ''),
            'timeline'        => sanitize($_POST['timeline'] ?? ''),
            'message'         => sanitize($_POST['message'] ?? ''),
        ];

        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = db();
            if ($db) {
                $stmt = $db->prepare("INSERT INTO consultations (name, email, phone, company, service_interest, budget_range, timeline, message, status, source, created_at) VALUES (?,?,?,?,?,?,?,?, 'new', 'website', NOW())");
                $stmt->execute(array_values($data));
            }
            sendConsultationConfirmation($data);
            sendConsultationAdminAlert($data);
            $success = 'Your consultation request has been submitted. We will respond within 24 business hours.';
        }
    }
}

$pageTitle = 'Free Consultation';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Consult</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Free Consultation</span>
      <h1 class="page-header-title">Let's discuss your<br>next project.</h1>
      <p class="page-header-desc">Book a free 30-minute call. No commitment, no pressure — just honest advice about your technology needs.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="contact-grid">
      <div class="reveal">
        <span class="section-label">What to Expect</span>
        <h2 class="section-title" style="margin-bottom: var(--space-8);">Here's how it works.</h2>

        <div style="display: flex; flex-direction: column; gap: var(--space-8);">
          <div style="display: flex; gap: var(--space-4);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: var(--accent-indigo); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff; flex-shrink: 0;">1</div>
            <div>
              <h4 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-2);">Book Your Slot</h4>
              <p style="font-size: var(--text-base); color: var(--text-secondary); line-height: 1.6;">Pick a time that works for you from our calendar. We'll send a confirmation with a video call link.</p>
            </div>
          </div>
          <div style="display: flex; gap: var(--space-4);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: var(--accent-cyan); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff; flex-shrink: 0;">2</div>
            <div>
              <h4 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-2);">Discovery Call</h4>
              <p style="font-size: var(--text-base); color: var(--text-secondary); line-height: 1.6;">We'll discuss your goals, challenges, and budget. We'll ask questions and share initial thoughts.</p>
            </div>
          </div>
          <div style="display: flex; gap: var(--space-4);">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: var(--accent-pink); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #ffffff; flex-shrink: 0;">3</div>
            <div>
              <h4 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-2);">Proposal</h4>
              <p style="font-size: var(--text-base); color: var(--text-secondary); line-height: 1.6;">Within 48 hours, you'll receive a detailed proposal with scope, timeline, and pricing.</p>
            </div>
          </div>
        </div>

        <div style="margin-top: var(--space-12); padding: var(--space-8); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
          <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-4);">Not ready for a call?</h3>
          <p style="font-size: var(--text-sm); color: var(--text-secondary); margin-bottom: var(--space-6);">Use our price calculator to get an instant estimate for your project.</p>
          <a href="calculator.php" class="btn btn-secondary">
            Price Calculator
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      </div>

      <div class="contact-form-card reveal">
        <h3 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-2);">Book your free call</h3>
        <p style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-8);">Fill out the form and we'll find a time that works.</p>

        <?php if ($success): ?>
          <div class="alert alert-success" style="margin-bottom: var(--space-6);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error" style="margin-bottom: var(--space-6);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <form action="" method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

          <div class="grid-2" style="gap: var(--space-4);">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" class="form-input" placeholder="John Doe" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" class="form-input" placeholder="john@company.com" required>
            </div>
          </div>

          <div class="grid-2" style="gap: var(--space-4);">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-input" placeholder="+27 68 826 1507">
            </div>
            <div class="form-group">
              <label class="form-label">Company</label>
              <input type="text" name="company" class="form-input" placeholder="Your company name">
            </div>
          </div>

          <div class="grid-2" style="gap: var(--space-4);">
            <div class="form-group">
              <label class="form-label">Service Interest</label>
              <select name="service_interest" class="form-select">
                <option value="">Select...</option>
                <option value="Custom Software & Web">Custom Software & Web</option>
                <option value="Data Engineering">Data Engineering</option>
                <option value="AI Agent Development">AI Agent Development</option>
                <option value="Multiple / Unsure">Multiple / Unsure</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Budget Range</label>
              <select name="budget_range" class="form-select">
                <option value="">Select...</option>
                <option value="R15k – R50k">R15k – R50k</option>
                <option value="R50k – R150k">R50k – R150k</option>
                <option value="R150k – R500k">R150k – R500k</option>
                <option value="R500k+">R500k+</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Desired Timeline</label>
            <select name="timeline" class="form-select">
              <option value="">Select...</option>
              <option value="Less than 1 month">Less than 1 month</option>
              <option value="1 – 3 months">1 – 3 months</option>
              <option value="3 – 6 months">3 – 6 months</option>
              <option value="6+ months">6+ months</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Project Brief *</label>
            <textarea name="message" class="form-textarea" rows="6" required placeholder="Describe your challenge, goals, and any existing systems..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
            Request Consultation
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
