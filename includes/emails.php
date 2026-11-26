<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/phpmailer-config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Email queue system with PHPMailer
 */

/* ========================================
   Core Email Functions
   ======================================== */

/**
 * Add email to queue for async processing
 */

/**
 * Send email immediately via PHPMailer (bypass queue)
 * NOTE: Railway has no sendmail. SMTP only.
 */
/**
 * Send email via SendGrid Web API (bypasses Railway SMTP block)
 */
/**
 * Send email immediately via Resend API (bypasses Railway SMTP block)
 */
/**
 * Send email immediately via Resend API (bypasses Railway SMTP block)
 */

 /**
 * Send email immediately via Resend API (bypasses Railway SMTP block)
 */
function sendEmailNow(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool {
    // Railway: use getenv() — $_ENV is often empty in PHP-FPM/built-in server
    $apiKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
    $apiKey = trim($apiKey);
    
    error_log("sendEmailNow: START | To: $toEmail | Subject: $subject | Key present: " . (empty($apiKey) ? 'NO' : 'YES'));
    
    if (!empty($apiKey)) {
        $result = sendViaResend($toEmail, $toName, $subject, $htmlBody, $textBody);
        error_log("sendEmailNow: Resend result = " . ($result ? 'SUCCESS' : 'FAILED'));
        return $result;
    }
    
    error_log("sendEmailNow: No RESEND_API_KEY found. Tried getenv() and _ENV.");
    return false;
}

/**
 * Resend API v1 — POST /emails
 */
/**
 * Resend API v1 — POST /emails
 */
function sendViaResend(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool {
    $apiKey = getenv('RESEND_API_KEY') ?: ($_ENV['RESEND_API_KEY'] ?? '');
    $fromEmail = getenv('SMTP_FROM') ?: ($_ENV['SMTP_FROM'] ?? getSetting('smtp_from', 'noreply@vueports.reloventura.site'));
    $fromName = getenv('SMTP_FROM_NAME') ?: ($_ENV['SMTP_FROM_NAME'] ?? getSetting('smtp_from_name', 'Vueports Solutions'));
    $replyTo = getenv('SMTP_REPLY_TO') ?: ($_ENV['SMTP_REPLY_TO'] ?? $fromEmail);
    
    $from = $fromName ? "$fromName <$fromEmail>" : $fromEmail;
    $to = filter_var(trim($toEmail), FILTER_SANITIZE_EMAIL);
    
    if (empty($to)) {
        error_log("sendViaResend: Invalid recipient email");
        return false;
    }
    
    // Resend expects reply_to as a STRING, not an object
    $payload = [
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $htmlBody,
        'text' => $textBody ?? strip_tags($htmlBody),
        'reply_to' => $replyTo,  // ← must be a string email address
    ];
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("sendViaResend: cURL ERROR: $curlError");
        return false;
    }
    
    error_log("sendViaResend: HTTP $httpCode | To: $to");
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        $id = $decoded['id'] ?? 'unknown';
        error_log("sendViaResend: SUCCESS — ID: $id");
        return true;
    }
    
    error_log("sendViaResend: API error HTTP $httpCode | Response: $response");
    return false;
}
/**
 * SendGrid V3 Mail Send API via cURL
 */
function sendViaSendGrid(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $textBody = null): bool {
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? '';
    $fromEmail = $_ENV['SMTP_FROM'] ?? getSetting('smtp_from', 'colourerrclrr@gmail.com');
    $fromName = $_ENV['SMTP_FROM_NAME'] ?? getSetting('smtp_from_name', 'Vueports Solutions');
    
    $payload = [
        'personalizations' => [[
            'to' => [[
                'email' => filter_var(trim($toEmail), FILTER_SANITIZE_EMAIL),
                'name' => $toName,
            ]]
        ]],
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName,
        ],
        'reply_to' => [
            'email' => $fromEmail,
            'name' => $fromName,
        ],
        'subject' => $subject,
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $textBody ?? strip_tags($htmlBody),
            ],
            [
                'type' => 'text/html',
                'value' => $htmlBody,
            ],
        ],
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("SendGrid cURL error: $curlError");
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("SendGrid: SUCCESS — sent to $toEmail | Subject: $subject");
        return true;
    }
    
    error_log("SendGrid API error: HTTP $httpCode | Response: $response");
    return false;
}
/**
 * Process pending emails from queue (called by cron)
 */
