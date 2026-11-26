<?php
declare(strict_types=1);
$pageTitle = 'Privacy Policy';
$pageDescription = 'Vueports Solutions Privacy Policy - POPIA compliant data protection practices.';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$lastUpdated = function_exists('getSetting') ? getSetting('privacy_last_updated', '2024-01-01') : '2024-01-01';
$companyName = function_exists('getSetting') ? getSetting('site_name', 'Vueports Solutions') : 'Vueports Solutions';
$contactEmail = function_exists('getSetting') ? getSetting('contact_email', 'njabulod.hlongwane@gmail.com') : 'njabulod.hlongwane@gmail.com';
?>

<section class="page-header">
    <div class="container">
        <span class="section-tag">/ Legal</span>
        <h1 class="page-header-title">Privacy <span class="highlight">Policy</span></h1>
        <p class="page-header-desc">Last updated: <?= sanitize($lastUpdated) ?></p>
    </div>
</section>

<section class="section section-alt">
    <div class="container legal-container">
        <div class="legal-content">
            <p class="lead">At <?= sanitize($companyName) ?>, we take your privacy seriously. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our services, in compliance with the Protection of Personal Information Act (POPIA) of South Africa.</p>

            <h2>1. Information We Collect</h2>
            <p>We may collect personal information that you voluntarily provide to us when you:</p>
            <ul>
                <li>Fill out contact forms or consultation requests</li>
                <li>Register for an account on our client portal</li>
                <li>Subscribe to newsletters or marketing communications</li>
                <li>Make payments for our services</li>
                <li>Communicate with us via email, phone, or social media</li>
            </ul>
            <p>This information may include your name, email address, phone number, company name, billing information, and project requirements.</p>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Provide, operate, and maintain our services</li>
                <li>Process transactions and send related information</li>
                <li>Communicate with you about projects, updates, and support</li>
                <li>Send marketing communications (with your consent)</li>
                <li>Improve our website and services</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>3. Legal Basis for Processing (POPIA)</h2>
            <p>Under POPIA, we process personal information based on:</p>
            <ul>
                <li><strong>Consent:</strong> When you opt-in to marketing communications</li>
                <li><strong>Contract:</strong> When processing is necessary for service delivery</li>
                <li><strong>Legal Obligation:</strong> When required by law</li>
                <li><strong>Legitimate Interest:</strong> For website analytics and security</li>
            </ul>

            <h2>4. Data Sharing and Disclosure</h2>
            <p>We do not sell your personal information. We may share your data with:</p>
            <ul>
                <li>Service providers (hosting, payment processing, email delivery)</li>
                <li>Professional advisers (lawyers, accountants)</li>
                <li>Law enforcement when required by law</li>
            </ul>
            <p>All third parties are bound by confidentiality agreements and data protection requirements.</p>

            <h2>5. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information, including:</p>
            <ul>
                <li>SSL/TLS encryption for data in transit</li>
                <li>Secure database storage with access controls</li>
                <li>Regular security audits and vulnerability assessments</li>
                <li>Staff training on data protection practices</li>
            </ul>

            <h2>6. Your Rights Under POPIA</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal information</li>
                <li>Request correction of inaccurate data</li>
                <li>Request deletion of your data (right to be forgotten)</li>
                <li>Object to processing for marketing purposes</li>
                <li>Lodge a complaint with the Information Regulator</li>
            </ul>

            <h2>7. Cookies and Tracking</h2>
            <p>We use cookies and similar technologies to:</p>
            <ul>
                <li>Remember your preferences</li>
                <li>Analyze website traffic and usage patterns</li>
                <li>Improve user experience</li>
            </ul>
            <p>You can control cookies through your browser settings. For more details, see our <a href="cookies.php">Cookie Policy</a>.</p>

            <h2>8. Data Retention</h2>
            <p>We retain personal information only for as long as necessary to fulfill the purposes outlined in this policy, or as required by law. Typically:</p>
            <ul>
                <li>Client project data: 7 years (for tax/legal compliance)</li>
                <li>Marketing data: Until you unsubscribe or request deletion</li>
                <li>Website analytics: 13 months (anonymized)</li>
            </ul>

            <h2>9. International Transfers</h2>
            <p>Your data is primarily stored and processed in South Africa. If we transfer data internationally, we ensure appropriate safeguards are in place, such as standard contractual clauses or adequacy decisions.</p>

            <h2>10. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new policy on this page and updating the "Last updated" date.</p>

            <h2>11. Contact Us</h2>
            <p>If you have questions about this Privacy Policy or wish to exercise your rights, please contact us:</p>
            <div class="card legal-contact-box">
                <p><strong><?= sanitize($companyName) ?></strong></p>
                <p>Email: <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a></p>
                <p>Phone: <a href="tel:+27688261507">+27 (68) 826-1507</a></p>
                <p>Address: Johannesburg, South Africa</p>
            </div>

            <div class="legal-meta">
                <p><strong>Information Officer:</strong> Njabulo Hlongwane<br>
                <strong>Registration:</strong> Registered with the South African Information Regulator under POPIA<br>
                <strong>Reference:</strong> DPR-<?= date('Y') ?>-VUEPORTS</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
