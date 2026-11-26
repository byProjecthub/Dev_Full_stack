<?php
/** Shared, session-aware public navigation. All destinations use the mount-aware URL helper. */
$vpNavGroups = [
  'solutions' => ['label' => 'Solutions', 'eyebrow' => 'WHAT WE BUILD', 'title' => 'Technology that moves you forward.', 'links' => [
    ['Software engineering', 'Custom applications and platforms built around your business.', 'services.php#software', 'software', 'CORE EXPERTISE'],
    ['Data engineering', 'Connected data, clear reporting and better informed decisions.', 'services.php#data', 'data', 'DATA & ANALYTICS'],
    ['AI & automation', 'Intelligent agents and workflows that give your team time back.', 'services.php#ai', 'ai', 'APPLIED INTELLIGENCE'],
    ['All services', 'Explore our capabilities and find the right place to start.', 'services.php', 'all', 'BUILT FOR YOUR GOALS']
  ], 'footer' => ['Not sure where to start? Let’s scope it together.', 'Book a consultation', 'consultation.php']],
  'business' => ['label' => 'For businesses', 'eyebrow' => 'FROM CHALLENGE TO WORKING SYSTEM', 'title' => 'A partner for your next step.', 'links' => [
    ['Selected work', 'Explore projects, platforms and real business use cases.', 'portfolio.php', 'work', 'OUR PORTFOLIO'],
    ['Our approach', 'See how we plan, build, review and deliver together.', 'index.php#approach', 'process', 'HOW WE WORK'],
    ['Book a meeting', 'Choose a time to talk through your requirements.', 'booking.php', 'calendar', 'LET’S CONNECT'],
    [$isLoggedIn ? 'Your client portal' : 'Client portal', 'Access your projects, invoices and account in one place.', $isLoggedIn ? 'client/dashboard.php' : 'client/login.php', 'portal', 'YOUR WORKSPACE']
  ], 'footer' => ['A clear brief is the start of a better project.', 'Discuss your project', 'consultation.php']],
  'resources' => ['label' => 'Resources', 'eyebrow' => 'TOOLS & INSIGHTS', 'title' => 'Make your next decision a clear one.', 'links' => [
    ['Insights', 'Ideas and updates from the Vueports team.', 'blog/index.php', 'insights', 'READ & EXPLORE'],
    ['Project calculator', 'Explore an indicative estimate for your project.', 'calculator.php', 'calculator', 'PLAN YOUR INVESTMENT'],
    ['Questions answered', 'Find answers about packages, delivery and support.', 'pricing.php#faq', 'help', 'FREQUENTLY ASKED']
  ], 'extra' => [['Privacy policy', 'legal/privacy.php'], ['Terms of service', 'legal/terms.php'], ['Cookie policy', 'legal/cookies.php'], ['POPIA compliance', 'legal/popia-compliance.php']], 'footer' => ['Need an answer specific to your business?', 'Get in touch', 'contact.php']],
  'company' => ['label' => 'Company', 'eyebrow' => 'MEET VUEPORTS', 'title' => 'Engineered here. Thinking ahead.', 'links' => [
    ['About us', 'Meet your South African technology partner.', 'about.php', 'company', 'WHO WE ARE'],
    ['Contact', 'Tell us what you need and start a conversation.', 'contact.php', 'contact', 'LET’S TALK'],
    ['Free consultation', 'Turn your ideas into a clear direction for delivery.', 'consultation.php', 'process', 'YOUR NEXT MOVE']
  ], 'extra' => $isLoggedIn ? [['Dashboard', 'client/dashboard.php'], ['My projects', 'client/projects.php'], ['Invoices & billing', 'client/invoices.php'], ['Sign out', 'client/logout.php']] : [['Client sign-in', 'client/login.php'], ['Create an account', 'client/register.php']], 'footer' => ['Built in South Africa. Designed for your business.', 'Explore our work', 'portfolio.php']]
];
$vpNavPath = parse_url($_SERVER['SCRIPT_NAME'] ?? '/index.php', PHP_URL_PATH) ?: '/index.php';
$vpNavCurrent = static function(string $route) use ($vpNavPath): bool {
    return !str_contains($route, '#') && $vpNavPath === parse_url(vueports_url($route), PHP_URL_PATH);
};
$vpNavEscape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$vpNavChevron = '<svg width="12" height="12" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>';
$vpNavArrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>';
?>
<div class="vp-navigation-root" data-vp-navigation>
<nav class="vp-site-nav" id="navbar" aria-label="Main navigation">
  <div class="vp-nav-bar">
    <a href="<?= $vpUrl('index.php') ?>" class="vp-brand" aria-label="Vueports Solutions home"><?= vueports_brand() ?></a>
    <div class="vp-nav-desktop">
      <?php foreach ($vpNavGroups as $key => $group): ?>
      <?php $vpGroupActive = (bool) array_filter($group['links'], static fn(array $item): bool => $vpNavCurrent($item[2])); ?>
      <?php if ($key === 'resources'): ?><a class="vp-nav-item<?= $vpNavCurrent('pricing.php') ? ' active' : '' ?>" href="<?= $vpUrl('pricing.php') ?>"<?= $vpNavCurrent('pricing.php') ? ' aria-current="page"' : '' ?>>Pricing</a><?php endif; ?>
      <button class="vp-nav-item vp-nav-trigger<?= $vpGroupActive ? ' active' : '' ?>" id="vpNavTrigger-<?= $key ?>" type="button" aria-expanded="false" aria-controls="vpNavPanel-<?= $key ?>" data-vp-menu="<?= $key ?>"><?= $group['label'] ?><?= $vpNavChevron ?></button>
      <?php endforeach; ?>
    </div>
    <div class="vp-nav-actions">
      <a class="vp-nav-signin" href="<?= $vpUrl($isLoggedIn ? 'client/dashboard.php' : 'client/login.php') ?>"><?= $isLoggedIn ? 'My account' : 'Sign in' ?></a>
      <a class="vp-nav-quote" href="<?= $vpUrl('consultation.php') ?>">Get a quote <?= $vpNavArrow ?></a>
      <button class="vp-nav-toggle" id="vpNavToggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="mobileNav"><span></span><span></span></button>
    </div>
  </div>
  <?php foreach ($vpNavGroups as $key => $group): ?>
  <section class="vp-mega-panel" id="vpNavPanel-<?= $key ?>" aria-labelledby="vpNavTrigger-<?= $key ?>" hidden>
    <div class="vp-mega-heading"><span><?= $group['eyebrow'] ?></span><p><?= $group['title'] ?></p></div>
    <div class="vp-mega-grid vp-mega-grid-<?= count($group['links']) ?>">
      <?php foreach ($group['links'] as $i => $item): ?>
      <a href="<?= $vpUrl($item[2]) ?>" class="vp-mega-card<?= $i === 0 ? ' vp-mega-featured' : '' ?>"<?= $vpNavCurrent($item[2]) ? ' aria-current="page"' : '' ?>>
        <span class="vp-mega-kicker"><?= $item[4] ?></span><h2><?= $item[0] ?></h2><p><?= $item[1] ?></p>
        <span class="vp-mega-card-bottom"><span class="vp-nav-art vp-nav-art-<?= $item[3] ?>" aria-hidden="true"><i></i><i></i><i></i></span><span class="vp-mega-arrow"><?= $vpNavArrow ?></span></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php if (!empty($group['extra'])): ?><div class="vp-mega-extra" aria-label="<?= $group['label'] ?> quick links"><?php foreach ($group['extra'] as $item): ?><a href="<?= $vpUrl($item[1]) ?>"<?= $vpNavCurrent($item[1]) ? ' aria-current="page"' : '' ?>><?= $item[0] ?><?= $vpNavArrow ?></a><?php endforeach; ?></div><?php endif; ?>
    <div class="vp-mega-footer"><span><?= $group['footer'][0] ?></span><a href="<?= $vpUrl($group['footer'][2]) ?>"><?= $group['footer'][1] ?> <?= $vpNavArrow ?></a></div>
  </section>
  <?php endforeach; ?>
