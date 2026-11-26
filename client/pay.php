<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payfast.php';
require_once __DIR__ . '/../includes/frontend.php';

$plan      = $_GET['plan'] ?? '';
$invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

$plans = [
    'starter'      => ['name' => 'Starter Package',      'amount' => 15000.00],
    'professional' => ['name' => 'Professional Package', 'amount' => 45000.00],
    'enterprise'   => ['name' => 'Enterprise Retainer',  'amount' => 0.00],
];

$invoice = null;
if ($invoiceId && db()) {
    $stmt = db()->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
}

if ($invoice) {
    $itemName = 'Invoice #' . $invoice['invoice_number'];
    $amount   = (float) $invoice['amount'];
} elseif (isset($plans[$plan])) {
    $itemName = $plans[$plan]['name'];
    $amount   = $plans[$plan]['amount'];
    if ($amount === 0.00) redirect('../contact.php?plan=enterprise');
} else {
    redirect('../services.php');
}

// Pre-fill if client is logged in
$client = null;
if (!empty($_SESSION['client_id']) && db()) {
    $stmt = db()->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_SESSION['client_id']]);
    $client = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name'] ?? '');
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone     = sanitize($_POST['phone'] ?? '');

    $db = db();
    $clientId = $client['id'] ?? null;
    $stmt = $db->prepare("INSERT INTO payments (client_id, invoice_id, plan_name, amount, currency, payment_status, payer_email, payer_name) VALUES (?,?,?,?,?,?,?,?) RETURNING id");
    $stmt->execute([
        $clientId, $invoiceId ?: null, $plan ?: null, $amount, 'ZAR', 'pending',
        $email, "$firstName $lastName"
    ]);
    $paymentId = (int) $stmt->fetchColumn();

    $pfConfig = getPayFastConfig();
    $baseUrl  = 'https://' . $_SERVER['HTTP_HOST'] . rtrim(vueports_url(''), '/');

    $payFastData = [
        'merchant_id'   => $pfConfig['merchant_id'],
        'merchant_key'  => $pfConfig['merchant_key'],
        'return_url'    => $baseUrl . '/payment-success.php?pid=' . $paymentId,
        'cancel_url'    => $baseUrl . '/payment-cancel.php?pid=' . $paymentId,
        'notify_url'    => $baseUrl . '/itn.php',
        'name_first'    => $firstName,
        'name_last'     => $lastName,
        'email_address' => $email,
        'm_payment_id'  => $paymentId,
        'amount'        => number_format($amount, 2, '.', ''),
        'item_name'     => $itemName,
    ];

    if (!empty($pfConfig['passphrase'])) {
        $payFastData['signature'] = generatePayFastSignature($payFastData, $pfConfig['passphrase']);
    }

    // Auto-submit form to PayFast
    echo '<!DOCTYPE html><html><body onload="document.forms[0].submit()">';
    echo '<form action="' . $pfConfig['url'] . '" method="post">';
    foreach ($payFastData as $k => $v) {
        echo '<input type="hidden" name="' . htmlspecialchars((string)$k) . '" value="' . htmlspecialchars((string)$v) . '">';
    }
    echo '<p style="font-family:sans-serif; text-align:center; margin-top:40px;">Redirecting to PayFast...</p>';
    echo '<noscript><button type="submit">Click here to continue</button></noscript>';
    echo '</form></body></html>';
    exit;
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';

$nameParts = explode(' ', trim($client['full_name'] ?? ''));
$firstName = $nameParts[0] ?? '';
$lastName  = implode(' ', array_slice($nameParts, 1));
?>

<section class="section" style="padding-top:140px;">
    <div class="container" style="max-width:600px;">
        <span class="section-tag">/ Checkout</span>
        <h1 style="margin-bottom:8px;">Secure Payment</h1>
        <p style="color:var(--text-secondary); margin-bottom:32px;">
            You are paying for: <strong><?= sanitize($itemName) ?></strong>
        </p>

        <div class="card" style="margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid var(--border-color);">
                <span style="font-weight:600;">Item</span>
                <span style="font-weight:700;"><?= sanitize($itemName) ?></span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-weight:600;">Total</span>
                <span style="font-size:1.5rem; font-weight:800; color:var(--color-accent);">R<?= number_format($amount, 2) ?></span>
            </div>
        </div>

        <form method="POST" style="display:flex; flex-direction:column; gap:16px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:500;">First Name *</label>
                    <input type="text" name="first_name" required value="<?= sanitize($firstName) ?>" class="form-input">
                </div>
                <div>
                    <label style="display:block; margin-bottom:6px; font-weight:500;">Last Name *</label>
                    <input type="text" name="last_name" required value="<?= sanitize($lastName) ?>" class="form-input">
                </div>
            </div>
            <div>
                <label style="display:block; margin-bottom:6px; font-weight:500;">Email *</label>
                <input type="email" name="email" required value="<?= sanitize($client['email'] ?? '') ?>" class="form-input">
            </div>
            <div>
                <label style="display:block; margin-bottom:6px; font-weight:500;">Phone</label>
                <input type="tel" name="phone" value="<?= sanitize($client['phone'] ?? '') ?>" class="form-input">
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width:100%; justify-content:center; margin-top:8px;">
                <i class="fas fa-lock"></i> Pay R<?= number_format($amount, 2) ?> via PayFast
            </button>
            <p style="font-size:0.75rem; color:var(--text-muted); text-align:center;">
                <i class="fas fa-shield-alt"></i> Secured by PayFast. Redirects to complete payment.
            </p>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>