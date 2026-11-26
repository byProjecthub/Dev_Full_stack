<?php
declare(strict_types=1);
// cron/generate-sitemap.php — Dynamic Sitemap Generator

require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['key'])) {
    http_response_code(403);
    exit('Forbidden');
}

$db = db();
$baseUrl = rtrim(getSetting('app_url', 'https://vueports.co.za'), '/');

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>');

// Helper to add URL
function addUrl(SimpleXMLElement $xml, string $loc, string $lastmod, string $freq, float $priority): void {
    $url = $xml->addChild('url');
    $url->addChild('loc', htmlspecialchars($loc));
    $url->addChild('lastmod', $lastmod);
    $url->addChild('changefreq', $freq);
    $url->addChild('priority', number_format($priority, 1));
}

// ── STATIC PAGES ──
$static = [
    ['', 'weekly', 1.0],
    ['about.php', 'monthly', 0.7],
    ['services.php', 'weekly', 0.9],
    ['calculator.php', 'monthly', 0.9],
    ['consultation.php', 'monthly', 0.8],
    ['booking.php', 'monthly', 0.8],
    ['portfolio.php', 'weekly', 0.8],
    ['contact.php', 'monthly', 0.7],
    ['pay.php', 'monthly', 0.6],
    ['client/login.php', 'monthly', 0.3],
];
foreach ($static as [$path, $freq, $pri]) {
    addUrl($xml, "$baseUrl/$path", date('Y-m-d'), $freq, $pri);
}

// ── DYNAMIC SERVICES ──
if ($db) {
    $services = $db->query("SELECT slug, updated_at FROM services WHERE status = 'active' ORDER BY updated_at DESC")->fetchAll();
    foreach ($services as $s) {
        addUrl($xml, "$baseUrl/service-detail.php?slug=" . urlencode($s['slug']), date('Y-m-d', strtotime($s['updated_at'])), 'monthly', 0.8);
    }
    
    // ── BLOG POSTS ──
    $posts = $db->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY updated_at DESC")->fetchAll();
    foreach ($posts as $p) {
        addUrl($xml, "$baseUrl/blog/post.php?slug=" . urlencode($p['slug']), date('Y-m-d', strtotime($p['updated_at'])), 'monthly', 0.6);
    }
    
    // ── PORTFOLIO ITEMS ──
    $portfolio = $db->query("SELECT slug, updated_at FROM portfolio_items WHERE status = 'active' ORDER BY updated_at DESC")->fetchAll();
    foreach ($portfolio as $item) {
        addUrl($xml, "$baseUrl/portfolio.php?item=" . urlencode($item['slug']), date('Y-m-d', strtotime($item['updated_at'])), 'monthly', 0.6);
    }
}

// ── WRITE FILE ──
$output = $xml->asXML();
$sitemapPath = '../sitemap.xml';
if (file_put_contents($sitemapPath, $output, LOCK_EX)) {
    echo "[" . date('c') . "] Sitemap generated: $sitemapPath (" . count($xml->url) . " URLs)\n";
} else {
    echo "[" . date('c') . "] ERROR: Failed to write sitemap\n";
    exit(1);
}

// ── PING SEARCH ENGINES ──
$engines = [
    "https://www.google.com/ping?sitemap=" . urlencode("$baseUrl/sitemap.xml"),
    "https://www.bing.com/ping?sitemap=" . urlencode("$baseUrl/sitemap.xml"),
];
foreach ($engines as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "[" . date('c') . "] Pinged search engine: $url (HTTP $httpCode)\n";
}