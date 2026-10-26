<?php
// includes/functions.php — COMPLETE FIX
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

/**
 * Core utility functions for Vueports Solutions
 * FIXED: Table name consistency, session key alignment, null safety
 */

/* ========================================
   Security & Input Sanitization
   ======================================== */

function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function generateNonce(): string {
    return base64_encode(random_bytes(16));
}

/* ========================================
   Authentication & Authorization
   ======================================== */

// FIXED: Check both possible session keys for role
function isAdmin(): bool {
    return isset($_SESSION['admin_id']) 
        && !empty($_SESSION['admin_id']) 
        && in_array(($_SESSION['role'] ?? $_SESSION['admin_role'] ?? ''), ['admin', 'super_admin', 'editor', 'support']);
}

function isSuperAdmin(): bool {
    $role = $_SESSION['role'] ?? $_SESSION['admin_role'] ?? '';
    return isAdmin() && $role === 'super_admin';
}

function isClient(): bool {
    return isset($_SESSION['client_id']) && !empty($_SESSION['client_id']);
}

function getCurrentClientId(): int {
    return (int) ($_SESSION['client_id'] ?? 0);
}

function getCurrentAdminId(): int {
    return (int) ($_SESSION['admin_id'] ?? 0);
}

function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('../admin/login.php');
    }
}

function requireClient(): void {
    if (!isClient()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('../client/login.php');
    }
}

/* ========================================
   Navigation & Redirects
   ======================================== */

function redirect(string $url): void {
    if (headers_sent()) {
        echo "<script>window.location.href='" . addslashes($url) . "';</script>";
        exit;
    }
    header("Location: $url");
    exit;
}

function back(): void {
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

function jsonResponse(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data], JSON_THROW_ON_ERROR);
    exit;
}

/* ========================================
   Settings & Configuration
   ======================================== */

function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    
    $db = db();
    if (!$db) return $default;
    
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $value = $row ? (string) $row['setting_value'] : $default;
        $cache[$key] = $value;
        return $value;
    } catch (PDOException $e) {
        error_log("getSetting error: " . $e->getMessage());
        return $default;
    }
}

function setSetting(string $key, string $value): bool {
    $db = db();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, updated_at) 
                              VALUES (?, ?, NOW()) 
                              ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("setSetting error: " . $e->getMessage());
        return false;
    }
}

/* ========================================
   Database Helpers
   ======================================== */

if (!function_exists('db')) {
    function db(): ?PDO {
        global $pdo;
        return $pdo ?? null;
    }
}

