<?php
require_once __DIR__ . '/includes/load-seo.php';
http_response_code(404);
$pageTitle = 'Страница не найдена (404) | ДомЭксперт';
$pageDesc  = 'Запрашиваемая страница не найдена. Вернитесь на главную или воспользуйтесь меню сайта.';
$pageUrl   = SITE_CANONICAL . '/404.php';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;
$robotsMeta = 'noindex, follow';
include __DIR__ . '/includes/header.php';
?>

<div class="page-wrapper" style="min-height: 70vh; display: flex; align-items: center;">
  <div class="container">
    <div style="text-align: center; padding: 60px 20px; max-width: 580px; margin: 0 auto;">

      <div style="font-size: 6rem; line-height: 1; margin-bottom: 20px;">🏚️</div>

      <h1 style="font-family: var(--font-display); font-size: 5rem; color: var(--brick); line-height: 1; margin-bottom: 12px;">404</h1>

      <h2 style="font-family: var(--font-display); font-size: 1.8rem; color: var(--charcoal); margin-bottom: 16px;">
        Страница не найдена
      </h2>

      <p style="color: var(--text-light); font-size: 1.05rem; margin-bottom: 36px; line-height: 1.7;">
        Похоже, эта страница была снесена во время ремонта. Зато у нас есть много другого полезного — воспользуйтесь меню или вернитесь на главную.
      </p>

      <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px;">
        <a href="/" class="btn btn-primary">🏠 На главную</a>
        <a href="/category/remont/" class="btn btn-outline" style="color: var(--charcoal); border-color: var(--beige-mid);">📂 Все статьи</a>
      </div>

      <div style="background: var(--white); border-radius: var(--radius-lg); padding: 28px; box-shadow: 0 2px 12px var(--shadow); text-align: left;">
        <h3 style="font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 16px; color: var(--charcoal);">Популярные разделы</h3>
        <ul style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <li><a href="/category/remont/" style="color: var(--text); font-size: 0.9rem;">🔨 Ремонт</a></li>
          <li><a href="/category/okna/" style="color: var(--text); font-size: 0.9rem;">🪟 Окна и двери</a></li>
          <li><a href="/category/santehnika/" style="color: var(--text); font-size: 0.9rem;">🚿 Сантехника</a></li>
          <li><a href="/category/elektrika/" style="color: var(--text); font-size: 0.9rem;">💡 Электрика</a></li>
          <li><a href="/category/interer/" style="color: var(--text); font-size: 0.9rem;">🛋️ Интерьер</a></li>
          <li><a href="/category/sovety/" style="color: var(--text); font-size: 0.9rem;">📋 Советы</a></li>
        </ul>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
