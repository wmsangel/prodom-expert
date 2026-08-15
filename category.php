<?php
/**
 * category.php — Страница категории с пагинацией
 * GET параметры: ?cat=slug  &  ?page=N
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

$allArticlesMeta = domexpert_all_articles_meta();
$validCategories = domexpert_categories();

$catSlug = isset($_GET['cat']) ? preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['cat'])) : '';

if (empty($catSlug) || !array_key_exists($catSlug, $validCategories)) {
  http_response_code(404);
  $catInfo  = ['label' => 'Категория не найдена', 'desc' => '', 'icon' => '🏠'];
  $notFound = true;
} else {
  $catInfo  = $validCategories[$catSlug];
  $notFound = false;
}


// Фильтрация и проверка файлов
$articles = [];
if (!$notFound) {
  foreach ($allArticlesMeta as $slug => $meta) {
    if ($meta['cat'] !== $catSlug) continue;
    $filePath = domexpert_article_html_path($slug, $catSlug);
    $articles[] = array_merge($meta, [
      'slug'     => $slug,
      'exists'   => is_file($filePath),
      'catLabel' => $catInfo['label'],
      'catIcon'  => domexpert_category_icon($catSlug),
    ]);
  }
  usort($articles, static function ($a, $b) {
    $ta = domexpert_article_date_ts($a['date'] ?? '');
    $tb = domexpert_article_date_ts($b['date'] ?? '');
    if ($tb !== $ta) {
      return $tb <=> $ta;
    }
    return strcmp($a['title'] ?? '', $b['title'] ?? '');
  });
}

// Пагинация
$perPage      = 8;
$totalItems   = count($articles);
$totalPages   = max(1, (int) ceil($totalItems / $perPage));
$currentPage  = isset($_GET['page']) ? max(1, min($totalPages, (int) $_GET['page'])) : 1;
$offset       = ($currentPage - 1) * $perPage;
$pageArticles = array_slice($articles, $offset, $perPage);

// SEO
$pageSuffix = $currentPage > 1 ? " — страница {$currentPage}" : '';
$pageTitle  = !$notFound
  ? $catInfo['label'] . ' — статьи и советы' . $pageSuffix . ' | ДомЭксперт'
  : 'Категория не найдена | ДомЭксперт';
$pageDesc   = !$notFound ? $catInfo['desc'] : 'Страница не найдена.';
$pageUrl    = du_category_url($catSlug, $currentPage);
$ogTitle    = $pageTitle;
$ogDesc     = $pageDesc;
$ogImage    = SITE_CANONICAL . '/assets/img/og-default.jpg';

if ($notFound) {
  $robotsMeta = 'noindex, follow';
} else {
  $breadcrumbJsonLd = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => SITE_CANONICAL . '/'],
      ['@type' => 'ListItem', 'position' => 2, 'name' => $catInfo['label'], 'item' => $pageUrl],
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
      'name'            => $catInfo['label'] . ' — статьи',
      'description'     => $catInfo['desc'],
      'numberOfItems'   => count($listItems),
      'itemListElement' => $listItems,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  }
}

include __DIR__ . '/includes/header.php';
?>

<div class="category-header">
  <div class="container">
    <nav aria-label="Навигационная цепочка" style="margin-bottom: 16px;">
      <ol style="display:flex; gap:8px; align-items:center; font-size:0.85rem; list-style:none;">
        <li><a href="/" style="color: rgba(255,255,255,0.6);">Главная</a></li>
        <li style="color: rgba(255,255,255,0.3);">›</li>
        <li style="color: rgba(255,255,255,0.9);"><?= htmlspecialchars($catInfo['label'], ENT_QUOTES, 'UTF-8') ?></li>
      </ol>
    </nav>
    <span style="font-size: 2.5rem; display:block; margin-bottom: 12px;"><?= $catInfo['icon'] ?></span>
    <h1><?= htmlspecialchars($catInfo['label'], ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($catInfo['desc']): ?>
    <p style="max-width: 650px; margin-top: 12px;"><?= htmlspecialchars($catInfo['desc'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if (!$notFound && $totalItems > 0): ?>
    <p style="margin-top: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.5);">
      <?= $totalItems ?> <?= $totalItems === 1 ? 'статья' : ($totalItems < 5 ? 'статьи' : 'статей') ?>
    </p>
    <?php endif; ?>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">
      <main id="main-content" role="main">

        <?php if ($notFound): ?>
          <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>Категория не найдена</h3>
            <p>Запрашиваемая категория не существует. Попробуйте выбрать раздел из меню.</p>
            <br><a href="/" class="btn btn-primary" style="display:inline-flex;">← На главную</a>
          </div>

        <?php elseif (empty($articles)): ?>
          <div class="empty-state">
            <div class="icon"><?= $catInfo['icon'] ?></div>
            <h3>Статьи скоро появятся</h3>
            <p>В этом разделе пока нет опубликованных статей. Загляните позже.</p>
            <br><a href="/" class="btn btn-primary" style="display:inline-flex;">← На главную</a>
          </div>

        <?php else: ?>
          <div class="section-header">
            <h2 class="section-title">
              <?= $currentPage > 1 ? 'Страница ' . $currentPage : 'Все статьи раздела' ?>
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
                <div class="list-item-cat"><?= htmlspecialchars($article['catLabel'], ENT_QUOTES, 'UTF-8') ?></div>
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
          <nav aria-label="Навигация по страницам" style="display:flex;justify-content:center;gap:8px;margin-top:8px;flex-wrap:wrap;">
            <?php if ($currentPage > 1): ?>
            <a href="/category/<?= $catSlug ?>/page/<?= $currentPage - 1 ?>/" aria-label="Предыдущая страница" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:var(--white);color:var(--text);border:1px solid var(--border);font-size:1.1rem;text-decoration:none;transition:all var(--transition);">‹</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/category/<?= $catSlug ?>/page/<?= $p ?>/" <?= $p === $currentPage ? 'aria-current="page"' : '' ?> style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:<?= $p === $currentPage ? 'var(--brick)' : 'var(--white)' ?>;color:<?= $p === $currentPage ? 'var(--white)' : 'var(--text)' ?>;border:1px solid <?= $p === $currentPage ? 'var(--brick)' : 'var(--border)' ?>;font-size:0.9rem;font-weight:<?= $p === $currentPage ? '600' : '400' ?>;text-decoration:none;"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
            <a href="/category/<?= $catSlug ?>/page/<?= $currentPage + 1 ?>/" aria-label="Следующая страница" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:var(--radius);background:var(--white);color:var(--text);border:1px solid var(--border);font-size:1.1rem;text-decoration:none;">›</a>
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
