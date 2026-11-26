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
            if ($db) {
                $stmt = $db->prepare("INSERT INTO messages (name, email, phone, subject, message, service_interest) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$name, $email, $phone, $subject, $message, $service]);
            }
            
            // AUTOMATED EMAIL NOTIFICATIONS
            $leadData = [
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'subject' => $subject, 'message' => $message,
                'service_interest' => $service
            ];
            
            sendContactConfirmation($leadData);
            sendAdminLeadNotification($leadData);
            
            $success = 'Thank you! Your message has been received. We will respond within 24 hours.';
        }
    }
}

$pageTitle = 'Contact';
require_once __DIR__ . '/includes/header.php';
?>

<section class="services-hero" style="padding-top:140px;">
    <div class="container">
        <span class="section-tag">/ Contact</span>
        <h1>Let's Build Something <span class="highlight">Profitable</span></h1>
        <p>Tell us about your project. We reply to all inquiries within one business day.</p>
    </div>
</section>

<section style="padding:60px 0 120px;">
    <div class="container">
        <div class="contact-layout">
            <div class="contact-info">
                <div class="contact-info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h4>Email</h4>
                        <p><?= sanitize(getSetting('contact_email', 'njabulod.hlongwane@gmail.com')) ?></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h4>Phone</h4>
                        <p><?= sanitize(getSetting('contact_phone', '+27 (68) 826-1507')) ?></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h4>Location</h4>
                        <p><?= sanitize(getSetting('location', 'Johannesburg, SA')) ?></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h4>Response Time</h4>
                        <p>Within 24 business hours</p>
                    </div>
                </div>
            </div>

            <div>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="contact.php" class="card card-hover">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" required class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" required class="form-input">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
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
                        <input type="text" name="subject" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" rows="6" required class="form-textarea" placeholder="Describe your project, goals, timeline, and budget..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>