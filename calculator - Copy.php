<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/emails.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quote') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid token.';
    } else {
        $db = db();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO calculator_leads (name, email, company, service_type, answer_json, estimated_min, estimated_max) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([
                sanitize($_POST['name'] ?? ''),
                filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
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

<section class="services-hero" style="padding-top:140px;">
    <div class="container">
        <span class="section-tag">/ Calculator</span>
        <h1>Project Cost <span class="highlight">Estimator</span></h1>
        <p>Answer a few questions and get an instant ballpark figure for your investment.</p>
    </div>
</section>

<section class="section">
    <div class="container" style="max-width:900px;">
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:24px;">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:24px;">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="calculator-tabs">
            <button type="button" class="calc-tab btn btn-primary" data-service="software" onclick="switchService('software')">
                <i class="fas fa-laptop-code"></i> Custom Software
            </button>
            <button type="button" class="calc-tab btn btn-outline" data-service="data" onclick="switchService('data')">
                <i class="fas fa-database"></i> Data Engineering
            </button>
            <button type="button" class="calc-tab btn btn-outline" data-service="ai" onclick="switchService('ai')">
                <i class="fas fa-robot"></i> AI Agents
            </button>
        </div>

        <div id="calc-software" class="calc-panel" style="display:block;">
            <div class="calculator-panel">
                <h3 style="margin-bottom:20px;">Custom Software & Web Development</h3>
                <div class="calc-question">
                    <label>Project Type</label>
                    <select id="sw-type" class="calc-select">
                        <option value="1">Landing / Marketing Site</option>
                        <option value="2" selected>Web Application</option>
                        <option value="3">SaaS Platform</option>
                        <option value="3">E-commerce</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Pages / Screens</label>
                    <select id="sw-pages" class="calc-select">
                        <option value="3">Under 5</option>
                        <option value="7" selected>5 – 10</option>
                        <option value="15">10 – 20</option>
                        <option value="30">20+</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Third-party Integrations</label>
                    <select id="sw-integrations" class="calc-select">
                        <option value="0">None</option>
                        <option value="8000">1 – 2 APIs</option>
                        <option value="18000">3 – 5 APIs</option>
                        <option value="30000">5+ APIs / Enterprise</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Design Requirement</label>
                    <select id="sw-design" class="calc-select">
                        <option value="0">Template-based</option>
                        <option value="10000" selected>Custom UI Design</option>
                        <option value="20000">Premium UX Research + Design</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Timeline Urgency</label>
                    <select id="sw-timeline" class="calc-select">
                        <option value="0">Standard (8+ weeks)</option>
                        <option value="15000">Fast (4–6 weeks)</option>
                        <option value="25000">Rush (< 4 weeks)</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="calc-data" class="calc-panel" style="display:none;">
            <div class="calculator-panel">
                <h3 style="margin-bottom:20px;">Data Engineering & Analytics</h3>
                <div class="calc-question">
                    <label>Data Sources</label>
                    <select id="de-sources" class="calc-select">
                        <option value="10000">1 – 2 sources</option>
                        <option value="25000" selected>3 – 5 sources</option>
                        <option value="45000">6 – 10 sources</option>
                        <option value="70000">10+ sources</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Monthly Data Volume</label>
                    <select id="de-volume" class="calc-select">
                        <option value="1">Under 1 GB</option>
                        <option value="1.3">1 – 50 GB</option>
                        <option value="1.6" selected>50 GB – 1 TB</option>
                        <option value="2.2">1 TB+</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Real-time Processing?</label>
                    <select id="de-realtime" class="calc-select">
                        <option value="0">No / Batch only</option>
                        <option value="20000" selected>Yes (Streaming)</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Dashboards Required</label>
                    <select id="de-dashboards" class="calc-select">
                        <option value="0">None</option>
                        <option value="12000" selected>1 – 3 dashboards</option>
                        <option value="25000">3 – 10 dashboards</option>
                        <option value="45000">10+ / Embedded</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>AI / ML Downstream?</label>
                    <select id="de-aiml" class="calc-select">
                        <option value="0">No</option>
                        <option value="25000" selected>Yes (Predictive models, etc.)</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="calc-ai" class="calc-panel" style="display:none;">
            <div class="calculator-panel">
                <h3 style="margin-bottom:20px;">AI Agent Development</h3>
                <div class="calc-question">
                    <label>Number of Agents</label>
                    <select id="ai-agents" class="calc-select">
                        <option value="1" selected>1 Agent</option>
                        <option value="2">2 Agents</option>
                        <option value="4">3 – 5 Agents</option>
                        <option value="7">5+ Agents</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Complexity Level</label>
                    <select id="ai-complexity" class="calc-select">
                        <option value="0.7">Simple Chatbot (Q&A)</option>
                        <option value="1" selected>Workflow Assistant</option>
                        <option value="1.8">Autonomous Agent (decision-making)</option>
                        <option value="2.5">Multi-Agent System (orchestration)</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>System Integrations</label>
                    <select id="ai-integrations" class="calc-select">
                        <option value="0">None</option>
                        <option value="8000" selected>1 – 2 (CRM, Slack, etc.)</option>
                        <option value="18000">3 – 5 integrations</option>
                        <option value="30000">5+ / ERP-level</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Custom Training Data</label>
                    <select id="ai-training" class="calc-select">
                        <option value="0">Not needed (use LLM base)</option>
                        <option value="5000" selected>Small proprietary dataset</option>
                        <option value="15000">Large / enterprise corpus</option>
                    </select>
                </div>
                <div class="calc-question">
                    <label>Timeline</label>
                    <select id="ai-timeline" class="calc-select">
                        <option value="0">Standard (6+ weeks)</option>
                        <option value="12000">Fast (< 4 weeks)</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="text-align:center; margin:32px 0;">
            <button type="button" onclick="calculateEstimate()" class="btn btn-primary btn-lg">
                <i class="fas fa-calculator"></i> Calculate Estimate
            </button>
        </div>

        <div id="estimateResult" class="estimate-result" style="display:none;">
            <p style="color:var(--text-secondary); margin-bottom:8px;">Estimated Investment Range</p>
            <div class="estimate-amount">
                R<span id="estMin">0</span> — R<span id="estMax">0</span>
            </div>
            <p class="estimate-range">This is a ballpark figure. Final quotes depend on detailed scoping.</p>
            <div id="breakdown" style="text-align:left; background:var(--bg-secondary); padding:20px; border-radius:var(--border-radius-md); margin-bottom:24px; font-size:0.9375rem;"></div>
        </div>

        <div id="quoteForm" style="display:none;">
            <h3 style="margin-bottom:20px;">Get a Formal Quote</h3>
            <form method="POST" action="calculator.php" style="display:flex; flex-direction:column; gap:16px;">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="quote">
                <input type="hidden" name="service_type" id="quoteService" value="">
                <input type="hidden" name="answer_json" id="quoteAnswers" value="">
                <input type="hidden" name="estimated_min" id="quoteMin" value="0">
                <input type="hidden" name="estimated_max" id="quoteMax" value="0">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <input type="text" name="name" placeholder="Full Name" required class="form-input">
                    <input type="email" name="email" placeholder="Email Address" required class="form-input">
                </div>
                <input type="text" name="company" placeholder="Company (optional)" class="form-input">
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;">
                    <i class="fas fa-paper-plane"></i> Send Estimate to My Inbox
                </button>
            </form>
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
    document.querySelectorAll('.calc-panel').forEach(p => p.style.display = 'none');
    document.getElementById('calc-' + key).style.display = 'block';
    document.querySelectorAll('.calc-tab').forEach(t => {
        if(t.dataset.service === key) {
            t.classList.remove('btn-outline');
            t.classList.add('btn-primary');
        } else {
            t.classList.remove('btn-primary');
            t.classList.add('btn-outline');
        }
    });
    document.getElementById('estimateResult').style.display = 'none';
    document.getElementById('quoteForm').style.display = 'none';
}

function calculateEstimate() {
    const calc = services[currentService].calculate();
    document.getElementById('estMin').textContent = Math.round(calc.min).toLocaleString();
    document.getElementById('estMax').textContent = Math.round(calc.max).toLocaleString();
    
    const bd = document.getElementById('breakdown');
    bd.innerHTML = '<strong>Cost Breakdown:</strong><ul style="margin:8px 0 0 20px; line-height:1.8;">' + 
        calc.breakdown.map(b => `<li>${b}</li>`).join('') + '</ul>';
    
    document.getElementById('estimateResult').style.display = 'block';
    document.getElementById('quoteForm').style.display = 'block';
    
    document.getElementById('quoteService').value = services[currentService].name;
    document.getElementById('quoteMin').value = Math.round(calc.min);
    document.getElementById('quoteMax').value = Math.round(calc.max);
    
    const answers = {};
    document.querySelectorAll(`#calc-${currentService} select`).forEach(s => answers[s.id] = s.value);
    document.getElementById('quoteAnswers').value = JSON.stringify(answers);
    
    document.getElementById('estimateResult').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>