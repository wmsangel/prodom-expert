<?php
/**
 * go.php — партнёрский редиректор /go/<id> с фильтром «человек vs бот».
 *
 * Зачем. Партнёрские ссылки раньше стояли в разметке прямым deeplink. Боты,
 * которые ходят по списку собранных URL, дёргали их, и сеть (Admitad и др.)
 * засчитывала это как клики: ~194 клика за 60 дней при 0 кликах людей в GA4.
 * Теперь все партнёрские ссылки идут через /go/<id>, и здесь мы отдаём 302 на
 * партнёра ТОЛЬКО если переход пришёл с нашей страницы (реальный клик браузера),
 * а боту по прямому URL — 204 без Location (клик не засчитывается).
 *
 * Это НЕ клоакинг: href, анкор и пометка «Реклама» одинаковы для всех, место
 * назначения у человека то же, что и было. Мы лишь не выдаём трекнутый редирект
 * тому, кто не пришёл с сайта. Цена: ссылка /go, вставленная прямо в адресную
 * строку или открытая со стороннего сайта, никуда не ведёт — но эти ссылки
 * noindex + Disallow и существуют, чтобы по ним кликали с наших страниц.
 *
 * Маршрут даёт .htaccess:  ^go/([A-Za-z0-9_-]+)/?$ → go.php?to=$1 [QSA]
 */

require_once __DIR__ . '/includes/affiliate.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
header('Referrer-Policy: strict-origin-when-cross-origin');

$id  = isset($_GET['to']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['to']) : '';
$url = $id !== '' ? domexpert_affiliate_url_by_id($id) : null;

if (!$url) {
  http_response_code(404);
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Not found';
  exit;
}

// Пришёл ли переход С НАШЕЙ страницы. Боты с резидентных прокси представляются
// Chrome, поэтому UA-фильтр вторичен; главный сигнал — Sec-Fetch-Site / Referer
// (их краулер по списку URL не подделывает). Браузеры с ~2020 шлют Sec-Fetch-Site
// на каждой навигации, а Referer на нашем хосте есть, т.к. ссылки идут с
// rel="...noopener" БЕЗ noreferrer.
$site = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
$mode = strtolower($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '');
$ua   = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ref  = $_SERVER['HTTP_REFERER'] ?? '';
$secPurpose    = strtolower($_SERVER['HTTP_SEC_PURPOSE'] ?? '');
$legacyPurpose = strtolower($_SERVER['HTTP_PURPOSE'] ?? ($_SERVER['HTTP_X_MOZ'] ?? ($_SERVER['HTTP_X_PURPOSE'] ?? '')));
$secUser       = $_SERVER['HTTP_SEC_FETCH_USER'] ?? null;

$ourHosts = ['prodom-expert.ru', 'www.prodom-expert.ru',
             'prodom-expert.com', 'www.prodom-expert.com', 'prodom-expert.pages.dev'];
$sameHost = false;
if ($ref !== '') {
  $h = parse_url($ref, PHP_URL_HOST);
  if ($h) { $sameHost = in_array(strtolower($h), $ourHosts, true); }
}
// Предзагрузка/предрендер браузера — не клик человека (главный источник накрутки).
$isPrefetch   = (bool) preg_match('/prefetch|prerender/', $secPurpose)
             || (bool) preg_match('/prefetch|prerender|preview/', $legacyPurpose);
$cameFromUs   = ($site === 'same-origin' || $site === 'same-site' || $sameHost);
$looksLikeBot = (stripos($ua, 'Mozilla') === false)
             || preg_match('/\b(bot|crawler|spider|scrape|preview|headless|monitor|fetch|curl|wget|python|axios|java|go-http|okhttp|libwww)\b/i', $ua);
// Не навигация верхнего уровня (fetch/XHR/iframe/скрипт) — не клик по ссылке.
$notNavigation = ($mode !== '' && $mode !== 'navigate');
// Навигация без жеста пользователя: реальный клик = Sec-Fetch-User ?1. При mode=navigate
// браузер человека шлёт ?1; его отсутствие/иное значение = не клик (скрипт/бот). Старые
// браузеры без Sec-Fetch шлют mode='' → сюда не попадают (ветка $notNavigation выше их пускает).
$noUserGesture = ($mode === 'navigate' && $secUser !== '?1');

if ($isPrefetch || $looksLikeBot || !$cameFromUs || $notNavigation || $noUserGesture) {
  http_response_code(204);   // без Location — сеть не засчитает это как клик
  exit;
}

// subid — помечаем страницу-источник (Admitad поддерживает subid). Добавляем
// только для Admitad-deeplink (путь /g/ или /c/), чтобы не ломать ссылки других
// сетей (Indoleads и пр. — другой формат меток).
$p = isset($_GET['p']) ? preg_replace('/[^A-Za-z0-9_-]/', '', (string) $_GET['p']) : '';
if ($p !== '') {
  $path = (string) parse_url($url, PHP_URL_PATH);
  $host = strtolower((string) parse_url($url, PHP_URL_HOST));
  if (preg_match('#/[gc]/#', $path)) {
    $url .= (strpos($url, '?') !== false ? '&' : '?') . 'subid=' . rawurlencode($p);
  } elseif (strpos($host, 'zaim-srochno24') !== false) {
    // Leads.su: метка страницы — параметр aff_sub1.
    $url .= (strpos($url, '?') !== false ? '&' : '?') . 'aff_sub1=' . rawurlencode($p);
  }
}

header('Location: ' . $url, true, 302);
exit;
