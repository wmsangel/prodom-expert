<?php
/**
 * embed.php — встраиваемая (iframe) версия калькулятора для чужих сайтов.
 * ЧПУ: /embed/calc/<slug>/
 *
 * Лёгкая автономная страница: только сам калькулятор + атрибуция-ссылка на источник,
 * без шапки/меню/футера/аналитики/куки-баннера. noindex + canonical на основную
 * страницу /calc/<slug>/ (чтобы не плодить дубли в выдаче). Высота передаётся
 * родителю через postMessage — сниппет на стороне клиента подгоняет iframe.
 *
 * X-Frame-Options для /embed/ снят в .htaccess, иначе встраивание запрещено.
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-calculators-meta.php';

$rawSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug    = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));

$allCalcs = domexpert_all_calculators_meta();
$groups   = domexpert_calculator_groups();

if ($slug === '' || !isset($allCalcs[$slug])) {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><meta charset="utf-8"><p style="font:14px sans-serif">Калькулятор не найден. <a href="https://prodom-expert.ru/calculators/">Все калькуляторы ДомЭксперт</a>.</p>';
  return;
}

$calc     = $allCalcs[$slug];
$calcPath = domexpert_calculator_html_path($slug);
$calcHtml = is_readable($calcPath) ? (string) file_get_contents($calcPath) : '';
if ($calcHtml === '') {
  http_response_code(404);
  header('Content-Type: text/html; charset=utf-8');
  echo '<!doctype html><meta charset="utf-8"><p style="font:14px sans-serif">Калькулятор недоступен. <a href="https://prodom-expert.ru/calculators/">Все калькуляторы</a>.</p>';
  return;
}

$group    = $groups[$calc['group']] ?? ['label' => 'Калькулятор', 'icon' => '🧮'];
$extraCss = $calc['assetsCss'] ?? [];
$extraJs  = $calc['assetsJs'] ?? [];
$canon    = du_calculator_url($slug);

function embed_esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, follow">
<title><?= embed_esc($calc['h1']) ?> — калькулятор ДомЭксперт</title>
<link rel="canonical" href="<?= embed_esc($canon) ?>">
<base target="_blank">
<link rel="stylesheet" href="<?= embed_esc(du_asset('/assets/css/style.css')) ?>">
<?php foreach ($extraCss as $css): ?>
<link rel="stylesheet" href="<?= embed_esc(du_asset($css)) ?>">
<?php endforeach; ?>
<style>
  /* Встраиваемый режим: карточка с калькулятором, без обвязки сайта. */
  html, body { background: transparent; margin: 0; padding: 0; }
  .embed-card {
    box-sizing: border-box; max-width: 680px; margin: 0 auto; padding: 18px 18px 14px;
    background: var(--white); color: var(--text);
    border: 1px solid var(--border); border-radius: 12px;
  }
  .embed-head { margin: 0 0 14px; }
  .embed-badge { display: inline-block; font-size: .78rem; font-weight: 700; color: var(--text-light);
    background: var(--beige); padding: 3px 10px; border-radius: 999px; margin-bottom: 8px; }
  .embed-card h1 { font-size: 1.3rem; line-height: 1.2; margin: 0; color: var(--heading); }
  /* Описание/пример/FAQ в embed не нужны — показываем сам инструмент. */
  .embed-card .calc-article { display: none !important; }
  .embed-attr { margin: 14px 0 0; padding-top: 12px; border-top: 1px solid var(--border);
    font-size: .82rem; color: var(--text-light); text-align: center; }
  .embed-attr a { color: var(--brick); font-weight: 700; text-decoration: none; }
  .embed-attr a:hover { text-decoration: underline; }
</style>
</head>
<body class="calc-embed">
  <div class="embed-card">
    <div class="embed-head">
      <span class="embed-badge"><?= embed_esc($group['icon'] . ' ' . $group['label']) ?></span>
      <h1><?= embed_esc($calc['h1']) ?></h1>
    </div>

    <!-- calc.js до разметки: внутри фрагмента инлайн DomCalc.register() -->
    <script src="<?= embed_esc(du_asset('/assets/js/calc.js')) ?>"></script>
    <?= $calcHtml ?>
    <?php foreach ($extraJs as $jsSrc): ?>
      <script src="<?= embed_esc(du_asset($jsSrc)) ?>" defer></script>
    <?php endforeach; ?>

    <p class="embed-attr">🧮 Калькулятор от <a href="<?= embed_esc($canon) ?>">ДомЭксперт</a> — ремонт в цифрах</p>
  </div>

  <script>
  // Передаём высоту родителю: сниппет на стороне клиента подгоняет iframe.
  (function () {
    var slug = <?= json_encode($slug) ?>;
    function post() {
      var h = Math.ceil(document.querySelector('.embed-card').getBoundingClientRect().height) + 8;
      try { parent.postMessage({ type: 'prodom-embed', slug: slug, height: h }, '*'); } catch (e) {}
    }
    window.addEventListener('load', post);
    if (window.ResizeObserver) { new ResizeObserver(post).observe(document.body); }
    document.addEventListener('input', function () { setTimeout(post, 30); });
    document.addEventListener('change', function () { setTimeout(post, 30); });
  })();
  </script>
</body>
</html>