function processEmailQueue(int $batchSize = 20, int $maxAttempts = 3): array {
    $db = db();
    if (!$db) return ['sent' => 0, 'failed' => 0];
    
    $stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND attempts < ? ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$maxAttempts, $batchSize]);
    $emails = $stmt->fetchAll();
    
    $sent = 0;
    $failed = 0;
    
    foreach ($emails as $email) {
        $success = sendSingleQueuedEmail((int) $email['id']);
        $success ? $sent++ : $failed++;
        usleep(200000);
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Send a single queued email by ID
 */
function sendSingleQueuedEmail(int $emailId): bool {
    $db = db();
    if (!$db) return false;
    
    if (!function_exists('getMailer')) {
        error_log('sendSingleQueuedEmail: getMailer() not found');
        return false;
    }
    
    $stmt = $db->prepare("SELECT * FROM email_queue WHERE id = ? AND status = 'pending'");
    $stmt->execute([$emailId]);
    $email = $stmt->fetch();
    if (!$email) return false;
    
    $db->prepare("UPDATE email_queue SET attempts = attempts + 1, updated_at = NOW() WHERE id = ?")->execute([$emailId]);
    
    try {
        $mailer = getMailer();
        if (!$mailer) {
            error_log('sendSingleQueuedEmail: getMailer() returned null');
            return false;
        }
        
        $mailer->clearAddresses();
        $mailer->clearAttachments();
        $mailer->addAddress($email['to_email'], $email['to_name'] ?? '');
        $mailer->Subject = $email['subject'];
        $mailer->isHTML(true);
        $mailer->Body = $email['body_html'];
        $mailer->AltBody = $email['body_text'] ?? strip_tags($email['body_html']);
        
        if (!empty($email['attachments'])) {
            $attachments = json_decode($email['attachments'], true) ?? [];
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['path'])) {
                    $mailer->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
                }
            }
        }
        
        $mailer->send();
        
        $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$emailId]);
        
        $db->prepare("INSERT INTO email_logs (recipient, subject, status, sent_at, created_at) VALUES (?, ?, 'delivered', NOW(), NOW())")
           ->execute([$email['to_email'], $email['subject']]);
        
        return true;
        
    } catch (\Exception $e) {
        $error = $e->getMessage();
        $attempts = (int) $email['attempts'] + 1;
        $newStatus = $attempts >= 3 ? 'failed' : 'pending';
        
        $db->prepare("UPDATE email_queue SET status = ?, error_message = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$newStatus, $error, $emailId]);
        
        $db->prepare("INSERT INTO email_logs (recipient, subject, status, error, created_at) VALUES (?, ?, 'failed', ?, NOW())")
           ->execute([$email['to_email'], $email['subject'], $error]);
        
        return false;
    }
}

/* ========================================
   Email Template Builder
   ======================================== */

