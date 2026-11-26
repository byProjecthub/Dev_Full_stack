<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin();

$db = db();
if (!$db) {
    die('Database connection required');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $id = $_POST['id'] ?? null;
                $data = [
                    'title' => sanitize($_POST['title'] ?? ''),
                    'slug' => sanitize($_POST['slug'] ?? ''),
                    'tagline' => sanitize($_POST['tagline'] ?? ''),
                    'description' => sanitize($_POST['description'] ?? ''),
                    'long_description' => sanitize($_POST['long_description'] ?? ''),
                    'features' => json_encode(array_filter(array_map('trim', explode("\n", $_POST['features'] ?? '')))),
                    'icon' => sanitize($_POST['icon'] ?? 'fa-laptop-code'),
                    'price_min' => (float) ($_POST['price_min'] ?? 0),
                    'price_max' => (float) ($_POST['price_max'] ?? 0),
                    'price_note' => sanitize($_POST['price_note'] ?? ''),
                    'delivery_time' => sanitize($_POST['delivery_time'] ?? ''),
                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                    'status' => sanitize($_POST['status'] ?? 'active'),
                    'meta_title' => sanitize($_POST['meta_title'] ?? ''),
                    'meta_description' => sanitize($_POST['meta_description'] ?? ''),
                ];
                
                if (empty($data['title']) || empty($data['slug'])) {
                    $error = 'Title and slug are required.';
                } else {
                    if ($id) {
                        $stmt = $db->prepare("UPDATE services SET title=?, slug=?, tagline=?, description=?, long_description=?, features=?, icon=?, price_min=?, price_max=?, price_note=?, delivery_time=?, sort_order=?, status=?, meta_title=?, meta_description=? WHERE id=?");
                        $stmt->execute([...array_values($data), $id]);
                        $success = 'Service updated successfully.';
                    } else {
                        $stmt = $db->prepare("INSERT INTO services (title, slug, tagline, description, long_description, features, icon, price_min, price_max, price_note, delivery_time, sort_order, status, meta_title, meta_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                        $stmt->execute(array_values($data));
                        $success = 'Service created successfully.';
                    }
                }
                redirect('services.php');
                break;
                
            case 'delete':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id) {
                    $stmt = $db->prepare("UPDATE services SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = 'Service deactivated.';
                    redirect('services.php');
                }
                break;
        }
    }
}

$services = $db->query("SELECT * FROM services ORDER BY sort_order, id")->fetchAll();

