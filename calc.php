<?php
/**
 * calc.php — Страница калькулятора
 * GET параметр: ?slug=smeta-remonta   (ЧПУ: /calc/smeta-remonta/)
 *
 * Мета берётся из includes/all-calculators-meta.php, разметка формы —
 * из calculators/<slug>.html. Логика счёта живёт в самом файле разметки,
 * общий каркас (сбор полей, пересчёт, форматирование) — в assets/js/calc.js.
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-calculators-meta.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

$rawSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

$allCalcs = domexpert_all_calculators_meta();
$groups   = domexpert_calculator_groups();

if ($slug === '' || !isset($allCalcs[$slug])) {
  // Неизвестный калькулятор — честный 404, а не пустая страница с кодом 200.
  http_response_code(404);
  $robotsMeta = 'noindex, follow';
  $calc = [
    'group' => 'smeta',
    'title' => 'Калькулятор не найден',
    'h1'    => 'Калькулятор не найден',
    'desc'  => 'Такого калькулятора на сайте нет. Посмотрите весь раздел расчётов.',
    'lead'  => '',
    'level' => '',
    'related' => [],
  ];
  $calcFile = null;
} else {
  $calc     = $allCalcs[$slug];
  $calcPath = domexpert_calculator_html_path($slug);
  $calcFile = is_readable($calcPath) ? $calcPath : null;
  if ($calcFile === null) {
    http_response_code(404);
    $robotsMeta = 'noindex, follow';
  }
}

$group      = $groups[$calc['group']] ?? ['label' => 'Калькуляторы', 'icon' => '🧮'];
$allArticles = domexpert_all_articles_meta();

// Статьи по теме калькулятора
$relatedArticles = [];
foreach ($calc['related'] as $relSlug) {
  if (isset($allArticles[$relSlug])) {
    $relatedArticles[$relSlug] = $allArticles[$relSlug];
  }
}
// Соседние калькуляторы той же группы
$siblings = array_filter(
  domexpert_calculators_by_group($calc['group']),
  static fn(string $s): bool => $s !== $slug,
  ARRAY_FILTER_USE_KEY
);

// === SEO ===
$siteName  = 'ДомЭксперт';
$pageTitle = $calc['title'] . ' | ДомЭксперт';
$pageDesc  = $calc['desc'];
$pageUrl   = $slug !== '' && isset($allCalcs[$slug])
  ? du_calculator_url($slug)
  : SITE_CANONICAL . '/calculators/';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

// header.php умеет $articleJsonLd, $breadcrumbJsonLd, $listingJsonLd, $faqJsonLd —
// калькулятор отдаём как WebApplication через $articleJsonLd.
$articleJsonLd = json_encode([
  '@context'            => 'https://schema.org',
  '@type'               => 'WebApplication',
  'name'                => $calc['h1'],
  'description'         => $calc['desc'],
  'url'                 => $pageUrl,
  'applicationCategory' => 'UtilitiesApplication',
  'operatingSystem'     => 'Any',
  'browserRequirements' => 'Требуется JavaScript',
  'isAccessibleForFree' => true,
  'inLanguage'          => 'ru-RU',
  'publisher'           => ['@type' => 'Organization', 'name' => $siteName, '@id' => domexpert_org_id()],
  'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'RUB'],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Калькулятор может попросить свои стиль и скрипт полями assetsCss/assetsJs —
// так делает планировщик, у которого разметки и логики слишком много для общих
// файлов. Оба подключаются с меткой версии (du_asset), иначе обновление не
// доедет до тех, у кого старая копия лежит в кэше на год вперёд.
$extraCss = $calc['assetsCss'] ?? [];
$extraJs  = $calc['assetsJs'] ?? [];

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',       'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Калькуляторы',  'item' => SITE_CANONICAL . '/calculators/'],
    ['@type' => 'ListItem', 'position' => 3, 'name' => $calc['h1'],     'item' => $pageUrl],
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
        <span itemprop="name"><?= htmlspecialchars($calc['h1'], ENT_QUOTES, 'UTF-8') ?></span>
        <meta itemprop="position" content="3">
      </li>
    </ol>
  </div>
</nav>

<main id="main-content">
  <div class="container">
    <div class="content-wrapper">
      <div class="main-content">

        <header class="article-header">
          <span class="article-category-badge"><?= $group['icon'] ?> <?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></span>
          <h1 class="article-title"><?= htmlspecialchars($calc['h1'], ENT_QUOTES, 'UTF-8') ?></h1>
          <?php if ($calc['lead'] !== ''): ?>
            <p class="calc-lead"><?= htmlspecialchars($calc['lead'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <?php if ($calc['level'] !== ''): ?>
            <p class="article-meta"><span>🧮 Калькулятор · <?= htmlspecialchars($calc['level'], ENT_QUOTES, 'UTF-8') ?> · считает в браузере, ничего не отправляется</span></p>
          <?php endif; ?>
        </header>

        <?php if ($calcFile): ?>
          <!-- Каркас подключается ДО разметки калькулятора и без defer: внутри
               фрагмента стоит инлайн-вызов DomCalc.register(), который выполняется
               прямо при разборе страницы. С defer каркас грузился уже после него,
               и регистрация падала с «DomCalc is not defined» — расчёт не запускался. -->
          <script src="<?= htmlspecialchars(du_asset('/assets/js/calc.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
          <?= file_get_contents($calcFile) ?>
          <?php foreach ($extraJs as $jsSrc): ?>
            <script src="<?= htmlspecialchars(du_asset($jsSrc), ENT_QUOTES, 'UTF-8') ?>" defer></script>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="icon">🧮</div>
            <h3>Калькулятор не найден</h3>
            <p>Возможно, адрес изменился. Откройте раздел расчётов и выберите нужный.</p>
            <br>
            <a href="/calculators/" class="btn btn-primary" style="display:inline-flex;">Все калькуляторы</a>
          </div>
        <?php endif; ?>

        <?php if ($calcFile): ?>
        <section class="calc-disclaimer" aria-label="Как пользоваться расчётом">
          <h2 class="section-title">Насколько точен этот расчёт</h2>
          <p>
            Калькулятор даёт <strong>ориентир для планирования</strong>, а не готовую смету или проект.
            Формула и коэффициенты описаны выше — вы можете проверить логику и пересчитать вручную.
            Цены приведены в рублях по состоянию на 2026 год для крупных городов; в вашем регионе
            они могут отличаться на 20–30%, а нормы расхода — зависеть от конкретного материала
            и состояния основания.
          </p>
          <p>
            Всё считается прямо в браузере: введённые значения никуда не отправляются и нигде
            не сохраняются. Работы, связанные с газовым оборудованием, несущими конструкциями
            и подключением к электросетям, выполняются только специалистами с допуском —
            расчёт помогает поставить задачу, но не заменяет проект.
            Заметили ошибку в цифрах? Напишите на <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a>.
          </p>
        </section>
        <?php endif; ?>

        <?php if ($relatedArticles): ?>
        <section class="calc-links" aria-label="Статьи по теме">
          <h2 class="section-title">Разобраться подробнее</h2>
          <p class="calc-links-note">Калькулятор даёт цифру, статьи объясняют, откуда она берётся и где ошибаются чаще всего.</p>
          <ul class="calc-link-list">
            <?php foreach ($relatedArticles as $relSlug => $relMeta): ?>
              <li>
                <a href="<?= htmlspecialchars(du_article_path($relSlug), ENT_QUOTES, 'UTF-8') ?>">
                  <strong><?= htmlspecialchars($relMeta['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <span><?= htmlspecialchars($relMeta['desc'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
        <?php endif; ?>

        <?php if ($siblings): ?>
        <section class="calc-links" aria-label="Другие калькуляторы раздела">
          <h2 class="section-title">Ещё в разделе «<?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?>»</h2>
          <ul class="calc-link-list calc-link-list--compact">
            <?php foreach ($siblings as $sibSlug => $sib): ?>
              <li>
                <a href="/calc/<?= htmlspecialchars($sibSlug, ENT_QUOTES, 'UTF-8') ?>/">
                  <strong><?= htmlspecialchars($sib['h1'], ENT_QUOTES, 'UTF-8') ?></strong>
                  <span><?= htmlspecialchars($sib['lead'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="calc-links-note"><a href="/calculators/">Все калькуляторы сайта →</a></p>
        </section>
        <?php endif; ?>

      </div>
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
