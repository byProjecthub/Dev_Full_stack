<?php
// Vueports Solutions — Footer Template (Root Level)
?>
<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
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
                </a>Vueports<span>.</span></div>
        <p>Custom software, data engineering, and AI agents for modern teams. Built in South Africa, serving the world.</p>
        <div class="footer-social">
          <a href="https://github.com/byprojecthub" target="_blank" rel="noopener" aria-label="GitHub">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
          </a>
          <a href="https://www.linkedin.com/in/njabulo-dlamini-58b66a268/" target="_blank" rel="noopener" aria-label="LinkedIn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
          </a>
          <a href="https://x.com/Colourerr" target="_blank" rel="noopener" aria-label="Twitter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
          </a>
        </div>
      </div>

      <div class="footer-column">
        <h4>Services</h4>
        <ul>
          <li><a href="/services.php">Custom Software</a></li>
          <li><a href="/services.php">Data Engineering</a></li>
          <li><a href="/services.php">AI Agents</a></li>
          <li><a href="/services.php">Cloud & DevOps</a></li>
          <li><a href="/services.php">Consulting</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h4>Company</h4>
        <ul>
          <li><a href="/about.php">About Us</a></li>
          <li><a href="/portfolio.php">Portfolio</a></li>
          <li><a href="/pricing.php">Pricing</a></li>
          <li><a href="/contact.php">Contact</a></li>
        </ul>
      </div>

      <div class="footer-column">
        <h4>Resources</h4>
        <ul>
          <li><a href="/consultation.php">Free Consultation</a></li>
          <li><a href="/calculator.php">Price Calculator</a></li>
          <li><a href="/booking.php">Book a Meeting</a></li>
          <li><a href="/login.php">Client Portal</a></li>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Vueports Solutions. All rights reserved.</p>
      <div class="footer-bottom-links">
        <a href="/legal/privacy.php">Privacy Policy</a>
        <a href="/legal/terms.php">Terms of Service</a>
      </div>
    </div>
  </div>
</footer>

<!-- Scroll Reveal -->
<script>
const revealEls = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
revealEls.forEach(el => revealObserver.observe(el));

// Mobile nav toggle
function toggleMobileNav() {
  document.getElementById('mobileNav').classList.toggle('open');
  document.body.style.overflow = document.getElementById('mobileNav').classList.contains('open') ? 'hidden' : '';
}

// Navbar scroll effect
window.addEventListener('scroll', () => {
  const nav = document.getElementById('navbar');
  if (window.scrollY > 50) {
    nav.style.boxShadow = 'var(--shadow-md)';
  } else {
    nav.style.boxShadow = 'none';
  }
});
</script>

</body>
</html>
