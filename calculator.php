<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quote') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token. Please refresh the page and try again.';
    } else {
        $db = db();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO calculator_leads 
                (name, email, phone, company, service_type, answer_json, estimated_min, estimated_max, created_at) 
                VALUES (?,?,?,?,?,?,?,?, NOW())");
            $stmt->execute([
                sanitize($_POST['name'] ?? ''),
                filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
                sanitize($_POST['phone'] ?? ''),
                sanitize($_POST['company'] ?? ''),
                sanitize($_POST['service_type'] ?? ''),
                $_POST['answer_json'] ?? '{}',
                (float) ($_POST['estimated_min'] ?? 0),
                (float) ($_POST['estimated_max'] ?? 0),
            ]);
        }
        sendCalculatorLead([
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'service_type' => sanitize($_POST['service_type'] ?? ''),
            'estimated_min' => (float) ($_POST['estimated_min'] ?? 0),
            'estimated_max' => (float) ($_POST['estimated_max'] ?? 0),
        ]);
        $success = 'Estimate sent to your email. We will follow up with a formal quote within 24 hours.';
    }
}

$pageTitle = 'Project Calculator';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="page-header-bg">Calculate</div>
  <div class="container">
    <div class="page-header-content">
      <span class="section-label">Price Calculator</span>
      <h1 class="page-header-title">Estimate your<br>project cost.</h1>
      <p class="page-header-desc">Answer a few questions and get an instant ballpark estimate for your project.</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 0;">
  <div class="container">
    <div class="calculator-card reveal">

      <?php if ($success): ?>
        <div class="alert alert-success" style="margin-bottom: var(--space-6);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <?php echo htmlspecialchars($success); ?>
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom: var(--space-6);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <!-- Service Tabs -->
      <div class="vp-calculator-tabs" style="display: flex; gap: var(--space-3); margin-bottom: var(--space-8); flex-wrap: wrap;">
        <button type="button" class="btn btn-primary" id="tab-software" onclick="switchService('software')">Custom Software</button>
        <button type="button" class="btn btn-secondary" id="tab-data" onclick="switchService('data')">Data Engineering</button>
        <button type="button" class="btn btn-secondary" id="tab-ai" onclick="switchService('ai')">AI Agents</button>
          <button type="button" class="btn btn-secondary" id="tab-mobile" onclick="switchService('mobile')">Mobile Applications</button>
      </div>

      <!-- ========== SOFTWARE PANEL ========== -->
      <div id="panel-software" class="calculator-section">
        <h3>Custom Software & Web Development</h3>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Project Type</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="sw-type" data-value="1">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Landing / Marketing Site</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="sw-type" data-value="2">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Web Application</span>
              </div>
              <span class="option-price">+40%</span>
            </label>
            <label class="calculator-option" data-target="sw-type" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">SaaS Platform</span>
              </div>
              <span class="option-price">+80%</span>
            </label>
            <label class="calculator-option" data-target="sw-type" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">E-commerce</span>
              </div>
              <span class="option-price">+60%</span>
            </label>
          </div>
          <select id="sw-type" style="display:none;">
            <option value="1">Landing / Marketing Site</option>
            <option value="2" selected>Web Application</option>
            <option value="3">SaaS Platform</option>
            <option value="3">E-commerce</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Pages / Screens</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="sw-pages" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Under 5</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="sw-pages" data-value="7">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5 – 10</span>
              </div>
              <span class="option-price">+R6,000</span>
            </label>
            <label class="calculator-option" data-target="sw-pages" data-value="15">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">10 – 20</span>
              </div>
              <span class="option-price">+R18,000</span>
            </label>
            <label class="calculator-option" data-target="sw-pages" data-value="30">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">20+</span>
              </div>
              <span class="option-price">+R40,500</span>
            </label>
          </div>
          <select id="sw-pages" style="display:none;">
            <option value="3">Under 5</option>
            <option value="7" selected>5 – 10</option>
            <option value="15">10 – 20</option>
            <option value="30">20+</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Third-party Integrations</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="sw-integrations" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">None</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="sw-integrations" data-value="8000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 2 APIs</span>
              </div>
              <span class="option-price">+R8,000</span>
            </label>
            <label class="calculator-option" data-target="sw-integrations" data-value="18000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 5 APIs</span>
              </div>
              <span class="option-price">+R18,000</span>
            </label>
            <label class="calculator-option" data-target="sw-integrations" data-value="30000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5+ APIs / Enterprise</span>
              </div>
              <span class="option-price">+R30,000</span>
            </label>
          </div>
          <select id="sw-integrations" style="display:none;">
            <option value="0" selected>None</option>
            <option value="8000">1 – 2 APIs</option>
            <option value="18000">3 – 5 APIs</option>
            <option value="30000">5+ APIs / Enterprise</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Design Requirement</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="sw-design" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Template-based</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="sw-design" data-value="10000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Custom UI Design</span>
              </div>
              <span class="option-price">+R10,000</span>
            </label>
            <label class="calculator-option" data-target="sw-design" data-value="20000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Premium UX Research + Design</span>
              </div>
              <span class="option-price">+R20,000</span>
            </label>
          </div>
          <select id="sw-design" style="display:none;">
            <option value="0">Template-based</option>
            <option value="10000" selected>Custom UI Design</option>
            <option value="20000">Premium UX Research + Design</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Timeline Urgency</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="sw-timeline" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Standard (8+ weeks)</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="sw-timeline" data-value="15000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Fast (4–6 weeks)</span>
              </div>
              <span class="option-price">+R15,000</span>
            </label>
            <label class="calculator-option" data-target="sw-timeline" data-value="25000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Rush (&lt; 4 weeks)</span>
              </div>
              <span class="option-price">+R25,000</span>
            </label>
          </div>
          <select id="sw-timeline" style="display:none;">
            <option value="0" selected>Standard (8+ weeks)</option>
            <option value="15000">Fast (4–6 weeks)</option>
            <option value="25000">Rush (&lt; 4 weeks)</option>
          </select>
        </div>
      </div>

      <!-- ========== DATA PANEL ========== -->
      <div id="panel-data" class="calculator-section" style="display: none;">
        <h3>Data Engineering & Analytics</h3>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Data Sources</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="de-sources" data-value="10000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 2 sources</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="de-sources" data-value="25000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 5 sources</span>
              </div>
              <span class="option-price">+R15,000</span>
            </label>
            <label class="calculator-option" data-target="de-sources" data-value="45000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">6 – 10 sources</span>
              </div>
              <span class="option-price">+R35,000</span>
            </label>
            <label class="calculator-option" data-target="de-sources" data-value="70000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">10+ sources</span>
              </div>
              <span class="option-price">+R60,000</span>
            </label>
          </div>
          <select id="de-sources" style="display:none;">
            <option value="10000">1 – 2 sources</option>
            <option value="25000" selected>3 – 5 sources</option>
            <option value="45000">6 – 10 sources</option>
            <option value="70000">10+ sources</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Monthly Data Volume</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="de-volume" data-value="1">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Under 1 GB</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="de-volume" data-value="1.3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 50 GB</span>
              </div>
              <span class="option-price">+30%</span>
            </label>
            <label class="calculator-option selected" data-target="de-volume" data-value="1.6">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">50 GB – 1 TB</span>
              </div>
              <span class="option-price">+60%</span>
            </label>
            <label class="calculator-option" data-target="de-volume" data-value="2.2">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 TB+</span>
              </div>
              <span class="option-price">+120%</span>
            </label>
          </div>
          <select id="de-volume" style="display:none;">
            <option value="1">Under 1 GB</option>
            <option value="1.3">1 – 50 GB</option>
            <option value="1.6" selected>50 GB – 1 TB</option>
            <option value="2.2">1 TB+</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Real-time Processing?</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="de-realtime" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">No / Batch only</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="de-realtime" data-value="20000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Yes (Streaming)</span>
              </div>
              <span class="option-price">+R20,000</span>
            </label>
          </div>
          <select id="de-realtime" style="display:none;">
            <option value="0">No / Batch only</option>
            <option value="20000" selected>Yes (Streaming)</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Dashboards Required</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="de-dashboards" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">None</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="de-dashboards" data-value="12000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 3 dashboards</span>
              </div>
              <span class="option-price">+R12,000</span>
            </label>
            <label class="calculator-option" data-target="de-dashboards" data-value="25000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 10 dashboards</span>
              </div>
              <span class="option-price">+R25,000</span>
            </label>
            <label class="calculator-option" data-target="de-dashboards" data-value="45000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">10+ / Embedded</span>
              </div>
              <span class="option-price">+R45,000</span>
            </label>
          </div>
          <select id="de-dashboards" style="display:none;">
            <option value="0">None</option>
            <option value="12000" selected>1 – 3 dashboards</option>
            <option value="25000">3 – 10 dashboards</option>
            <option value="45000">10+ / Embedded</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">AI / ML Downstream?</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="de-aiml" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">No</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="de-aiml" data-value="25000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Yes (Predictive models, etc.)</span>
              </div>
              <span class="option-price">+R25,000</span>
            </label>
          </div>
          <select id="de-aiml" style="display:none;">
            <option value="0">No</option>
            <option value="25000" selected>Yes (Predictive models, etc.)</option>
          </select>
        </div>
      </div>

      <!-- ========== AI PANEL ========== -->
      <div id="panel-ai" class="calculator-section" style="display: none;">
        <h3>AI Agent Development</h3>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Number of Agents</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="ai-agents" data-value="1">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 Agent</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="ai-agents" data-value="2">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">2 Agents</span>
              </div>
              <span class="option-price">2×</span>
            </label>
            <label class="calculator-option" data-target="ai-agents" data-value="4">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 5 Agents</span>
              </div>
              <span class="option-price">4×</span>
            </label>
            <label class="calculator-option" data-target="ai-agents" data-value="7">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5+ Agents</span>
              </div>
              <span class="option-price">7×</span>
            </label>
          </div>
          <select id="ai-agents" style="display:none;">
            <option value="1" selected>1 Agent</option>
            <option value="2">2 Agents</option>
            <option value="4">3 – 5 Agents</option>
            <option value="7">5+ Agents</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Complexity Level</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="ai-complexity" data-value="0.7">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Simple Chatbot (Q&A)</span>
              </div>
              <span class="option-price">–30%</span>
            </label>
            <label class="calculator-option selected" data-target="ai-complexity" data-value="1">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Workflow Assistant</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="ai-complexity" data-value="1.8">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Autonomous Agent (decision-making)</span>
              </div>
              <span class="option-price">+80%</span>
            </label>
            <label class="calculator-option" data-target="ai-complexity" data-value="2.5">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Multi-Agent System (orchestration)</span>
              </div>
              <span class="option-price">+150%</span>
            </label>
          </div>
          <select id="ai-complexity" style="display:none;">
            <option value="0.7">Simple Chatbot (Q&A)</option>
            <option value="1" selected>Workflow Assistant</option>
            <option value="1.8">Autonomous Agent (decision-making)</option>
            <option value="2.5">Multi-Agent System (orchestration)</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">System Integrations</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="ai-integrations" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">None</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="ai-integrations" data-value="8000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 2 (CRM, Slack, etc.)</span>
              </div>
              <span class="option-price">+R8,000</span>
            </label>
            <label class="calculator-option" data-target="ai-integrations" data-value="18000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 5 integrations</span>
              </div>
              <span class="option-price">+R18,000</span>
            </label>
            <label class="calculator-option" data-target="ai-integrations" data-value="30000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5+ / ERP-level</span>
              </div>
              <span class="option-price">+R30,000</span>
            </label>
          </div>
          <select id="ai-integrations" style="display:none;">
            <option value="0">None</option>
            <option value="8000" selected>1 – 2 (CRM, Slack, etc.)</option>
            <option value="18000">3 – 5 integrations</option>
            <option value="30000">5+ / ERP-level</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Custom Training Data</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="ai-training" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Not needed (use LLM base)</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="ai-training" data-value="5000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Small proprietary dataset</span>
              </div>
              <span class="option-price">+R5,000</span>
            </label>
            <label class="calculator-option" data-target="ai-training" data-value="15000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Large / enterprise corpus</span>
              </div>
              <span class="option-price">+R15,000</span>
            </label>
          </div>
          <select id="ai-training" style="display:none;">
            <option value="0">Not needed (use LLM base)</option>
            <option value="5000" selected>Small proprietary dataset</option>
            <option value="15000">Large / enterprise corpus</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Timeline</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="ai-timeline" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Standard (6+ weeks)</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="ai-timeline" data-value="12000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Fast (&lt; 4 weeks)</span>
              </div>
              <span class="option-price">+R12,000</span>
            </label>
          </div>
          <select id="ai-timeline" style="display:none;">
            <option value="0" selected>Standard (6+ weeks)</option>
            <option value="12000">Fast (&lt; 4 weeks)</option>
          </select>
        </div>
      </div>

         <!-- ========== MOBILE APP PANEL ========== -->
      <div id="panel-mobile" class="calculator-section">
        <h3>Custom Mobile Application Development</h3>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Project Type</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="mob-type" data-value="1">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Native App</span>
              </div>
              <span class="option-price">Web Based</span>
            </label>
            <label class="calculator-option" data-target="mob-type" data-value="2">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Hybrid</span>
              </div>
              <span class="option-price">+40%</span>
            </label>
            <label class="calculator-option" data-target="mob-type" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Cross-Platform</span>
              </div>
              <span class="option-price">+60%</span>
            </label>
            <label class="calculator-option" data-target="mob-type" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Progressive </span>
              </div>
              <span class="option-price">+80%</span>
            </label>
          </div>
          <select id="mob-type" style="display:none;">
            <option value="1">Native</option>
            <option value="2" selected>Web Based</option>
            <option value="3">Hybrid</option>
            <option value="4">Cross-Platform</option>
            <option value="5">Progressive Wep Apps</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Pages / Screens</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="mob-pages" data-value="3">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Under 5</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="mob-pages" data-value="7">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5 – 10</span>
              </div>
              <span class="option-price">+R6,000</span>
            </label>
            <label class="calculator-option" data-target="mob-pages" data-value="15">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">10 – 20</span>
              </div>
              <span class="option-price">+R18,000</span>
            </label>
            <label class="calculator-option" data-target="mob-pages" data-value="30">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">20+</span>
              </div>
              <span class="option-price">+R40,500</span>
            </label>
          </div>
          <select id="mob-pages" style="display:none;">
            <option value="3">Under 5</option>
            <option value="7" selected>5 – 10</option>
            <option value="15">10 – 20</option>
            <option value="30">20+</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Third-party Integrations</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="mob-integrations" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">None</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="mob-integrations" data-value="8000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">1 – 2 APIs</span>
              </div>
              <span class="option-price">+R8,000</span>
            </label>
            <label class="calculator-option" data-target="mob-integrations" data-value="18000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">3 – 5 APIs</span>
              </div>
              <span class="option-price">+R18,000</span>
            </label>
            <label class="calculator-option" data-target="mob-integrations" data-value="30000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">5+ APIs / Enterprise</span>
              </div>
              <span class="option-price">+R30,000</span>
            </label>
          </div>
          <select id="mob-integrations" style="display:none;">
            <option value="0" selected>None</option>
            <option value="8000">1 – 2 APIs</option>
            <option value="18000">3 – 5 APIs</option>
            <option value="30000">5+ APIs / Enterprise</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Design Requirement</h3>
          <div class="calculator-options">
            <label class="calculator-option" data-target="mob-design" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Template-based</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option selected" data-target="mob-design" data-value="10000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Custom UI Design</span>
              </div>
              <span class="option-price">+R10,000</span>
            </label>
            <label class="calculator-option" data-target="mob-design" data-value="20000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Premium UX Research + Design</span>
              </div>
              <span class="option-price">+R20,000</span>
            </label>
          </div>
          <select id="mob-design" style="display:none;">
            <option value="0">Template-based</option>
            <option value="10000" selected>Custom UI Design</option>
            <option value="20000">Premium UX Research + Design</option>
          </select>
        </div>

        <div class="calculator-section">
          <h3 style="font-size: var(--text-lg); margin-bottom: var(--space-4);">Timeline Urgency</h3>
          <div class="calculator-options">
            <label class="calculator-option selected" data-target="mob-timeline" data-value="0">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Standard (8+ weeks)</span>
              </div>
              <span class="option-price">Base</span>
            </label>
            <label class="calculator-option" data-target="mob-timeline" data-value="15000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Fast (4–6 weeks)</span>
              </div>
              <span class="option-price">+R15,000</span>
            </label>
            <label class="calculator-option" data-target="mob-timeline" data-value="25000">
              <div class="option-info">
                <div class="option-radio"><div></div></div>
                <span class="option-name">Rush (&lt; 4 weeks)</span>
              </div>
              <span class="option-price">+R25,000</span>
            </label>
          </div>
          <select id="mob-timeline" style="display:none;">
            <option value="0" selected>Standard (8+ weeks)</option>
            <option value="15000">Fast (4–6 weeks)</option>
            <option value="25000">Rush (&lt; 4 weeks)</option>
          </select>
        </div>
      </div>

      <!-- Calculate Button -->
      <div style="text-align:center; margin: var(--space-8) 0;">
        <button type="button" onclick="calculateEstimate()" class="btn btn-primary btn-lg" style="min-width: 280px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:text-bottom; margin-right:var(--space-2);"><rect x="4" y="2" width="16" height="20" rx="2"></rect><line x1="8" y1="6" x2="16" y2="6"></line><line x1="16" y1="14" x2="16" y2="14.01"></line><line x1="12" y1="18" x2="12" y2="18.01"></line></svg>
          Calculate Estimate
        </button>
      </div>

      <!-- Result -->
      <div id="estimateResult" class="estimate-result" style="display:none;">
        <div class="calculator-total" style="margin-bottom: var(--space-6);">
          <div class="total-label">Estimated Investment Range</div>
          <div class="total-value">R<span id="estMin">0</span> — R<span id="estMax">0</span></div>
        </div>
        <p style="font-size: var(--text-sm); color: var(--text-muted); text-align: center; margin-bottom: var(--space-6);">This is a ballpark figure. Final quotes depend on detailed scoping.</p>
        <div id="breakdown" style="text-align:left; background:var(--bg-secondary); padding:var(--space-6); border-radius:var(--border-radius-md); margin-bottom:var(--space-6); font-size:0.9375rem;"></div>
      </div>

      <!-- CTA Row -->
      <div id="calcActions" style="display:none; display: flex; gap: var(--space-4); margin-top: var(--space-8); flex-wrap: wrap;">
        <button type="button" onclick="showQuoteForm()" class="btn btn-primary btn-lg" style="flex: 1; min-width: 200px;">
          Get Formal Quote
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:text-bottom; margin-left:var(--space-2);"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
        </button>
        <a href="consultation.php" class="btn btn-secondary btn-lg" style="flex: 1; min-width: 200px; text-align:center;">Book a Free Call</a>
      </div>

      <p style="font-size: var(--text-xs); color: var(--text-muted); text-align: center; margin-top: var(--space-6);">This is a ballpark estimate. Final pricing depends on detailed requirements. Book a call for an accurate quote.</p>

      <!-- Quote Form -->
      <div id="quoteForm" style="display: none; margin-top: var(--space-8); padding-top: var(--space-8); border-top: 1px solid var(--border-subtle);">
        <h3 style="font-size: var(--text-xl); font-weight: 700; margin-bottom: var(--space-6);">Get a Formal Quote</h3>
        <form method="POST" action="calculator.php">
          <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
          <input type="hidden" name="action" value="quote">
          <input type="hidden" name="service_type" id="quoteService" value="Custom Software & Web Development">
          <input type="hidden" name="answer_json" id="quoteAnswer" value="{}">
          <input type="hidden" name="estimated_min" id="quoteMin" value="0">
          <input type="hidden" name="estimated_max" id="quoteMax" value="0">

          <div class="grid-2" style="gap: var(--space-4);">
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="name" required class="form-input" placeholder="John Doe">
            </div>
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="email" required class="form-input" placeholder="john@company.com">
            </div>
          </div>

          <div class="grid-2" style="gap: var(--space-4);">
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" class="form-input" placeholder="+27 68 826 1507">
            </div>
            <div class="form-group">
              <label class="form-label">Company</label>
              <input type="text" name="company" class="form-input" placeholder="Your company">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-4);">
            Send Estimate to My Inbox
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; vertical-align:text-bottom; margin-left:var(--space-2);"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
          </button>
        </form>
      </div>

    </div>
  </div>