</nav>
<div class="vp-mobile-navigation" id="mobileNav" role="dialog" aria-modal="true" aria-labelledby="vpMobileTitle" tabindex="-1" hidden inert>
  <div class="vp-mobile-head"><span id="vpMobileTitle">Explore Vueports</span><button class="vp-mobile-close" type="button" aria-label="Close navigation"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg></button></div>
  <nav aria-label="Mobile navigation" class="vp-mobile-links">
    <a class="vp-mobile-direct" href="<?= $vpUrl('index.php') ?>"<?= $vpNavCurrent('index.php') ? ' aria-current="page"' : '' ?>>Home <?= $vpNavArrow ?></a>
    <?php foreach ($vpNavGroups as $key => $group): ?>
    <?php if ($key === 'resources'): ?><a class="vp-mobile-direct" href="<?= $vpUrl('pricing.php') ?>"<?= $vpNavCurrent('pricing.php') ? ' aria-current="page"' : '' ?>>Pricing <?= $vpNavArrow ?></a><?php endif; ?>
    <details class="vp-mobile-group"><summary><?= $group['label'] ?><?= $vpNavChevron ?></summary><div class="vp-mobile-group-links">
      <?php foreach ($group['links'] as $item): ?><a href="<?= $vpUrl($item[2]) ?>"<?= $vpNavCurrent($item[2]) ? ' aria-current="page"' : '' ?>><span><?= $item[0] ?><small><?= $item[1] ?></small></span><?= $vpNavArrow ?></a><?php endforeach; ?>
      <?php foreach ($group['extra'] ?? [] as $item): ?><a href="<?= $vpUrl($item[1]) ?>"><?= $item[0] ?><?= $vpNavArrow ?></a><?php endforeach; ?>
    </div></details>
    <?php endforeach; ?>
  </nav>
  <div class="vp-mobile-actions"><a class="vp-nav-quote" href="<?= $vpUrl('consultation.php') ?>">Get a quote <?= $vpNavArrow ?></a><a href="<?= $vpUrl($isLoggedIn ? 'client/dashboard.php' : 'client/login.php') ?>"><?= $isLoggedIn ? 'My account' : 'Client sign-in' ?></a></div>
</div>
</div>
<!-- /Vueports navigation -->
