<?php
/**
 * slovar.php — Словарь ремонтных терминов (ЧПУ: /slovar/)
 * Алфавитный указатель + живой поиск. Данные — includes/all-glossary-meta.php.
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-glossary-meta.php';

$terms = domexpert_all_glossary_meta();

// Сортировка по алфавиту и группировка по первой букве термина.
uasort($terms, static fn($a, $b) => strcoll($a['term'], $b['term']));
$byLetter = [];
foreach ($terms as $slug => $t) {
  $first = mb_strtoupper(mb_substr($t['term'], 0, 1, 'UTF-8'), 'UTF-8');
  $byLetter[$first][$slug] = $t;
}
$letters = array_keys($byLetter);

$siteName  = 'ДомЭксперт';
$pageTitle = 'Словарь ремонтных терминов: что это такое простыми словами | ДомЭксперт';
$pageDesc  = 'Понятный словарь ремонта: стяжка, грунтовка, гидроизоляция, УЗО, сечение кабеля, точка росы и другие термины — коротко и по делу, со ссылками на статьи и калькуляторы.';
$pageUrl   = SITE_CANONICAL . '/slovar/';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;
$extraCss  = ['/assets/css/glossary.css'];

// JSON-LD: набор определений (DefinedTermSet) + хлебные крошки.
$definedTerms = [];
foreach ($terms as $slug => $t) {
  $definedTerms[] = [
    '@type'       => 'DefinedTerm',
    'name'        => $t['term'],
    'description' => $t['short'],
    'url'         => du_term_url($slug),
  ];
}
$listingJsonLd = json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'DefinedTermSet',
  'name'        => 'Словарь ремонтных терминов',
  'description' => $pageDesc,
  'url'         => $pageUrl,
  'inLanguage'  => 'ru-RU',
  'hasDefinedTerm' => $definedTerms,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Словарь', 'item' => $pageUrl],
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
        <span itemprop="name">Словарь</span>
        <meta itemprop="position" content="2">
      </li>
    </ol>
  </div>
</nav>

<div class="category-header">
  <div class="container">
    <h1>📖 Словарь ремонтных терминов</h1>
    <p>Что означают слова, которые слышишь от прораба и в магазине. Коротко, простыми словами и со ссылками на подробные статьи и калькуляторы.</p>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout main-layout--solo">
      <main id="main-content" role="main">

        <div class="glossary-tools">
          <label class="glossary-search">
            <span class="visually-hidden">Поиск по словарю</span>
            <input type="search" id="glossarySearch" placeholder="Найти термин — например, стяжка или УЗО…"
                   autocomplete="off" aria-controls="glossaryList">
          </label>
          <nav class="glossary-alphabet" aria-label="По алфавиту">
            <?php foreach ($letters as $L): ?>
              <a href="#letter-<?= esc($L) ?>"><?= esc($L) ?></a>
            <?php endforeach; ?>
          </nav>
        </div>

        <p class="glossary-empty" id="glossaryEmpty" hidden>Ничего не найдено. Попробуйте другое слово.</p>

        <div id="glossaryList">
          <?php foreach ($byLetter as $L => $group): ?>
          <section class="glossary-letter" id="letter-<?= esc($L) ?>">
            <h2 class="glossary-letter-title"><?= esc($L) ?></h2>
            <ul class="glossary-index">
              <?php foreach ($group as $slug => $t): ?>
              <li class="glossary-item"
                  data-search="<?= esc(mb_strtolower($t['term'] . ' ' . implode(' ', $t['aliases'] ?? []) . ' ' . $t['short'], 'UTF-8')) ?>">
                <a class="glossary-item-term" href="<?= esc(du_term_path($slug)) ?>"><?= esc($t['term']) ?></a>
                <span class="glossary-item-desc"><?= esc($t['short']) ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </section>
          <?php endforeach; ?>
        </div>

        <p class="glossary-foot">
          Не нашли нужное слово? Напишите на <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a> —
          добавим термин в словарь.
        </p>

      </main>
    </div>
  </div>
</div>

<script>
(function () {
  var input = document.getElementById('glossarySearch');
  var list  = document.getElementById('glossaryList');
  var empty = document.getElementById('glossaryEmpty');
  if (!input || !list) return;
  var items    = Array.prototype.slice.call(list.querySelectorAll('.glossary-item'));
  var sections = Array.prototype.slice.call(list.querySelectorAll('.glossary-letter'));

  function norm(s) { return (s || '').toLowerCase().replace(/ё/g, 'е').trim(); }

  input.addEventListener('input', function () {
    var q = norm(input.value);
    var shown = 0;
    items.forEach(function (li) {
      var hit = q === '' || norm(li.getAttribute('data-search')).indexOf(q) !== -1;
      li.hidden = !hit;
      if (hit) shown++;
    });
    // прячем буквы-заголовки без видимых терминов
    sections.forEach(function (sec) {
      var any = sec.querySelector('.glossary-item:not([hidden])');
      sec.hidden = !any;
    });
    empty.hidden = shown !== 0;
    if (window.duTrack && q.length >= 3) window.duTrack('glossary_search', { q: q.slice(0, 40) });
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