</section>

<script>
const services = {
    software: {
        name: 'Custom Software & Web Development',
        base: 15000,
        pageRate: 1500,
        calculate() {
            const typeMult = parseFloat(document.getElementById('sw-type').value);
            const pages = parseInt(document.getElementById('sw-pages').value);
            const integrations = parseInt(document.getElementById('sw-integrations').value);
            const design = parseInt(document.getElementById('sw-design').value);
            const timeline = parseInt(document.getElementById('sw-timeline').value);
            const subtotal = this.base + (pages * this.pageRate * typeMult) + integrations + design + timeline;
            return { min: subtotal * 0.9, max: subtotal * 1.15, breakdown: [
                `Base project: R${this.base.toLocaleString()}`,
                `Pages/Screens (${pages} × R${this.pageRate} × ${typeMult}x): R${Math.round(pages*this.pageRate*typeMult).toLocaleString()}`,
                `Integrations: R${integrations.toLocaleString()}`,
                `Design: R${design.toLocaleString()}`,
                `Timeline: R${timeline.toLocaleString()}`
            ]};
        }
    },
    mobile: {
        name: 'Mobile Applications Development',
        base: 20000,
        pageRate: 1500,
        calculate() {
             const typeMult = parseFloat(document.getElementById('mob-type').value);
            const pages = parseInt(document.getElementById('mob-pages').value);
            const integrations = parseInt(document.getElementById('mob-integrations').value);
            const design = parseInt(document.getElementById('mob-design').value);
            const timeline = parseInt(document.getElementById('mob-timeline').value);
            const subtotal = this.base + (pages * this.pageRate * typeMult) + integrations + design + timeline;
            return { min: subtotal * 0.9, max: subtotal * 1.15, breakdown: [
                `Base project: R${this.base.toLocaleString()}`,
                `Pages/Screens (${pages} × R${this.pageRate} × ${typeMult}x): R${Math.round(pages*this.pageRate*typeMult).toLocaleString()}`,
                `Integrations: R${integrations.toLocaleString()}`,
                `Design: R${design.toLocaleString()}`,
                `Timeline: R${timeline.toLocaleString()}`
            ]};
        }
    },
    data: {
        name: 'Data Engineering & Analytics',
        base: 25000,
        calculate() {
            const sources = parseInt(document.getElementById('de-sources').value);
            const volMult = parseFloat(document.getElementById('de-volume').value);
            const realtime = parseInt(document.getElementById('de-realtime').value);
            const dashboards = parseInt(document.getElementById('de-dashboards').value);
            const aiml = parseInt(document.getElementById('de-aiml').value);
            const subtotal = (this.base * volMult) + sources + realtime + dashboards + aiml;
            return { min: subtotal * 0.9, max: subtotal * 1.15, breakdown: [
                `Base platform (×${volMult} volume factor): R${Math.round(this.base*volMult).toLocaleString()}`,
                `Data sources: R${sources.toLocaleString()}`,
                `Real-time: R${realtime.toLocaleString()}`,
                `Dashboards: R${dashboards.toLocaleString()}`,
                `AI/ML downstream: R${aiml.toLocaleString()}`
            ]};
        }
    },
    ai: {
        name: 'AI Agent Development',
        basePerAgent: 18000,
        calculate() {
            const agents = parseInt(document.getElementById('ai-agents').value);
            const complexity = parseFloat(document.getElementById('ai-complexity').value);
            const integrations = parseInt(document.getElementById('ai-integrations').value);
            const training = parseInt(document.getElementById('ai-training').value);
            const timeline = parseInt(document.getElementById('ai-timeline').value);
            const subtotal = (agents * this.basePerAgent * complexity) + integrations + training + timeline;
            return { min: subtotal * 0.9, max: subtotal * 1.15, breakdown: [
                `Agents (${agents} × R${this.basePerAgent} × ${complexity}x): R${Math.round(agents*this.basePerAgent*complexity).toLocaleString()}`,
                `Integrations: R${integrations.toLocaleString()}`,
                `Training data: R${training.toLocaleString()}`,
                `Timeline: R${timeline.toLocaleString()}`
            ]};
        }
    }
};

