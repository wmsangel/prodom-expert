<?php
require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/all-articles-meta.php';
require_once __DIR__ . '/includes/article-cover.php';
http_response_code(404);

// Адрес, по которому человек пришёл, обычно описывает тему: подбираем статьи
// по нему, вместо того чтобы просто разводить руками.
$notFoundPath = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
$suggestions  = domexpert_guess_articles($notFoundPath, 4);
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

      <p style="color: var(--text-light); font-size: 1.05rem; margin-bottom: 28px; line-height: 1.7;">
        Похоже, эта страница была снесена во время ремонта. Ниже — материалы, близкие к тому, что вы искали.
      </p>

      <form action="/search.php" method="GET" role="search" aria-label="Поиск по сайту"
            style="display: flex; gap: 10px; margin-bottom: 32px;">
        <input type="search" name="q" placeholder="Найти статью — например: укладка плитки"
               aria-label="Поисковый запрос" autocomplete="off"
               style="flex: 1; padding: 12px 16px; border: 2px solid var(--border); border-radius: var(--radius); font-family: var(--font-body); font-size: 1rem; background: var(--white); color: var(--text);">
        <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Найти</button>
      </form>

      <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin-bottom: 40px;">
        <a href="/" class="btn btn-primary">🏠 На главную</a>
        <a href="/articles.php" class="btn btn-outline" style="color: var(--heading); border-color: var(--beige-mid);">📂 Все статьи</a>
      </div>

      <?php if ($suggestions): ?>
      <div style="background: var(--white); border-radius: var(--radius-lg); padding: 28px; box-shadow: 0 2px 12px var(--shadow); text-align: left; margin-bottom: 28px;">
        <h3 style="font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 16px; color: var(--heading);">Возможно, вы искали</h3>
        <ul class="popular-list">
          <?php foreach ($suggestions as $item): ?>
          <li>
            <a href="<?= htmlspecialchars(du_article_path($item['slug']), ENT_QUOTES, 'UTF-8') ?>" class="popular-link">
              <span class="popular-num" aria-hidden="true"><?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?></span>
              <span>
                <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
                <span style="display: block; color: var(--text-muted); font-size: 0.82rem; margin-top: 2px;">
                  <?= htmlspecialchars($item['catLabel'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['readTime'], ENT_QUOTES, 'UTF-8') ?>
                </span>
              </span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <div style="background: var(--white); border-radius: var(--radius-lg); padding: 28px; box-shadow: 0 2px 12px var(--shadow); text-align: left;">
        <h3 style="font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 16px; color: var(--heading);">Разделы сайта</h3>
        <ul style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
          <?php foreach (domexpert_categories() as $cat404 => $cfg404): ?>
          <li>
            <a href="<?= htmlspecialchars(du_category_path($cat404), ENT_QUOTES, 'UTF-8') ?>" style="color: var(--text); font-size: 0.9rem;">
              <?= $cfg404['icon'] ?> <?= htmlspecialchars($cfg404['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
