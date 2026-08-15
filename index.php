<?php
/**
 * index.php — Главная страница
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

require_once __DIR__ . '/includes/all-calculators-meta.php';

// Счётчик для блока УТП — берём из реестра, чтобы цифра не разъезжалась с фактом.
$uspCalcCount = count(domexpert_all_calculators_meta());

/** Русское склонение существительного при числительном: 1 калькулятор, 2 калькулятора, 5 калькуляторов. */
if (!function_exists('domexpert_plural')) {
  function domexpert_plural(int $n, string $one, string $few, string $many): string {
    $n = abs($n) % 100;
    if ($n >= 11 && $n <= 14) return $many;
    $n %= 10;
    if ($n === 1) return $one;
    if ($n >= 2 && $n <= 4) return $few;
    return $many;
  }
}
$uspCalcWord = domexpert_plural($uspCalcCount, 'калькулятор', 'калькулятора', 'калькуляторов');

$pageTitle = 'ДомЭксперт — Всё о ремонте и обустройстве дома';
$pageDesc  = 'Ремонт квартиры в конкретных величинах: допуски, сечения, уклоны и сроки. Разборы по окнам, сантехнике, электрике и интерьеру плюс калькуляторы расчётов.';
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
      <span class="hero-eyebrow">Ремонт в цифрах, а не на глаз</span>
      <h1>Всё о ремонте и<br><em>обустройстве дома</em></h1>
      <p class="hero-desc">
        Разбираем ремонт до конкретных величин: допуски, сечения, уклоны, толщины и сроки высыхания.
        Чтобы вы могли проверить работу подрядчика правилом и рулеткой, а не поверить на слово.
      </p>
      <div class="hero-actions">
        <a href="/category/remont/" class="btn btn-primary">🔨 Статьи о ремонте</a>
        <a href="/calculators/" class="btn btn-outline">🧮 Калькуляторы</a>
      </div>
    </div>
  </div>
</section>

<!-- УТП: чем сайт отличается. Цифры берутся из реестров, вручную не правятся. -->
<section class="usp" aria-labelledby="usp-title">
  <div class="container">
    <h2 id="usp-title" class="usp-title">Почему здесь можно проверить, а не просто прочитать</h2>
    <ul class="usp-grid">
      <li class="usp-item">
        <span class="usp-icon" aria-hidden="true">📐</span>
        <h3>Нормы и допуски в числах</h3>
        <p>Не «ровные стены», а просвет 2 мм на 2 м. Не «правильный уклон», а 20 мм на метр для трубы 110. Каждую цифру можно взять и проверить на объекте.</p>
      </li>
      <li class="usp-item">
        <span class="usp-icon" aria-hidden="true">🧮</span>
        <h3><?= (int) $uspCalcCount ?> <?= htmlspecialchars($uspCalcWord, ENT_QUOTES, 'UTF-8') ?> под расчёты</h3>
        <p>Смета, стяжка, плитка, краска, сечение кабеля, воздухообмен. Считают по тем же формулам, что описаны в статьях, — с запасом на подрезку.</p>
      </li>
      <li class="usp-item">
        <span class="usp-icon" aria-hidden="true">🚩</span>
        <h3>Разбор ошибок и красных флагов</h3>
        <p>В каждом материале — что идёт не так на практике: от «точки» в смете электрика до цанговых фитингов в стяжке. Дешевле прочитать, чем вскрывать плитку.</p>
      </li>
      <li class="usp-item">
        <span class="usp-icon" aria-hidden="true">📖</span>
        <h3>Прозрачная редполитика</h3>
        <p>Никаких платных обзоров под видом советов. У каждой статьи видна дата, откуда берутся цены и где проходят границы наших рекомендаций — <a href="/editorial.php">читайте правила</a>.</p>
      </li>
    </ul>
  </div>
</section>

<!-- Флагманский инструмент. Блок намеренно стоит сразу после УТП: это самое
     сильное, что есть на сайте, и до ленты статей его должны увидеть все. -->