let currentService = 'software';

function switchService(key) {
    currentService = key;
    document.querySelectorAll('[id^="panel-"]').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + key).style.display = 'block';
    document.querySelectorAll('[id^="tab-"]').forEach(t => {
        t.classList.remove('btn-primary');
        t.classList.add('btn-secondary');
    });
    document.getElementById('tab-' + key).classList.remove('btn-secondary');
    document.getElementById('tab-' + key).classList.add('btn-primary');
    
    document.getElementById('estimateResult').style.display = 'none';
    document.getElementById('quoteForm').style.display = 'none';
    document.getElementById('calcActions').style.display = 'none';
}

// Handle option card clicks
document.querySelectorAll('.calculator-option').forEach(option => {
    option.addEventListener('click', function() {
        const targetId = this.dataset.target;
        const value = this.dataset.value;
        const target = document.getElementById(targetId);
        if (target) target.value = value;

        // Single-select within the same question
        const parent = this.closest('.calculator-options');
        parent.querySelectorAll('.calculator-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
    });
});

function calculateEstimate() {
    const calc = services[currentService].calculate();
    document.getElementById('estMin').textContent = Math.round(calc.min).toLocaleString();
    document.getElementById('estMax').textContent = Math.round(calc.max).toLocaleString();
    
    const bd = document.getElementById('breakdown');
    bd.innerHTML = '<strong style="display:block; margin-bottom:var(--space-2);">Cost Breakdown:</strong><ul style="margin:0 0 0 20px; line-height:1.8; color: var(--text-secondary);">' + 
        calc.breakdown.map(b => `<li>${b}</li>`).join('') + '</ul>';
    
    document.getElementById('estimateResult').style.display = 'block';
    document.getElementById('calcActions').style.display = 'flex';
    
    document.getElementById('quoteService').value = services[currentService].name;
    document.getElementById('quoteMin').value = Math.round(calc.min);
    document.getElementById('quoteMax').value = Math.round(calc.max);
    
    const answer = {};
    document.querySelectorAll(`#panel-${currentService} select`).forEach(s => answer[s.id] = s.value);
    document.getElementById('quoteAnswer').value = JSON.stringify(answer);
    
    document.getElementById('estimateResult').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function showQuoteForm() {
    document.getElementById('quoteForm').style.display = 'block';
    document.getElementById('quoteForm').scrollIntoView({ behavior: 'smooth' });
}

// Show only the selected service and keep its controls isolated.
switchService(currentService);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