function buildEmailTemplate(string $content, string $title = ''): string {
    $appName = getSetting('app_name', 'Vueports Solutions');
    $appUrl = getSetting('app_url', 'https://vueports.reloventura.site'); // ← updated
    $year = date('Y');
    $contactEmail = getSetting('contact_email', 'njabulod.hlongwane@gmail.com');
    $contactPhone = getSetting('contact_phone', '+27 (68) 826-1507');
    

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title ?: $appName) . '</title>
    <style>
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; padding: 10px !important; }
            .content { padding: 20px !important; }
            .header { padding: 20px !important; }
            .footer { padding: 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; font-family: Inter, -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f1f5f9; color: #0f172a; line-height: 1.6;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="container" style="max-width: 600px; width: 100%; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 32px; text-align: center;">
                            <div style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">
                                <span style="display: inline-block; margin-right: 8px;">&#8897;</span>' . htmlspecialchars($appName) . '
                            </div>
                            <div style="font-size: 12px; color: rgba(255,255,255,0.7); margin-top: 4px; letter-spacing: 3px; text-transform: uppercase;">Solutions</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 32px;">
                            ' . $content . '
                        </td>
                    </tr>
                    <tr>
                        <td class="footer" style="background: #f8fafc; padding: 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <div style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                                <strong style="color: #0f172a;">' . htmlspecialchars($appName) . '</strong><br>
                                Johannesburg, South Africa
                            </div>
                            <div style="font-size: 12px; color: #94a3b8; margin-bottom: 16px;">
                                <a href="mailto:' . htmlspecialchars($contactEmail) . '" style="color: #6366f1; text-decoration: none;">' . htmlspecialchars($contactEmail) . '</a> &bull; ' . htmlspecialchars($contactPhone) . '
                            </div>
                            <div style="font-size: 11px; color: #cbd5e1;">
                                &copy; ' . $year . ' ' . htmlspecialchars($appName) . '. All rights reserved.<br>
                                <a href="' . htmlspecialchars($appUrl) . '/legal/privacy.php" style="color: #94a3b8;">Privacy Policy</a> &bull; 
                                <a href="' . htmlspecialchars($appUrl) . '/legal/terms.php" style="color: #94a3b8;">Terms of Service</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function loadEmailTemplate(string $templateKey, array $variables = []): array {
    $db = db();
    if (!$db) return ['subject' => '', 'body' => ''];
    
    $stmt = $db->prepare("SELECT subject, body_html, body_text FROM email_templates WHERE template_key = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$templateKey]);
    $template = $stmt->fetch();
    
    if (!$template) {
        return ['subject' => '', 'body' => ''];
    }
    
    $subject = $template['subject'];
    $body = $template['body_html'];
    
    foreach ($variables as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value), $subject);
        $body = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value), $body);
    }
    
    return [
        'subject' => $subject,
        'body' => buildEmailTemplate($body, $subject),
        'text' => $template['body_text'] ?? strip_tags($body),
    ];
}

/* ========================================
   Branded Transactional Emails
   ======================================== */

function sendContactConfirmation(array $data): bool {
    $template = loadEmailTemplate('contact_confirmation', [
        'name' => $data['name'] ?? 'there',
        'subject' => $data['subject'] ?? 'General Inquiry',
        'message' => $data['message'] ?? '',
    ]);
    
    if (empty($template['subject'])) {
        $subject = 'We received your message — Vueports Solutions';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($data['name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Thank you for reaching out. We have received your message regarding <strong style="color: #6366f1;">' . htmlspecialchars($data['subject'] ?? 'General Inquiry') . '</strong>.</p>
            <p style="margin: 0 0 16px;">Our team reviews every inquiry within <strong>24 business hours</strong>.</p>
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #6366f1;">
                ' . nl2br(htmlspecialchars($data['message'] ?? '')) . '
            </div>
        ', $subject);
        
        return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $subject, $body);
    }
    
    return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $template['subject'], $template['body']);
}

