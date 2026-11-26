<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payfast.php';
require_once __DIR__ . '/../includes/emails.php';

header('HTTP/1.0 200 OK');

// Log raw ITN for debugging
// Callback logs use private writable scratch space on container hosting.
file_put_contents(vueports_runtime_path('logs/itn_raw.txt'), date('c') . ' ' . json_encode($_POST) . "\n", FILE_APPEND);

if (empty($_POST)) {
    http_response_code(400);
    exit('No data received');
}

$pfConfig = getPayFastConfig();

// Security checks
if (!pfValidIP()) {
    error_log('PayFast ITN: Invalid IP - ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    exit('Invalid source IP');
}

if (!verifyPayFastITN($_POST, $pfConfig['passphrase'])) {
    error_log('PayFast ITN: Signature verification failed');
    http_response_code(400);
    exit('Signature mismatch');
}

if (!pfValidData($_POST, $pfConfig['itn_url'])) {
    error_log('PayFast ITN: Data validation failed');
    http_response_code(400);
    exit('Data validation failed');
}

$paymentId     = (int) ($_POST['m_payment_id'] ?? 0);
$paymentStatus = $_POST['payment_status'] ?? '';
$gatewayId     = $_POST['pf_payment_id'] ?? '';
$amountGross   = (float) ($_POST['amount_gross'] ?? 0);

if (!$paymentId) {
    http_response_code(400);
    exit('Invalid payment ID');
}

$db = db();
if (!$db) {
    http_response_code(500);
    exit('Database unavailable');
}

// Verify amount matches
$stmt = $db->prepare("SELECT * FROM payments WHERE id = ?");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    error_log("PayFast ITN: Payment #$paymentId not found");
    http_response_code(404);
    exit('Payment not found');
}

if (abs((float)$payment['amount'] - $amountGross) > 0.01) {
    error_log("PayFast ITN: Amount mismatch for payment #$paymentId");
    http_response_code(400);
    exit('Amount mismatch');
}

// Update payment record
$db->prepare("UPDATE payments SET gateway_transaction_id = ?, payment_status = ?, response_data = ? WHERE id = ?")
   ->execute([
       $gatewayId, 
       strtolower($paymentStatus), 
       json_encode($_POST), 
       $paymentId
   ]);

// On successful payment
if (strcasecmp($paymentStatus, 'COMPLETE') === 0) {
    // Update invoice if linked
    if ($payment['invoice_id']) {
        $db->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?")
           ->execute([$payment['invoice_id']]);
    }
    
    // Link to client if email matches
    if (!$payment['client_id'] && $payment['payer_email']) {
        $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$payment['payer_email']]);
        $clientId = $stmt->fetchColumn();
        if ($clientId) {
            $db->prepare("UPDATE payments SET client_id = ? WHERE id = ?")
               ->execute([$clientId, $paymentId]);
            $payment['client_id'] = $clientId;
        }
    }
    
    // Fetch client for email
    $client = null;
    if ($payment['client_id']) {
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$payment['client_id']]);
        $client = $stmt->fetch();
    }
    
    // Send receipt email
    sendPaymentReceipt($payment, $client ?: null);
    
    error_log("PayFast ITN: Payment #$paymentId completed successfully");
}

echo 'OK';