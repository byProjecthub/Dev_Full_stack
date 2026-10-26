<?php
// Vueports Solutions — Header Template (Root Level)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth state
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$userRole = $_SESSION['user_role'] ?? 'client';

// Flash messages
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Active page helper
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
function isActive($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : ''; ?>Vueports Solutions</title>
  <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'Vueports Solutions — Custom Software, Data Engineering & AI Agents'; ?>">
  <link rel="stylesheet" href="/assets/css/variables.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css">
    <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js"></script>
</head>
<body>

<!-- Flash Message -->
<?php if ($flashMessage): ?>
<div id="flashMessage" class="alert alert-<?php echo htmlspecialchars($flashType); ?>" style="position: fixed; top: 90px; left: 50%; transform: translateX(-50%); z-index: 500; max-width: 500px; width: 90%; text-align: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
  <?php echo htmlspecialchars($flashMessage); ?>
</div>
<script>
setTimeout(() => {
  const el = document.getElementById('flashMessage');
  if (el) { el.style.opacity = '0'; el.style.transition = 'opacity 0.5s'; setTimeout(() => el.remove(), 500); }
}, 4000);
</script>
<?php endif; ?>

<!-- Navigation -->
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
      <div class="logo"> <a href="/index.php" class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 200" width="200" height="50">
                        <defs>
                            <linearGradient id="vGradF" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                            </linearGradient>
                            <linearGradient id="accentGradF" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" style="stop-color:#ec4899;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#f43f5e;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                        <!-- Stylized V Mark — FLIPPED UPSIDE DOWN -->
                        <g transform="rotate(180, 65, 65)">
                            <path d="M 60 10 L 110 130 L 130 130 L 70 0 L 50 0 L 0 130 L 20 130 Z" fill="url(#vGradF)"/>
                            <path d="M 70 35 L 95 95 L 105 95 L 75 20 Z" fill="url(#accentGradF)" opacity="0.9"/>
                            <circle cx="115" cy="145" r="8" fill="url(#accentGradF)"/>
                        </g>
                        <text x="170" y="95" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="72" font-weight="800" fill="#0f172a" letter-spacing="-2">Vueports</text>
                        <text x="172" y="130" font-family="Inter, -apple-system, BlinkMacSystemFont, sans-serif" font-size="22" font-weight="500" fill="#64748b" letter-spacing="4">SOLUTIONS</text>
                    </svg>
                </a></div>

    <div class="navbar-links">
      <a href="/index.php" class="<?php echo isActive('index', $currentPage); ?>">Home</a>
      <a href="/about.php" class="<?php echo isActive('about', $currentPage); ?>">About</a>
      <a href="/services.php" class="<?php echo isActive('services', $currentPage); ?>">Services</a>
      <a href="/pricing.php" class="<?php echo isActive('pricing', $currentPage); ?>">Pricing</a>
      <a href="/contact.php" class="<?php echo isActive('contact', $currentPage); ?>">Contact</a>
      <?php if ($isLoggedIn): ?>
        <a href="/client/dashboard.php" class="<?php echo isActive('dashboard', $currentPage); ?>">Portal</a>
      <?php endif; ?>
    </div>

    <div class="navbar-actions">
      <?php if ($isLoggedIn): ?>
        <span style="font-size: 0.875rem; color: #6b7280; display: inline-flex; align-items: center; gap: 0.5rem;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
          <?php echo htmlspecialchars($userName ?: 'Account'); ?>
        </span>
        <a href="/client/logout.php" class="btn btn-secondary" style="display:inline-flex;">Sign Out</a>
      <?php else: ?>
  <!-- <a href="/login.php" class="btn btn-secondary" style="display:inline-flex;">Sign In</a>-->
     <a href="/consultation.php" class="btn btn-primary" style="display:inline-flex;">Get Started</a>
      <?php endif; ?>
      <button class="navbar-toggle" onclick="toggleMobileNav()" aria-label="Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</nav>

<!-- Mobile Nav Overlay -->
<div class="mobile-nav" id="mobileNav">
  <button class="mobile-nav-close" onclick="toggleMobileNav()" aria-label="Close">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="18" y1="6" x2="6" y2="18"></line>
      <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
  </button>
  <a href="/index.php" onclick="toggleMobileNav()">Home</a>
  <a href="/about.php" onclick="toggleMobileNav()">About</a>
  <a href="/services.php" onclick="toggleMobileNav()">Services</a>
  <a href="/pricing.php" onclick="toggleMobileNav()">Pricing</a>
  <a href="/contact.php" onclick="toggleMobileNav()">Contact</a>
  <?php if ($isLoggedIn): ?>
    <a href="/client/dashboard.php" onclick="toggleMobileNav()">Portal</a>
    <a href="/client/logout.php" onclick="toggleMobileNav()">Sign Out</a>
  <?php else: ?>
 <!--   <a href="/login.php" onclick="toggleMobileNav()">Sign In</a>-->
  <?php endif; ?>
</div>
