<?php
/**
 * vidzhety.php — Лендинг встраиваемых калькуляторов (ЧПУ: /vidzhety/)
 * Каталог всех виджетов: у каждого — готовый код для вставки и превью.
 * Точка входа под запросы «калькулятор для сайта», «встроить калькулятор».
 */

require_once __DIR__ . '/includes/load-seo.php';
$showAds = false; // служебная/лендинг — без рекламы (карта плотности AdSense)
require_once __DIR__ . '/includes/all-calculators-meta.php';

$groups   = domexpert_calculator_groups();
$allCalcs = domexpert_all_calculators_meta();

$siteName  = 'ДомЭксперт';
$pageTitle = 'Калькуляторы для сайта: бесплатные встраиваемые виджеты | ДомЭксперт';
$pageDesc  = 'Бесплатные калькуляторы ремонта, которые можно встроить на свой сайт, блог или форум одним кодом: смета, обои, плитка, краска, кабель, радиаторы. Считают в браузере, с активной ссылкой на источник.';
$pageUrl   = SITE_CANONICAL . '/vidzhety/';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

$position  = 1;
$listItems = [];
foreach ($allCalcs as $slug => $calc) {
  $listItems[] = [
    '@type'    => 'ListItem',
    'position' => $position++,
    'url'      => du_calculator_embed_url($slug),
    'name'     => $calc['h1'] . ' — виджет для сайта',
  ];
}
$listingJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'ItemList',
  'name'            => 'Встраиваемые калькуляторы ремонта',
  'itemListElement' => $listItems,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',       'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Калькуляторы',  'item' => SITE_CANONICAL . '/calculators/'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => 'Виджеты для сайта', 'item' => $pageUrl],
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
        <a href="/calculators/" itemprop="item"><span itemprop="name">Калькуляторы</span></a>
        <meta itemprop="position" content="2">
      </li>
      <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name">Виджеты для сайта</span>
        <meta itemprop="position" content="3">
      </li>
    </ol>
  </div>
</nav>

<main id="main-content">
  <div class="container">
    <header class="category-header">
      <h1>🧩 Калькуляторы для вашего сайта</h1>
      <p>
        Все <?= count($allCalcs) ?> калькулятора ДомЭксперт можно бесплатно встроить на свой сайт,
        блог или форум. Виджет считает прямо у посетителя в браузере, подстраивает высоту сам
        и ставит активную ссылку на источник — вам полезный инструмент, нам упоминание.
      </p>
    </header>

    <div class="content-wrapper">
      <div class="main-content">

        <section class="widget-how">
          <h2 class="section-title">Как встроить за минуту</h2>
          <ol class="widget-steps">
            <li>Выберите калькулятор ниже и нажмите <b>«Код для сайта»</b>.</li>
            <li>Скопируйте код и вставьте в HTML своей страницы, статьи или поста.</li>
            <li>Готово — калькулятор появится на странице и сам подстроит высоту.</li>
          </ol>
          <p class="calc-links-note">Условие одно: не убирайте строку-ссылку на источник под калькулятором. Использование бесплатное, в том числе на коммерческих сайтах.</p>
        </section>

        <?php foreach ($groups as $groupSlug => $group): ?>
          <?php $inGroup = domexpert_calculators_by_group($groupSlug); ?>
          <?php if (!$inGroup) { continue; } ?>
          <section class="calc-group" id="<?= htmlspecialchars($groupSlug, ENT_QUOTES, 'UTF-8') ?>">
            <div class="section-header">
              <h2 class="section-title"><?= $group['icon'] ?> <?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></h2>
            </div>

            <div class="calc-grid">
              <?php foreach ($inGroup as $slug => $calc): ?>
                <?php $snippet = du_embed_snippet($slug, $calc['h1']); ?>
                <article class="calc-card widget-card">
                  <h3 class="calc-card-title"><?= htmlspecialchars($calc['h1'], ENT_QUOTES, 'UTF-8') ?></h3>
                  <p class="calc-card-desc"><?= htmlspecialchars($calc['lead'], ENT_QUOTES, 'UTF-8') ?></p>
                  <div class="widget-actions">
                    <button type="button" class="btn btn-outline" data-widget-toggle="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" aria-expanded="false">🧩 Код для сайта</button>
                    <a class="widget-preview" href="<?= htmlspecialchars('/embed/calc/' . $slug . '/', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">превью ↗</a>
                  </div>
                  <div class="widget-code-wrap" id="wc-<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" hidden>
                    <textarea class="embed-code" readonly rows="6" spellcheck="false" onclick="this.select()"><?= htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <button type="button" class="btn btn-primary" data-widget-copy="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">Скопировать код</button>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>

        <section class="calc-links">
          <h2 class="section-title">Зачем это вам</h2>
          <p class="calc-links-note">
            Готовый интерактивный калькулятор повышает вовлечённость и время на странице,
            а вам не нужно ничего программировать. Хотите другой расчёт или свой стиль виджета —
            напишите на <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a>.
            Все расчёты и их логика открыты в разделе <a href="/calculators/">калькуляторов</a>.
          </p>
        </section>

      </div>
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</main>

<script>
(function () {
  function flash(btn, ok) {
    var old = btn.textContent;
    btn.textContent = ok ? 'Скопировано ✓' : 'Выделено — Ctrl+C';
    setTimeout(function () { btn.textContent = old; }, 1800);
  }
  document.addEventListener('click', function (e) {
    var t = e.target.closest ? e.target.closest('[data-widget-toggle]') : null;
    if (t) {
      var slug = t.getAttribute('data-widget-toggle');
      var wrap = document.getElementById('wc-' + slug);
      if (wrap) {
        var open = wrap.hidden;
        wrap.hidden = !open;
        t.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open && window.duTrack) window.duTrack('embed_view', { calc: slug });
      }
      return;
    }
    var c = e.target.closest ? e.target.closest('[data-widget-copy]') : null;
    if (c) {
      var s = c.getAttribute('data-widget-copy');
      var ta = document.querySelector('#wc-' + s + ' .embed-code');
      if (!ta) return;
      ta.focus(); ta.select();
      var ok = false;
      try {
        if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(ta.value); ok = true; }
        else { ok = document.execCommand('copy'); }
      } catch (err) {}
      flash(c, ok);
      if (window.duTrack) window.duTrack('embed_copy', { calc: s, from: 'vidzhety' });
    }
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
