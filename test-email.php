<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

echo "<h1>Email Diagnostic</h1>";
echo "<pre>";

// Check environment variables
$resendKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
echo "RESEND_API_KEY (getenv): " . (empty($resendKey) ? 'MISSING' : 'PRESENT (' . substr($resendKey, 0, 6) . '...)') . "\n";
echo "RESEND_API_KEY (_ENV):   " . (empty($_ENV['RESEND_API_KEY']) ? 'MISSING' : 'PRESENT') . "\n";
echo "SMTP_FROM:               " . (getenv('SMTP_FROM') ?: ($_ENV['SMTP_FROM'] ?? 'NOT SET')) . "\n";
echo "SMTP_FROM_NAME:          " . (getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? 'NOT SET')) . "\n";

// Check if emails.php loaded correctly
echo "sendEmailNow exists:     " . (function_exists('sendEmailNow') ? 'YES' : 'NO') . "\n";
echo "sendViaResend exists:    " . (function_exists('sendViaResend') ? 'YES' : 'NO') . "\n";

// Try sending a test email
echo "\n--- Sending test email ---\n";

$testTo = 'colourerrclrr@gmail.com'; // or your own email
$subject = 'Resend Test from Vueports';
$html = '<h1>Test</h1><p>If you see this, Resend is working.</p>';

$result = sendEmailNow($testTo, 'Test User', $subject, $html);

echo "\nResult: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
echo "</pre>";
