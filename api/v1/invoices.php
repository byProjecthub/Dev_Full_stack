<?php
declare(strict_types=1);
// api/v1/invoices.php — Invoice REST API

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/jwt.php';

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
        
        // Single invoice
        if ($id) {
            if ($isAdmin) {
                $stmt = $db->prepare("SELECT i.*, c.full_name as client_name, c.email as client_email FROM invoices i JOIN clients c ON i.client_id = c.id WHERE i.id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("SELECT * FROM invoices WHERE id = ? AND client_id = ?");
                $stmt->execute([$id, $clientId]);
            }
            
            $invoice = $stmt->fetch();
            if (!$invoice) {
                apiResponse(false, null, 'Invoice not found', 404);
            }
            
            // Line items
            $items = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
            $items->execute([$id]);
            $invoice['items'] = $items->fetchAll();
            
            // Payments
            $payments = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY created_at DESC");
            $payments->execute([$id]);
            $invoice['payments'] = $payments->fetchAll();
            
            apiResponse(true, $invoice);
        }
        
        // List invoices
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, (int) ($_GET['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        $status = sanitize($_GET['status'] ?? '');
        
        $where = $isAdmin ? "WHERE 1=1" : "WHERE i.client_id = ?";
        $params = $isAdmin ? [] : [$clientId];
        
        if ($status) {
            $where .= " AND i.status = ?";
            $params[] = $status;
        }
        
        $countStmt = $db->prepare("SELECT COUNT(*) FROM invoices i $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        
        $sql = "SELECT i.*, c.full_name as client_name FROM invoices i JOIN clients c ON i.client_id = c.id $where ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll();
        
        // Totals
        $totalOutstanding = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status IN ('sent', 'overdue')" . ($isAdmin ? '' : " AND client_id = $clientId"))->fetchColumn();
        $totalPaid = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM invoices WHERE status = 'paid'" . ($isAdmin ? '' : " AND client_id = $clientId"))->fetchColumn();
        
        apiResponse(true, [
            'invoices' => $invoices,
            'summary' => ['outstanding' => $totalOutstanding, 'paid' => $totalPaid],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit)
            ]
        ]);
        break;

    case 'POST':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $clientIdNew = (int) ($input['client_id'] ?? 0);
        $projectId = !empty($input['project_id']) ? (int) $input['project_id'] : null;
        $amount = (float) ($input['amount'] ?? 0);
        $dueDate = $input['due_date'] ?? date('Y-m-d', strtotime('+14 days'));
        $notes = sanitize($input['notes'] ?? '');
        $items = $input['items'] ?? [];
        
        if (!$clientIdNew || $amount <= 0) {
            apiResponse(false, null, 'client_id and valid amount required', 400);
        }
        
        $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) ($db->query("SELECT COUNT(*) FROM invoices WHERE EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM NOW())")->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
        
        $db->beginTransaction();
        
        try {
            $stmt = $db->prepare("INSERT INTO invoices (client_id, project_id, invoice_number, amount, status, due_date, notes, created_at) VALUES (?, ?, ?, ?, 'sent', ?, ?, NOW()) RETURNING id");
            $stmt->execute([$clientIdNew, $projectId, $invoiceNumber, $amount, $dueDate, $notes]);
            $invoiceId = (int) $stmt->fetchColumn();
            
            // Insert line items
            if (!empty($items)) {
                $itemStmt = $db->prepare("INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) VALUES (?, ?, ?, ?)");
                foreach ($items as $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $itemStmt->execute([
                        $invoiceId,
                        sanitize($item['description'] ?? ''),
                        $qty,
                        $price
                    ]);
                }
            }
            
            $db->commit();
            apiResponse(true, ['id' => $invoiceId, 'invoice_number' => $invoiceNumber], 'Invoice created', 201);
        } catch (Exception $e) {
            $db->rollBack();
            apiResponse(false, null, 'Failed to create invoice', 500);
        }
        break;

    case 'PUT':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        
        if (!$id) {
            apiResponse(false, null, 'Invoice ID required', 400);
        }
        
        $allowed = ['status', 'amount', 'due_date', 'notes', 'paid_at'];
        $sets = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $params[] = is_numeric($input[$field]) && $field === 'amount' ? (float) $input[$field] : sanitize($input[$field]);
            }
        }
        
        if (empty($sets)) {
            apiResponse(false, null, 'No fields to update', 400);
        }
        
        $params[] = $id;
        $db->prepare("UPDATE invoices SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
        
        apiResponse(true, null, 'Invoice updated');
        break;

    case 'DELETE':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            apiResponse(false, null, 'Invoice ID required', 400);
        }
        
        $db->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
        apiResponse(true, null, 'Invoice deleted');
        break;

    default:
        apiResponse(false, null, 'Method not allowed', 405);
}