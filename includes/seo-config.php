<?php
/**
 * Единый канонический домен для canonical, Open Graph, JSON-LD, RSS и sitemap.
 * При смене основного домена правьте только значение ниже.
 */
if (!defined('SITE_CANONICAL')) {
  define('SITE_CANONICAL', 'https://prodom-expert.ru');
}

/**
 * Google AdSense. Loader подключён в header.php. Ручные блоки расставляются по
 * карте плотности (includes/ads.php, du_ad/du_inject_ads).
 *   CLIENT — издатель (ca-pub-…).
 *   SLOT   — id рекламного блока из кабинета AdSense (Медийный блок → data-ad-slot).
 *            Пока пусто — на проде блоки НЕ выводятся (безопасно), включатся как
 *            только впишете slot. Для предпросмотра расстановки см. DOMEXPERT_ADS_PREVIEW.
 */
if (!defined('DOMEXPERT_ADSENSE_CLIENT')) define('DOMEXPERT_ADSENSE_CLIENT', 'ca-pub-5535516142831006');
if (!defined('DOMEXPERT_ADSENSE_SLOT'))   define('DOMEXPERT_ADSENSE_SLOT', '6468792657');
/** true → вместо реальных блоков рисуются видимые плейсхолдеры (для оценки расстановки). */
if (!defined('DOMEXPERT_ADS_PREVIEW'))    define('DOMEXPERT_ADS_PREVIEW', false);

/**
 * Профили сайта во внешних сервисах — уходят в sameAs у Organization.
 * Яндекс использует их для связки сайта с соцсетями и подтверждения принадлежности.
 * Впишите реальные адреса (VK, Telegram, Дзен, YouTube). Пустой список ничего не ломает:
 * свойство sameAs просто не попадает в разметку.
 */
if (!defined('DOMEXPERT_SOCIAL_PROFILES')) {
  define('DOMEXPERT_SOCIAL_PROFILES', json_encode([
    'https://t.me/prodom_expert',
    // 'https://vk.com/…',
    // 'https://dzen.ru/…',
  ]));
}

/** Публичный контактный адрес — печатается в разметке Organization. */
if (!defined('DOMEXPERT_CONTACT_EMAIL')) {
  define('DOMEXPERT_CONTACT_EMAIL', 'info@prodom-expert.ru');
}

/** Список профилей как массив; пустой, если ничего не задано. */
if (!function_exists('domexpert_social_profiles')) {
  function domexpert_social_profiles(): array {
    $list = json_decode(DOMEXPERT_SOCIAL_PROFILES, true);
    return is_array($list) ? array_values(array_filter($list)) : [];
  }
}
