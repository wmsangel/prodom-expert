<?php
/**
 * index.php — Главная страница
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

$pageTitle = 'ДомЭксперт — Всё о ремонте и обустройстве дома';
$pageDesc  = 'Практические советы по ремонту квартиры, выбору окон, сантехнике, электрике и дизайну интерьера. Сделайте ваш дом уютным вместе с ДомЭксперт.';
$pageUrl   = SITE_CANONICAL . '/';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

// Schema.org WebPage для главной
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
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

include __DIR__ . '/includes/header.php';

// Карточки-статьи для главной: строим из общего реестра мета и сортируем по дате (свежие сверху)
$homeCatMeta = [
  'remont'     => ['label' => 'Ремонт',       'icon' => '🔨'],
  'okna'       => ['label' => 'Окна и двери', 'icon' => '🪟'],
  'santehnika' => ['label' => 'Сантехника',   'icon' => '🚿'],
  'elektrika'  => ['label' => 'Электрика',     'icon' => '💡'],
  'interer'    => ['label' => 'Интерьер',      'icon' => '🛋️'],
  'sovety'     => ['label' => 'Советы',        'icon' => '📋'],
];

$featuredArticles = [];
foreach (domexpert_all_articles_meta() as $slug => $meta) {
  $cat = $meta['cat'] ?? '';
  $featuredArticles[] = [
    'slug'     => $slug,
    'cat'      => $cat,
    'catLabel' => $homeCatMeta[$cat]['label'] ?? 'Статьи',
    'icon'     => $homeCatMeta[$cat]['icon'] ?? '🏠',
    'title'    => $meta['title'] ?? '',
    'desc'     => $meta['desc'] ?? '',
    'date'     => $meta['date'] ?? '',
    '_ts'      => domexpert_article_date_ts($meta['date'] ?? ''),
  ];
}

// Сортировка по дате (новые сверху), затем по заголовку — как в /category.php и /articles.php
usort($featuredArticles, static function ($a, $b) {
  if ($b['_ts'] !== $a['_ts']) {
    return $b['_ts'] <=> $a['_ts'];
  }
  return strcmp($a['title'], $b['title']);
});

// На главную — 12 самых свежих статей
$featuredArticles = array_slice($featuredArticles, 0, 12);
?>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <span class="hero-eyebrow">Ваш эксперт по ремонту</span>
      <h1>Всё о ремонте и<br><em>обустройстве дома</em></h1>
      <p class="hero-desc">
        Практические руководства, советы профессионалов и вдохновляющие идеи — чтобы ваш дом стал именно таким, каким вы его представляете.
      </p>
      <div class="hero-actions">
        <a href="/category/remont/" class="btn btn-primary">🔨 Статьи о ремонте</a>
        <a href="/category/interer/" class="btn btn-outline">🛋️ Идеи интерьера</a>
      </div>
    </div>
  </div>
</section>

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">

      <!-- Статьи -->
      <main role="main">

        <div class="section-header">
          <h2 class="section-title">Последние статьи</h2>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <a href="/articles.php" class="section-link">Полный архив</a>
            <a href="/search.php" class="section-link">Поиск →</a>
          </div>
        </div>

        <div class="articles-grid">
          <?php foreach ($featuredArticles as $article): ?>
          <?php $featCover = article_cover_web_path($article['slug']); ?>
          <article class="article-card">
            <div class="card-image">
              <?php if ($featCover): ?>
                <img src="<?= htmlspecialchars($featCover, ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>"
                     loading="lazy"
                     decoding="async">
              <?php else: ?>
                <span class="card-image-placeholder"><?= $article['icon'] ?></span>
              <?php endif; ?>
              <span class="card-category">
                <a href="/category/<?= htmlspecialchars($article['cat'], ENT_QUOTES, 'UTF-8') ?>/"
                   style="color:inherit; text-decoration:none;">
                  <?= htmlspecialchars($article['catLabel'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </span>
            </div>
            <div class="card-body">
              <h3 class="card-title">
                <a href="/article/<?= htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8') ?>/">
                  <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              </h3>
              <p class="card-desc"><?= htmlspecialchars($article['desc'], ENT_QUOTES, 'UTF-8') ?></p>
              <div class="card-footer">
                <span class="card-date">📅 <?= $article['date'] ?></span>
                <a href="/article/<?= htmlspecialchars($article['slug'], ENT_QUOTES, 'UTF-8') ?>/" class="card-link">Читать</a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>


        <!-- Секция категорий -->
        <div class="section-header" style="margin-top: 10px;">
          <h2 class="section-title">Разделы сайта</h2>
        </div>

        <div class="articles-grid">
          <?php
          $articlesDirIdx = __DIR__ . '/articles/';
          $catArticleCounts = [];
          foreach (['remont', 'okna', 'santehnika', 'elektrika', 'interer', 'sovety'] as $_cs) {
            $_dir = $articlesDirIdx . $_cs . '/';
            $catArticleCounts[$_cs] = is_dir($_dir) ? count(glob($_dir . '*.html')) : 0;
          }
          $categories = [
            ['slug' => 'remont',     'label' => 'Ремонт',       'icon' => '🔨', 'desc' => 'Отделка, полы, потолки, стены — всё о капитальном и косметическом ремонте'],
            ['slug' => 'okna',       'label' => 'Окна и двери', 'icon' => '🪟', 'desc' => 'Как выбрать, установить и ухаживать за окнами, дверями и балконами'],
            ['slug' => 'santehnika', 'label' => 'Сантехника',   'icon' => '🚿', 'desc' => 'Трубы, смесители, ванны, унитазы — монтаж и ремонт своими руками'],
            ['slug' => 'elektrika',  'label' => 'Электрика',    'icon' => '💡', 'desc' => 'Безопасная проводка, розетки, выключатели и освещение в доме'],
            ['slug' => 'interer',    'label' => 'Интерьер',     'icon' => '🛋️', 'desc' => 'Дизайнерские решения, тренды, советы по стилю и зонированию'],
            ['slug' => 'sovety',     'label' => 'Советы',       'icon' => '📋', 'desc' => 'Практические лайфхаки и общие рекомендации для домашних мастеров'],
          ];
          foreach ($categories as $cat): ?>
          <a href="/category/<?= $cat['slug'] ?>/" class="article-card" style="text-decoration:none; color:inherit;">
            <div class="card-image" style="aspect-ratio:16/8;">
              <span class="card-image-placeholder" style="font-size: 3.5rem;"><?= $cat['icon'] ?></span>
            </div>
            <div class="card-body">
              <h3 class="card-title"><?= htmlspecialchars($cat['label'], ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="card-desc"><?= htmlspecialchars($cat['desc'], ENT_QUOTES, 'UTF-8') ?></p>
              <div class="card-footer">
                <span class="card-date"><?= (int) $catArticleCounts[$cat['slug']] ?> <?= (int) $catArticleCounts[$cat['slug']] === 1 ? 'статья' : ((int) $catArticleCounts[$cat['slug']] < 5 ? 'статьи' : 'статей') ?></span>
                <span class="card-link">Перейти в раздел</span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>

      </main>

      <!-- Сайдбар -->
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
