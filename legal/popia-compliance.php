<?php
declare(strict_types=1);
$pageTitle = 'POPIA Compliance';
$pageDescription = 'Vueports Solutions POPIA Compliance - Protection of Personal Information Act compliance practices.';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$contactEmail = function_exists('getSetting') ? getSetting('contact_email', 'njabulod.hlongwane@gmail.com') : 'njabulod.hlongwane@gmail.com';
$contactPhone = function_exists('getSetting') ? getSetting('contact_phone', '+27 (68) 826-1507') : '+27 (68) 826-1507';
?>

<section class="page-header">
    <div class="container">
        <span class="section-tag">/ Legal</span>
        <h1 class="page-header-title">POPIA <span class="highlight">Compliance</span></h1>
    </div>
</section>

<section class="section section-alt">
    <div class="container legal-container">
        <div class="legal-content">
            <p class="lead">The <strong>Protection of Personal Information Act (Act 4 of 2013)</strong> ("POPIA") is South Africa's data protection law. Vueports Solutions is fully committed to complying with POPIA in all our data processing activities.</p>

            <div class="card legal-contact-box">
                <p style="margin:0;"><strong>Information Officer:</strong> Njabulo Dlamini</p>
                <p style="margin:var(--space-2) 0 0;"><strong>Contact:</strong> <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a></p>
            </div>

            <h2>1. About POPIA</h2>
            <p>POPIA regulates how organizations collect, process, store, and share personal information. It gives data subjects specific rights and places obligations on responsible parties.</p>

            <h2>2. Lawful Processing</h2>
            <p>We process personal information only when one of the following lawful bases applies:</p>
            <ul>
                <li><strong>Consent</strong> — You have given clear consent (e.g., newsletter signup, contact forms)</li>
                <li><strong>Contract</strong> — Processing is necessary for a contract (e.g., project engagement)</li>
                <li><strong>Legal obligation</strong> — We are required by law (e.g., tax records)</li>
                <li><strong>Legitimate interest</strong> — For our legitimate business interests (e.g., analytics, security)</li>
            </ul>

            <h2>3. Information We Process</h2>
            <div class="legal-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Purpose</th>
                            <th>Retention</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Contact details</td>
                            <td>Communication, project delivery</td>
                            <td>7 years</td>
                        </tr>
                        <tr>
                            <td>Project data</td>
                            <td>Service delivery, support</td>
                            <td>7 years</td>
                        </tr>
                        <tr>
                            <td>Payment records</td>
                            <td>Accounting, tax compliance</td>
                            <td>7 years</td>
                        </tr>
                        <tr>
                            <td>Website analytics</td>
                            <td>Improve user experience</td>
                            <td>26 months</td>
                        </tr>
                        <tr>
                            <td>Email communications</td>
                            <td>Customer service, legal</td>
                            <td>7 years</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2>4. Your Rights Under POPIA</h2>
            <p>As a data subject, you have the following rights:</p>

            <h3>4.1 Right to Access</h3>
            <p>You may request a copy of all personal information we hold about you. We will respond within 30 days.</p>

            <h3>4.2 Right to Correction</h3>
            <p>You may request that we correct any inaccurate or incomplete information.</p>

            <h3>4.3 Right to Deletion</h3>
            <p>You may request deletion of your personal data, subject to legal retention requirements.</p>

            <h3>4.4 Right to Object</h3>
            <p>You may object to processing for direct marketing or legitimate interests.</p>

            <h3>4.5 Right to Lodge a Complaint</h3>
            <p>You may complain to the <strong>Information Regulator</strong>:</p>
            <p>Website: <a href="https://inforegulator.org.za" target="_blank">inforegulator.org.za</a><br>
            Email: <a href="mailto:inforeg@justice.gov.za">inforeg@justice.gov.za</a></p>

            <h2>5. Data Security Measures</h2>
            <p>We implement the following security measures:</p>
            <ul>
                <li>SSL/TLS encryption for all data transmission</li>
                <li>Password hashing using bcrypt</li>
                <li>Role-based access control</li>
                <li>Regular security audits and penetration testing</li>
                <li>Automated backup and disaster recovery</li>
                <li>Employee confidentiality agreements</li>
            </ul>

            <h2>6. Data Breaches</h2>
            <p>In the event of a data breach, we will:</p>
            <ul>
                <li>Notify the Information Regulator within 72 hours</li>
                <li>Notify affected data subjects without undue delay</li>
                <li>Take immediate steps to contain and remediate the breach</li>
            </ul>

            <h2>7. Cross-Border Transfers</h2>
            <p>We primarily process data in South Africa. If data is transferred outside the country, we ensure the recipient country has adequate data protection laws or implement appropriate safeguards.</p>

            <h2>8. Contact Our Information Officer</h2>
            <p>For POPIA-related inquiries, data subject requests, or complaints:</p>
            <div class="card legal-contact-box">
                <p><strong>Njabulo Dlamini</strong><br>
                Information Officer<br>
                Vueports Solutions (Pty) Ltd<br>
                Email: <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a><br>
                Phone: <?= sanitize($contactPhone) ?></p>
            </div>

            <div class="legal-meta">
                <p><strong>Effective Date:</strong> May 27, 2026<br>
                <strong>Last Updated:</strong> <?= date('Y-m-d') ?></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
