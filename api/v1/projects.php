<?php
declare(strict_types=1);
// api/v1/projects.php — Project REST API

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

// ── ROUTES ──
switch ($method) {
    case 'GET':
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        
        // Single project
        if ($id) {
            if ($isAdmin) {
                $stmt = $db->prepare("SELECT p.*, c.full_name as client_name FROM projects p JOIN clients c ON p.client_id = c.id WHERE p.id = ?");
                $stmt->execute([$id]);
            } else {
                $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND client_id = ?");
                $stmt->execute([$id, $clientId]);
            }
            
            $project = $stmt->fetch();
            if (!$project) {
                apiResponse(false, null, 'Project not found', 404);
            }
            
            // Attach milestones
            $m = $db->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date");
            $m->execute([$id]);
            $project['milestones'] = $m->fetchAll();
            
            // Attach files
            $f = $db->prepare("SELECT id, filename, file_size, uploaded_at FROM project_files WHERE project_id = ?");
            $f->execute([$id]);
            $project['files'] = $f->fetchAll();
            
            apiResponse(true, $project);
        }
        
        // List projects
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(50, (int) ($_GET['limit'] ?? 10));
        $offset = ($page - 1) * $limit;
        $status = sanitize($_GET['status'] ?? '');
        
        $where = $isAdmin ? "WHERE 1=1" : "WHERE p.client_id = ?";
        $params = $isAdmin ? [] : [$clientId];
        
        if ($status) {
            $where .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $countStmt = $db->prepare("SELECT COUNT(*) FROM projects p $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        
        $sql = "SELECT p.*, c.full_name as client_name FROM projects p JOIN clients c ON p.client_id = c.id $where ORDER BY p.updated_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $projects = $stmt->fetchAll();
        
        apiResponse(true, [
            'projects' => $projects,
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
        
        $title = sanitize($input['title'] ?? '');
        $clientIdNew = (int) ($input['client_id'] ?? 0);
        $description = sanitize($input['description'] ?? '');
        $budget = (float) ($input['budget'] ?? 0);
        $deadline = $input['deadline'] ?? null;
        $serviceType = sanitize($input['service_type'] ?? '');
        $priority = sanitize($input['priority'] ?? 'normal');
        
        if (!$title || !$clientIdNew) {
            apiResponse(false, null, 'Title and client_id required', 400);
        }
        
        $stmt = $db->prepare("INSERT INTO projects (client_id, title, description, status, budget, start_date, deadline, service_type, priority, created_at, updated_at) VALUES (?, ?, ?, 'planning', ?, NOW(), ?, ?, ?, NOW(), NOW()) RETURNING id");
        $stmt->execute([$clientIdNew, $title, $description, $budget, $deadline, $serviceType, $priority]);
        
        $projectId = (int) $stmt->fetchColumn();
        apiResponse(true, ['id' => $projectId], 'Project created', 201);
        break;

    case 'PUT':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = (int) ($input['id'] ?? 0);
        
        if (!$id) {
            apiResponse(false, null, 'Project ID required', 400);
        }
        
        $allowed = ['title', 'description', 'status', 'budget', 'deadline', 'service_type', 'priority', 'project_manager'];
        $sets = [];
        $params = [];
        
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $sets[] = "$field = ?";
                $params[] = is_numeric($input[$field]) && $field === 'budget' ? (float) $input[$field] : sanitize($input[$field]);
            }
        }
        
        if (empty($sets)) {
            apiResponse(false, null, 'No fields to update', 400);
        }
        
        $sets[] = "updated_at = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE projects SET " . implode(', ', $sets) . " WHERE id = ?";
        $db->prepare($sql)->execute($params);
        
        apiResponse(true, null, 'Project updated');
        break;

    case 'DELETE':
        if (!$isAdmin) {
            apiResponse(false, null, 'Forbidden', 403);
        }
        
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id) {
            apiResponse(false, null, 'Project ID required', 400);
        }
        
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
        apiResponse(true, null, 'Project deleted');
        break;

    default:
        apiResponse(false, null, 'Method not allowed', 405);
}