function sendAdminLeadNotification(array $data): bool {
    $adminEmail = getSetting('contact_email', 'colourerrclrr@gmail.com');
    $template = loadEmailTemplate('admin_lead_notification', $data);
    
    if (empty($template['subject'])) {
        $subject = 'New Lead: ' . ($data['name'] ?? 'Unknown') . ' — ' . ($data['service_interest'] ?? 'General');
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 20px; font-size: 22px;">New Contact Form Submission</h2>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; width: 120px; color: #64748b; font-weight: 600;">Name</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['name'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Email</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['email'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Phone</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['phone'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Service</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['service_interest'] ?? 'General') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; vertical-align: top;">Message</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($data['message'] ?? '')) . '</td></tr>
            </table>
        ', $subject);
        
        return sendEmailNow($adminEmail, 'Vueports Admin', $subject, $body);
    }
    
    return sendEmailNow($adminEmail, 'Vueports Admin', $template['subject'], $template['body']);
}

function sendPaymentReceipt(array $payment, ?array $client = null): bool {
    $to = filter_var($client['email'] ?? $payment['payer_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $name = $client['full_name'] ?? $payment['payer_name'] ?? 'Valued Client';
    $template = loadEmailTemplate('payment_receipt', array_merge($payment, ['name' => $name]));
    
    if (empty($template['subject'])) {
        $subject = 'Payment Received — ' . ($payment['plan_name'] ?? 'Invoice Payment');
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 20px; font-size: 22px;">Payment Confirmation</h2>
            <p style="margin: 0 0 16px;">Hi ' . htmlspecialchars($name) . ',</p>
            <p style="margin: 0 0 20px;">We have successfully received your payment. Thank you for choosing Vueports Solutions.</p>
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Amount</span><span style="font-weight: 700; color: #0f172a;">R' . number_format((float) ($payment['amount'] ?? 0), 2) . '</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Reference</span><span style="font-weight: 600;">' . htmlspecialchars($payment['gateway_transaction_id'] ?? ('VPP-' . ($payment['id'] ?? ''))) . '</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Date</span><span>' . date('j F Y, H:i') . '</span></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Item</span><span>' . htmlspecialchars($payment['plan_name'] ?? 'Service Payment') . '</span></div>
            </div>
            <p style="margin: 0;">You can view your full invoice history in the <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/invoices.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">Client Portal</a>.</p>
        ', $subject);
        
        return sendEmailNow($to, $name, $subject, $body) > 0;
    }
    
    return sendEmailNow($to, $name, $template['subject'], $template['body']) > 0;
}

function sendWelcomeEmail(array $client): bool {
    $template = loadEmailTemplate('welcome_client', $client);
    
    if (empty($template['subject'])) {
        $subject = 'Welcome to Vueports Solutions Client Portal';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Welcome, ' . htmlspecialchars($client['full_name'] ?? 'there') . '!</h2>
            <p style="margin: 0 0 16px;">Your client account has been created successfully. You can now log in to track projects, view invoices, and manage payments.</p>
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <div style="margin-bottom: 12px;"><strong style="color: #64748b; display: inline-block; width: 100px;">Portal URL</strong> <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/login.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/login.php</a></div>
                <div><strong style="color: #64748b; display: inline-block; width: 100px;">Email</strong> ' . htmlspecialchars($client['email'] ?? '') . '</div>
            </div>
            <div style="margin-top: 24px;">
                <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/login.php" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">Access Portal</a>
            </div>
        ', $subject);
        
        return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $template['subject'], $template['body']) > 0;
}

function sendPasswordReset(string $email, string $name, string $resetUrl, string $token): bool {
    $template = loadEmailTemplate('password_reset', ['name' => $name, 'reset_url' => $resetUrl]);
    
    if (empty($template['subject'])) {
        $subject = 'Reset your Vueports password';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($name) . ',</h2>
            <p style="margin: 0 0 16px;">We received a request to reset your password. Click the button below to set a new password. This link expires in <strong>1 hour</strong>.</p>
            <div style="margin: 24px 0;">
                <a href="' . htmlspecialchars($resetUrl) . '" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">Reset Password</a>
            </div>
            <p style="margin: 0 0 16px; font-size: 13px; color: #64748b;">If you did not request this reset, you can safely ignore this email. Your password will not be changed.</p>
            <p style="margin: 0; font-size: 12px; color: #94a3b8; word-break: break-all;">Or copy this link: ' . htmlspecialchars($resetUrl) . '</p>
        ', $subject);
        
        return sendEmailNow($email, $name, $subject, $body) > 0;
    }
    
    return sendEmailNow($email, $name, $template['subject'], $template['body']) > 0;
}

/* ========================================
   Consultation Emails
   ======================================== */

function sendConsultationConfirmation(array $data): bool {
    $template = loadEmailTemplate('consultation_confirmation', $data);
    
    if (empty($template['subject'])) {
        $subject = 'Consultation Request Received — Vueports Solutions';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($data['name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Thank you for requesting a consultation. We have received your project brief and will review it within <strong>24 hours</strong>.</p>
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #6366f1;">
                <div style="margin-bottom: 8px;"><strong style="color: #64748b;">Service:</strong> ' . htmlspecialchars($data['service_interest'] ?? 'General') . '</div>
                <div style="margin-bottom: 8px;"><strong style="color: #64748b;">Budget:</strong> ' . htmlspecialchars($data['budget_range'] ?? 'Not specified') . '</div>
                <div><strong style="color: #64748b;">Timeline:</strong> ' . htmlspecialchars($data['timeline'] ?? 'Not specified') . '</div>
            </div>
            <p style="margin: 0;">While you wait, explore our <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/calculator.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">Project Calculator</a> to estimate your investment.</p>
        ', $subject);
        
        return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $template['subject'], $template['body']) > 0;
}

function sendConsultationAdminAlert(array $data): bool {
    $to = getSetting('consultation_email', getSetting('contact_email', 'colourerrclrr@gmail.com'));
    $template = loadEmailTemplate('consultation_admin_alert', $data);
    
    if (empty($template['subject'])) {
        $subject = 'New Consultation: ' . ($data['name'] ?? 'Unknown');
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 20px; font-size: 22px;">New Consultation Request</h2>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; width: 120px; color: #64748b; font-weight: 600;">Name</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['name'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Email</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['email'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Phone</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['phone'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Company</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['company'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Service</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['service_interest'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Budget</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['budget_range'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Timeline</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['timeline'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; vertical-align: top;">Message</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($data['message'] ?? '')) . '</td></tr>
            </table>
            <div style="margin-top: 24px;">
                <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/admin/consultations.php" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">Manage in Admin</a>
            </div>
        ', $subject);
        
        return sendEmailNow($to, 'Vueports Admin', $subject, $body) > 0;
    }
    
    return sendEmailNow($to, 'Vueports Admin', $template['subject'], $template['body']) > 0;
}

/* ========================================
   Booking Emails
   ======================================== */

function sendBookingConfirmation(array $data): bool {
    $template = loadEmailTemplate('booking_confirmation', $data);
    
    if (empty($template['subject'])) {
        $subject = 'Meeting Confirmed — Vueports Solutions';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($data['name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Your consultation has been scheduled. Here are your details:</p>
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Date</span><span style="font-weight: 600;">' . date('l, j F Y', strtotime($data['booking_date'] ?? 'now')) . '</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Time</span><span style="font-weight: 600;">' . date('g:i A', strtotime($data['booking_time'] ?? 'now')) . ' (' . htmlspecialchars($data['timezone'] ?? 'Africa/Johannesburg') . ')</span></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Service</span><span>' . htmlspecialchars($data['service_type'] ?? 'General Consultation') . '</span></div>
            </div>
            <p style="margin: 0 0 16px;">We will send you a Google Meet or Zoom link <strong>2 hours before</strong> the meeting.</p>
            <p style="margin: 0;">To reschedule, reply to this email or <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/booking.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">book a new slot</a>.</p>
        ', $subject);
        
        return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $template['subject'], $template['body']) > 0;
}

function sendBookingAdminAlert(array $data): bool {
    $to = getSetting('booking_email', getSetting('contact_email', 'colourerrclrr@gmail.com'));
    $template = loadEmailTemplate('booking_admin_alert', $data);
    
    if (empty($template['subject'])) {
        $subject = 'New Booking: ' . ($data['name'] ?? 'Unknown') . ' on ' . ($data['booking_date'] ?? date('Y-m-d'));
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 20px; font-size: 22px;">New Client Booking</h2>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; width: 120px; color: #64748b; font-weight: 600;">Name</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['name'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Email</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['email'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Phone</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['phone'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Date</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . date('l, j F Y', strtotime($data['booking_date'] ?? 'now')) . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Time</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . date('g:i A', strtotime($data['booking_time'] ?? 'now')) . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600;">Service</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($data['service_type'] ?? '-') . '</td></tr>
                <tr><td style="padding: 10px; border-bottom: 1px solid #e2e8f0; color: #64748b; font-weight: 600; vertical-align: top;">Notes</td><td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($data['notes'] ?? '')) . '</td></tr>
            </table>
            <div style="margin-top: 24px;">
                <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/admin/bookings.php" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">View Bookings</a>
            </div>
        ', $subject);
        
        return sendEmailNow($to, 'Vueports Admin', $subject, $body) > 0;
    }
    
    return sendEmailNow($to, 'Vueports Admin', $template['subject'], $template['body']) > 0;
}

/* ========================================
   Calculator Lead Email
   ======================================== */

function sendCalculatorLead(array $data): bool {
    $template = loadEmailTemplate('calculator_lead', $data);
    
    if (empty($template['subject'])) {
        $subject = 'Your Project Estimate — Vueports Solutions';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($data['name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Thank you for using our Project Calculator. Based on your selections for <strong style="color: #6366f1;">' . htmlspecialchars($data['service_type'] ?? 'Custom Project') . '</strong>, here is your estimated investment range:</p>
            <div style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 32px; border-radius: 12px; margin: 24px 0; text-align: center; color: #ffffff;">
                <div style="font-size: 13px; opacity: 0.8; margin-bottom: 8px;">Estimated Range</div>
                <div style="font-size: 32px; font-weight: 800; letter-spacing: -0.5px;">R' . number_format($data['estimated_min'] ?? 0, 0) . ' — R' . number_format($data['estimated_max'] ?? 0, 0) . '</div>
            </div>
            <p style="margin: 0 0 16px; font-size: 13px; color: #64748b;">This is a rough estimate based on your selections. For a precise, fixed-price quote, reply to this email or schedule a consultation.</p>
            <div style="margin-top: 24px; text-align: center;">
                <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/consultation.php" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">Schedule Consultation</a>
            </div>
        ', $subject);
        
        return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($data['email'] ?? '', $data['name'] ?? '', $template['subject'], $template['body']) > 0;
}

/* ========================================
   Invoice Emails
   ======================================== */

function sendInvoiceNotification(array $invoice, array $client, array $items = []): bool {
    $template = loadEmailTemplate('invoice_notification', array_merge($invoice, $client));
    
    if (empty($template['subject'])) {
        $subject = 'Invoice ' . ($invoice['invoice_number'] ?? '') . ' from Vueports Solutions';
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= '
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($item['description'] ?? '') . '</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: center;">' . ($item['quantity'] ?? 1) . '</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right;">R' . number_format((float) ($item['unit_price'] ?? 0), 2) . '</td>
                    <td style="padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600;">R' . number_format((float) ($item['total'] ?? 0), 2) . '</td>
                </tr>';
        }
        
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($client['full_name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 20px;">Please find your invoice below. Payment is due by <strong>' . date('j F Y', strtotime($invoice['due_date'] ?? '+14 days')) . '</strong>.</p>
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Invoice #</span><span style="font-weight: 600;">' . htmlspecialchars($invoice['invoice_number'] ?? '') . '</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #64748b;">Amount</span><span style="font-weight: 700; color: #0f172a; font-size: 18px;">R' . number_format((float) ($invoice['amount'] ?? 0), 2) . '</span></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Due Date</span><span>' . date('j F Y', strtotime($invoice['due_date'] ?? '+14 days')) . '</span></div>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 20px 0;">
                <thead>
                    <tr style="background: #f1f5f9;">
                        <th style="padding: 10px; text-align: left; font-weight: 600;">Description</th>
                        <th style="padding: 10px; text-align: center; font-weight: 600;">Qty</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600;">Unit Price</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600;">Total</th>
                    </tr>
                </thead>
                <tbody>' . $itemsHtml . '</tbody>
            </table>
            <div style="margin-top: 24px; text-align: center;">
                <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/pay.php?invoice_id=' . ($invoice['id'] ?? '') . '" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">Pay Now</a>
            </div>
        ', $subject);
        
        return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $template['subject'], $template['body']) > 0;
}

/* ========================================
   Project Update Emails
   ======================================== */

function sendProjectUpdateNotification(int $projectId, string $updateText, array $recipients): bool {
    $db = db();
    if (!$db) return false;
    
    $project = getProject($projectId);
    if (!$project) return false;
    
    $template = loadEmailTemplate('project_update', [
        'project_title' => $project['title'],
        'update_text' => $updateText,
    ]);
    
    $subject = $template['subject'] ?? 'Update on your project: ' . $project['title'];
    $body = $template['body'] ?? buildEmailTemplate('
        <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Project Update</h2>
        <p style="margin: 0 0 16px;">There is a new update on your project <strong style="color: #6366f1;">' . htmlspecialchars($project['title']) . '</strong>:</p>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #6366f1;">
            ' . nl2br(htmlspecialchars($updateText)) . '
        </div>
        <div style="margin-top: 24px;">
            <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/project-detail.php?id=' . $projectId . '" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 14px;">View Project</a>
        </div>
    ', $subject);
    
    $success = true;
    foreach ($recipients as $recipient) {
        if (!sendEmailNow($recipient['email'], $recipient['name'] ?? '', $subject, $body)) {
            $success = false;
        }
    }
    
    return $success;
}

/* ========================================
   Subscription Emails
   ======================================== */

function sendSubscriptionRenewal(array $subscription, array $client): bool {
    $template = loadEmailTemplate('subscription_renewal', array_merge($subscription, $client));
    
    if (empty($template['subject'])) {
        $subject = 'Your ' . ($subscription['plan_name'] ?? 'Subscription') . ' has been renewed';
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($client['full_name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Your <strong style="color: #6366f1;">' . htmlspecialchars($subscription['plan_name'] ?? 'Subscription') . '</strong> has been successfully renewed.</p>
            <div style="background: #f8fafc; padding: 24px; border-radius: 12px; margin: 20px 0; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Amount</span><span style="font-weight: 700; color: #0f172a;">R' . number_format((float) ($subscription['amount'] ?? 0), 2) . '</span></div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px;"><span style="color: #64748b;">Billing Cycle</span><span>' . ucfirst($subscription['billing_cycle'] ?? 'monthly') . '</span></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: #64748b;">Next Billing</span><span>' . date('j F Y', strtotime($subscription['next_billing_date'] ?? '+1 month')) . '</span></div>
            </div>
            <p style="margin: 0;">You can manage your subscription in the <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/dashboard.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">Client Portal</a>.</p>
        ', $subject);
        
        return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $template['subject'], $template['body']) > 0;
}

function sendSubscriptionReminder(array $subscription, array $client, int $daysUntil): bool {
    $template = loadEmailTemplate('subscription_reminder', array_merge($subscription, $client, ['days_until' => $daysUntil]));
    
    if (empty($template['subject'])) {
        $subject = 'Upcoming renewal: ' . ($subscription['plan_name'] ?? 'Your subscription');
        $body = buildEmailTemplate('
            <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($client['full_name'] ?? 'there') . ',</h2>
            <p style="margin: 0 0 16px;">Your <strong style="color: #6366f1;">' . htmlspecialchars($subscription['plan_name'] ?? 'Subscription') . '</strong> will renew in <strong>' . $daysUntil . ' days</strong> on <strong>' . date('j F Y', strtotime($subscription['next_billing_date'] ?? '+3 days')) . '</strong>.</p>
            <div style="background: #fef3c7; padding: 20px; border-radius: 12px; margin: 20px 0; border: 1px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;"><span style="color: #92400e;">Amount</span><span style="font-weight: 700; color: #92400e;">R' . number_format((float) ($subscription['amount'] ?? 0), 2) . '</span></div>
                <div style="display: flex; justify-content: space-between;"><span style="color: #92400e;">Billing Cycle</span><span style="color: #92400e;">' . ucfirst($subscription['billing_cycle'] ?? 'monthly') . '</span></div>
            </div>
            <p style="margin: 0;">No action is required — your subscription will renew automatically. To cancel or modify, visit the <a href="' . getSetting('app_url', 'https://vueports.reloventura.site') . '/client/dashboard.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">Client Portal</a>.</p>
        ', $subject);
        
        return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $subject, $body) > 0;
    }
    
    return sendEmailNow($client['email'] ?? '', $client['full_name'] ?? '', $template['subject'], $template['body']) > 0;
}

/* ========================================
   POPIA / Compliance Emails
   ======================================== */

function sendDataSubjectRequestConfirmation(string $email, string $name, string $requestType): bool {
    $subject = 'Data Subject Request Received — Vueports Solutions';
    $body = buildEmailTemplate('
        <h2 style="color: #0f172a; margin: 0 0 16px; font-size: 22px;">Hi ' . htmlspecialchars($name) . ',</h2>
        <p style="margin: 0 0 16px;">We have received your <strong style="color: #6366f1;">' . htmlspecialchars($requestType) . '</strong> request under the Protection of Personal Information Act (POPIA).</p>
        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #6366f1;">
            <p style="margin: 0; font-size: 14px;"><strong>Request Type:</strong> ' . htmlspecialchars($requestType) . '</p>
            <p style="margin: 8px 0 0; font-size: 14px;"><strong>Reference:</strong> DSR-' . date('Ymd') . '-' . strtoupper(substr(md5($email . time()), 0, 8)) . '</p>
        </div>
        <p style="margin: 0 0 16px;">We will process your request within <strong>30 days</strong> as required by POPIA Section 23.</p>
        <p style="margin: 0; font-size: 13px; color: #64748b;">If you have any questions, contact our Information Officer at <a href="mailto:' . getSetting('contact_email') . '" style="color: #6366f1;">' . getSetting('contact_email') . '</a>.</p>
    ', $subject);
    
    return sendEmailNow($email, $name, $subject, $body) > 0;
}

/* ========================================
   Bulk / Marketing Emails (with POPIA consent check)
   ======================================== */

function canSendMarketing(string $email): bool {
    $db = db();
    if (!$db) return false;
    
    $stmt = $db->prepare("SELECT marketing_consent, marketing_consent_at FROM clients WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    
    if ($client) {
        return (bool) ($client['marketing_consent'] ?? false);
    }
    
    $stmt = $db->prepare("SELECT id FROM unsubscribes WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    return !$stmt->fetch();
}

function recordUnsubscribe(string $email, string $source = 'email'): bool {
    $db = db();
    if (!$db) return false;
    
    try {
        $stmt = $db->prepare("INSERT INTO unsubscribes (email, source, created_at) VALUES (?, ?, NOW()) ON CONFLICT (email) DO UPDATE SET source = EXCLUDED.source, created_at = NOW()");
        $stmt->execute([$email, $source]);
        
        $db->prepare("UPDATE clients SET marketing_consent = 0, marketing_consent_at = NULL WHERE email = ?")->execute([$email]);
        
        return true;
    } catch (PDOException $e) {
        error_log("recordUnsubscribe error: " . $e->getMessage());
        return false;
    }
}
