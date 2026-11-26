<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

$success = '';
$error = '';

$timeSlots = ['09:00:00','10:00:00','11:00:00','13:00:00','14:00:00','15:00:00','16:00:00'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $date  = $_POST['booking_date'] ?? '';
        $time  = $_POST['booking_time'] ?? '';
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if (empty($date) || empty($time) || empty($_POST['name']) || empty($email)) {
            $error = 'Please fill in all required fields and select a time slot.';
        } elseif ($date < date('Y-m-d')) {
            $error = 'You cannot book a date in the past.';
        } elseif (function_exists('isSlotAvailable') && !isSlotAvailable($date, $time)) {
            $error = 'That time slot has just been taken. Please select another.';
        } else {
            $data = [
                'name'         => sanitize($_POST['name'] ?? ''),
                'email'        => $email,
                'phone'        => sanitize($_POST['phone'] ?? ''),
                'service_type' => sanitize($_POST['service_type'] ?? ''),
                'booking_date' => $date,
                'booking_time' => $time,
                'timezone'     => sanitize($_POST['timezone'] ?? 'Africa/Johannesburg'),
                'notes'        => sanitize($_POST['notes'] ?? ''),
            ];

            $db = db();
            if ($db) {
                $stmt = $db->prepare("INSERT INTO bookings (name, email, phone, service_type, booking_date, booking_time, timezone, notes) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute(array_values($data));
            }
            sendBookingConfirmation($data);
            sendBookingAdminAlert($data);
            $success = 'Your meeting is scheduled. A confirmation email has been sent with your date and time.';
        }
    }
}

$pageTitle = 'Book a Meeting';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Book</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Book a Meeting</span>
      <h1 class="page-header-title">Pick a time that<br>works for you.</h1>
      <p class="page-header-desc">Select a date and time slot below. We'll send a calendar invite with a video link.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="booking-grid">
      <div class="booking-sidebar reveal">
        <div class="booking-step-indicator">
          <div class="booking-step active">
            <div class="step-number">1</div>
            <div class="step-label">Date</div>
          </div>
          <div class="booking-step-line"></div>
          <div class="booking-step">
            <div class="step-number">2</div>
            <div class="step-label">Time</div>
          </div>
          <div class="booking-step-line"></div>
          <div class="booking-step">
            <div class="step-number">3</div>
            <div class="step-label">Details</div>
          </div>
        </div>

        <div style="padding: var(--space-8); background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl);">
          <h3 style="font-size: var(--text-lg); font-weight: 600; margin-bottom: var(--space-4);">Meeting Details</h3>
          <div style="display: flex; flex-direction: column; gap: var(--space-4);">
            <div style="display: flex; align-items: center; gap: var(--space-3);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-indigo)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              <span style="font-size: var(--text-sm); color: var(--text-secondary);">30 minutes</span>
            </div>
            <div style="display: flex; align-items: center; gap: var(--space-3);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-indigo)" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
              <span style="font-size: var(--text-sm); color: var(--text-secondary);">Google Meet (video)</span>
            </div>
            <div style="display: flex; align-items: center; gap: var(--space-3);">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent-indigo)" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              <span style="font-size: var(--text-sm); color: var(--text-secondary);">SAST (Johannesburg time)</span>
            </div>
          </div>
        </div>
      </div>

      <div class="reveal">
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

        <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl); padding: var(--space-10); margin-bottom: var(--space-8);">
          <h3 style="font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-6);">Select a Date</h3>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="booking_date" id="bookingDate" required min="<?php echo date('Y-m-d'); ?>" class="form-input">
          </div>
        </div>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl); padding: var(--space-10); margin-bottom: var(--space-8);">
          <h3 style="font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-6);">Available Time Slots</h3>
          <div class="time-slot-grid">
            <?php foreach ($timeSlots as $slot): ?>
            <label class="time-slot">
              <input type="radio" name="booking_time" value="<?php echo $slot; ?>" required style="display:none;">
              <span><?php echo date('g:i A', strtotime($slot)); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="background: var(--bg-surface); border: 1px solid var(--border-subtle); border-radius: var(--radius-xl); padding: var(--space-10);">
          <h3 style="font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-6);">Your Details</h3>
          <form method="POST" action="booking.php" id="bookingForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="booking_date" id="formDate" value="">
            <input type="hidden" name="booking_time" id="formTime" value="">

            <div class="grid-2" style="gap: var(--space-4);">
              <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" required class="form-input" placeholder="John Doe">
              </div>
              <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" required class="form-input" placeholder="john@company.com">
              </div>
            </div>

            <div class="grid-2" style="gap: var(--space-4);">
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-input" placeholder="+27 68 826 1507">
              </div>
              <div class="form-group">
                <label class="form-label">Service</label>
                <select name="service_type" class="form-select">
                  <option value="">General Consultation</option>
                  <option value="Custom Software & Web">Custom Software & Web</option>
                  <option value="Data Engineering">Data Engineering</option>
                  <option value="AI Agent Development">AI Agent Development</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Timezone</label>
              <select name="timezone" class="form-select">
                <option value="Africa/Johannesburg">Johannesburg (SAST)</option>
                <option value="Europe/London">London (GMT/BST)</option>
                <option value="America/New_York">New York (ET)</option>
                <option value="Asia/Dubai">Dubai (GST)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Notes / Agenda</label>
              <textarea name="notes" rows="3" class="form-textarea" placeholder="What should we focus on during the call?"></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
              Confirm Booking
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.querySelectorAll('.time-slot').forEach(slot => {
  slot.addEventListener('click', function() {
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('formTime').value = this.querySelector('input').value;
  });
});

document.getElementById('bookingDate').addEventListener('change', function() {
  document.getElementById('formDate').value = this.value;
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
