<?php
/**
 * includes/partner-ads.php
 * Кросс-промо собственных проектов автора. Пока нет AdSense — эти блоки
 * занимают рекламные места и ведут трафик на другие сайты проекта.
 *
 * Честность перед читателем: в футере обещано, что рекламные блоки помечены
 * отдельно, поэтому у каждого блока есть подпись «Наш проект», а ссылки идут
 * с rel="sponsored nofollow noopener" и target="_blank".
 *
 * Использование:
 *   require_once __DIR__ . '/partner-ads.php';
 *   echo domexpert_partner_ad('banner', 'santehnika');   // горизонтальный баннер
 *   echo domexpert_partner_ad('card', 'santehnika', 1);   // карточка в сайдбар
 *
 * $seed — любая строка (обычно слаг статьи): по ней детерминированно
 * выбирается партнёр, поэтому на одной странице блок стабилен и дружелюбен к кэшу,
 * а на разных страницах показываются разные проекты.
 * $offset — сдвиг в списке, чтобы сайдбар и баннер на одной странице
 * показывали разные проекты.
 */

if (!function_exists('domexpert_partners')) {
  /**
   * Реестр промо-проектов. prodom-expert.ru сюда НЕ входит — это и есть
   * продакшн-адрес самого сайта (SITE_CANONICAL), рекламировать его на нём же незачем.
   */
  function domexpert_partners(): array {
    return [
      [
        'id'    => 'zdorovie',
        'title' => '24Здоровье',
        'desc'  => 'Доказательная медицина без мифов: питание, сон, движение и понятные протоколы.',
        'cta'   => 'Проверить здоровье',
        'url'   => 'https://24zdorovie.com',
        'icon'  => '🩺',
      ],
      [
        'id'    => 'calclumen',
        'title' => 'CalcLumen',
        'desc'  => '48 бесплатных онлайн-калькуляторов: финансы, здоровье, авто и быт.',
        'cta'   => 'Открыть калькуляторы',
        'url'   => 'https://calclumen.com',
        'icon'  => '🧮',
      ],
      [
        'id'    => 'cryptotools',
        'title' => 'TheCryptoTools',
        'desc'  => '69+ бесплатных крипто-калькуляторов: прибыль, риск, портфель. Без регистрации.',
        'cta'   => 'Посчитать крипту',
        'url'   => 'https://thecryptotools.com',
        'icon'  => '📊',
      ],
      [
        'id'    => 'costtrek',
        'title' => 'CostTrek',
        'desc'  => 'Сравнение стоимости жизни в городах мира: аренда, налоги и зарплата под переезд.',
        'cta'   => 'Сравнить города',
        'url'   => 'https://costtrek.com',
        'icon'  => '🌍',
      ],
      [
        'id'    => 'iznkit',
        'title' => 'iznkit',
        'desc'  => 'Генераторы PDF и калькуляторы: счета, сметы, налоги, QR — без регистрации.',
        'cta'   => 'Собрать документ',
        'url'   => 'https://iznkit.com',
        'icon'  => '📄',
      ],
      [
        'id'    => 'izngames',
        'title' => 'izn.games',
        'desc'  => 'Бесплатные браузерные игры — без загрузок и регистрации, играй сразу.',
        'cta'   => 'Играть',
        'url'   => 'https://izngames.com',
        'icon'  => '🎮',
      ],
      [
        'id'    => 'izntools',
        'title' => 'IZN Tools',
        'desc'  => '100 бесплатных онлайн-инструментов: сжатие фото, форматирование JSON, SEO-разметка, генераторы. Всё в браузере.',
        'cta'   => 'Открыть инструменты',
        'url'   => 'https://izntools.com',
        'icon'  => '🧰',
      ],
    ];
  }
}

if (!function_exists('domexpert_pick_partner')) {
  /**
   * Детерминированный выбор партнёра по строке-сиду со сдвигом.
   */
  function domexpert_pick_partner(string $seed = '', int $offset = 0): array {
    $partners = domexpert_partners();
    $n = count($partners);
    if ($n === 0) {
      return [];
    }
    $base = $seed !== '' ? (crc32($seed) % $n) : 0;
    $idx  = (($base + $offset) % $n + $n) % $n;
    return $partners[$idx];
  }
}

if (!function_exists('domexpert_partner_ad')) {
  /**
   * Рендерит промо-блок.
   * @param string $format 'banner' (горизонтальный) или 'card' (для сайдбара)
   * @param string $seed   строка для стабильного выбора проекта (обычно слаг)
   * @param int    $offset сдвиг в списке проектов
   */
  function domexpert_partner_ad(string $format = 'banner', string $seed = '', int $offset = 0): string {
    $p = domexpert_pick_partner($seed, $offset);
    if (empty($p)) {
      return '';
    }

    $url   = htmlspecialchars($p['url'],   ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8');
    $desc  = htmlspecialchars($p['desc'],  ENT_QUOTES, 'UTF-8');
    $cta   = htmlspecialchars($p['cta'],   ENT_QUOTES, 'UTF-8');
    $icon  = htmlspecialchars($p['icon'],  ENT_QUOTES, 'UTF-8');
    $rel   = 'sponsored nofollow noopener';

    if ($format === 'card') {
      return <<<HTML
<div class="sidebar-widget partner-widget">
  <span class="partner-label">Наш проект</span>
  <a class="partner-card" href="{$url}" target="_blank" rel="{$rel}">
    <span class="partner-card-icon" aria-hidden="true">{$icon}</span>
    <span class="partner-card-body">
      <span class="partner-card-title">{$title}</span>
      <span class="partner-card-desc">{$desc}</span>
      <span class="partner-card-cta">{$cta} →</span>
    </span>
  </a>
</div>
HTML;
    }

    // format === 'banner'
    return <<<HTML
<aside class="partner-banner" aria-label="Рекламный блок — наш проект">
  <span class="partner-banner-label">Реклама · наш проект</span>
  <a class="partner-banner-link" href="{$url}" target="_blank" rel="{$rel}">
    <span class="partner-banner-icon" aria-hidden="true">{$icon}</span>
    <span class="partner-banner-text">
      <strong class="partner-banner-title">{$title}</strong>
      <span class="partner-banner-desc">{$desc}</span>
    </span>
    <span class="partner-banner-cta">{$cta} →</span>
  </a>
</aside>
HTML;
  }
}
