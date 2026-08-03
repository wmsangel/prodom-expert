<?php
require_once __DIR__ . '/includes/load-seo.php';
$pageTitle = 'Контакты | ДомЭксперт';
$pageDesc  = 'Свяжитесь с редакцией ДомЭксперт по вопросам сотрудничества, размещения статей или рекламы.';
$pageUrl   = SITE_CANONICAL . '/contacts.php';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

$articleJsonLd = json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'ContactPage',
  'name'        => $pageTitle,
  'description' => $pageDesc,
  'url'         => $pageUrl,
  'inLanguage'  => 'ru-RU',
  'mainEntity'  => [
    '@type'         => 'Organization',
    'name'          => 'ДомЭксперт',
    'url'           => SITE_CANONICAL . '/',
    'email'         => 'info@prodom-expert.ru',
    'contactPoint'  => [
      [
        '@type'         => 'ContactPoint',
        'contactType'   => 'customer support',
        'email'         => 'info@prodom-expert.ru',
        'availableLanguage' => ['Russian'],
      ],
      [
        '@type'         => 'ContactPoint',
        'contactType'   => 'sales',
        'email'         => 'ads@prodom-expert.ru',
        'availableLanguage' => ['Russian'],
      ],
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',  'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Контакты', 'item' => $pageUrl],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

include __DIR__ . '/includes/header.php';
?>

<div class="category-header">
  <div class="container">
    <h1>Контакты</h1>
    <p>Свяжитесь с редакцией</p>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">
      <main role="main">
        <div class="article-body" style="margin-top: 0;">
          <h2>Напишите нам</h2>
          <p>По вопросам сотрудничества, размещения материалов и рекламы:</p>
          <ul>
            <li>📧 Email: <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a></li>
            <li>📧 Реклама: <a href="mailto:ads@prodom-expert.ru">ads@prodom-expert.ru</a></li>
          </ul>
          <h2>Время ответа</h2>
          <p>Мы стараемся отвечать на все письма в течение 1–2 рабочих дней.</p>

          <h2>Вы эксперт в строительстве или ремонте?</h2>
          <p>Если вы профессионал и хотите поделиться своим опытом, мы рады рассмотреть ваши статьи для публикации. Напишите нам с темой «Авторская статья».</p>
        </div>
      </main>
      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
