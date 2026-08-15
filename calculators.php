<?php
/**
 * calculators.php — Раздел калькуляторов (ЧПУ: /calculators/)
 * Список всех расчётов, сгруппированный по темам из реестра.
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-calculators-meta.php';

$groups   = domexpert_calculator_groups();
$allCalcs = domexpert_all_calculators_meta();

$siteName  = 'ДомЭксперт';
$pageTitle = 'Калькуляторы ремонта: смета, материалы, электрика, сантехника | ДомЭксперт';
$pageDesc  = 'Бесплатные калькуляторы для ремонта квартиры: смета по этапам, стоимость за м², обои, краска, плитка, стяжка, сечение кабеля, радиаторы, бойлер и воздухообмен.';
$pageUrl   = SITE_CANONICAL . '/calculators/';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

$position  = 1;
$listItems = [];
foreach ($allCalcs as $slug => $calc) {
  $listItems[] = [
    '@type'    => 'ListItem',
    'position' => $position++,
    'url'      => du_calculator_url($slug),
    'name'     => $calc['h1'],
  ];
}

$listingJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'ItemList',
  'name'            => 'Калькуляторы ремонта',
  'itemListElement' => $listItems,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',      'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Калькуляторы', 'item' => $pageUrl],
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
        <span itemprop="name">Калькуляторы</span>
        <meta itemprop="position" content="2">
      </li>
    </ol>
  </div>
</nav>

<main id="main-content">
  <div class="container">
    <header class="category-header">
      <h1>🧮 Калькуляторы ремонта</h1>
      <p>
        <?= count($allCalcs) ?> расчёта для планирования ремонта: от полной сметы по этапам
        до количества рулонов обоев. Всё считается прямо в браузере — ничего не нужно
        отправлять и нигде регистрироваться. Цены и нормы — 2026 год.
      </p>
    </header>

    <div class="content-wrapper">
      <div class="main-content">
        <?php foreach ($groups as $groupSlug => $group): ?>
          <?php $inGroup = domexpert_calculators_by_group($groupSlug); ?>
          <?php if (!$inGroup) { continue; } ?>
          <section class="calc-group" id="<?= htmlspecialchars($groupSlug, ENT_QUOTES, 'UTF-8') ?>">
            <div class="section-header">
              <h2 class="section-title"><?= $group['icon'] ?> <?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>
            <p class="calc-group-desc"><?= htmlspecialchars($group['desc'], ENT_QUOTES, 'UTF-8') ?></p>

            <div class="calc-grid">
              <?php foreach ($inGroup as $slug => $calc): ?>
                <article class="calc-card">
                  <a href="/calc/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>/" class="calc-card-link">
                    <h3 class="calc-card-title"><?= htmlspecialchars($calc['h1'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="calc-card-desc"><?= htmlspecialchars($calc['lead'], ENT_QUOTES, 'UTF-8') ?></p>
                    <span class="calc-card-level"><?= htmlspecialchars($calc['level'], ENT_QUOTES, 'UTF-8') ?> расчёт</span>
                  </a>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>

        <section class="calc-links">
          <h2 class="section-title">Как пользоваться расчётами</h2>
          <p class="calc-links-note">
            Калькулятор даёт ориентир, а не окончательную цифру: цены различаются по регионам,
            а нормы расхода — по конкретному материалу и основанию. Поэтому у каждого расчёта
            есть ссылки на статьи, где разобрано, откуда берутся коэффициенты и что проверить
            перед закупкой. Перед разговором с бригадой полезно посмотреть
            <a href="/article/smeta-remonta-kvartiry/">как устроена смета на ремонт</a> и
            <a href="/article/kontrol-smety-remonta-po-etapam/">как контролировать её по этапам</a>.
          </p>
        </section>
      </div>
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
