<?php
declare(strict_types=1);
// api/v1/payments.php — Payment REST API

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/jwt.php';
require_once __DIR__ . '/../../includes/payfast.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = db();

function apiResponse(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// ── AUTH ──
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $auth);
$payload = verifyJWT($token);

if (!$payload) {
    apiResponse(false, null, 'Unauthorized', 401);
}

$clientId = ($payload['role'] === 'client') ? (int) $payload['sub'] : null;
$isAdmin = in_array($payload['role'] ?? '', ['admin', 'super_admin'], true);

switch ($method) {
    case 'GET':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        // Single payment
        if ($id) {
            if ($isAdmin) {
                $stmt = $db->prepare("SELECT p.*, c.full_name as client_name FROM payments p LEFT JOIN clients c ON p.client_id = c.id WHERE p.id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND client_id = ?");
                $stmt->execute([$id, $clientId]);
            }
            
            $payment = $stmt->fetch();
            if (!$payment) {
                apiResponse(false, null, 'Payment not found', 404);
            }
            apiResponse(true, $payment);
        }
        
        // List payments
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, (int) ($_GET['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        $status = sanitize($_GET['status'] ?? '');
        
        $where = $isAdmin ? "WHERE 1=1" : "WHERE client_id = ?";
        $params = $isAdmin ? [] : [$clientId];
        
        if ($status) {
            $where .= " AND payment_status = ?";
            $params[] = $status;
        }
        
        $countStmt = $db->prepare("SELECT COUNT(*) FROM payments $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        
        $sql = "SELECT p.*, c.full_name as client_name FROM payments p LEFT JOIN clients c ON p.client_id = c.id $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();
        
        // Revenue summary
        $revenue = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'completed'" . ($isAdmin ? '' : " AND client_id = $clientId"))->fetchColumn();
        $pending = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'pending'" . ($isAdmin ? '' : " AND client_id = $clientId"))->fetchColumn();
        
        apiResponse(true, [
            'payments' => $payments,
            'summary' => ['revenue' => $revenue, 'pending' => $pending],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = $input['action'] ?? '';
        
        // ── INITIATE PAYMENT (client or admin on behalf) ──
        if ($action === 'initiate') {
            $amount = (float) ($input['amount'] ?? 0);
            $invoiceId = !empty($input['invoice_id']) ? (int) $input['invoice_id'] : null;
            $planName = sanitize($input['plan_name'] ?? '');
            $payerEmail = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $payerName = sanitize($input['name'] ?? '');
            
            if ($amount <= 0 || !$payerEmail || !$payerName) {
                apiResponse(false, null, 'Amount, name, and email required', 400);
            }
            
            $vpInsert = $db->prepare("INSERT INTO payments (client_id, invoice_id, plan_name, amount, currency, payment_status, payer_email, payer_name, created_at) VALUES (?, ?, ?, ?, 'ZAR', 'pending', ?, ?, NOW()) RETURNING id");
            $vpInsert->execute([$clientId, $invoiceId, $planName, $amount, $payerEmail, $payerName]);
            
            $paymentId = (int) $vpInsert->fetchColumn();
            $pfConfig = getPayFastConfig();
            $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
            
            $payFastData = [
                'merchant_id'   => $pfConfig['merchant_id'],
                'merchant_key'  => $pfConfig['merchant_key'],
                'return_url'    => $baseUrl . '/payment-success.php?pid=' . $paymentId,
                'cancel_url'    => $baseUrl . '/payment-cancel.php?pid=' . $paymentId,
                'notify_url'    => $baseUrl . '/itn.php',
                'name_first'    => explode(' ', $payerName)[0] ?? $payerName,
                'name_last'     => implode(' ', array_slice(explode(' ', $payerName), 1)) ?: '',
                'email_address' => $payerEmail,
                'm_payment_id'  => $paymentId,
                'amount'        => number_format($amount, 2, '.', ''),
                'item_name'     => $planName ?: 'Invoice Payment #' . ($invoiceId ?: $paymentId),
            ];
            
            if (!empty($pfConfig['passphrase'])) {
                $payFastData['signature'] = generatePayFastSignature($payFastData, $pfConfig['passphrase']);
            }
            
            apiResponse(true, [
                'payment_id' => $paymentId,
                'payfast_url' => $pfConfig['url'],
                'payfast_data' => $payFastData
            ], 'Payment initiated');
        }
        
        // ── MANUAL STATUS UPDATE (admin only) ──
        if ($action === 'update_status') {
            if (!$isAdmin) {
                apiResponse(false, null, 'Forbidden', 403);
            }
            
            $paymentId = (int) ($input['payment_id'] ?? 0);
            $newStatus = sanitize($input['status'] ?? '');
            $gatewayId = sanitize($input['gateway_transaction_id'] ?? '');
            
            if (!$paymentId || !$newStatus) {
                apiResponse(false, null, 'payment_id and status required', 400);
            }
            
            $db->prepare("UPDATE payments SET payment_status = ?, gateway_transaction_id = ?, updated_at = NOW() WHERE id = ?")
               ->execute([$newStatus, $gatewayId, $paymentId]);
            
            // Update invoice if completed
            if ($newStatus === 'completed') {
                $inv = $db->prepare("SELECT invoice_id FROM payments WHERE id = ?");
                $inv->execute([$paymentId]);
                $invoiceId = $inv->fetchColumn();
                if ($invoiceId) {
                    $db->prepare("UPDATE invoices SET status = 'paid', paid_at = NOW() WHERE id = ?")->execute([$invoiceId]);
                }
            }
            
            apiResponse(true, null, 'Payment status updated');
        }
        
        apiResponse(false, null, 'Unknown action', 400);
        break;

    case 'PUT':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        
        if (!$id) {
            apiResponse(false, null, 'Payment ID required', 400);
        }
        
        $allowed = ['payment_status', 'gateway_transaction_id', 'response_data'];
        $sets = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $params[] = sanitize($input[$field]);
            }
        }
        
        if (empty($sets)) {
            apiResponse(false, null, 'No fields to update', 400);
        }
        
        $params[] = $id;
        $db->prepare("UPDATE payments SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        
        apiResponse(true, null, 'Payment updated');
        break;

    case 'DELETE':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            apiResponse(false, null, 'Payment ID required', 400);
        }
        
        $db->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
        apiResponse(true, null, 'Payment deleted');
        break;

    default:
        apiResponse(false, null, 'Method not allowed', 405);
}