$pageTitle = 'Services Management';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Vueports Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/vueports-light.css?v=20261126">
</head>
<body class="admin-body">
    <aside class="admin-sidebar">
        <div class="admin-brand"><i class="fas fa-shield-alt"></i> Vueports Admin</div>
        <nav class="admin-nav">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
            <a href="consultations.php"><i class="fas fa-comments"></i> Consultations</a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
            <a href="services.php" class="active"><i class="fas fa-briefcase"></i> Services</a>
            <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
            <a href="invoices.php"><i class="fas fa-file-invoice"></i> Invoices</a>
            <a href="payments.php"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div class="admin-user">
            <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            <div class="admin-user-info">
                <div class="admin-user-name"><?= sanitize($_SESSION['username'] ?? 'Admin') ?></div>
                <div class="admin-user-role">Administrator</div>
            </div>
        </div>
    </aside>
    
    <main class="admin-main">
        <header class="admin-header">
            <h1>Services Management</h1>
            <div>Manage your service offerings</div>
        </header>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <section class="admin-section">
            <div class="admin-section-header">
                <h2 class="admin-section-title">All Services</h2>
                <button onclick="resetForm(); document.getElementById('serviceForm').style.display='block'" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i> Add Service
                </button>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Service</th>
                        <th>Tagline</th>
                        <th>Price Range</th>
                        <th>Delivery</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $svc): ?>
                    <tr>
                        <td><?= (int)$svc['sort_order'] ?></td>
                        <td>
                            <strong><?= sanitize($svc['title'] ?? '') ?></strong><br>
                            <small style="color:var(--text-muted);"><?= sanitize($svc['slug'] ?? '') ?></small>
                        </td>
                        <td><?= sanitize($svc['tagline'] ?? '') ?></td>
                        <td>R<?= number_format((float)($svc['price_min'] ?? 0), 0) ?> - R<?= number_format((float)($svc['price_max'] ?? 0), 0) ?></td>
                        <td><?= sanitize($svc['delivery_time'] ?? '') ?></td>
                        <td><span class="status-badge status-<?= sanitize($svc['status'] ?? 'inactive') ?>"><?= ucfirst($svc['status'] ?? 'inactive') ?></span></td>
                        <td>
                            <button onclick="editService(<?= htmlspecialchars(json_encode($svc), ENT_QUOTES, 'UTF-8') ?>)" class="admin-btn admin-btn-sm admin-btn-secondary">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this service?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $svc['id'] ?>">
                                <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($services)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px;">No services found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
        
        <!-- Service Form Modal -->
        <div id="serviceForm" style="display:none;" class="admin-modal-overlay" onclick="if(event.target===this)this.style.display='none'">
            <div class="admin-modal">
                <div class="admin-modal-header">
                    <h3 id="formTitle">Add Service</h3>
                    <button onclick="document.getElementById('serviceForm').style.display='none'" class="admin-modal-close"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" class="admin-modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="id" id="svcId" value="">
                    
                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" name="title" id="svcTitle" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" name="slug" id="svcSlug" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="tagline" id="svcTagline" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Short Description</label>
                        <textarea name="description" id="svcDescription" rows="2" class="form-textarea"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Full Description (HTML allowed)</label>
                        <textarea name="long_description" id="svcLongDescription" rows="4" class="form-textarea"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Features (one per line)</label>
                        <textarea name="features" id="svcFeatures" rows="4" class="form-textarea" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Icon Class (Font Awesome)</label>
                        <input type="text" name="icon" id="svcIcon" value="fa-laptop-code" class="form-input">
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Price Min</label>
                            <input type="number" name="price_min" id="svcPriceMin" step="0.01" min="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Price Max</label>
                            <input type="number" name="price_max" id="svcPriceMax" step="0.01" min="0" class="form-input">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Price Note</label>
                        <input type="text" name="price_note" id="svcPriceNote" class="form-input" placeholder="e.g., Final quote after scoping session">
                    </div>
                    <div class="form-group">
                        <label>Delivery Time</label>
                        <input type="text" name="delivery_time" id="svcDeliveryTime" class="form-input" placeholder="e.g., 2–16 weeks">
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="sort_order" id="svcOrder" value="0" class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="svcStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" name="meta_title" id="svcMetaTitle" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea name="meta_description" id="svcMetaDescription" rows="2" class="form-textarea"></textarea>
                    </div>
                    
                    <div class="admin-modal-footer">
                        <button type="button" onclick="document.getElementById('serviceForm').style.display='none'" class="admin-btn admin-btn-secondary">Cancel</button>
                        <button type="submit" class="admin-btn admin-btn-primary">Save Service</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
    function resetForm() {
        document.getElementById('formTitle').textContent = 'Add Service';
        document.querySelector('input[name="action"]').value = 'create';
        document.getElementById('svcId').value = '';
        document.getElementById('svcTitle').value = '';
        document.getElementById('svcSlug').value = '';
        document.getElementById('svcTagline').value = '';
        document.getElementById('svcDescription').value = '';
        document.getElementById('svcLongDescription').value = '';
        document.getElementById('svcFeatures').value = '';
        document.getElementById('svcIcon').value = 'fa-laptop-code';
        document.getElementById('svcPriceMin').value = '';
        document.getElementById('svcPriceMax').value = '';
        document.getElementById('svcPriceNote').value = '';
        document.getElementById('svcDeliveryTime').value = '';
        document.getElementById('svcOrder').value = '0';
        document.getElementById('svcStatus').value = 'active';
        document.getElementById('svcMetaTitle').value = '';
        document.getElementById('svcMetaDescription').value = '';
    }
    
    function editService(svc) {
        document.getElementById('formTitle').textContent = 'Edit Service';
        document.querySelector('input[name="action"]').value = 'update';
        document.getElementById('svcId').value = svc.id;
        document.getElementById('svcTitle').value = svc.title || '';
        document.getElementById('svcSlug').value = svc.slug || '';
        document.getElementById('svcTagline').value = svc.tagline || '';
        document.getElementById('svcDescription').value = svc.description || '';
        document.getElementById('svcLongDescription').value = svc.long_description || '';
        
        let features = '';
        try {
            const f = JSON.parse(svc.features || '[]');
            features = Array.isArray(f) ? f.join('\n') : '';
        } catch(e) {}
        document.getElementById('svcFeatures').value = features;
        
        document.getElementById('svcIcon').value = svc.icon || 'fa-laptop-code';
        document.getElementById('svcPriceMin').value = svc.price_min || '';
        document.getElementById('svcPriceMax').value = svc.price_max || '';
        document.getElementById('svcPriceNote').value = svc.price_note || '';
        document.getElementById('svcDeliveryTime').value = svc.delivery_time || '';
        document.getElementById('svcOrder').value = svc.sort_order || 0;
        document.getElementById('svcStatus').value = svc.status || 'active';
        document.getElementById('svcMetaTitle').value = svc.meta_title || '';
        document.getElementById('svcMetaDescription').value = svc.meta_description || '';
        document.getElementById('serviceForm').style.display = 'block';
    }
    </script>
</body>
</html>