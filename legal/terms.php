<?php
declare(strict_types=1);
$pageTitle = 'Terms of Service';
$pageDescription = 'Vueports Solutions Terms of Service - Legal terms and conditions for using our website and services.';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$companyName = function_exists('getSetting') ? getSetting('site_name', 'Vueports Solutions') : 'Vueports Solutions';
$contactEmail = function_exists('getSetting') ? getSetting('contact_email', 'njabulod.hlongwane@gmail.com') : 'njabulod.hlongwane@gmail.com';
?>

<section class="page-header">
    <div class="container">
        <span class="section-tag">/ Legal</span>
        <h1 class="page-header-title">Terms of <span class="highlight">Service</span></h1>
    </div>
</section>

<section class="section section-alt">
    <div class="container legal-container">
        <div class="legal-content">
            <p class="lead">Please read these Terms of Service carefully before using the <?= sanitize($companyName) ?> website or engaging our services. By accessing or using our services, you agree to be bound by these terms.</p>

            <h2>1. Definitions</h2>
            <p>In these Terms:</p>
            <ul>
                <li><strong>"Company," "we," "us,"</strong> or <strong>"our"</strong> refers to <?= sanitize($companyName) ?>.</li>
                <li><strong>"Client," "you,"</strong> or <strong>"your"</strong> refers to the individual or entity using our services.</li>
                <li><strong>"Services"</strong> refers to software development, data engineering, AI development, consulting, and related IT services.</li>
                <li><strong>"Deliverables"</strong> refers to all work product, code, documentation, and materials provided to you.</li>
            </ul>

            <h2>2. Service Engagement</h2>
            <h3>2.1 Project Scope</h3>
            <p>All projects begin with a formal proposal or statement of work (SOW) that defines:</p>
            <ul>
                <li>Project objectives and requirements</li>
                <li>Deliverables and milestones</li>
                <li>Timeline and deadlines</li>
                <li>Payment terms and schedule</li>
                <li>Intellectual property provisions</li>
            </ul>

            <h3>2.2 Changes and Scope Creep</h3>
            <p>Any changes to the agreed scope require a written change request. We reserve the right to adjust timelines and costs for out-of-scope work.</p>

            <h2>3. Payment Terms</h2>
            <ul>
                <li><strong>Deposit:</strong> 50% upfront for fixed-price projects</li>
                <li><strong>Milestone Payments:</strong> As defined in the SOW</li>
                <li><strong>Final Payment:</strong> 50% upon project completion</li>
                <li><strong>Retainers:</strong> Monthly billing for ongoing support</li>
                <li><strong>Late Payments:</strong> Subject to 2% monthly service charge</li>
            </ul>
            <p>All fees are quoted in South African Rand (ZAR) unless otherwise agreed. VAT applies where applicable.</p>

            <h2>4. Intellectual Property</h2>
            <h3>4.1 Ownership</h3>
            <p>Upon full payment, you receive ownership of custom code and deliverables specifically created for your project. We retain:</p>
            <ul>
                <li>Rights to reusable components, libraries, and frameworks</li>
                <li>The right to use anonymized case studies for marketing</li>
                <li>Rights to open-source contributions derived from the work</li>
            </ul>

            <h3>4.2 Third-Party Components</h3>
            <p>Third-party libraries, APIs, and services remain subject to their respective licenses. We will inform you of any such dependencies.</p>

            <h2>5. Confidentiality</h2>
            <p>Both parties agree to maintain confidentiality of proprietary information disclosed during the engagement. This obligation survives termination of the agreement for a period of 3 years.</p>

            <h2>6. Warranties and Liability</h2>
            <h3>6.1 Our Warranties</h3>
            <p>We warrant that:</p>
            <ul>
                <li>Services will be performed in a professional manner</li>
                <li>Deliverables will conform to the agreed specifications</li>
                <li>We have the right to provide the deliverables</li>
            </ul>

            <h3>6.2 Limitation of Liability</h3>
            <p>Our total liability shall not exceed the total amount paid by you for the specific project giving rise to the claim. We are not liable for:</p>
            <ul>
                <li>Indirect, incidental, or consequential damages</li>
                <li>Loss of profits, data, or business opportunities</li>
                <li>Issues arising from third-party services or integrations</li>
            </ul>

            <h2>7. Termination</h2>
            <p>Either party may terminate the engagement with 30 days written notice. Upon termination:</p>
            <ul>
                <li>You pay for all work completed up to the termination date</li>
                <li>We deliver all completed deliverables</li>
                <li>Confidentiality obligations remain in effect</li>
            </ul>

            <h2>8. Dispute Resolution</h2>
            <p>Any disputes shall first be attempted to be resolved through good faith negotiation. If unresolved, disputes shall be submitted to mediation in Johannesburg, South Africa, under the rules of the Arbitration Foundation of Southern Africa.</p>

            <h2>9. Governing Law</h2>
            <p>These Terms are governed by the laws of the Republic of South Africa. You consent to the exclusive jurisdiction of the courts of Johannesburg.</p>

            <h2>10. General Provisions</h2>
            <ul>
                <li><strong>Entire Agreement:</strong> These Terms constitute the entire agreement between us</li>
                <li><strong>Severability:</strong> If any provision is invalid, the remainder continues in effect</li>
                <li><strong>Waiver:</strong> Failure to enforce any right does not constitute a waiver</li>
                <li><strong>Assignment:</strong> You may not assign these Terms without our consent</li>
            </ul>

            <h2>11. Contact Information</h2>
            <div class="card legal-contact-box">
                <p><strong><?= sanitize($companyName) ?></strong></p>
                <p>Email: <a href="mailto:<?= sanitize($contactEmail) ?>"><?= sanitize($contactEmail) ?></a></p>
                <p>Phone: <a href="tel:+27688261507">+27 (68) 826-1507</a></p>
                <p>Address: Johannesburg, South Africa</p>
            </div>

            <div class="legal-meta">
                <p>By using our website or services, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
