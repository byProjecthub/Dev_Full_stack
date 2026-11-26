<?php
require_once __DIR__ . '/frontend.php';
$vpUrl = static fn(string $path): string => htmlspecialchars(vueports_url($path), ENT_QUOTES, 'UTF-8');
?>
<?php if (in_array($currentPage ?? '', ['about','services','service-detail','portfolio','portfolio-detail','pricing'], true)): ?>
<section class="vp-cta-section"><div class="container"><div class="vp-cta-panel"><div><h2>What could your<br>business do next?</h2><p>Let’s turn the challenge into a clear project brief.</p></div><a class="btn btn-primary btn-lg" href="<?= $vpUrl('consultation.php') ?>">Start the conversation <span class="vp-cta-arrow" aria-hidden="true"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg></span></a></div></div></section>
<?php endif; ?>
<footer class="footer"><div class="container"><div class="footer-grid">
<div class="footer-brand"><a class="vp-brand" href="<?= $vpUrl('index.php') ?>" aria-label="Vueports Solutions home"><?= vueports_brand() ?></a><p>Custom software, data engineering and AI agents. Built in South Africa, serving the world.</p><div class="footer-social">
          <a href="https://github.com/byprojecthub" target="_blank" rel="noopener" aria-label="GitHub">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>
          </a>
          <a href="https://www.linkedin.com/in/njabulo-dlamini-58b66a268/" target="_blank" rel="noopener" aria-label="LinkedIn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
          </a>
          <a href="https://x.com/Colourerr" target="_blank" rel="noopener" aria-label="Twitter">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
          </a>
        </div></div>
<div class="footer-column"><h4>Expertise</h4><ul><li><a href="<?= $vpUrl('services.php') ?>">Custom software</a></li><li><a href="<?= $vpUrl('services.php#data') ?>">Data engineering</a></li><li><a href="<?= $vpUrl('services.php#ai') ?>">AI & automation</a></li><li><a href="<?= $vpUrl('services.php') ?>">Cloud & consulting</a></li></ul></div>
<div class="footer-column"><h4>Explore Vueports</h4><ul><li><a href="<?= $vpUrl('about.php') ?>">About us</a></li><li><a href="<?= $vpUrl('portfolio.php') ?>">Selected work</a></li><li><a href="<?= $vpUrl('pricing.php') ?>">Pricing</a></li><li><a href="<?= $vpUrl('contact.php') ?>">Contact</a></li><li><a href="<?= $vpUrl('blog/index.php') ?>">Insights</a></li></ul></div>
<div class="footer-column"><h4>Let’s work together</h4><ul><li><a href="<?= $vpUrl('consultation.php') ?>">Free consultation</a></li><li><a href="<?= $vpUrl('calculator.php') ?>">Project calculator</a></li><li><a href="<?= $vpUrl('booking.php') ?>">Book a meeting</a></li><li><a href="<?= $vpUrl('login.php') ?>">Client portal</a></li><?php if (!empty($isLoggedIn)): ?><li><a href="<?= $vpUrl('client/logout.php') ?>">Sign out</a></li><?php endif; ?></ul></div>
</div><div class="footer-bottom"><p>&copy; <?= date('Y') ?> Vueports Solutions. All rights reserved.</p><div class="footer-bottom-links"><a href="<?= $vpUrl('legal/privacy.php') ?>">Privacy policy</a><a href="<?= $vpUrl('legal/terms.php') ?>">Terms of service</a><a href="<?= $vpUrl('legal/cookies.php') ?>">Cookies</a></div></div></div></footer>
<script>
(() => {
  const elements = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('visible'); observer.unobserve(entry.target); } }), {threshold:0.08});
    elements.forEach(element => observer.observe(element));
  } else { elements.forEach(element => element.classList.add('visible')); }
  const flash = document.getElementById('flashMessage');
  if (flash) setTimeout(() => flash.remove(), 5000);
})();
</script>
</body></html>
