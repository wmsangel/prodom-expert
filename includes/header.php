<?php
/**
 * includes/header.php — SEO-оптимизированная шапка
 * Переменные (задаются до include):
 *   $pageTitle, $pageDesc, $pageUrl, $ogTitle, $ogDesc, $ogImage
 *   $articleJsonLd  — (опционально) готовый JSON-LD для статьи / WebPage
 *   $breadcrumbJsonLd — (опционально) JSON-LD для хлебных крошек
 *   $listingJsonLd — (опционально) JSON-LD ItemList для листингов категорий
 *   $robotsMeta — (опционально) значение meta robots (по умолчанию index,follow…)
 */

require_once __DIR__ . '/load-seo.php';

$siteName   = 'ДомЭксперт';
// Аккаунт в соцсети: пустая строка — тег twitter:site не выводится.
// Указывать здесь несуществующий handle не нужно: он ведёт в никуда и
// расходится с доменом сайта.
$twitterHandle = '';
$logoSchemaUrl = SITE_CANONICAL . '/assets/img/og-default.jpg';

$__uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$reqPath = strtok($__uri, '?');
if ($reqPath === false || $reqPath === '') {
  $reqPath = '/';
}
$pageTitle = isset($pageTitle) ? $pageTitle : 'ДомЭксперт — Всё о ремонте и обустройстве дома';
$pageDesc  = isset($pageDesc)  ? $pageDesc  : 'Практические советы по ремонту квартиры, выбору окон, сантехнике, электрике и дизайну интерьера. Сделайте ваш дом уютным вместе с ДомЭксперт.';
$pageUrl   = isset($pageUrl)   ? $pageUrl   : SITE_CANONICAL . $reqPath;
$ogTitle   = isset($ogTitle)   ? $ogTitle   : $pageTitle;
$ogDesc    = isset($ogDesc)    ? $ogDesc    : $pageDesc;
$ogImage   = isset($ogImage)   ? $ogImage   : SITE_CANONICAL . '/assets/img/og-default.jpg';
$robotsContent = isset($robotsMeta) ? $robotsMeta : 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';

$pageDesc = domexpert_trim_meta_description($pageDesc);
$ogDesc   = domexpert_trim_meta_description($ogDesc);

$__ogW = isset($ogImageWidth) ? max(1, (int) $ogImageWidth) : 1200;
$__ogH = isset($ogImageHeight) ? max(1, (int) $ogImageHeight) : 630;
$__ogImgAlt = isset($ogImageAlt) ? $ogImageAlt : $ogTitle;

// Очистка для атрибутов (function_exists — на случай повторного подключения шапки)
if (!function_exists('esc')) {
  function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}
