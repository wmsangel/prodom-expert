<?php
/**
 * termin.php — Страница термина словаря
 * GET: ?slug=styazhka-pola   (ЧПУ: /slovar/styazhka-pola/)
 *
 * Мета берётся из includes/all-glossary-meta.php. Раздел — /slovar/ (slovar.php).
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-glossary-meta.php';
require_once __DIR__ . '/includes/all-articles-meta.php';
require_once __DIR__ . '/includes/all-calculators-meta.php';

$rawSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

$terms = domexpert_all_glossary_meta();

if ($slug === '' || !isset($terms[$slug])) {
  http_response_code(404);
  $robotsMeta = 'noindex, follow';
  $term = null;
} else {
  $term = $terms[$slug];
}

$siteName = 'ДомЭксперт';

if ($term === null) {
  $pageTitle = 'Термин не найден | ДомЭксперт';
  $pageDesc  = 'Такого термина в словаре нет. Посмотрите весь словарь ремонтных терминов.';
  $pageUrl   = SITE_CANONICAL . '/slovar/';
  $ogTitle   = $pageTitle; $ogDesc = $pageDesc;
  $extraCss  = ['/assets/css/glossary.css'];
  include __DIR__ . '/includes/header.php';
  ?>
  <div class="page-wrapper"><div class="container">
    <div class="article-body" style="text-align:center; padding:40px 0;">
      <h1>Термин не найден</h1>
      <p>Похоже, такого слова в словаре пока нет.</p>
      <p><a class="help-btn" href="/slovar/">← Весь словарь терминов</a></p>
    </div>
  </div></div>
  <?php
  include __DIR__ . '/includes/footer.php';
  return;
}

$allArticles = domexpert_all_articles_meta();
$allCalcs    = domexpert_all_calculators_meta();
$groups      = domexpert_glossary_groups();

// Связанные материалы (только существующие).
$relArticles = [];
foreach ($term['related_articles'] ?? [] as $s) {
  if (isset($allArticles[$s])) $relArticles[$s] = $allArticles[$s];
}
$relCalcs = [];
foreach ($term['related_calcs'] ?? [] as $s) {
  if (isset($allCalcs[$s])) $relCalcs[$s] = $allCalcs[$s];
}
$relTerms = [];
foreach ($term['related_terms'] ?? [] as $s) {
  if (isset($terms[$s])) $relTerms[$s] = $terms[$s];
}

// === SEO ===
$aliasText = !empty($term['aliases']) ? ' (' . implode(', ', $term['aliases']) . ')' : '';
// Опциональные seo_title/seo_desc в реестре термина перекрывают шаблон (для докрутки
// под конкретный поисковый интент); без них — дефолт «X — что это такое простыми словами».
$pageTitle = ($term['seo_title'] ?? ($term['term'] . ' — что это такое простыми словами')) . ' | ДомЭксперт';
$pageDesc  = $term['seo_desc'] ?? $term['short'];
$pageUrl   = du_term_url($slug);
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;
$extraCss  = ['/assets/css/glossary.css'];

$articleJsonLd = json_encode([
  '@context'         => 'https://schema.org',
  '@type'            => 'DefinedTerm',
  'name'             => $term['term'],
  'description'      => $term['short'],
  'url'              => $pageUrl,
  'inLanguage'       => 'ru-RU',
  'inDefinedTermSet' => [
    '@type' => 'DefinedTermSet',
    'name'  => 'Словарь ремонтных терминов',
    'url'   => SITE_CANONICAL . '/slovar/',
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Словарь', 'item' => SITE_CANONICAL . '/slovar/'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $term['term'], 'item' => $pageUrl],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

include __DIR__ . '/includes/header.php';
?>

<nav class="breadcrumbs" aria-label="Навигационная цепочка">
  <div class="container">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="/" itemprop="item"><span itemprop="name">Главная</span></a>
        <meta itemprop="position" content="1">
      </li>
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="/slovar/" itemprop="item"><span itemprop="name">Словарь</span></a>
        <meta itemprop="position" content="2">
      </li>
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name"><?= esc($term['term']) ?></span>
        <meta itemprop="position" content="3">
      </li>
    </ol>
  </div>
</nav>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout main-layout--solo">
      <main id="main-content" role="main">
        <article class="term-article">

          <span class="term-group"><?= esc(($groups[$term['group']]['icon'] ?? '') . ' ' . ($groups[$term['group']]['label'] ?? '')) ?></span>
          <h1><?= esc($term['term']) ?></h1>
          <?php if (!empty($term['aliases'])): ?>
            <p class="term-aliases">Также: <?= esc(implode(', ', $term['aliases'])) ?></p>
          <?php endif; ?>

          <div class="term-lead"><?= esc($term['short']) ?></div>

          <div class="article-body term-body">
            <?= $term['body'] ?>
          </div>

          <?php if ($relCalcs): ?>
          <section class="term-related">
            <h2>Посчитать</h2>
            <ul class="term-links">
              <?php foreach ($relCalcs as $s => $c): ?>
                <li><a href="<?= esc('/calc/' . $s . '/') ?>">🧮 <?= esc($c['h1']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <?php endif; ?>

          <?php if ($relArticles): ?>
          <section class="term-related">
            <h2>Разобраться подробнее</h2>
            <ul class="term-links">
              <?php foreach ($relArticles as $s => $a): ?>
                <li><a href="<?= esc(du_article_path($s)) ?>">📄 <?= esc($a['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </section>
          <?php endif; ?>

          <?php if ($relTerms): ?>
          <section class="term-related">
            <h2>Смежные термины</h2>
            <div class="term-chips">
              <?php foreach ($relTerms as $s => $t): ?>
                <a class="term-chip" href="<?= esc(du_term_path($s)) ?>"><?= esc($t['term']) ?></a>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <p class="term-back"><a href="/slovar/">← Весь словарь терминов</a></p>

        </article>
      </main>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
