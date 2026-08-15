<?php
/**
 * articles.php — Архив всех статей с пагинацией (внутренние ссылки на каждый URL для обхода поисковиками)
 * GET: ?page=N
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

$allArticlesMeta = domexpert_all_articles_meta();

$articles = [];
foreach ($allArticlesMeta as $slug => $meta) {
  $path = domexpert_article_html_path($slug, $meta['cat']);
  $articles[] = domexpert_expand_article_meta($slug, $meta) + [
    'exists'  => is_file($path),
    'catIcon' => domexpert_category_icon($meta['cat']),
  ];
}

usort($articles, static function ($a, $b) {
  $ta = domexpert_article_date_ts($a['date'] ?? '');
  $tb = domexpert_article_date_ts($b['date'] ?? '');
  if ($tb !== $ta) {
    return $tb <=> $ta;
  }
  return strcmp($a['title'] ?? '', $b['title'] ?? '');
});

$perPage      = 12;
$totalItems   = count($articles);
$totalPages   = max(1, (int) ceil($totalItems / $perPage));
$currentPage  = isset($_GET['page']) ? max(1, min($totalPages, (int) $_GET['page'])) : 1;
$offset       = ($currentPage - 1) * $perPage;
$pageArticles = array_slice($articles, $offset, $perPage);

$pageSuffix = $currentPage > 1 ? ' — страница ' . $currentPage : '';
$pageTitle  = 'Все статьи' . $pageSuffix . ' | ДомЭксперт';
$pageDesc   = 'Полный архив материалов ДомЭксперт: ремонт, окна и двери, сантехника, электрика, интерьер и практические советы. Пагинация по дате — удобный обход для людей и поисковых роботов.';
$pageUrl    = SITE_CANONICAL . '/articles.php' . ($currentPage > 1 ? '?page=' . $currentPage : '');
$ogTitle    = $pageTitle;
$ogDesc     = $pageDesc;

// Страницы пагинации — обход и передача веса, но не индексация (как в category.php)
if ($currentPage > 1) {
  $robotsMeta = 'noindex, follow';
}

$articleJsonLd = json_encode([
  '@context'   => 'https://schema.org',
  '@type'      => 'WebPage',
  'name'       => $pageTitle,
  'description'=> $pageDesc,
  'url'        => $pageUrl,
  'inLanguage' => 'ru-RU',
  'breadcrumb' => [
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => SITE_CANONICAL . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => 'Все статьи', 'item' => $pageUrl],
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Все статьи', 'item' => $pageUrl],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$listPos   = $offset + 1;
$listItems = [];
foreach ($pageArticles as $pa) {
  if (empty($pa['exists'])) {
    continue;
  }
  $listItems[] = [
    '@type'    => 'ListItem',
    'position' => $listPos++,
    'url'      => du_article_url($pa['slug']),
    'name'     => $pa['title'],
  ];
}
if ($listItems !== []) {
  $listingJsonLd = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => 'Все статьи ДомЭксперт',
    'description'     => $pageDesc,
    'numberOfItems'   => count($listItems),
    'itemListElement' => $listItems,
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

include __DIR__ . '/includes/header.php';
?>

<div class="category-header">
  <div class="container">
    <nav aria-label="Навигационная цепочка" style="margin-bottom: 16px;">
      <ol style="display:flex; gap:8px; align-items:center; font-size:0.85rem; list-style:none;">
        <li><a href="/" style="color: rgba(255,255,255,0.6);">Главная</a></li>
        <li style="color: rgba(255,255,255,0.3);">›</li>
        <li style="color: rgba(255,255,255,0.9);">Все статьи</li>
      </ol>
    </nav>
    <span style="font-size: 2.5rem; display:block; margin-bottom: 12px;">📚</span>
    <h1>Все статьи</h1>
    <p style="max-width: 720px; margin-top: 12px;">Архив материалов по категориям с датой публикации. Каждая опубликованная статья имеет здесь прямую ссылку — это упрощает навигацию и первичный обход сайта поисковыми системами.</p>
    <?php if ($totalItems > 0): ?>
    <p style="margin-top: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
      В каталоге <?= $totalItems ?> <?= $totalItems === 1 ? 'запись' : ($totalItems < 5 ? 'записи' : 'записей') ?> (включая будущие без файла)
    </p>
    <?php endif; ?>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">
      <main id="main-content" role="main">

        <?php if (empty($articles)): ?>
          <div class="empty-state">
            <div class="icon">📄</div>
            <h3>Статьи скоро появятся</h3>
            <p>Пока список пуст.</p>
            <br><a href="/" class="btn btn-primary" style="display:inline-flex;">← На главную</a>
          </div>

        <?php else: ?>
          <div class="section-header">
            <h2 class="section-title">
              <?= $currentPage > 1 ? 'Страница ' . $currentPage : 'Каталог материалов' ?>
            </h2>
            <span style="font-size: 0.88rem; color: var(--text-muted);">
              <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalItems) ?> из <?= $totalItems ?>
            </span>
          </div>

          <div class="article-list">
            <?php foreach ($pageArticles as $article): ?>
            <?php $listCover = article_cover_web_path($article['slug']); ?>
            <article class="article-list-item">
              <div class="list-item-image">
                <?php if ($listCover): ?>
                  <img src="<?= htmlspecialchars($listCover, ENT_QUOTES, 'UTF-8') ?>"
                       alt="<?= htmlspecialchars($article['title'] . ' — превью статьи', ENT_QUOTES, 'UTF-8') ?>"
                       loading="lazy"
                       decoding="async">
                <?php else: ?>
                  <?= $article['catIcon'] ?>
                <?php endif; ?>
              </div>
              <div class="list-item-body">
                <div class="list-item-cat">
                  <a href="/category/<?= htmlspecialchars($article['cat'], ENT_QUOTES, 'UTF-8') ?>/" style="color:inherit;text-decoration:none;">
                    <?= htmlspecialchars($article['catLabel'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </div>
                <h2 class="list-item-title">
                  <?php if ($article['exists']): ?>
                    <a href="/article/<?= htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8') ?>/">
                      <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  <?php else: ?>
                    <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
                    <span style="font-size:0.7rem;color:var(--text-muted);font-family:var(--font-body);font-weight:400;"> (скоро)</span>
                  <?php endif; ?>
                </h2>
                <p class="list-item-desc"><?= htmlspecialchars($article['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="list-item-footer">
                  <span>📅 <?= htmlspecialchars($article['date'], ENT_QUOTES, 'UTF-8') ?> &nbsp;·&nbsp; ⏱️ <?= htmlspecialchars($article['readTime'], ENT_QUOTES, 'UTF-8') ?></span>
                  <?php if ($article['exists']): ?>
                    <a href="/article/<?= htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8') ?>/" class="card-link">Читать</a>
                  <?php endif; ?>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>


          <?php if ($totalPages > 1): ?>
          <nav aria-label="Навигация по страницам архива" style="display:flex;justify-content:center;gap:8px;margin-top:8px;flex-wrap:wrap;">
            <?php if ($currentPage > 1): ?>
            <a href="/articles.php<?= $currentPage > 2 ? '?page=' . ($currentPage - 1) : '' ?>" aria-label="Предыдущая страница" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:var(--white);color:var(--text);border:1px solid var(--border);font-size:1.1rem;text-decoration:none;transition:all var(--transition);">‹</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/articles.php<?= $p > 1 ? '?page=' . $p : '' ?>" <?= $p === $currentPage ? 'aria-current="page"' : '' ?> style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:<?= $p === $currentPage ? 'var(--brick)' : 'var(--white)' ?>;color:<?= $p === $currentPage ? 'var(--white)' : 'var(--text)' ?>;border:1px solid <?= $p === $currentPage ? 'var(--brick)' : 'var(--border)' ?>;font-size:0.9rem;font-weight:<?= $p === $currentPage ? '600' : '400' ?>;text-decoration:none;"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="/articles.php?page=<?= $currentPage + 1 ?>" aria-label="Следующая страница" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:var(--white);color:var(--text);border:1px solid var(--border);font-size:1.1rem;text-decoration:none;">›</a>
            <?php endif; ?>
          </nav>
          <?php endif; ?>

        <?php endif; ?>

      </main>

      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
