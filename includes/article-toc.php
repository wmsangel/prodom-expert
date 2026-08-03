<?php
declare(strict_types=1);

/**
 * includes/article-toc.php — оглавление статьи.
 *
 * Тексты статей в /articles/ лежат как HTML-фрагменты без якорей: <h2>Заголовок</h2>.
 * Здесь якоря проставляются на лету при выводе, поэтому 194 файла статей править
 * не нужно и новые статьи получают оглавление автоматически.
 */

/** Транслитерация заголовка в якорь: «Шаг лаг и толщина настила» → «shag-lag-i-tolshchina-nastila». */
function domexpert_slugify_heading(string $text): string
{
  static $map = [
    'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z',
    'и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r',
    'с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch',
    'ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
  ];
  $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $text = mb_strtolower(trim($text), 'UTF-8');
  $text = strtr($text, $map);
  $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
  $text = trim($text, '-');
  // Длинные якоря нечитаемы в адресной строке — обрезаем по границе слова.
  if (strlen($text) > 60) {
    $text = substr($text, 0, 60);
    $cut  = strrpos($text, '-');
    if ($cut !== false && $cut > 20) {
      $text = substr($text, 0, $cut);
    }
    $text = trim($text, '-');
  }
  return $text !== '' ? $text : 'razdel';
}

/**
 * Проставляет id заголовкам h2/h3 и собирает оглавление.
 *
 * @return array{html: string, toc: array<int, array{id: string, title: string}>}
 *         toc — только разделы h2: подпункты h3 сделали бы список длиннее самой статьи,
 *         но якорь они получают тоже — на вопрос из FAQ можно дать прямую ссылку.
 */
function domexpert_article_toc(string $html): array
{
  $used = [];
  $toc  = [];

  $addAnchor = static function (array $m) use (&$used, &$toc): string {
    $level = $m[1];
    $inner = $m[2];
    $title = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($title === '') {
      return $m[0];
    }
    $id = domexpert_slugify_heading($title);
    if (isset($used[$id])) {
      $id .= '-' . (++$used[$id]);   // одинаковые заголовки в одной статье
    } else {
      $used[$id] = 1;
    }
    if ($level === '2') {
      $toc[] = ['id' => $id, 'title' => $title];
    }
    return '<h' . $level . ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $inner . '</h' . $level . '>';
  };

  $processed = preg_replace_callback('~<h([23])>(.*?)</h\1>~su', $addAnchor, $html);

  return [
    'html' => $processed ?? $html,
    'toc'  => $toc,
  ];
}
