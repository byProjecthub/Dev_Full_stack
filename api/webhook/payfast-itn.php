<?php
declare(strict_types=1);
// webhooks/payfast-itn.php — PayFast Instant Transaction Notification (canonical)

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/payfast.php';
require_once __DIR__ . '/../../includes/emails.php';

// PayFast requires exactly 200 OK for successful acceptance
header('HTTP/1.0 200 OK');

// Ensure logs directory exists
// Callback logs use private writable scratch space on container hosting.

// ── RAW LOG ──
$rawInput = file_get_contents('php://input');
$rawPost = $_POST;
$logLine = date('c') . ' | IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'CLI') . ' | ' . json_encode($rawPost) . PHP_EOL;
file_put_contents(vueports_runtime_path('logs/itn_raw.txt'), $logLine, FILE_APPEND | LOCK_EX);

if (empty($_POST)) {
    error_log('PayFast ITN: Empty POST body');
    exit('OK'); // Still return OK to stop retries for empty body
}

$pfConfig = getPayFastConfig();

// ── SECURITY LAYERS ──

// 1. IP validation
if (!pfValidIP()) {
    error_log('PayFast ITN: Invalid source IP — ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    exit('OK'); // Return OK to prevent retry storm, but log it
}

// 2. Signature verification
if (!empty($pfConfig['passphrase']) && !verifyPayFastITN($_POST, $pfConfig['passphrase'])) {
    error_log('PayFast ITN: Signature verification failed');
    http_response_code(400);
    exit('Signature mismatch');
}

// 3. Data validation (server-to-server fetchback)
if (!pfValidData($_POST, $pfConfig['itn_url'])) {
    error_log('PayFast ITN: Data validation fetchback failed');
    http_response_code(400);
    exit('Data validation failed');
}

// ── EXTRACT DATA ──
$paymentId     = (int) ($_POST['m_payment_id'] ?? 0);
$pfPaymentId   = $_POST['pf_payment_id'] ?? '';
$paymentStatus = $_POST['payment_status'] ?? '';
$amountGross   = (float) ($_POST['amount_gross'] ?? 0);
$amountFee     = (float) ($_POST['amount_fee'] ?? 0);
$amountNet     = (float) ($_POST['amount_net'] ?? 0);
$signature     = $_POST['signature'] ?? '';

if (!$paymentId) {
    error_log('PayFast ITN: Missing m_payment_id');
    http_response_code(400);
    exit('Invalid payment ID');
}

$db = db();
if (!$db) {
    error_log('PayFast ITN: Database unavailable');
    http_response_code(500);
    exit('Database unavailable');
}

// ── VERIFY PAYMENT RECORD ──
$stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    error_log("PayFast ITN: Payment #$paymentId not found");
    http_response_code(404);
    exit('Payment not found');
}

// Amount match (allow 0.01 variance for rounding)
if (abs((float)$payment['amount'] - $amountGross) > 0.01) {
    error_log("PayFast ITN: Amount mismatch. Expected {$payment['amount']}, got $amountGross");
    http_response_code(400);
    exit('Amount mismatch');
}

// ── IDEMPOTENCY: Don't re-process completed payments ──
if ($payment['payment_status'] === 'completed' && strcasecmp($paymentStatus, 'COMPLETE') === 0) {
    error_log("PayFast ITN: Payment #$paymentId already completed — skipping");
    exit('OK');
}

// ── UPDATE DATABASE ──
$newStatus = strtolower($paymentStatus);
$db->prepare("UPDATE payments SET 
    gateway_transaction_id = ?, 
    payment_status = ?, 
    response_data = ?, 
    amount_fee = ?, 
    amount_net = ?,
    updated_at = NOW() 
    WHERE id = ?")
   ->execute([
       $pfPaymentId,
       $newStatus,
       json_encode($_POST),
       $amountFee,
       $amountNet,
       $paymentId
   ]);

// ── COMPLETE FLOW ──
if (strcasecmp($paymentStatus, 'COMPLETE') === 0) {
    // Update linked invoice
    if ($payment['invoice_id']) {
        $db->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW(), updated_at = NOW() WHERE id = ?")
           ->execute([$payment['invoice_id']]);
    }
    
    // Link to client by email if not already linked
    if (!$payment['client_id'] && $payment['payer_email']) {
        $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
        $stmt->execute([$payment['payer_email']]);
        $foundClientId = $stmt->fetchColumn();
        if ($foundClientId) {
            $db->prepare("UPDATE payments SET client_id = ? WHERE id = ?")
               ->execute([$foundClientId, $paymentId]);
            $payment['client_id'] = $foundClientId;
        }
    }
    
    // Fetch client for receipt
    $client = null;
    if ($payment['client_id']) {
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$payment['client_id']]);
        $client = $stmt->fetch();
    }
    
    // Send receipt
    sendPaymentReceipt($payment, $client ?: null);
    
    // Log success
    error_log("PayFast ITN: Payment #$paymentId completed (PayFast ID: $pfPaymentId, Gross: $amountGross)");
}

exit('OK');