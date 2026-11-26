<?php
declare(strict_types=1);
// webhooks/zapier.php — Zapier / Make.com Integration

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

function respond(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// ── AUTHENTICATION ──
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$validKey = $_ENV['ZAPIER_API_KEY'] ?? getSetting('zapier_api_key', '');

if (!$validKey || !hash_equals($validKey, $apiKey)) {
    respond(false, null, 'Unauthorized', 401);
}

// ── INCOMING WEBHOOK (Zapier sends data TO us) ──
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $event = sanitize($input['event'] ?? 'generic');
    
    // Log incoming Zapier event
    // Callback logs use private writable scratch space on container hosting.
    file_put_contents(vueports_runtime_path('logs/zapier_inbound.txt'), date('c') . ' ' . json_encode($input) . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    switch ($event) {
        case 'new_lead':
            $name  = sanitize($input['name'] ?? '');
            $email = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $phone = sanitize($input['phone'] ?? '');
            $source = sanitize($input['source'] ?? 'Zapier');
            
            if (!$email) respond(false, null, 'Email required', 400);
            
            // Insert as consultation lead
            $stmt = $db->prepare("INSERT INTO consultations (name, email, phone, service_interest, message, status, source, created_at) VALUES (?, ?, ?, ?, ?, 'new', ?, NOW()) RETURNING id");
            $stmt->execute([$name, $email, $phone, sanitize($input['service'] ?? ''), sanitize($input['message'] ?? ''), $source]);
            
            respond(true, ['id' => (int) $stmt->fetchColumn()], 'Lead captured');
            break;
            
        case 'update_project_status':
            $projectId = (int) ($input['project_id'] ?? 0);
            $status = sanitize($input['status'] ?? '');
            
            if (!$projectId || !$status) respond(false, null, 'project_id and status required', 400);
            
            $db->prepare("UPDATE projects SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $projectId]);
            respond(true, null, 'Project status updated');
            break;
            
        case 'create_invoice':
            $clientId = (int) ($input['client_id'] ?? 0);
            $amount = (float) ($input['amount'] ?? 0);
            
            if (!$clientId || $amount <= 0) respond(false, null, 'client_id and amount required', 400);
            
            $invNum = 'INV-ZAP-' . date('Ymd') . '-' . random_int(1000, 9999);
            $vpInsert = $db->prepare("INSERT INTO invoices (client_id, invoice_number, amount, status, due_date, created_at) VALUES (?, ?, ?, 'sent', (NOW() + INTERVAL '14 day'), NOW()) RETURNING id");
            $vpInsert->execute([$clientId, $invNum, $amount]);
            
            respond(true, ['invoice_id' => (int) $vpInsert->fetchColumn(), 'number' => $invNum], 'Invoice created', 201);
            break;
            
        default:
            respond(true, null, 'Event received but no handler configured');
    }
}

// ── OUTGOING DATA (We send data TO Zapier catch hook) ──
if ($method === 'GET') {
    $action = sanitize($_GET['action'] ?? 'ping');
    
    switch ($action) {
        case 'new_consultations':
            $since = sanitize($_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-1 hour')));
            $stmt = $db->prepare("SELECT * FROM consultations WHERE created_at > ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$since]);
            respond(true, $stmt->fetchAll());
            break;
            
        case 'new_payments':
            $since = sanitize($_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-1 hour')));
            $stmt = $db->prepare("SELECT p.*, c.full_name as client_name FROM payments p LEFT JOIN clients c ON p.client_id = c.id WHERE p.created_at > ? ORDER BY p.created_at DESC LIMIT 50");
            $stmt->execute([$since]);
            respond(true, $stmt->fetchAll());
            break;
            
        case 'ping':
            respond(true, ['timestamp' => date('c'), 'status' => 'online'], 'Zapier webhook active');
            break;
            
        default:
            respond(false, null, 'Unknown action', 400);
    }
}

respond(false, null, 'Method not allowed', 405);