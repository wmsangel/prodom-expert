<?php
/**
 * includes/article-calculators.php
 * Блок «Посчитать» на странице статьи.
 *
 * Список строится обратным индексом по полю related из реестра калькуляторов,
 * поэтому связь двусторонняя по построению: добавили калькулятор с указанием
 * статей — ссылки появились и там, и там. Править разметку статей не нужно.
 *
 * Слаг статьи берётся из $calcArticleSlug, а при его отсутствии — из адреса.
 * На $slug полагаться нельзя: подключаемые в глобальной области шаблоны
 * (menu.php, sidebar.php) исторически использовали это имя под свои циклы.
 */

require_once __DIR__ . '/all-calculators-meta.php';

$calcSlugSource = '';
if (isset($calcArticleSlug) && $calcArticleSlug !== '') {
  $calcSlugSource = (string) $calcArticleSlug;
} elseif (isset($_GET['slug'])) {
  $calcSlugSource = (string) $_GET['slug'];
}
$calcSlugSource = preg_replace('/[^a-z0-9\-]/', '', strtolower($calcSlugSource));

$articleCalcs = domexpert_calculators_for_article($calcSlugSource);

if ($articleCalcs):
?>
<section class="calc-links calc-links--inline" aria-label="Калькуляторы по теме статьи">
  <h2 class="section-title">🧮 Посчитать по этой теме</h2>
  <ul class="calc-link-list calc-link-list--compact">
    <?php foreach ($articleCalcs as $calcSlug => $calc): ?>
      <li>
        <a href="/calc/<?= htmlspecialchars($calcSlug, ENT_QUOTES, 'UTF-8') ?>/">
          <strong><?= htmlspecialchars($calc['h1'], ENT_QUOTES, 'UTF-8') ?></strong>
          <span><?= htmlspecialchars($calc['lead'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="calc-links-note"><a href="/calculators/">Все калькуляторы ремонта →</a></p>
</section>
<?php endif; ?>