?>
<!DOCTYPE html>
<html lang="ru" prefix="og: https://ogp.me/ns#">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="yandex-verification" content="0d826ddff2bfcc24" />

  <!-- ═══ ОСНОВНЫЕ SEO-ТЕГИ ═══ -->
  <title><?= esc($pageTitle) ?></title>
  <meta name="description"  content="<?= esc($pageDesc) ?>">
  <meta name="robots"       content="<?= esc($robotsContent) ?>">
  <meta name="author"       content="<?= esc($siteName) ?>">
  <meta name="copyright"    content="<?= esc($siteName) ?> <?= date('Y') ?>">
  <meta name="language"     content="ru">
  <link rel="canonical"     href="<?= esc($pageUrl) ?>">
  <link rel="alternate" hreflang="ru-RU" href="<?= esc($pageUrl) ?>">
  <?php if (!empty($preloadLcpImage)): ?>
  <link rel="preload" as="image" href="<?= esc($preloadLcpImage) ?>">
  <?php endif; ?>

  <!-- ═══ OPEN GRAPH ═══ -->
  <meta property="og:type"        content="<?= isset($isArticle) && $isArticle ? 'article' : 'website' ?>">
  <meta property="og:url"         content="<?= esc($pageUrl) ?>">
  <meta property="og:title"       content="<?= esc($ogTitle) ?>">
  <meta property="og:description" content="<?= esc($ogDesc) ?>">
  <meta property="og:image"       content="<?= esc($ogImage) ?>">
  <?php if (strpos((string) $ogImage, 'https://') === 0): ?>
  <meta property="og:image:secure_url" content="<?= esc($ogImage) ?>">
  <?php endif; ?>
  <meta property="og:image:width"  content="<?= esc((string) $__ogW) ?>">
  <meta property="og:image:height" content="<?= esc((string) $__ogH) ?>">
  <meta property="og:image:alt"    content="<?= esc($__ogImgAlt) ?>">
  <meta property="og:site_name"   content="<?= esc($siteName) ?>">
  <meta property="og:locale"      content="ru_RU">
  <?php if (isset($isArticle) && $isArticle && isset($articlePubDate)): ?>
  <meta property="article:published_time" content="<?= esc($articlePubDate) ?>">
  <meta property="article:modified_time"  content="<?= esc($articlePubDate) ?>">
  <meta property="article:section"        content="<?= esc(isset($ogCatLabel) ? $ogCatLabel : '') ?>">
  <?php endif; ?>

  <!-- ═══ TWITTER CARD ═══ -->
  <meta name="twitter:card"        content="summary_large_image">
  <?php if ($twitterHandle !== ''): ?>
  <meta name="twitter:site"        content="<?= esc($twitterHandle) ?>">
  <?php endif; ?>
  <meta name="twitter:title"       content="<?= esc($ogTitle) ?>">
  <meta name="twitter:description" content="<?= esc($ogDesc) ?>">
  <meta name="twitter:image"       content="<?= esc($ogImage) ?>">
  <meta name="twitter:image:alt"  content="<?= esc($__ogImgAlt) ?>">

  <!-- ═══ SCHEMA.ORG — Organization + WebSite (единый граф с @id) ═══ -->
  <script type="application/ld+json">
  <?= json_encode([
    '@context' => 'https://schema.org',
    '@graph'   => [
      array_filter([
        '@type'       => 'Organization',
        '@id'         => domexpert_org_id(),
        'name'        => $siteName,
        'url'         => SITE_CANONICAL . '/',
        'description' => 'Независимое издание о ремонте, инженерных системах и обустройстве жилья: практические руководства с нормами, расчётами и калькуляторами.',
        'logo'        => [
          '@type' => 'ImageObject',
          'url'   => $logoSchemaUrl,
        ],
        'contactPoint' => [
          '@type'             => 'ContactPoint',
          'contactType'       => 'editorial',
          'email'             => DOMEXPERT_CONTACT_EMAIL,
          'availableLanguage' => ['ru'],
          'url'               => SITE_CANONICAL . '/contacts.php',
        ],
        // sameAs печатается только если профили заданы в seo-config.php:
        // пустой массив в разметке — бесполезный шум.
        'sameAs' => domexpert_social_profiles(),
      ]),
      [
        '@type'       => 'WebSite',
        '@id'         => domexpert_website_id(),
        'name'        => $siteName,
        'url'         => SITE_CANONICAL . '/',
        'description' => 'Практические советы по ремонту и обустройству дома',
        'inLanguage'  => 'ru-RU',
        'publisher'   => ['@id' => domexpert_org_id()],
        'potentialAction' => [
          '@type'       => 'SearchAction',
          'target'      => [
            '@type'        => 'EntryPoint',
            'urlTemplate'  => SITE_CANONICAL . '/search.php?q={search_term_string}',
          ],
          'query-input' => 'required name=search_term_string',
        ],
      ],
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>

  <?php if (isset($articleJsonLd)): ?>
  <!-- ═══ SCHEMA.ORG — Article ═══ -->
  <script type="application/ld+json">
  <?= $articleJsonLd ?>
  </script>
  <?php endif; ?>

  <?php if (isset($breadcrumbJsonLd)): ?>
  <!-- ═══ SCHEMA.ORG — BreadcrumbList ═══ -->
  <script type="application/ld+json">
  <?= $breadcrumbJsonLd ?>
  </script>
  <?php endif; ?>

  <?php if (isset($faqJsonLd)): ?>
  <!-- ═══ SCHEMA.ORG — FAQPage ═══ -->
  <script type="application/ld+json">
  <?= $faqJsonLd ?>
  </script>
  <?php endif; ?>

  <?php if (isset($listingJsonLd)): ?>
  <!-- ═══ SCHEMA.ORG — ItemList (категория) ═══ -->
  <script type="application/ld+json">
  <?= $listingJsonLd ?>
  </script>
  <?php endif; ?>

  <!-- ═══ ПРОИЗВОДИТЕЛЬНОСТЬ — preconnect ═══ -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="dns-prefetch" href="//yandex.ru">
  <link rel="dns-prefetch" href="//mc.yandex.ru">

  <!-- ═══ ШРИФТЫ ═══ -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600&display=swap">

  <!-- ═══ СТИЛИ ═══ -->
  <link rel="stylesheet" href="<?= htmlspecialchars(du_asset('/assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
  <?php
  /* Страница может попросить свой стиль, объявив $extraCss до include header.php.
     Нужно тяжёлым инструментам вроде планировщика: тянуть их CSS на все 200+
     страниц ради одной — лишние килобайты в критическом пути. */
  foreach (($extraCss ?? []) as $cssHref): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(du_asset($cssHref), ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>

  <!-- ═══ FAVICON (ICO в корне + SVG + PNG — рекомендации Яндекса/Google) ═══ -->
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/assets/img/favicon-32.png" type="image/png" sizes="32x32">
  <link rel="icon" href="/assets/img/favicon-16.png" type="image/png" sizes="16x16">
  <link rel="icon" href="/assets/img/favicon-120.png" type="image/png" sizes="120x120">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

  <!-- ═══ ТЕМА ОФОРМЛЕНИЯ ═══ -->
  <meta name="theme-color" content="#2C2C2C" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#0E1013" media="(prefers-color-scheme: dark)">
  <!-- Ставим тему до отрисовки: иначе у выбравших тёмную мелькает белый экран.
       Скрипт намеренно синхронный и в <head> — он должен отработать раньше <body>. -->
  <script>
    (function () {
      try {
        var saved = localStorage.getItem('domexpert-theme');
        var dark  = saved ? saved === 'dark'
                          : window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
      } catch (e) {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
  </script>

  <!-- ═══ RSS ЛЕНТА ═══ -->
  <link rel="alternate" type="application/rss+xml" title="<?= esc($siteName) ?> — RSS" href="/rss.php">

  <!-- ═══ Подтверждение вебмастера (при необходимости раскомментируйте) ═══ -->
  <!-- <meta name="google-site-verification" content="ВАШТОКЕН"> -->

  <!-- Yandex РСЯ Autoplacement 19600856 -->
  <script src="https://yandex.ru/ads/system/context.js" async></script>
  <script data-page-id="19600856" src="https://yandex.ru/ads/system/ap-loader.js" async></script>

  <!-- Google AdSense -->
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5535516142831006"
       crossorigin="anonymous"></script>
</head>
<body>

<!-- Первая цель Tab: позволяет пропустить шапку и меню, не проходя их ссылки
     на каждой странице. Виден только при фокусе с клавиатуры. -->
<a class="skip-link" href="#main-content">Перейти к содержимому</a>

<div class="top-bar">
  🛠️ Профессиональные советы по ремонту и обустройству вашего дома
</div>

<header class="site-header" role="banner" itemscope itemtype="https://schema.org/WPHeader">
  <div class="container">
    <div class="header-inner">
      <a href="/" class="site-logo" aria-label="ДомЭксперт — на главную" itemprop="url">
        <span class="logo-icon" aria-hidden="true">🏠</span>Дом<span class="logo-accent">Эксперт</span>
      </a>

      <button class="theme-toggle" id="themeToggle" type="button"
              aria-label="Переключить тёмную тему"
              aria-pressed="false"
              title="Светлая или тёмная тема">
        <span class="theme-toggle-icon" aria-hidden="true"></span>
      </button>

      <button class="burger-btn" id="burgerBtn"
              aria-label="Открыть меню навигации"
              aria-expanded="false"
              aria-controls="mainNav">
        <span></span><span></span><span></span>
      </button>

      <nav class="main-nav" id="mainNav" role="navigation" aria-label="Главное меню">
        <?php include __DIR__ . '/menu.php'; ?>
      </nav>
    </div>
  </div>
</header>
