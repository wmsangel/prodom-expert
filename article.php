<?php
/**
 * article.php — Страница статьи
 * GET параметр: ?slug=nazvaniye-stati
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';
require_once __DIR__ . '/includes/article-toc.php';
require_once __DIR__ . '/includes/partner-ads.php';
require_once __DIR__ . '/includes/affiliate.php';

// === Безопасная обработка slug (защита от path traversal) ===
$rawSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

if (empty($slug)) {
  http_response_code(400);
  header('Location: /');
  exit;
}

// === Поиск файла статьи ===
// Статьи могут лежать как /articles/slug.html, так и /articles/cat/slug.html
$articleFile = null;
$articleDir  = __DIR__ . '/articles/';

// 1. Прямое расположение
$directPath = $articleDir . $slug . '.html';
if (file_exists($directPath)) {
  $articleFile = $directPath;
}

// 2. В подпапках категорий
if (!$articleFile) {
  foreach (array_keys(domexpert_categories()) as $cat) {
    $catPath = $articleDir . $cat . '/' . $slug . '.html';
    if (file_exists($catPath)) {
      $articleFile = $catPath;
      $articleCatSlug = $cat;
      break;
    }
  }
}

// === Мета-данные статьи ===
// Единый источник — includes/all-articles-meta.php. Здесь только подстановка
// заглушки для слага, которого нет в реестре (такая страница всё равно уйдёт в 404 ниже).
$meta = domexpert_article_meta($slug) ?? [
  'title'    => ucwords(str_replace('-', ' ', $slug)),
  'catSlug'  => isset($articleCatSlug) ? $articleCatSlug : 'sovety',
  'catLabel' => domexpert_category_label(isset($articleCatSlug) ? $articleCatSlug : 'sovety'),
  'date'     => date('d F Y'),
  'author'   => DOMEXPERT_DEFAULT_AUTHOR,
  'readTime' => '5 мин',
  'desc'     => 'Статья о ремонте и обустройстве дома на сайте ДомЭксперт.',
];

// === Нет файла статьи → честный 404 ===
// Иначе любой опечатанный или устаревший слаг отдаёт код 200 с пустым телом,
// и поисковик копит такие адреса как soft 404.
$articleMissing = !($articleFile && is_readable($articleFile));
if ($articleMissing) {
  http_response_code(404);
  $robotsMeta = 'noindex, follow';
}

// === SEO переменные ===
$siteUrl   = SITE_CANONICAL;
$pageTitle = $meta['title'] . ' | ДомЭксперт';
$pageDesc  = $meta['desc'];
$pageUrl   = du_article_url($slug);
$ogTitle   = $meta['title'];
$ogDesc    = $meta['desc'];
$articleCoverPath = article_cover_web_path($slug);
$ogImage   = $articleCoverPath
  ? SITE_CANONICAL . $articleCoverPath
  : SITE_CANONICAL . '/assets/img/og-default.jpg';
$ogImageWidth  = 1200;
$ogImageHeight = 630;
$preloadLcpImage = null;
$ogImageAlt      = $ogTitle;
if ($articleCoverPath) {
  $coverFs = article_cover_fs_path($slug);
  if ($coverFs && is_readable($coverFs)) {
    $gis = @getimagesize($coverFs);
    if (is_array($gis)) {
      $ogImageWidth  = (int) $gis[0];
      $ogImageHeight = (int) $gis[1];
    }
  }
  $preloadLcpImage = SITE_CANONICAL . $articleCoverPath;
  $ogImageAlt      = $meta['title'] . ' — иллюстрация к материалу на ДомЭксперт';
}
$isArticle = true;
$ogCatLabel       = $meta['catLabel'];

// Дата в ISO 8601 для Schema.org (день + русский месяц + год)
$ruMonths = ['января'=>'01','февраля'=>'02','марта'=>'03','апреля'=>'04','мая'=>'05','июня'=>'06',
             'июля'=>'07','августа'=>'08','сентября'=>'09','октября'=>'10','ноября'=>'11','декабря'=>'12'];
$dateParts = preg_split('/\s+/u', trim($meta['date']));
$isoDate = '2025-01-01';
if (count($dateParts) >= 3) {
  $day   = (int) $dateParts[0];
  $month = $ruMonths[$dateParts[1]] ?? '01';
  $year  = ctype_digit((string) $dateParts[2]) ? (int) $dateParts[2] : 2025;
  $isoDate = sprintf('%04d-%s-%02d', $year, $month, $day);
}
$articlePubDate = $isoDate;

// Похожие материалы — подбор по теме, см. domexpert_related_articles()
$relatedArticles = domexpert_related_articles($slug, 3);

// Соседи по категории — для последовательного чтения
$articleNeighbours = domexpert_adjacent_articles($slug);

// === JSON-LD: Article ===
$articleJsonLd = json_encode([
  '@context'         => 'https://schema.org',
  '@type'            => 'Article',
  'headline'         => $meta['title'],
  'description'      => $meta['desc'],
  'url'              => $pageUrl,
  'datePublished'    => $isoDate,
  'dateModified'     => $isoDate,
  // Автор: именной байлайн размечается как Person, редакционный — как Organization.
  // Раньше здесь всегда стоял Organization, из-за чего 96 статей с именами авторов
  // отдавали заведомо неверную разметку.
  'author'           => $meta['author'] === DOMEXPERT_DEFAULT_AUTHOR
    ? [
        '@type' => 'Organization',
        '@id'   => domexpert_org_id(),
        'name'  => $meta['author'],
        'url'   => $siteUrl . '/editorial.php',
      ]
    : [
        '@type'          => 'Person',
        'name'           => $meta['author'],
        'url'            => $siteUrl . '/editorial.php',
        'worksFor'       => ['@id' => domexpert_org_id()],
      ],
  'publisher' => [
    '@id' => domexpert_org_id(),
  ],
  'isPartOf' => [
    '@id' => domexpert_website_id(),
  ],
  'isAccessibleForFree' => true,
  'image' => [
    '@type'  => 'ImageObject',
    'url'    => $ogImage,
    'width'  => $ogImageWidth,
    'height' => $ogImageHeight,
    'caption'=> $meta['title'],
  ],
  'mainEntityOfPage' => [
    '@type'      => 'WebPage',
    '@id'        => $pageUrl . '#webpage',
    'url'        => $pageUrl,
    'name'       => $meta['title'],
    'isPartOf'   => ['@id' => domexpert_website_id()],
    'inLanguage' => 'ru-RU',
  ],
  'articleSection'   => $meta['catLabel'],
  'inLanguage'       => 'ru-RU',
  'timeRequired'     => 'PT' . (int) $meta['readTime'] . 'M',
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// === Текст статьи: читаем один раз, из него же берём FAQ и оглавление ===
$articleSource = ($articleFile && is_readable($articleFile))
  ? (string) file_get_contents($articleFile)
  : '';
$articleParsed = domexpert_article_toc($articleSource);
$articleHtml   = $articleParsed['html'];   // тот же текст, но с якорями у заголовков
$articleToc    = $articleParsed['toc'];

// === JSON-LD: FAQPage (из блока «Частые вопросы» статьи) ===
$articleFaq = $articleSource !== '' ? domexpert_extract_faq($articleSource) : [];
if (!empty($articleFaq)) {
  $faqJsonLd = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'inLanguage' => 'ru-RU',
    'mainEntity' => array_map(static function (array $qa): array {
      return [
        '@type'          => 'Question',
        'name'           => $qa['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']],
      ];
    }, $articleFaq),
  ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// === JSON-LD: BreadcrumbList ===
$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',                    'item' => $siteUrl . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => $meta['catLabel'],            'item' => du_category_url($meta['catSlug'])],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $meta['title'],               'item' => $pageUrl],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Передаём cat в меню для подсветки
$_GET['cat'] = $meta['catSlug'];

include __DIR__ . '/includes/header.php';
?>

<!-- BREADCRUMBS -->
<nav class="breadcrumbs" aria-label="Навигационная цепочка">
  <div class="container">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="/" itemprop="item"><span itemprop="name">Главная</span></a>
        <meta itemprop="position" content="1">
      </li>
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="/category/<?= htmlspecialchars($meta['catSlug'], ENT_QUOTES, 'UTF-8') ?>/" itemprop="item">
          <span itemprop="name"><?= htmlspecialchars($meta['catLabel'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <meta itemprop="position" content="2">
      </li>
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name"><?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?></span>
        <meta itemprop="position" content="3">
      </li>
    </ol>
  </div>
</nav>

<!-- ARTICLE HEADER -->
<div class="article-header">
  <div class="container">
    <a href="/category/<?= htmlspecialchars($meta['catSlug'], ENT_QUOTES, 'UTF-8') ?>/"
       class="article-category-badge">
      <?= htmlspecialchars($meta['catLabel'], ENT_QUOTES, 'UTF-8') ?>
    </a>
    <h1 class="article-title"><?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="article-meta">
      <span>📅 <?= htmlspecialchars($meta['date'], ENT_QUOTES, 'UTF-8') ?></span>
      <span>✍️ <?= htmlspecialchars($meta['author'], ENT_QUOTES, 'UTF-8') ?></span>
      <span>⏱️ <?= htmlspecialchars($meta['readTime'], ENT_QUOTES, 'UTF-8') ?> чтения</span>
    </div>
  </div>
</div>

<!-- PAGE WRAPPER -->
<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">

      <main id="main-content" role="main">

        <!-- Рекламный блок ПЕРЕД статьёй -->

        <!-- КОНТЕНТ СТАТЬИ -->
        <article class="article-body" itemscope itemtype="https://schema.org/Article">
          <meta itemprop="headline"     content="<?= htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8') ?>">
          <meta itemprop="datePublished" content="<?= htmlspecialchars($articlePubDate, ENT_QUOTES, 'UTF-8') ?>">
          <meta itemprop="author"       content="<?= htmlspecialchars($meta['author'], ENT_QUOTES, 'UTF-8') ?>">

          <?php if ($articleCoverPath): ?>
          <figure class="article-lead-figure">
            <img itemprop="image"
                 src="<?= htmlspecialchars($articleCoverPath, ENT_QUOTES, 'UTF-8') ?>"
                 width="<?= (int) $ogImageWidth ?>"
                 height="<?= (int) $ogImageHeight ?>"
                 alt="<?= htmlspecialchars($ogImageAlt, ENT_QUOTES, 'UTF-8') ?>"
                 decoding="async"
                 fetchpriority="high">
          </figure>
          <?php endif; ?>

          <?php if ($articleFile && is_readable($articleFile)): ?>
            <?php if (count($articleToc) >= 4): ?>
            <nav class="article-toc" aria-labelledby="toc-heading">
              <h2 class="article-toc-title" id="toc-heading">Содержание</h2>
              <ol class="article-toc-list">
                <?php foreach ($articleToc as $tocItem): ?>
                <li>
                  <a href="#<?= htmlspecialchars($tocItem['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($tocItem['title'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </li>
                <?php endforeach; ?>
              </ol>
            </nav>
            <?php endif; ?>
            <?= $articleHtml ?>
          <?php else: ?>
            <div class="empty-state">
              <div class="icon">📄</div>
              <h3>Статья не найдена</h3>
              <p>Возможно, она была перемещена или переименована. Вот материалы на близкую тему:</p>
            </div>

            <?php $missingSuggestions = domexpert_guess_articles($slug, 4); ?>
            <?php if ($missingSuggestions): ?>
            <ul class="popular-list" style="margin: 0 0 24px;">
              <?php foreach ($missingSuggestions as $item): ?>
              <li>
                <a href="<?= htmlspecialchars(du_article_path($item['slug']), ENT_QUOTES, 'UTF-8') ?>" class="popular-link">
                  <span class="popular-num" aria-hidden="true"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span>
                    <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                    <span style="display:block; color:var(--text-muted); font-size:0.82rem; margin-top:2px;">
                      <?= htmlspecialchars($item['catLabel'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['readTime'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </span>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <div style="display:flex; gap:12px; flex-wrap:wrap;">
              <a href="/" class="btn btn-primary" style="display:inline-flex;">← На главную</a>
              <a href="/articles.php" class="btn btn-outline" style="display:inline-flex; color:var(--heading); border-color:var(--beige-mid);">Все статьи</a>
            </div>
          <?php endif; ?>
        </article>

        <!-- СОБСТВЕННЫЕ ФОТО (появляются сами, когда есть assets/img/photos/<slug>/) -->
        <?php $photoArticleSlug = $slug; ?>
        <?php include __DIR__ . '/includes/article-photos.php'; ?>

        <!-- КАЛЬКУЛЯТОРЫ ПО ТЕМЕ (строится из реестра, разметку статей не трогает) -->
        <?php $calcArticleSlug = $slug; ?>
        <?php include __DIR__ . '/includes/article-calculators.php'; ?>

        <!-- Партнёрский блок «Где купить» (пусто, пока в реестре нет офферов) -->
        <?= domexpert_affiliate_block([$slug, $meta['catSlug'] ?? ''], 2) ?>

        <!-- Рекламный блок ПОСЛЕ статьи: кросс-промо наших проектов (пока нет AdSense) -->
        <?php if (!$articleMissing): ?>
        <?= domexpert_partner_ad('banner', $slug, 0) ?>
        <?php endif; ?>

        <!-- СОСЕДНИЕ СТАТЬИ КАТЕГОРИИ (по дате публикации) -->
        <?php if ($articleNeighbours['prev'] || $articleNeighbours['next']): ?>
        <nav class="article-nav" aria-label="Другие статьи категории «<?= htmlspecialchars($meta['catLabel'], ENT_QUOTES, 'UTF-8') ?>»">
          <?php if ($articleNeighbours['prev']): ?>
          <a class="article-nav-link article-nav-prev" href="<?= htmlspecialchars(du_article_path($articleNeighbours['prev']['slug']), ENT_QUOTES, 'UTF-8') ?>" rel="prev">
            <span class="article-nav-label">← Предыдущая</span>
            <span class="article-nav-title"><?= htmlspecialchars($articleNeighbours['prev']['title'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <?php endif; ?>
          <?php if ($articleNeighbours['next']): ?>
          <a class="article-nav-link article-nav-next" href="<?= htmlspecialchars(du_article_path($articleNeighbours['next']['slug']), ENT_QUOTES, 'UTF-8') ?>" rel="next">
            <span class="article-nav-label">Следующая →</span>
            <span class="article-nav-title"><?= htmlspecialchars($articleNeighbours['next']['title'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>

        <!-- ЧИТАЙТЕ ТАКЖЕ -->
        <section class="related-section" aria-label="Похожие статьи">
          <div class="section-header">
            <h2 class="section-title">Читайте также</h2>
          </div>
          <div class="related-grid">
            <?php foreach ($relatedArticles as $rel): ?>
            <?php $relCover = article_cover_web_path($rel['slug']); ?>
            <article class="article-card">
              <div class="card-image">
                <?php if ($relCover): ?>
                  <img src="<?= htmlspecialchars($relCover, ENT_QUOTES, 'UTF-8') ?>"
                       alt="<?= htmlspecialchars($rel['title'] . ' — превью статьи', ENT_QUOTES, 'UTF-8') ?>"
                       loading="lazy"
                       decoding="async">
                <?php else: ?>
                  <span class="card-image-placeholder"><?= htmlspecialchars($rel['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="card-category">
                  <a href="/category/<?= htmlspecialchars($rel['catSlug'], ENT_QUOTES, 'UTF-8') ?>/" style="color:inherit;text-decoration:none;">
                    <?= htmlspecialchars($rel['catLabel'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </span>
              </div>
              <div class="card-body">
                <h3 class="card-title">
                  <a href="/article/<?= htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8') ?>/">
                    <?= htmlspecialchars($rel['title'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h3>
                <p class="card-desc"><?= htmlspecialchars($rel['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="card-footer">
                  <span class="card-date">📅 <?= htmlspecialchars($rel['date'], ENT_QUOTES, 'UTF-8') ?></span>
                  <a href="/article/<?= htmlspecialchars($rel['slug'], ENT_QUOTES, 'UTF-8') ?>/" class="card-link">Читать</a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        </section>

      </main>

      <?php $sidebarArticleSlug = $slug; ?>
      <?php include __DIR__ . '/includes/sidebar.php'; ?>

    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
