<?php
/**
 * search.php — Страница поиска по сайту
 */

require_once __DIR__ . '/includes/load-seo.php';
require_once __DIR__ . '/includes/article-cover.php';
require_once __DIR__ . '/includes/all-articles-meta.php';

$query = isset($_GET['q']) ? trim(strip_tags($_GET['q'])) : '';
$query = mb_substr($query, 0, 100); // ограничиваем длину запроса

$pageTitle = $query ? 'Поиск: ' . htmlspecialchars($query) . ' | ДомЭксперт' : 'Поиск по сайту | ДомЭксперт';
$pageDesc  = 'Поиск статей по ремонту, интерьеру и обустройству дома на сайте ДомЭксперт.';
$pageUrl   = SITE_CANONICAL . '/search.php' . ($query ? '?q=' . urlencode($query) : '');
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;

// Страницы поиска с запросом не индексируем — это типовая SEO-практика
if ($query !== '') {
  $robotsMeta = 'noindex, follow';
}

// Все статьи для поиска — единый реестр includes/all-articles-meta.php
$allArticles = array_values(domexpert_all_articles_expanded());

// Поиск — по корням слов, с сортировкой по релевантности
$results = domexpert_search_articles($query);

include __DIR__ . '/includes/header.php';
?>

<div class="category-header">
  <div class="container">
    <h1>🔍 Поиск по сайту</h1>
    <?php if ($query): ?>
    <p>Результаты по запросу: «<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>»</p>
    <?php endif; ?>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout">
      <main role="main">

        <!-- Форма поиска -->
        <div style="background:var(--white); border-radius:var(--radius-lg); padding:24px; margin-bottom:28px; box-shadow:0 2px 12px var(--shadow);">
          <form action="/search.php" method="GET" role="search" aria-label="Поиск по сайту">
            <div style="display:flex; gap:12px;">
              <input
                type="search"
                name="q"
                value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="Найти статью... (например: укладка плитки)"
                aria-label="Поисковый запрос"
                autocomplete="off"
                style="flex:1; padding:12px 16px; border:2px solid var(--border); border-radius:var(--radius); font-family:var(--font-body); font-size:1rem; outline:none; transition:border-color var(--transition);"
                onfocus="this.style.borderColor='var(--brick)'"
                onblur="this.style.borderColor='var(--border)'">
              <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Найти</button>
            </div>
          </form>
        </div>

        <?php if ($query === ''): ?>
          <!-- Без запроса — показываем все статьи -->
          <div class="section-header">
            <h2 class="section-title">Все статьи сайта</h2>
            <span style="color:var(--text-muted); font-size:0.88rem;"><?= count($allArticles) ?> статей</span>
          </div>
          <div class="article-list">
            <?php foreach ($allArticles as $a): ?>
            <?php $searchCover = article_cover_web_path($a['slug']); ?>
            <article class="article-list-item">
              <div class="list-item-image">
                <?php if ($searchCover): ?>
                  <img src="<?= htmlspecialchars($searchCover, ENT_QUOTES, 'UTF-8') ?>"
                       alt="<?= htmlspecialchars($a['title'] . ' — превью статьи', ENT_QUOTES, 'UTF-8') ?>"
                       loading="lazy"
                       decoding="async">
                <?php else: ?>
                  <?= ['remont'=>'🔨','okna'=>'🪟','santehnika'=>'🚿','elektrika'=>'💡','interer'=>'🛋️','sovety'=>'📋'][$a['cat']] ?? '📄' ?>
                <?php endif; ?>
              </div>
              <div class="list-item-body">
                <div class="list-item-cat"><?= htmlspecialchars($a['catLabel'], ENT_QUOTES, 'UTF-8') ?></div>
                <h2 class="list-item-title">
                  <a href="/article/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>/">
                    <?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h2>
                <p class="list-item-desc"><?= htmlspecialchars($a['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="list-item-footer">
                  <span></span>
                  <a href="/article/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>/" class="card-link">Читать</a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>

        <?php elseif (empty($results)): ?>
          <div class="empty-state">
            <div class="icon">🔍</div>
            <h3>Ничего не найдено</h3>
            <p>По запросу «<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>» статей не найдено. Попробуйте другой запрос или выберите категорию из меню.</p>
          </div>

        <?php else: ?>
          <div class="section-header">
            <h2 class="section-title">Найдено статей: <?= count($results) ?></h2>
          </div>
          <div class="article-list">
            <?php foreach ($results as $a): ?>
            <?php $searchCover = article_cover_web_path($a['slug']); ?>
            <article class="article-list-item">
              <div class="list-item-image">
                <?php if ($searchCover): ?>
                  <img src="<?= htmlspecialchars($searchCover, ENT_QUOTES, 'UTF-8') ?>"
                       alt="<?= htmlspecialchars($a['title'] . ' — превью статьи', ENT_QUOTES, 'UTF-8') ?>"
                       loading="lazy"
                       decoding="async">
                <?php else: ?>
                  <?= ['remont'=>'🔨','okna'=>'🪟','santehnika'=>'🚿','elektrika'=>'💡','interer'=>'🛋️','sovety'=>'📋'][$a['cat']] ?? '📄' ?>
                <?php endif; ?>
              </div>
              <div class="list-item-body">
                <div class="list-item-cat"><?= htmlspecialchars($a['catLabel'], ENT_QUOTES, 'UTF-8') ?></div>
                <h2 class="list-item-title">
                  <a href="/article/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>/">
                    <?= htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h2>
                <p class="list-item-desc"><?= htmlspecialchars($a['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="list-item-footer">
                  <span></span>
                  <a href="/article/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>/" class="card-link">Читать</a>
                </div>
              </div>
            </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </main>

      <?php include __DIR__ . '/includes/sidebar.php'; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
