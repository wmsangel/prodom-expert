<?php
/**
 * includes/sidebar.php
 * Сайдбар — считает реальное количество статей из файловой системы
 */

require_once __DIR__ . '/all-articles-meta.php';

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

  <!-- Популярные статьи -->
  <div class="sidebar-widget">
    <h2 class="widget-title">Популярные статьи</h2>
    <ul class="popular-list">
      <li>
        <a href="/article/kak-vybrat-plastikovye-okna/" class="popular-link">
          <span class="popular-num">1</span>
          <span>Как выбрать пластиковые окна: полное руководство</span>
        </a>
      </li>
      <li>
        <a href="/article/gidroizolyatsiya-vannoy/" class="popular-link">
          <span class="popular-num">2</span>
          <span>Гидроизоляция ванной комнаты своими руками</span>
        </a>
      </li>
      <li>
        <a href="/article/zamena-provodki-v-kvartire/" class="popular-link">
          <span class="popular-num">3</span>
          <span>Замена электропроводки в квартире: с чего начать</span>
        </a>
      </li>
      <li>
        <a href="/article/priemka-kvartiry-ot-zastroishchika/" class="popular-link">
          <span class="popular-num">4</span>
          <span>Приёмка квартиры у застройщика: чек-лист</span>
        </a>
      </li>
      <li>
        <a href="/article/uzo-i-avr-v-schitke/" class="popular-link">
          <span class="popular-num">5</span>
          <span>УЗО и дифавтоматы в квартирном щите</span>
        </a>
      </li>
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

</aside>
