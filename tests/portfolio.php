<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Run against the development database; every fixture is rolled back.
chdir(dirname(__DIR__));
require_once __DIR__ . '/../includes/functions.php';

function portfolioCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$database = db();
portfolioCheck($database instanceof PDO, 'A development database connection is required.');
$originalCount = (int)$database->query('SELECT COUNT(*) FROM portfolio_items')->fetchColumn();
$prefix = 'portfolio-regression-' . bin2hex(random_bytes(8));
$category = $prefix . '-category';
$ids = [];
$database->beginTransaction();

try {
    foreach (['main', 'same-category', 'other-category', 'draft'] as $name) {
        $record = [
            'title' => $prefix . '-' . $name,
            'slug' => $prefix . '-' . $name,
            'description' => 'Portfolio regression fixture.',
            'service_type' => $name === 'other-category' ? $prefix . '-other' : $category,
            'technologies' => ['PHP', 'MySQL'],
            'image' => 'Finlytics.png',
            'gallery' => ['images/Finlytics1.png'],
            'project_url' => 'https://example.invalid/' . $prefix,
            'status' => $name === 'draft' ? 'draft' : 'active',
            'sort_order' => 0,
        ];
        if ($name === 'same-category') {
            // Existing view field names remain accepted by the write helper.
            $record['tech_stack'] = $record['technologies'];
            $record['live_url'] = $record['project_url'];
            unset($record['technologies'], $record['project_url']);
        }
        portfolioCheck(savePortfolioItem($record), 'Could not save a portfolio fixture.');
        $ids[$name] = (int)$database->lastInsertId();
    }

    $main = getPortfolioItemBySlug($prefix . '-main');
    portfolioCheck($main !== null && (int)$main['id'] === $ids['main'], 'Published item lookup failed.');
    portfolioCheck($main['technologies'] === ['PHP', 'MySQL']
        && $main['tech_stack'] === $main['technologies'], 'Technology JSON or view mapping failed.');
    portfolioCheck($main['live_url'] === $main['project_url'], 'Project link mapping failed.');
    portfolioCheck($main['image'] === 'images/Finlytics.png'
        && $main['gallery'] === ['images/Finlytics1.png'], 'Image paths or gallery JSON were corrupted.');
    portfolioCheck(getPortfolioItemBySlug($prefix . '-draft') === null, 'Draft item was published.');
    portfolioCheck(getPortfolioItemBySlug($prefix . '-missing') === null, 'Unknown item was returned.');

    $published = getPortfolioItems($originalCount + 10);
    $publishedIds = array_map('intval', array_column($published, 'id'));
    portfolioCheck(in_array($ids['main'], $publishedIds, true)
        && !in_array($ids['draft'], $publishedIds, true), 'Published listing does not respect status.');
    portfolioCheck(count(getPortfolioItems(1)) === 1 && getPortfolioItems(0) === [], 'Listing limit failed.');
    portfolioCheck(in_array($category, getPortfolioCategories(), true), 'Published category was omitted.');

    $related = getRelatedProjects($prefix . '-main', 2);
    portfolioCheck(count($related) === 2 && (int)$related[0]['id'] === $ids['same-category'],
        'Related items did not prefer the same service type.');
    $relatedIds = array_map('intval', array_column($related, 'id'));
    portfolioCheck(!in_array($ids['main'], $relatedIds, true)
        && !in_array($ids['draft'], $relatedIds, true), 'Related items included current or unpublished content.');

    portfolioCheck(savePortfolioItem(['id' => $ids['main'], 'title' => $prefix . '-updated',
        'technologies' => ['PHP', 'Redis']]), 'Portfolio update failed.');
    $main = getPortfolioItemBySlug($prefix . '-main');
    portfolioCheck($main['title'] === $prefix . '-updated' && $main['tech_stack'] === ['PHP', 'Redis'],
        'Portfolio update did not persist canonical fields.');
    portfolioCheck(!savePortfolioItem(['id' => $ids['main'], 'unknown_column' => 'invalid']),
        'An unsupported database column was accepted.');

    $_GET['slug'] = $prefix . '-main';
    $_SERVER['REQUEST_URI'] = '/portfolio-detail.php?slug=' . $_GET['slug'];
    $_SERVER['PHP_SELF'] = '/portfolio-detail.php';
    ob_start();
    try {
        require __DIR__ . '/../portfolio-detail.php';
        $page = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    portfolioCheck(str_contains($page, $prefix . '-updated') && str_contains($page, 'Redis')
        && str_contains($page, 'https://example.invalid/' . $prefix)
        && str_contains($page, 'images/Finlytics.png'), 'Portfolio detail did not render database fields.');

    portfolioCheck(deletePortfolioItem($ids['main']), 'Portfolio archival failed.');
    portfolioCheck(getPortfolioItemBySlug($prefix . '-main') === null, 'Archived item remains published.');
    $archivedIds = array_map('intval', array_column(getPortfolioItems($originalCount + 10, 'archived'), 'id'));
    portfolioCheck(in_array($ids['main'], $archivedIds, true), 'Archived content was deleted or lost.');
} finally {
    $database->rollBack();
}

portfolioCheck((int)$database->query('SELECT COUNT(*) FROM portfolio_items')->fetchColumn() === $originalCount,
    'Portfolio fixture cleanup failed.');
echo "PASS portfolio lookup, listing, related items, JSON fields, rendering, update and archival; fixtures rolled back.\n";
