<?php
/**
 * includes/self-promo.php — собственные промо-блоки ДомЭксперт.
 * Подсвечивают СВОИ разделы (калькуляторы, словарь, виджеты, донат, TG) — это и
 * удержание на сайте, и внутренняя перелинковка (do-follow на важные страницы).
 *
 * Ротация по неделям: всю неделю показывается одна цель, со следующей недели — другая
 * (детерминированно по номеру ISO-недели, дружелюбно к кэшу). Текущая страница из
 * показа исключается (не рекламируем страницу на ней же).
 *
 * Отличие от partner-ads.php: тот ведёт на ДРУГИЕ сайты сети (nofollow, увод трафика),
 * а этот — на НАШИ страницы (do-follow, удержание).
 */

require_once __DIR__ . '/load-seo.php';

if (!function_exists('domexpert_self_promo_targets')) {
  /** Цели промо в порядке ротации. Правьте здесь. */
  function domexpert_self_promo_targets(): array {
    return [
      ['icon' => '🧮', 'title' => 'Планировщик ремонта',
       'desc' => 'Нарисуйте план квартиры — получите площади, материалы и смету по этапам.',
       'cta'  => 'Открыть планировщик', 'url' => '/calc/planirovshchik-remonta/'],
      ['icon' => '💰', 'title' => 'Смета на ремонт',
       'desc' => 'Прикиньте стоимость ремонта по комнатам и этапам работ за пару минут.',
       'cta'  => 'Посчитать смету', 'url' => '/calc/smeta-remonta/'],
      ['icon' => '📖', 'title' => 'Словарь терминов',
       'desc' => 'Стяжка, УЗО, керамогранит и другие слова ремонта — простыми словами.',
       'cta'  => 'Открыть словарь', 'url' => '/slovar/'],
      ['icon' => '🧩', 'title' => 'Калькуляторы для сайта',
       'desc' => 'Встройте наши калькуляторы к себе на сайт — бесплатно, одним кодом.',
       'cta'  => 'Посмотреть виджеты', 'url' => '/vidzhety/'],
      ['icon' => '✈️', 'title' => 'Телеграм-канал',
       'desc' => 'Новые разборы, расчёты и лайфхаки по ремонту — коротко, в Telegram.',
       'cta'  => 'Подписаться', 'url' => 'https://t.me/prodom_expert', 'ext' => true],
      ['icon' => '☕', 'title' => 'Поддержать проект',
       'desc' => 'ДомЭксперт держится на энтузиазме. Если материалы помогли — спасибо.',
       'cta'  => 'Поддержать', 'url' => '/podderzhat.php'],
    ];
  }
}

if (!function_exists('domexpert_self_promo_pick')) {
  /** Цель недели; если совпадает с текущей страницей — берём следующую. */
  function domexpert_self_promo_pick(string $currentPath = ''): array {
    $t = domexpert_self_promo_targets();
    $n = count($t);
    if ($n === 0) { return []; }
    $idx = ((int) date('W')) % $n;
    $cur = rtrim($currentPath, '/');
    for ($i = 0; $i < $n; $i++) {
      $cand = $t[($idx + $i) % $n];
      if ($cur === '' || rtrim($cand['url'], '/') !== $cur) {
        return $cand;
      }
    }
    return $t[$idx];
  }
}

if (!function_exists('domexpert_self_promo_block')) {
  /**
   * Рендер промо-блока.
   * @param string $currentPath путь текущей страницы (для исключения самопромо)
   * @param bool   $sticky      липнуть при прокрутке (для сайдбара на десктопе)
   */
  function domexpert_self_promo_block(string $currentPath = '', bool $sticky = true): string {
    $p = domexpert_self_promo_pick($currentPath);
    if (empty($p)) { return ''; }
    $ext  = !empty($p['ext']);
    $rel  = $ext ? ' target="_blank" rel="noopener"' : '';
    $cls  = 'selfpromo' . ($sticky ? ' selfpromo--sticky' : '');
    $url  = htmlspecialchars($p['url'],   ENT_QUOTES, 'UTF-8');
    $icon = htmlspecialchars($p['icon'],  ENT_QUOTES, 'UTF-8');
    $ttl  = htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8');
    $desc = htmlspecialchars($p['desc'],  ENT_QUOTES, 'UTF-8');
    $cta  = htmlspecialchars($p['cta'],   ENT_QUOTES, 'UTF-8');
    return <<<HTML
<aside class="{$cls}" aria-label="Рекомендуем на ДомЭксперт">
  <span class="selfpromo-badge">Рекомендуем</span>
  <span class="selfpromo-icon" aria-hidden="true">{$icon}</span>
  <span class="selfpromo-title">{$ttl}</span>
  <span class="selfpromo-desc">{$desc}</span>
  <a class="selfpromo-cta" href="{$url}"{$rel} data-track="selfpromo_click" data-net="{$url}">{$cta} →</a>
</aside>
HTML;
  }
}
