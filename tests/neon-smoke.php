<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(404);exit;}
require_once dirname(__DIR__) . '/config/postgres.php';
$db = null;
try {
    $db = vueports_connect(true);
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') throw new RuntimeException('PostgreSQL driver required.');
    $db->beginTransaction();
    $suffix = bin2hex(random_bytes(8));
    $stmt = $db->prepare("INSERT INTO clients(full_name,email,password) VALUES (?,?,?) RETURNING id");
    $stmt->execute(['Neon smoke test', 'smoke-' . $suffix . '@example.invalid', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $client = (int) $stmt->fetchColumn();
    $stmt = $db->prepare("INSERT INTO projects(client_id,title) VALUES (?,?) RETURNING id");$stmt->execute([$client,'Neon smoke test']);$project = (int) $stmt->fetchColumn();
    $stmt = $db->prepare("INSERT INTO invoices(client_id,project_id,invoice_number,amount,tax_amount) VALUES (?,?,?,?,?) RETURNING id,total_amount");
    $stmt->execute([$client,$project,'SMOKE-' . $suffix,100,15]);$invoice = $stmt->fetch();
    if ((float) $invoice['total_amount'] !== 115.0) throw new RuntimeException('Invoice total check failed.');
    $stmt = $db->prepare("INSERT INTO invoice_items(invoice_id,description,quantity,unit_price) VALUES (?,?,?,?) RETURNING total");$stmt->execute([$invoice['id'],'Smoke line',3,25]);
    if ((float) $stmt->fetchColumn() !== 75.0) throw new RuntimeException('Line total check failed.');
    $stmt = $db->prepare("INSERT INTO payments(client_id,invoice_id,amount,response_data) VALUES (?,?,?,?) RETURNING id");$stmt->execute([$client,$invoice['id'],115,json_encode(['test'=>true])]);
    if ((int) $stmt->fetchColumn() < 1) throw new RuntimeException('Payment ID check failed.');
    $stmt = $db->prepare("INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=EXCLUDED.setting_value");
    $stmt->execute(['smoke_' . $suffix,'one']);$stmt->execute(['smoke_' . $suffix,'two']);
    $check = $db->prepare('SELECT setting_value FROM settings WHERE setting_key=?');$check->execute(['smoke_' . $suffix]);
    if ($check->fetchColumn() !== 'two') throw new RuntimeException('Settings upsert check failed.');
    $db->rollBack();
    echo "PASS: PostgreSQL identity IDs, relationships, generated totals, JSON and upserts.\nAll synthetic records were rolled back; no email or payment request was sent.\n";
} catch (Throwable $error) {
    if ($db?->inTransaction()) $db->rollBack();
    fwrite(STDERR,"FAIL: Verify the connection, migration version and PostgreSQL extensions. No customer records were committed.\n");exit(1);
}
