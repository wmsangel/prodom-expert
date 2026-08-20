<?php
/**
 * includes/affiliate.php
 * Партнёрские блоки (Admitad и др.) — «Где купить» на страницах с коммерческим
 * интентом: калькуляторы и статьи про материалы.
 *
 * Пока реестр пуст — на сайте НИЧЕГО не показывается (никаких заглушек).
 * Как появятся офферы: добавь строку в domexpert_affiliate_offers() с deeplink-
 * ссылкой из Admitad — блок появится сам на страницах с подходящим тегом.
 *
 * Ссылки идут с rel="sponsored nofollow noopener" и target="_blank" — это
 * правильная разметка партнёрских ссылок для поисковиков.
 *
 * Использование:
 *   require_once __DIR__ . '/affiliate.php';
 *   echo domexpert_affiliate_block(['materialy']);      // блок под тегом
 *   echo domexpert_affiliate_block(['plitka','materialy'], 2);
 */

if (!function_exists('domexpert_affiliate_offers')) {
  /**
   * Реестр партнёрских офферов. Каждый оффер:
   *   'id'    — короткий идентификатор
   *   'title' — название магазина/оффера
   *   'desc'  — короткое пояснение (1 строка)
   *   'cta'   — текст кнопки
   *   'url'   — ПАРТНЁРСКАЯ (deeplink) ссылка из Admitad
   *   'icon'  — эмодзи
   *   'tags'  — на каких страницах показывать: 'all' — везде, либо тематические
   *             теги ('materialy','plitka','kraska','oboi','laminat','kabel',
   *             'instrument','mebel','santehnika','okna','finansy','strahovanie')
   *
   * ПРИМЕР (раскомментируй и подставь свою deeplink-ссылку):
   *
   *   ['id' => 'ozon-materialy',
   *    'title' => 'Ozon',
   *    'desc'  => 'Стройматериалы, инструмент и техника с доставкой.',
   *    'cta'   => 'Смотреть на Ozon',
   *    'url'   => 'https://ad.admitad.com/g/ВАША_ССЫЛКА/',
   *    'icon'  => '🛒',
   *    'tags'  => ['all']],
   */
  function domexpert_affiliate_offers(): array {
    return [
      // Пусто. Добавь офферы сюда — блоки появятся автоматически.
    ];
  }
}

if (!function_exists('domexpert_affiliate_pick')) {
  /**
   * Выбор офферов под теги страницы. Оффер подходит, если у него есть тег 'all'
   * или пересечение с $tags. Порядок реестра сохраняется.
   */
  function domexpert_affiliate_pick(array $tags = [], int $limit = 2): array {
    $picked = [];
    foreach (domexpert_affiliate_offers() as $o) {
      if (empty($o['url'])) { continue; }
      $ot = $o['tags'] ?? ['all'];
      if (in_array('all', $ot, true) || array_intersect($ot, $tags)) {
        $picked[] = $o;
        if (count($picked) >= $limit) { break; }
      }
    }
    return $picked;
  }
}

if (!function_exists('domexpert_affiliate_block')) {
  /**
   * Рендерит блок «Где купить». Возвращает '' если подходящих офферов нет —
   * поэтому вызов безопасен на любой странице.
   */
  function domexpert_affiliate_block(array $tags = [], int $limit = 2): string {
    $offers = domexpert_affiliate_pick($tags, $limit);
    if (!$offers) { return ''; }

    $rel = 'sponsored nofollow noopener';
    $items = '';
    foreach ($offers as $o) {
      $url   = htmlspecialchars($o['url'],   ENT_QUOTES, 'UTF-8');
      $title = htmlspecialchars($o['title'], ENT_QUOTES, 'UTF-8');
      $desc  = htmlspecialchars($o['desc'] ?? '', ENT_QUOTES, 'UTF-8');
      $cta   = htmlspecialchars($o['cta'] ?? 'Перейти', ENT_QUOTES, 'UTF-8');
      $icon  = htmlspecialchars($o['icon'] ?? '🛒', ENT_QUOTES, 'UTF-8');
      $items .= <<<HTML
    <a class="aff-item" href="{$url}" target="_blank" rel="{$rel}">
      <span class="aff-icon" aria-hidden="true">{$icon}</span>
      <span class="aff-text"><strong class="aff-title">{$title}</strong><span class="aff-desc">{$desc}</span></span>
      <span class="aff-cta">{$cta} →</span>
    </a>

HTML;
    }

    return <<<HTML
<aside class="aff-block" aria-label="Партнёрские магазины">
  <span class="aff-label">Реклама · где купить</span>
{$items}</aside>
HTML;
  }
}
