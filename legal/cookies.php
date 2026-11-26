<?php
declare(strict_types=1);
$pageTitle = 'Cookie Policy';
$pageDescription = 'Vueports Solutions Cookie Policy - How we use cookies and similar technologies.';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$companyName = function_exists('getSetting') ? getSetting('site_name', 'Vueports Solutions') : 'Vueports Solutions';
$contactEmail = function_exists('getSetting') ? getSetting('contact_email', 'njabulod.hlongwane@gmail.com') : 'njabulod.hlongwane@gmail.com';
?>

<section class="page-header" >
    <div class="container">
        <span class="section-tag">/ Legal</span>
        <h1 class="page-header-title">Cookie <span class="highlight">Policy</span></h1>
    </div>
</section>

<section class="section section-alt">
    <div class="container legal-container">
        <div class="legal-content">
            <p class="lead">This Cookie Policy explains how <?= sanitize($companyName) ?> uses cookies and similar technologies when you visit our website.</p>

            <h2>1. What Are Cookies?</h2>
            <p>Cookies are small text files stored on your device when you visit a website. They help us provide you with a better experience by:</p>
            <ul>
                <li>Remembering your preferences and settings</li>
                <li>Understanding how you use our website</li>
                <li>Improving website performance and security</li>
            </ul>

            <h2>2. Types of Cookies We Use</h2>

            <h3>2.1 Essential Cookies</h3>
            <p>These cookies are necessary for the website to function properly. They cannot be disabled.</p>
            <div class="legal-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Cookie Name</th>
                            <th>Purpose</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>session_id</td>
                            <td>Maintains your session state</td>
                            <td>Session</td>
                        </tr>
                        <tr>
                            <td>csrf_token</td>
                            <td>Security token for form submissions</td>
                            <td>Session</td>
                        </tr>
                        <tr>
                            <td>vueports_theme</td>
                            <td>Stores your theme preference (light/dark)</td>
                            <td>1 year</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>2.2 Analytics Cookies</h3>
            <p>These cookies help us understand how visitors interact with our website. All data is anonymized.</p>
            <div class="legal-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Cookie Name</th>
                            <th>Purpose</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>_ga</td>
                            <td>Google Analytics — distinguishes users</td>
                            <td>13 months</td>
                        </tr>
                        <tr>
                            <td>_gid</td>
                            <td>Google Analytics — distinguishes sessions</td>
                            <td>24 hours</td>
                        </tr>
                        <tr>
                            <td>vueports_visit</td>
                            <td>Internal analytics — page view tracking (anonymized)</td>
                            <td>30 days</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>2.3 Functional Cookies</h3>
            <p>These cookies enable enhanced functionality and personalization.</p>
            <div class="legal-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Cookie Name</th>
                            <th>Purpose</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>vueports_consent</td>
                            <td>Stores your cookie consent preferences</td>
                            <td>1 year</td>
                        </tr>
                        <tr>
                            <td>vueports_return</td>
                            <td>Remembers if you are a returning visitor</td>
                            <td>1 year</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h2>3. Third-Party Cookies</h2>
            <p>We may allow third-party service providers to place cookies on your device for the following purposes:</p>
            <ul>
                <li><strong>Google Analytics:</strong> Website traffic analysis and user behavior insights</li>
                <li><strong>Font Awesome / CDNJS:</strong> Icon and font delivery</li>
                <li><strong>Google Fonts:</strong> Typography loading</li>
            </ul>
            <p>These third parties have their own privacy and cookie policies. We encourage you to review them:</p>
            <ul>
                <li><a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google Privacy Policy</a></li>
                <li><a href="https://fontawesome.com/privacy" target="_blank" rel="noopener noreferrer">Font Awesome Privacy Policy</a></li>
            </ul>

            <h2>4. Managing Cookies</h2>
            <p>You can control and manage cookies in several ways:</p>
            <ul>
                <li><strong>Browser Settings:</strong> Most browsers allow you to refuse or delete cookies. Check your browser's help menu for instructions.</li>
                <li><strong>Cookie Consent Banner:</strong> When you first visit our site, you can choose which non-essential cookies to accept.</li>
                <li><strong>Do Not Track:</strong> We respect browser "Do Not Track" signals for analytics cookies.</li>
            </ul>
            <p><strong>Note:</strong> Disabling essential cookies may prevent certain features of our website from functioning correctly.</p>

            <h2>5. Cookie Consent</h2>
            <p>When you first visit our website, you will see a cookie consent banner. By clicking "Accept All" or continuing to browse, you consent to the use of cookies as described in this policy. You can change your preferences at any time by clicking the "Cookie Settings" link in the footer.</p>

            <h2>6. Changes to This Cookie Policy</h2>
            <p>We may update this Cookie Policy from time to time to reflect changes in technology, legislation, or our business practices. Any changes will be posted on this page with an updated effective date.</p>

            <h2>7. Contact Us</h2>
            <p>If you have questions about our use of cookies or this policy, please contact us:</p>
            <div class="card legal-contact-box">
                <p><strong><?= sanitize($companyName) ?></strong></p>
                <p>Email: <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a></p>
                <p>Phone: <a href="tel:+27688261507">+27 (68) 826-1507</a></p>
                <p>Address: Johannesburg, South Africa</p>
            </div>

            <div class="legal-meta">
                <p><strong>Effective Date:</strong> <?= date('Y') ?>-01-01<br>
                <strong>Last Updated:</strong> <?= date('Y-m-d') ?></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
