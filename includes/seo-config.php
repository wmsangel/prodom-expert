<?php
/**
 * Единый канонический домен для canonical, Open Graph, JSON-LD, RSS и sitemap.
 * При смене основного домена правьте только значение ниже.
 */
if (!defined('SITE_CANONICAL')) {
  define('SITE_CANONICAL', 'https://prodom-expert.ru');
}

/**
 * Профили сайта во внешних сервисах — уходят в sameAs у Organization.
 * Яндекс использует их для связки сайта с соцсетями и подтверждения принадлежности.
 * Впишите реальные адреса (VK, Telegram, Дзен, YouTube). Пустой список ничего не ломает:
 * свойство sameAs просто не попадает в разметку.
 */
if (!defined('DOMEXPERT_SOCIAL_PROFILES')) {
  define('DOMEXPERT_SOCIAL_PROFILES', json_encode([
    // 'https://vk.com/…',
    // 'https://t.me/…',
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
