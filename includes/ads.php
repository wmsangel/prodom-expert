<?php
/**
 * includes/ads.php — размещение рекламных блоков AdSense по карте плотности.
 *
 * Карта (решение владельца 2026-10-02, «умеренно, где больше/меньше»):
 *   статьи   — по объёму: <800 слов → 1, 800–1800 → 2, ≥1800 → 3 блока в теле + сайдбар;
 *   главная/категории — 1 блок + сайдбар;
 *   калькулятор       — 1 блок;
 *   служебные/лендинги — без рекламы ($showAds=false до include header).
 * РСЯ-автоплейсмент работает параллельно (header.php) — это осознанный выбор.
 *
 * Страница может отключить рекламу: до подключения header задать $showAds = false.
 */

require_once __DIR__ . '/load-seo.php';

/** Разрешена ли реклама на текущей странице (служебные выключают через $showAds=false). */
if (!function_exists('du_ads_enabled')) {
  function du_ads_enabled(): bool {
    return !isset($GLOBALS['showAds']) || $GLOBALS['showAds'] !== false;
  }
}

/** Один рекламный блок (AdSense ins). Пустая строка, если реклама выключена или slot не задан. */
if (!function_exists('du_ad')) {
  function du_ad(string $label = ''): string {
    if (!du_ads_enabled()) {
      return '';
    }
    $slot   = defined('DOMEXPERT_ADSENSE_SLOT') ? DOMEXPERT_ADSENSE_SLOT : '';
    $preview = defined('DOMEXPERT_ADS_PREVIEW') && DOMEXPERT_ADS_PREVIEW;

    if ($slot === '') {
      if ($preview) {
        $cap = $label !== '' ? ' · ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') : '';
        return '<div class="ad-slot ad-slot--preview" aria-hidden="true">Рекламный блок' . $cap . '</div>';
      }
      return ''; // slot ещё не настроен — на проде ничего не выводим
    }

    $client = defined('DOMEXPERT_ADSENSE_CLIENT') ? DOMEXPERT_ADSENSE_CLIENT : '';
    $c = htmlspecialchars($client, ENT_QUOTES, 'UTF-8');
    $s = htmlspecialchars($slot, ENT_QUOTES, 'UTF-8');
    return '<div class="ad-slot">'
         . '<ins class="adsbygoogle" style="display:block" data-ad-client="' . $c . '" '
         . 'data-ad-slot="' . $s . '" data-ad-format="auto" data-full-width-responsive="true"></ins>'
         . '<script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>'
         . '</div>';
  }
}

/** Число блоков в теле статьи по объёму текста. */
if (!function_exists('du_article_ad_count')) {
  function du_article_ad_count(string $html): int {
    $words = str_word_count(strip_tags($html), 0, 'абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ');
    if ($words >= 1800) return 3;
    if ($words >= 800)  return 2;
    return 1;
  }
}

/**
 * Вставляет $n рекламных блоков в тело статьи, равномерно между разделами <h2>.
 * Не ставит перед первым разделом (реклама не до начала чтения) и перед последним
 * (обычно «Частые вопросы»/«Итог»). Если <h2> мало — вставляет один блок в середину по </p>.
 */
if (!function_exists('du_inject_ads')) {
  function du_inject_ads(string $html, int $n): string {
    if ($n < 1 || !du_ads_enabled()) {
      return $html;
    }
    // позиции начала всех <h2
    $pos = [];
    $off = 0;
    while (($p = stripos($html, '<h2', $off)) !== false) { $pos[] = $p; $off = $p + 3; }

    if (count($pos) >= 3) {
      // кандидаты: со второго <h2> по предпоследний
      $cand = array_slice($pos, 1, count($pos) - 2);
      if (empty($cand)) { $cand = array_slice($pos, 1); }
      $n = min($n, count($cand));
      $chosen = [];
      for ($i = 1; $i <= $n; $i++) {
        $idx = (int) floor($i * count($cand) / ($n + 1));
        $chosen[$cand[min($idx, count($cand) - 1)]] = true;
      }
      $points = array_keys($chosen);
      rsort($points); // вставляем с конца, чтобы смещения не сбивались
      foreach ($points as $p) {
        $html = substr($html, 0, $p) . du_ad('в статье') . substr($html, $p);
      }
      return $html;
    }

    // Мало разделов: один блок примерно в середину по границе абзаца.
    $paras = [];
    $off = 0;
    while (($p = stripos($html, '</p>', $off)) !== false) { $paras[] = $p + 4; $off = $p + 4; }
    if (count($paras) >= 2) {
      $mid = $paras[(int) floor(count($paras) / 2)];
      $html = substr($html, 0, $mid) . du_ad('в статье') . substr($html, $mid);
    }
    return $html;
  }
}