<section class="promo-planner" aria-labelledby="promo-planner-title">
  <div class="container">
    <div class="promo-planner-inner">

      <div class="promo-planner-text">
        <span class="promo-planner-badge">Новое · бесплатно · без регистрации</span>
        <h2 id="promo-planner-title">Планировщик ремонта</h2>
        <p class="promo-planner-lead">
          Нарисуйте план своей квартиры прямо в браузере — и получите площади пола, стен
          и потолков, список материалов с запасом и смету по этапам со сроками.
        </p>
        <ul class="promo-planner-list">
          <li>Комнаты, двери и окна на сетке с шагом 10 см</li>
          <li>Своя отделка для каждой комнаты — от неё зависит и цена, и расход</li>
          <li>Смета по пяти этапам, материалы с запасом и календарь работ</li>
          <li>План сохраняется в браузере, на сервер ничего не уходит</li>
        </ul>
        <div class="promo-planner-actions">
          <a href="/calc/planirovshchik-remonta/" class="btn btn-primary">Открыть планировщик</a>
          <a href="/calculators/" class="promo-planner-more">Все калькуляторы →</a>
        </div>
      </div>

      <div class="promo-planner-art" aria-hidden="true">
        <svg viewBox="0 0 320 234" role="img" focusable="false">
          <defs>
            <pattern id="pp-grid" width="20" height="20" patternUnits="userSpaceOnUse">
              <path d="M20 0H0v20" fill="none" stroke="rgba(255,255,255,.10)" stroke-width="1"/>
            </pattern>
          </defs>
          <rect width="320" height="234" fill="url(#pp-grid)" rx="8"/>

          <!-- Заливки комнат отдельно от стен: стены рисуются отрезками, чтобы
               проёмы были настоящими разрывами, а не «вырезом» цветом фона —
               подогнать такой вырез под градиент под холстом невозможно. -->
          <g>
            <rect x="22" y="24" width="150" height="106" fill="hsla(210,55%,58%,.30)"/>
            <rect x="172" y="24" width="126" height="106" fill="hsla(35,55%,58%,.30)"/>
            <rect x="22" y="130" width="150" height="70"  fill="hsla(275,55%,60%,.28)"/>
            <rect x="172" y="130" width="126" height="70" fill="hsla(172,55%,52%,.30)"/>
          </g>

          <!-- Стены с разрывами: два окна в верхней стене, дверь в перегородке -->
          <!-- square-концы закрывают наружные углы: при butt на стыке двух
               отрезков остаётся заметная выщербина в полтолщины стены. -->
          <g stroke="#EDEFF2" stroke-width="3.6" stroke-linecap="square" fill="none">
            <path d="M22 24h48M124 24h82M256 24h42"/>
            <path d="M22 200h276"/>
            <path d="M22 24v176M298 24v176M172 24v176"/>
            <path d="M22 130h84M146 130h152"/>
          </g>

          <!-- Окна: двойная линия в разрыве стены -->
          <g stroke="#7EC8F0" stroke-width="1.8" fill="none">
            <path d="M70 21h54M70 27h54M206 21h50M206 27h50"/>
          </g>

          <!-- Дверь: полотно и дуга открывания в гостиную -->
          <path d="M106 130v-40" stroke="#EDEFF2" stroke-width="2.4" fill="none"/>
          <path d="M146 130a40 40 0 0 0-40-40" stroke="#EDEFF2" stroke-width="1.5" fill="none" opacity=".5"/>

          <g fill="#F4F6F8" font-family="'Source Sans 3',Arial,sans-serif" text-anchor="middle">
            <text x="97"  y="72"  font-size="12" font-weight="600">Гостиная</text>
            <text x="97"  y="88"  font-size="10" opacity=".7">15,8 м²</text>
            <text x="235" y="72"  font-size="12" font-weight="600">Кухня</text>
            <text x="235" y="88"  font-size="10" opacity=".7">11,5 м²</text>
            <text x="97"  y="170" font-size="11" font-weight="600">Коридор</text>
            <text x="235" y="170" font-size="11" font-weight="600">Санузел</text>
          </g>

          <!-- Размерная линия под планом, подпись под линией -->
          <g stroke="#E2705C" stroke-width="1.2">
            <path d="M22 214h150M22 210v8M172 210v8" fill="none"/>
            <text x="97" y="230" font-size="10" font-weight="600" text-anchor="middle"
                  fill="#E2705C" stroke="none">4,40 м</text>
          </g>
        </svg>

        <div class="promo-planner-figures">
          <span><b>42,6 м²</b> пола</span>
          <span><b>132,8 м²</b> стен</span>
          <span><b>931 805 ₽</b> смета</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- MAIN CONTENT -->
<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">

      <!-- Статьи -->
      <main id="main-content" role="main">

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
