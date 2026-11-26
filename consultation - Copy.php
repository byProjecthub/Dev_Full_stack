<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php'; // Include the database connection
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
                $stmt = $db->prepare("INSERT INTO consultations (name, email, phone, company, service_interest, budget_range, timeline, message) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute(array_values($data));
            }
            sendConsultationConfirmation($data);
            sendConsultationAdminAlert($data);
            $success = 'Your consultation request has been submitted. We will respond within 24 business hours.';
        }
    }
}

function verifyRecaptcha(string $token): bool {
    $secret = getenv('RECAPTCHA_SECRET_KEY');
    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $token);
    $data = json_decode($response, true);
    return ($data['success'] ?? false) && ($data['score'] ?? 0) >= 0.5;
}

$pageTitle = 'Consultation';
require_once __DIR__ . '/includes/header.php';
?>

<section class="services-hero" style="padding-top:140px;">
    <div class="container">
        <span class="section-tag">/ Consultation</span>
        <h1>Book a Free <span class="highlight">Strategy Call</span></h1>
        <p>Tell us about your project. We will assess scope, stack, and ROI potential — no commitment required.</p>
    </div>
</section>

<section style="padding:60px 0 120px;">
    <div class="container">
        <div style="max-width:800px; margin:0 auto;">
            <?php if ($success): ?>
                <div style="padding:16px 24px; background:#dcfce7; border:1px solid #22c55e; border-radius:var(--radius-md); color:#166534; margin-bottom:24px;">
                    <i class="fas fa-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="padding:16px 24px; background:#fee2e2; border:1px solid #ef4444; border-radius:var(--radius-md); color:#991b1b; margin-bottom:24px;">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="consultation.php" style="display:flex; flex-direction:column; gap:20px;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="recaptcha_token" id="recaptchaToken">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Full Name *</label>
                        <input type="text" name="name" required style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Email *</label>
                        <input type="email" name="email" required style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Phone</label>
                        <input type="tel" name="phone" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Company</label>
                        <input type="text" name="company" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px;">
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Service Interest</label>
                        <select name="service_interest" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                            <option value="">Select...</option>
                            <option value="Custom Software & Web">Custom Software & Web</option>
                            <option value="Data Engineering">Data Engineering</option>
                            <option value="AI Agent Development">AI Agent Development</option>
                            <option value="Multiple / Unsure">Multiple / Unsure</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Budget Range</label>
                        <select name="budget_range" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                            <option value="">Select...</option>
                            <option value="R15k – R50k">R15k – R50k</option>
                            <option value="R50k – R150k">R50k – R150k</option>
                            <option value="R150k – R500k">R150k – R500k</option>
                            <option value="R500k+">R500k+</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:8px; font-weight:500;">Desired Timeline</label>
                        <select name="timeline" style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary);">
                            <option value="">Select...</option>
                            <option value="Less than 1 month">Less than 1 month</option>
                            <option value="1 – 3 months">1 – 3 months</option>
                            <option value="3 – 6 months">3 – 6 months</option>
                            <option value="6+ months">6+ months</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block; margin-bottom:8px; font-weight:500;">Project Brief *</label>
                    <textarea name="message" rows="6" required placeholder="Describe your challenge, goals, and any existing systems..." style="width:100%; padding:14px; border:1px solid var(--border); border-radius:var(--radius-md); background:var(--bg-card); color:var(--text-primary); resize:vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="align-self:flex-start;">
                    <i class="fas fa-paper-plane"></i> Request Consultation
                </button>
                <script src="https://www.google.com/recaptcha/api.js?render=YOUR_SITE_KEY"></script>
<script>
grecaptcha.ready(function() {
    grecaptcha.execute('YOUR_SITE_KEY', {action: 'submit'}).then(function(token) {
        document.getElementById('recaptchaToken').value = token;
    });
});
</script>


            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>