function dbTransaction(callable $callback): bool {
    $db = db();
    if (!$db) return false;
    
    try {
        $db->beginTransaction();
        $result = $callback($db);
        if ($result === false) {
            $db->rollBack();
            return false;
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log("Transaction failed: " . $e->getMessage());
        return false;
    }
}

/* ========================================
   Content & CMS Helpers
   ======================================== */

function getServiceBySlug(string $slug): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM services WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// FIXED: Removed duplicate getServices() - consolidated into one function
function getServices(int $limit = 6, string $status = 'active'): array {
    $db = db();
    if (!$db) return [];
    
    $sql = "SELECT * FROM services";
    $params = [];
    if ($status !== 'all') {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY sort_order ASC, created_at DESC";
    if ($limit > 0) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// FIXED: getAllServices now calls getServices for consistency
function getAllServices(string $status = 'active'): array {
    return getServices(0, $status); // 0 = no limit
}

function getBlogPosts(int $limit = 10, int $offset = 0, string $category = '', string $status = 'published'): array {
    $db = db();
    if (!$db) return [];
    
    $where = ["status = ?", "published_at <= NOW()"];
    $params = [$status];
    
    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }
    
    $sql = "SELECT * FROM blog_posts WHERE " . implode(" AND ", $where) . " ORDER BY published_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getBlogPostBySlug(string $slug): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published' AND published_at <= NOW() LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ========================================
   Portfolio Helpers
   ======================================== */

/** Normalize database JSON fields and preserve the view's existing field names. */
function formatPortfolioItem(array $item): array {
    $imagePath = static function (string $path): string {
        if ($path !== '' && !str_contains($path, '/')) {
            return 'images/' . $path;
        }
        return $path;
    };

    $item['image'] = $imagePath($item['image'] ?? '');
    $item['gallery'] = array_map($imagePath, safeJsonDecode($item['gallery'] ?? null));
    $item['technologies'] = safeJsonDecode($item['technologies'] ?? null);
    $item['tech_stack'] = $item['technologies'];
    $item['live_url'] = $item['project_url'] ?? null;
    return $item;
}

function getPortfolioItems(int $limit = 6, string $status = 'active'): array {
    $db = db();
    if (!$db || $limit <= 0 || !in_array($status, ['active', 'draft', 'archived'], true)) return [];

    try {
        $stmt = $db->prepare("
            SELECT * FROM portfolio_items
            WHERE status = ?
            ORDER BY sort_order ASC, created_at DESC, id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $status);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('formatPortfolioItem', $stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("getPortfolioItems error: " . $e->getMessage());
        return [];
    }
}

/** Get a published portfolio item by its unique slug. */
function getPortfolioItemBySlug(string $slug): ?array {
    $db = db();
    if (!$db) return null;

    try {
        $stmt = $db->prepare("SELECT * FROM portfolio_items WHERE slug = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? formatPortfolioItem($row) : null;
    } catch (PDOException $e) {
        error_log("getPortfolioItemBySlug error: " . $e->getMessage());
        return null;
    }
}

/** Prefer the same service type, then fill with other published projects. */
function getRelatedProjects(string $slug, int $limit = 3): array {
    $db = db();
    if (!$db || $limit <= 0) return [];

    try {
        $stmt = $db->prepare("
            SELECT p.* FROM portfolio_items p
            WHERE p.status = 'active' AND p.slug != ?
            ORDER BY CASE WHEN p.service_type = (
                SELECT service_type FROM portfolio_items WHERE slug = ? LIMIT 1
            ) THEN 0 ELSE 1 END, p.sort_order ASC, p.created_at DESC, p.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $slug);
        $stmt->bindValue(2, $slug);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('formatPortfolioItem', $stmt->fetchAll());
    } catch (PDOException $e) {
        error_log("getRelatedProjects error: " . $e->getMessage());
        return [];
    }
}

function getPortfolioCategories(): array {
    $db = db();
    if (!$db) return [];

    try {
        $stmt = $db->query("
            SELECT DISTINCT service_type FROM portfolio_items
            WHERE status = 'active' AND service_type IS NOT NULL AND service_type != ''
            ORDER BY service_type
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("getPortfolioCategories error: " . $e->getMessage());
        return [];
    }
}

/** Create or update an item using columns from the shipped portfolio schema. */
function savePortfolioItem(array $data): bool {
    $db = db();
    if (!$db) return false;

    // Accept the existing view names without trying to store nonexistent columns.
    foreach (['tech_stack' => 'technologies', 'live_url' => 'project_url'] as $alias => $column) {
        if (array_key_exists($alias, $data)) {
            if (!array_key_exists($column, $data)) $data[$column] = $data[$alias];
            unset($data[$alias]);
        }
    }
    $id = (int)($data['id'] ?? 0);
    unset($data['id']);
    $columns = ['title', 'slug', 'description', 'client_name', 'service_type', 'technologies',
        'image', 'gallery', 'project_url', 'github_url', 'testimonial', 'testimonial_author',
        'sort_order', 'status'];
    if (!$data || array_diff(array_keys($data), $columns)) return false;
    if (isset($data['status']) && !in_array($data['status'], ['active', 'draft', 'archived'], true)) return false;

    try {
        foreach (['gallery', 'technologies'] as $column) {
            if (isset($data[$column]) && is_array($data[$column])) {
                $data[$column] = json_encode($data[$column], JSON_THROW_ON_ERROR);
            }
        }

        if ($id > 0) {
            $fields = array_map(static fn(string $column): string => "`$column` = ?", array_keys($data));
            $stmt = $db->prepare("UPDATE portfolio_items SET " . implode(', ', $fields) . " WHERE id = ?");
            return $stmt->execute([...array_values($data), $id]);
        }

        $fields = '`' . implode('`, `', array_keys($data)) . '`';
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $db->prepare("INSERT INTO portfolio_items ($fields) VALUES ($placeholders)");
        return $stmt->execute(array_values($data));
    } catch (PDOException | JsonException $e) {
        error_log("savePortfolioItem error: " . $e->getMessage());
        return false;
    }
}

/** Archive an item without deleting its stored content. */
function deletePortfolioItem(int $id): bool {
    $db = db();
    if (!$db || $id <= 0) return false;

    try {
        $stmt = $db->prepare("UPDATE portfolio_items SET status = 'archived' WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (PDOException $e) {
        error_log("deletePortfolioItem error: " . $e->getMessage());
        return false;
    }
}
// ============================================================
// SETTINGS FUNCTION (if not already in your functions.php)
// ============================================================

/**
 * Get a setting value from database
 * @param string $key
 * @param string $default
 * @return string
 */


/**
 * Sanitize output
 * @param string|null $text
 * @return string
 */

/* ========================================
   Client Portal Helpers
   ======================================== */

function getClient(int $id): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT id, full_name, email, company_name, phone, avatar, status, created_at, last_login_at 
                          FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getClientByEmail(string $email): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM clients WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getClientProjects(int $clientId, string $status = 'all'): array {
    $db = db();
    if (!$db) return [];
    
    $sql = "SELECT p.*, c.full_name as client_name 
            FROM projects p 
            JOIN clients c ON p.client_id = c.id 
            WHERE p.client_id = ?";
    $params = [$clientId];
    
    if ($status !== 'all') {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY p.updated_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProject(int $id, ?int $clientId = null): ?array {
    $db = db();
    if (!$db) return null;
    
    if ($clientId) {
        $stmt = $db->prepare("SELECT p.*, c.full_name as client_name, c.email as client_email 
                              FROM projects p 
                              JOIN clients c ON p.client_id = c.id 
                              WHERE p.id = ? AND p.client_id = ? LIMIT 1");
        $stmt->execute([$id, $clientId]);
    } else {
        $stmt = $db->prepare("SELECT p.*, c.full_name as client_name, c.email as client_email 
                              FROM projects p 
                              JOIN clients c ON p.client_id = c.id 
                              WHERE p.id = ? LIMIT 1");
        $stmt->execute([$id]);
    }
    
    $row = $stmt->fetch();
    return $row ?: null;
}

function getProjectMilestones(int $projectId): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY due_date ASC, sort_order ASC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getProjectFiles(int $projectId): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT id, filename, file_size, mime_type, uploaded_by_type, uploaded_at 
                          FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getProjectComments(int $projectId): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT pc.*, 
                           CASE WHEN pc.admin_id IS NOT NULL THEN a.name ELSE c.full_name END as author_name,
                           CASE WHEN pc.admin_id IS NOT NULL THEN 'admin' ELSE 'client' END as author_type
                           FROM project_comments pc
                           LEFT JOIN admins a ON pc.admin_id = a.id
                           LEFT JOIN clients c ON pc.client_id = c.id
                           WHERE pc.project_id = ? AND pc.is_internal = 0
                           ORDER BY pc.created_at DESC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getClientInvoices(int $clientId, string $status = 'all'): array {
    $db = db();
    if (!$db) return [];
    
    $sql = "SELECT i.*, p.title as project_title 
            FROM invoices i 
            LEFT JOIN projects p ON i.project_id = p.id 
            WHERE i.client_id = ?";
    $params = [$clientId];
    
    if ($status !== 'all') {
        $sql .= " AND i.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY i.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getInvoice(int $id, ?int $clientId = null): ?array {
    $db = db();
    if (!$db) return null;
    
    if ($clientId) {
        $stmt = $db->prepare("SELECT * FROM invoices WHERE id = ? AND client_id = ? LIMIT 1");
        $stmt->execute([$id, $clientId]);
    } else {
        $stmt = $db->prepare("SELECT i.*, c.full_name as client_name, c.email as client_email, c.company_name 
                              FROM invoices i 
                              JOIN clients c ON i.client_id = c.id 
                              WHERE i.id = ? LIMIT 1");
        $stmt->execute([$id]);
    }
    
    $row = $stmt->fetch();
    return $row ?: null;
}

function getInvoiceItems(int $invoiceId): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function getInvoicePayments(int $invoiceId): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY created_at DESC");
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

/* ========================================
   Lead & CRM Helpers
   ======================================== */

function getConsultations(string $status = 'all', ?int $assignedTo = null, int $limit = 50, int $offset = 0): array {
    $db = db();
    if (!$db) return [];
    
    $where = ["1=1"];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = "status = ?";
        $params[] = $status;
    }
    if ($assignedTo) {
        $where[] = "assigned_admin_id = ?";
        $params[] = $assignedTo;
    }
    
    $sql = "SELECT c.*, a.name as assigned_admin_name 
            FROM consultations c 
            LEFT JOIN admins a ON c.assigned_admin_id = a.id 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getConsultation(int $id): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT c.*, a.name as assigned_admin_name 
                          FROM consultations c 
                          LEFT JOIN admins a ON c.assigned_admin_id = a.id 
                          WHERE c.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getBookings(string $status = 'all', ?string $dateFrom = null, ?string $dateTo = null): array {
    $db = db();
    if (!$db) return [];
    
    $where = ["1=1"];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = "status = ?";
        $params[] = $status;
    }
    if ($dateFrom) {
        $where[] = "booking_date >= ?";
        $params[] = $dateFrom;
    }
    if ($dateTo) {
        $where[] = "booking_date <= ?";
        $params[] = $dateTo;
    }
    
    $sql = "SELECT * FROM bookings WHERE " . implode(" AND ", $where) . " ORDER BY booking_date DESC, booking_time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getBooking(int $id): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function isSlotAvailable(string $date, string $time, ?int $excludeBookingId = null): bool {
    $db = db();
    if (!$db) return true;
    
    $sql = "SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND booking_time = ? AND status != 'cancelled'";
    $params = [$date, $time];
    
    if ($excludeBookingId) {
        $sql .= " AND id != ?";
        $params[] = $excludeBookingId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return ((int) $stmt->fetchColumn()) === 0;
}

function getMessages(string $status = 'all', int $limit = 50, int $offset = 0): array {
    $db = db();
    if (!$db) return [];
    
    $sql = "SELECT m.*, a.name as replied_by_name 
            FROM messages m 
            LEFT JOIN admins a ON m.replied_by = a.id";
    $params = [];
    
    if ($status !== 'all') {
        $sql .= " WHERE m.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMessage(int $id): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT m.*, a.name as replied_by_name 
                          FROM messages m 
                          LEFT JOIN admins a ON m.replied_by = a.id 
                          WHERE m.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getCalculatorLeads(int $limit = 50): array {
    $db = db();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT cl.*, c.name as consultation_name 
                          FROM calculator_leads cl 
                          LEFT JOIN consultations c ON cl.converted_to_consultation_id = c.id 
                          ORDER BY cl.created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/* ========================================
   Payment & Financial Helpers
   ======================================== */

function getPayments(string $status = 'all', ?int $clientId = null, int $limit = 50): array {
    $db = db();
    if (!$db) return [];
    
    $where = ["1=1"];
    $params = [];
    
    if ($status !== 'all') {
        $where[] = "payment_status = ?";
        $params[] = $status;
    }
    if ($clientId) {
        $where[] = "client_id = ?";
        $params[] = $clientId;
    }
    
    $sql = "SELECT p.*, c.full_name as client_name 
            FROM payments p 
            LEFT JOIN clients c ON p.client_id = c.id 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY p.created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPayment(int $id): ?array {
    $db = db();
    if (!$db) return null;
    
    $stmt = $db->prepare("SELECT p.*, c.full_name as client_name, c.email as client_email 
                          FROM payments p 
                          LEFT JOIN clients c ON p.client_id = c.id 
                          WHERE p.id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function getRevenueStats(string $period = '30days'): array {
    $db = db();
    if (!$db) return ['total' => 0, 'count' => 0, 'outstanding' => 0];
    
    $since = match($period) {
        '7days' => date('Y-m-d', strtotime('-7 days')),
        '30days' => date('Y-m-d', strtotime('-30 days')),
        '90days' => date('Y-m-d', strtotime('-90 days')),
        'year' => date('Y-m-d', strtotime('-1 year')),
        default => date('Y-m-d', strtotime('-30 days')),
    };
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count 
                          FROM payments 
                          WHERE payment_status = 'completed' AND DATE(created_at) >= ?");
    $stmt->execute([$since]);
    $payments = $stmt->fetch();
    
    $outstanding = (float) $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status IN ('sent', 'overdue')")->fetchColumn();
    
    return [
        'total' => (float) ($payments['total'] ?? 0),
        'count' => (int) ($payments['count'] ?? 0),
        'outstanding' => $outstanding,
    ];
}

/* ========================================
   Analytics Helpers
   ======================================== */

function logPageView(string $page, ?string $referrer = null, ?string $sessionId = null): void {
    $db = db();
    if (!$db) return;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = hash('sha256', $ip . getSetting('app_key', 'salt'));
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $device = 'desktop';
    if (stripos($ua, 'mobile') !== false) $device = 'mobile';
    elseif (stripos($ua, 'tablet') !== false) $device = 'tablet';
    
    try {
        $stmt = $db->prepare("INSERT INTO page_views (page_path, referrer, ip_hash, user_agent, device_type, session_id, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            sanitize($page),
            sanitize($referrer ?? ''),
            $ipHash,
            sanitize(substr($ua, 0, 255)),
            $device,
            $sessionId ?? session_id(),
        ]);
    } catch (PDOException $e) {
        error_log("logPageView error: " . $e->getMessage());
    }
}

function logEvent(string $type, array $data = [], string $userType = 'guest', ?int $userId = null): void {
    $db = db();
    if (!$db) return;
    
    try {
        $stmt = $db->prepare("INSERT INTO events (event_type, event_data, user_type, user_id, ip_hash, session_id, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            sanitize($type),
            json_encode($data),
            $userType,
            $userId,
            hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . getSetting('app_key', 'salt')),
            session_id(),
        ]);
    } catch (PDOException $e) {
        error_log("logEvent error: " . $e->getMessage());
    }
}

function getAnalyticsSummary(string $period = '30days'): array {
    $db = db();
    if (!$db) return [];
    
    $since = match($period) {
        '7days' => date('Y-m-d', strtotime('-7 days')),
        '30days' => date('Y-m-d', strtotime('-30 days')),
        '90days' => date('Y-m-d', strtotime('-90 days')),
        'year' => date('Y-m-d', strtotime('-1 year')),
        default => date('Y-m-d', strtotime('-30 days')),
    };
    
    $views = (int) $db->query("SELECT COUNT(*) FROM page_views WHERE DATE(created_at) >= '$since'")->fetchColumn();
    $unique = (int) $db->query("SELECT COUNT(DISTINCT ip_hash) FROM page_views WHERE DATE(created_at) >= '$since'")->fetchColumn();
    $consultations = (int) $db->query("SELECT COUNT(*) FROM consultations WHERE DATE(created_at) >= '$since'")->fetchColumn();
    $bookings = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) >= '$since'")->fetchColumn();
    $messages = (int) $db->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) >= '$since'")->fetchColumn();
    
    return compact('views', 'unique', 'consultations', 'bookings', 'messages');
}

/* ========================================
   Audit & Compliance
   ======================================== */

function auditLog(string $action, string $entityType, int $entityId, ?array $oldValues = null, ?array $newValues = null): void {
    $db = db();
    if (!$db) return;
    
    $userType = isAdmin() ? 'admin' : (isClient() ? 'client' : 'guest');
    $userId = isAdmin() ? getCurrentAdminId() : (isClient() ? getCurrentClientId() : null);
    
    try {
        $stmt = $db->prepare("INSERT INTO audit_log (user_type, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userType,
            $userId,
            sanitize($action),
            sanitize($entityType),
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            inet_pton($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
            sanitize(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)),
        ]);
    } catch (PDOException $e) {
        error_log("auditLog error: " . $e->getMessage());
    }
}

/* ========================================
   Utility Functions
   ======================================== */

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    if ($diff < 2592000) return floor($diff / 604800) . ' week' . (floor($diff / 604800) > 1 ? 's' : '') . ' ago';
    return date('M j, Y', $time);
}

function formatCurrency(float $amount, string $currency = 'ZAR'): string {
    $symbol = match($currency) {
        'ZAR' => 'R',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $currency . ' ',
    };
    return $symbol . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'M j, Y'): string {
    return date($format, strtotime($date));
}

function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

function generateSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function generateInvoiceNumber(): string {
    $db = db();
    $year = date('Y');
    $count = 1;
    
    if ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE ?");
        $stmt->execute(["INV-$year-%"]);
        $count = (int) $stmt->fetchColumn() + 1;
    }
    
    return "INV-$year-" . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
}

function paginate(int $total, int $page, int $perPage): array {
    $totalPages = (int) max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    
    return [
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'offset' => ($page - 1) * $perPage,
        'hasPrev' => $page > 1,
        'hasNext' => $page < $totalPages,
    ];
}

function uploadFile(array $file, string $directory, array $allowedTypes = [], int $maxSize = 10485760): array {
    $result = ['success' => false, 'path' => '', 'error' => ''];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $result['error'] = 'No file uploaded';
        return $result;
    }
    
    if ($file['size'] > $maxSize) {
        $result['error'] = 'File too large (max ' . round($maxSize / 1048576, 1) . 'MB)';
        return $result;
    }
    
    $mime = mime_content_type($file['tmp_name']);
    if (!empty($allowedTypes) && !in_array($mime, $allowedTypes)) {
        $result['error'] = 'Invalid file type';
        return $result;
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $path = rtrim($directory, '/') . '/' . $filename;
    
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $path)) {
        $result['success'] = true;
        $result['path'] = $path;
        $result['filename'] = $filename;
        $result['originalName'] = $file['name'];
        $result['size'] = $file['size'];
        $result['mime'] = $mime;
    } else {
        $result['error'] = 'Failed to save file';
    }
    
    return $result;
}

function rateLimit(string $key, int $maxAttempts = 60, int $windowSeconds = 60): bool {
    if (getSetting('rate_limit_enabled', 'true') !== 'true') return true;
    
    $dir = __DIR__ . '/../cache/ratelimit';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $file = $dir . '/' . preg_replace('/[^a-z0-9_-]/', '', $key) . '.json';
    $now = time();
    $data = ['count' => 0, 'reset' => $now + $windowSeconds];
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
        if ($data['reset'] < $now) {
            $data = ['count' => 0, 'reset' => $now + $windowSeconds];
        }
    }
    
    $data['count']++;
    file_put_contents($file, json_encode($data), LOCK_EX);
    
    return $data['count'] <= $maxAttempts;
}

function loadEnv(): array {
    static $env = null;
    if ($env !== null) return $env;
    
    $env = [];
    $file = __DIR__ . '/../.env';
    
    if (!file_exists($file)) {
        $file = __DIR__ . '/../.env.example';
    }
    
    if (file_exists($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
    
    return $env;
}

// Initialize environment
$env = loadEnv();
foreach ($env as $key => $value) {
    if (!isset($_ENV[$key])) {
        $existing = getenv($key);
        $_ENV[$key] = $existing === false ? $value : $existing;
        if ($existing === false) {
            putenv("$key=$value");
        }
    }
}

// Session security — FIXED: Only start if not already active
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $_ENV['SESSION_SECURE'] ?? '0');
    ini_set('session.cookie_samesite', $_ENV['SESSION_SAMESITE'] ?? 'Lax');
    ini_set('session.gc_maxlifetime', (int) ($_ENV['SESSION_LIFETIME'] ?? 120) * 60);
    session_start();
}

// FIXED: Added helper to safely decode JSON from DB fields
function safeJsonDecode(?string $json, array $default = []): array {
    if (empty($json)) return $default;
    try {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// FIXED: Added email template renderer
function renderEmailTemplate(string $templateKey, array $variables = []): ?array {
    $db = db();
    if (!$db) return null;
    
    try {
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE template_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$templateKey]);
        $template = $stmt->fetch();
        
        if (!$template) return null;
        
        $subject = $template['subject'];
        $html = $template['body_html'];
        $text = $template['body_text'];
        
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
            $html = str_replace('{{' . $key . '}}', $value, $html);
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        
        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
        ];
    } catch (PDOException $e) {
        error_log("renderEmailTemplate error: " . $e->getMessage());
        return null;
    }
}


/**
 * Queue an email for async sending.
 * 
 * Template mode: queueEmail('user@x.com', 'Name', 'template_key', ['name' => 'John'])
 * Direct mode:   queueEmail('user@x.com', 'Name', 'Subject', '<html>...</html>')
 */
function queueEmail(string $toEmail, string $toName, string $subjectOrTemplateKey, array|string $bodyOrVariables = [], ?string $textBody = null): int {
    $db = db();
    if (!$db) {
        error_log('queueEmail: No database connection');
        return 0;
    }
    
    $fromEmail = getSetting('smtp_from', 'njabulod.hlongwane@gmail.com');
    $fromName  = getSetting('smtp_from_name', 'Vueports Solutions');
    
    $subject   = $subjectOrTemplateKey;
    $htmlBody  = '';
    $plainText = $textBody;
    
    // Template mode: 4th arg is array of variables
    if (is_array($bodyOrVariables)) {
        $template = renderEmailTemplate($subjectOrTemplateKey, $bodyOrVariables);
        if (!$template) {
            error_log("queueEmail: Template not found: $subjectOrTemplateKey");
            return 0;
        }
        $subject   = $template['subject'];
        $htmlBody  = $template['html'];
        $plainText = $template['text'];
    } 
    // Direct mode: 4th arg is HTML string
    else {
        $htmlBody  = $bodyOrVariables;
        $plainText = $textBody ?? strip_tags($bodyOrVariables);
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO email_queue 
            (to_email, to_name, subject, body_html, body_text, from_email, from_name, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        $stmt->execute([
            filter_var(trim($toEmail), FILTER_SANITIZE_EMAIL),
            sanitize($toName),
            sanitize($subject),
            $htmlBody,
            $plainText,
            $fromEmail,
            $fromName,
        ]);
        
        error_log("queueEmail: Queued email to $toEmail, ID " . $db->lastInsertId());
        return (int) $db->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("queueEmail DB error: " . $e->getMessage());
        return 0;
    }
}
// FIXED: Added password hashing helper with automatic rehash
function verifyPassword(string $password, string $hash): bool {
    if (!password_verify($password, $hash)) {
        // Fallback for legacy plaintext passwords (like your admin table row 1)
        return hash_equals($hash, $password) || hash_equals($hash, md5($password));
    }
    return true;
}

function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// FIXED: Added function to check if password needs rehashing
function passwordNeedsRehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

/* ========================================
   SMS Integration
   ======================================== */

/**
 * Send SMS via Twilio
 */
function sendSMSTwilio(string $toPhone, string $message): bool {
    $sid = getenv('TWILIO_SID') ?: ($_ENV['TWILIO_SID'] ?? '');
    $token = getenv('TWILIO_TOKEN') ?: ($_ENV['TWILIO_TOKEN'] ?? '');
    $from = getenv('TWILIO_FROM') ?: ($_ENV['TWILIO_FROM'] ?? '');
    
    if (empty($sid) || empty($token) || empty($from)) {
        error_log('sendSMSTwilio: Missing credentials');
        return false;
    }
    
    // Format SA number if needed
    $to = formatSANumber($toPhone);
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'To' => $to,
        'From' => $from,
        'Body' => $message
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$sid:$token");
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Twilio SMS cURL error: $curlError");
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Twilio SMS sent to $to");
        return true;
    }
    
    error_log("Twilio SMS failed: HTTP $httpCode | $response");
    return false;
}

/**
 * Send SMS via Africa's Talking
 */
function sendSMSAfrica(string $toPhone, string $message): bool {
    $username = getenv('AT_USERNAME') ?: ($_ENV['AT_USERNAME'] ?? '');
    $apiKey = getenv('AT_API_KEY') ?: ($_ENV['AT_API_KEY'] ?? '');
    $sender = getenv('AT_SENDER_ID') ?: ($_ENV['AT_SENDER_ID'] ?? '');
    
    if (empty($username) || empty($apiKey)) {
        error_log('sendSMSAfrica: Missing credentials');
        return false;
    }
    
    $to = formatSANumber($toPhone);
    
    $url = 'https://api.africastalking.com/version1/messaging';
    
    $postData = [
        'username' => $username,
        'to' => $to,
        'message' => $message,
    ];
    
    if (!empty($sender)) {
        $postData['from'] = $sender;
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apiKey: ' . $apiKey,
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Africa's Talking SMS cURL error: $curlError");
        return false;
    }
    
    $decoded = json_decode($response, true);
    if (isset($decoded['SMSMessageData']['Recipients'][0]['status']) && 
        $decoded['SMSMessageData']['Recipients'][0]['status'] === 'Success') {
        error_log("Africa's Talking SMS sent to $to");
        return true;
    }
    
    error_log("Africa's Talking SMS failed: HTTP $httpCode | $response");
    return false;
}

/**
 * Unified SMS sender — auto-detects provider
 */
function sendSMS(string $toPhone, string $message): bool {
    // Try Twilio first if configured
    if (getenv('TWILIO_SID') || ($_ENV['TWILIO_SID'] ?? false)) {
        return sendSMSTwilio($toPhone, $message);
    }
    
    // Fallback to Africa's Talking
    if (getenv('AT_USERNAME') || ($_ENV['AT_USERNAME'] ?? false)) {
        return sendSMSAfrica($toPhone, $message);
    }
    
    error_log('sendSMS: No SMS provider configured');
    return false;
}

/**
 * Format South African phone numbers
 * +27 68 826 1507 → +27688261507
 * 068 826 1507 → +27688261507
 */
function formatSANumber(string $phone): string {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    
    // If starts with 0, replace with +27
    if (str_starts_with($clean, '0')) {
        return '+27' . substr($clean, 1);
    }
    
    // If no country code, assume SA
    if (!str_starts_with($clean, '27') && !str_starts_with($clean, '+')) {
        return '+27' . $clean;
    }
    
    // If starts with 27 but no +
    if (str_starts_with($clean, '27')) {
        return '+' . $clean;
    }
    
    return $clean;
}
