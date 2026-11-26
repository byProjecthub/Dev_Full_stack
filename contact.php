<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';
require_once __DIR__ . '/includes/security.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } elseif (!checkRateLimit($_SERVER['REMOTE_ADDR'] . ':contact', 5, 1)) {
        $error = 'Too many attempts. Please wait a minute.';
    } else {
        $name    = sanitize($_POST['name'] ?? '');
        $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone   = sanitize($_POST['phone'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        $service = sanitize($_POST['service_interest'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $db = db();
            if (!$db) {
                $error = 'Database connection failed. Please try again later.';
                error_log('CONTACT FORM ERROR: db() returned null');
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO messages (name, email, phone, subject, message, service_interest, status, created_at) VALUES (?,?,?,?,?,?, 'new', NOW()) RETURNING id");
                    $stmt->execute([$name, $email, $phone, $subject, $message, $service]);
                    $messageId = (int) $stmt->fetchColumn();
                    
                    if ($messageId === 0) {
                        throw new Exception('Insert succeeded but no ID returned');
                    }
                    
                    error_log("CONTACT FORM: Saved message ID $messageId from $email");
                    
                    $leadData = [
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'subject' => $subject,
                        'message' => $message,
                        'service_interest' => $service
                    ];
                    
                    $confirmOk = sendContactConfirmation($leadData);
                    $adminOk   = sendAdminLeadNotification($leadData);
                    
                    if (!$confirmOk) error_log("CONTACT FORM: sendContactConfirmation failed for $email");
                    if (!$adminOk) error_log("CONTACT FORM: sendAdminLeadNotification failed for $email");
                    
                    $success = 'Thank you! Your message has been received. We will respond within 24 hours.';
                } catch (PDOException $e) {
                    error_log('CONTACT FORM DB ERROR: ' . $e->getMessage());
                    $error = 'Unable to save your message. Please try again later.';
                } catch (Exception $e) {
                    error_log('CONTACT FORM ERROR: ' . $e->getMessage());
                    $error = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Contact</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Contact</span>
      <h1 class="page-header-title">Let's start<br>a conversation.</h1>
      <p class="page-header-desc">Have a project in mind? We'd love to hear about it. Reach out and we'll get back to you within 24 hours.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="contact-grid">
      <div class="reveal">
        <span class="section-label">Get in Touch</span>
        <h2 class="section-title" style="margin-bottom: var(--space-8);">We're here to help.</h2>

        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
          </div>
          <div class="contact-info-text">
            <h4>Email</h4>
            <p><?php echo htmlspecialchars(getSetting('contact_email', 'hello@vueports.com')); ?><br>support@vueports.com</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          </div>
          <div class="contact-info-text">
            <h4>Phone</h4>
            <p><?php echo htmlspecialchars(getSetting('contact_phone', '+254 700 123 456')); ?><br>Mon–Fri, 9am–6pm SAT</p>
          </div>
        </div>

        <div class="contact-info-item">
          <div class="contact-info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          </div>
          <div class="contact-info-text">
            <h4>Office</h4>
            <p><?php echo htmlspecialchars(getSetting('location', 'Nairobi, Kenya')); ?></p>
          </div>
        </div>

        <div style="margin-top: var(--space-12); padding: var(--space-8); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
          <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-4);">Prefer to book directly?</h3>
          <p style="font-size: var(--text-sm); color: var(--text-secondary); margin-bottom: var(--space-6);">Schedule a free 30-minute consultation at a time that works for you.</p>
          <a href="booking.php" class="btn btn-primary">
            Book a Meeting
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
          </a>
        </div>
      </div>

      <div class="contact-form-card reveal">
        <h3 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-2);">Send us a message</h3>
        <p style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-8);">Fill out the form below and we'll respond within 24 hours.</p>

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
              <label class="form-label">Name *</label>
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
              <input type="tel" name="phone" class="form-input" placeholder="+254 700 123 456">
            </div>
            <div class="form-group">
              <label class="form-label">Service Interest</label>
              <select name="service_interest" class="form-select">
                <option value="">General Inquiry</option>
                <option value="custom-software-web">Custom Software & Web</option>
                <option value="data-engineering-analytics">Data Engineering</option>
                <option value="ai-agent-development">AI Agent Development</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Subject</label>
            <input type="text" name="subject" class="form-input" placeholder="How can we help?">
          </div>

          <div class="form-group">
            <label class="form-label">Message *</label>
            <textarea name="message" class="form-textarea" placeholder="Describe your project, goals, timeline, and budget..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
            Send Message
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
