<?php
/**
 * includes/sidebar.php
 * Сайдбар: подборка материалов, категории с реальным счётчиком статей.
 *
 * Подключающая страница может задать $sidebarArticleSlug — тогда в подборке
 * идут статьи по теме этой, кроме тех трёх, что уже показаны в «Читайте также»
 * внизу материала. Иначе — свежие статьи.
 */

require_once __DIR__ . '/all-articles-meta.php';
require_once __DIR__ . '/partner-ads.php';

// Позиции 4–8 в подборе похожих: первые три заняты блоком «Читайте также»,
// повторять их в сайдбаре незачем.
$sidebarPicks = [];
if (!empty($sidebarArticleSlug)) {
  $sidebarPicks = array_slice(domexpert_related_articles($sidebarArticleSlug, 8), 3, 5);
}
$sidebarIsTopical = !empty($sidebarPicks);
if (!$sidebarIsTopical) {
  $sidebarPicks = domexpert_latest_articles(5);
}

// Реальный подсчёт статей по категориям
$catConfig = domexpert_categories();

$articlesDir = dirname(__DIR__) . '/articles/';
$catCounts   = [];
// Переменная цикла с префиксом: sidebar.php подключается в глобальной области,
// generic-имя $slug затирало бы слаг статьи на подключающей странице.
foreach ($catConfig as $catSlugSide => $cfg) {
  $dir = $articlesDir . $catSlugSide . '/';
  $count = is_dir($dir) ? count(glob($dir . '*.html')) : 0;
  $catCounts[$catSlugSide] = $count;
}
?>
<aside class="sidebar" role="complementary" aria-label="Дополнительная информация">

  <!-- Материалы по теме статьи, а вне статьи — свежие -->
  <div class="sidebar-widget">
    <h2 class="widget-title"><?= $sidebarIsTopical ? 'Ещё по теме' : 'Свежие статьи' ?></h2>
    <ul class="popular-list">
      <?php foreach ($sidebarPicks as $pick): ?>
      <li>
        <a href="<?= htmlspecialchars(du_article_path($pick['slug']), ENT_QUOTES, 'UTF-8') ?>" class="popular-link">
          <span class="popular-num" aria-hidden="true"><?= htmlspecialchars($pick['icon'], ENT_QUOTES, 'UTF-8') ?></span>
          <span><?= htmlspecialchars($pick['title'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Категории с реальным счётчиком -->
  <div class="sidebar-widget">
    <h2 class="widget-title">Категории</h2>
    <ul class="cat-list">
      <?php foreach ($catConfig as $catSlugSide => $cfg): ?>
      <li>
        <a href="/category/<?= $catSlugSide ?>/" class="cat-link">
          <?= $cfg['icon'] ?> <?= htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8') ?>
          <span class="cat-count"><?= $catCounts[$catSlugSide] ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>

  <!-- Кросс-промо: наши проекты (пока нет AdSense) -->
  <?= domexpert_partner_ad('card', $sidebarArticleSlug ?? '', 1) ?>

</aside>
