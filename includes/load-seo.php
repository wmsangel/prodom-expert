<?php
/**
 * Подключает seo-config.php при наличии и гарантирует SITE_CANONICAL
 * (если файл не залит на сервер или кэш «видит» старую версию — сайт не падает с 500).
 */
if (!defined('SITE_CANONICAL')) {
  $__seoCfg = __DIR__ . '/seo-config.php';
  if (is_file($__seoCfg)) {
    require_once $__seoCfg;
  }
  if (!defined('SITE_CANONICAL')) {
    define('SITE_CANONICAL', 'https://prodom-expert.ru');
  }
}

/**
 * Режим управления согласием (CMP). Не должно быть ДВУХ CMP одновременно.
 *   'own'   — собственный баннер cookie-consent.php + Google Consent Mode v2 (текущий).
 *   'ezoic' — рекламу и согласие (GDPR/CCPA, IAB-TCF) ведёт сертифицированный CMP Ezoic.
 * Переключить на 'ezoic', КОГДА Ezoic активен: тогда свой баннер прячется
 * (см. footer.php), чтобы два CMP не конфликтовали. По умолчанию — 'own'.
 */
if (!defined('DOMEXPERT_CMP')) {
  define('DOMEXPERT_CMP', 'own');
}

/**
 * ЧПУ-адреса. Единая точка формирования ссылок на статьи и категории —
 * правьте схему здесь, а не в шаблонах. Реальные скрипты (article.php / category.php)
 * подключаются через mod_rewrite в .htaccess; сюда же ведут 301 со старых ?slug=/?cat=.
 */
if (!function_exists('du_article_path')) {
  function du_article_path(string $slug): string {
    return '/article/' . rawurlencode($slug) . '/';
  }
}

/**
 * Адрес статического файла с меткой версии: /assets/js/planner.js?v=1786…
 *
 * Сервер отдаёт скрипты и стили с длинным max-age, поэтому без такой метки
 * обновлённый файл не доезжает до тех, кто уже был на сайте: браузер год
 * держит старую копию и даже не спрашивает сервер. Метка берётся из времени
 * изменения файла — меняется сама при каждой заливке.
 */
if (!function_exists('du_asset')) {
  function du_asset(string $path): string {
    $file = dirname(__DIR__) . $path;
    $ver  = is_file($file) ? filemtime($file) : null;
    return $ver ? $path . '?v=' . $ver : $path;
  }
}
if (!function_exists('du_category_path')) {
  function du_category_path(string $cat, int $page = 1): string {
    $cat = rawurlencode($cat);
    return $page > 1 ? "/category/{$cat}/page/{$page}/" : "/category/{$cat}/";
  }
}
if (!function_exists('du_article_url')) {
  function du_article_url(string $slug): string {
    return SITE_CANONICAL . du_article_path($slug);
  }
}
if (!function_exists('du_category_url')) {
  function du_category_url(string $cat, int $page = 1): string {
    return SITE_CANONICAL . du_category_path($cat, $page);
  }
}

if (!function_exists('domexpert_org_id')) {
  function domexpert_org_id(): string {
    return SITE_CANONICAL . '/#organization';
  }
}

if (!function_exists('domexpert_website_id')) {
  function domexpert_website_id(): string {
    return SITE_CANONICAL . '/#website';
  }
}

/**
 * Извлекает блок «Частые вопросы» из HTML-фрагмента статьи и возвращает пары
 * [['q' => вопрос, 'a' => ответ], …] для генерации FAQPage-разметки.
 * Ищет <h2>…Частые вопрос…</h2>, затем пары <h3>вопрос</h3> + текст до следующего <h3>/<h2>.
 */
if (!function_exists('domexpert_extract_faq')) {
  function domexpert_extract_faq(string $html): array {
    if ($html === '' || stripos($html, 'Частые вопрос') === false) {
      return [];
    }
    // Блок от заголовка «Частые вопросы» до следующего <h2> (или конца).
    if (!preg_match('/<h2[^>]*>\s*Частые вопрос[^<]*<\/h2>(.*?)(?:<h2\b|$)/isu', $html, $block)) {
      return [];
    }
    if (!preg_match_all('/<h3[^>]*>(.*?)<\/h3>(.*?)(?=<h3\b|$)/isu', $block[1], $pairs, PREG_SET_ORDER)) {
      return [];
    }
    $clean = static function (string $s): string {
      $s = preg_replace('/<[^>]+>/u', ' ', $s);        // убрать теги
      $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $s = preg_replace('/\s+/u', ' ', $s);            // схлопнуть пробелы
      return trim($s);
    };
    $faq = [];
    foreach ($pairs as $p) {
      $q = $clean($p[1]);
      $a = $clean($p[2]);
      if ($q !== '' && $a !== '') {
        $faq[] = ['q' => $q, 'a' => $a];
      }
    }
    return $faq;
  }
}

/**
 * Укороченное meta description / og:description для сниппетов (без обрезки посередине слова).
 */
if (!function_exists('domexpert_trim_meta_description')) {
  function domexpert_trim_meta_description(string $text, int $max = 165): string {
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
      return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text) <= $max) {
      return $text;
    }
    if (!function_exists('mb_strlen') && strlen($text) <= $max) {
      return $text;
    }

    $len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($len <= $max) {
      return $text;
    }

    $substr   = function_exists('mb_substr') ? 'mb_substr' : 'substr';
    $strrpos  = function_exists('mb_strrpos') ? 'mb_strrpos' : 'strrpos';
    $cut      = $substr($text, 0, max(1, $max - 1));
    $lastSp   = $strrpos($cut, ' ');
    if ($lastSp !== false && $lastSp > (int) ($max * 0.55)) {
      $cut = $substr($cut, 0, $lastSp);
    }
    return rtrim($cut, " \t\n\r\0\x0B,-;:") . '…';
  }
}
