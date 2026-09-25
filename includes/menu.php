<?php
/**
 * includes/menu.php
 * Навигационное меню. Подсвечивает активный пункт по $_GET['cat']
 */

require_once __DIR__ . '/all-articles-meta.php';

$menuItems = domexpert_categories();

$activeCat = isset($_GET['cat']) ? $_GET['cat'] : '';
$scriptBase = basename(isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '');
$isArticlesArchive = ($scriptBase === 'articles.php');
$isCalculators     = ($scriptBase === 'calculators.php' || $scriptBase === 'calc.php');
$isGlossary        = ($scriptBase === 'slovar.php' || $scriptBase === 'termin.php');
?>
<ul>
  <li>
    <a href="/articles.php"
       class="<?= $isArticlesArchive ? 'active' : '' ?>"
       <?= $isArticlesArchive ? 'aria-current="page"' : '' ?>>
      Все статьи
    </a>
  </li>
  <?php /* Переменная цикла с префиксом: menu.php подключается в глобальной области,
           и generic-имя $slug затирало слаг статьи на странице, которая его подключает. */ ?>
  <?php foreach ($menuItems as $menuSlug => $item): ?>
    <li>
      <a href="/category/<?= htmlspecialchars($menuSlug, ENT_QUOTES, 'UTF-8') ?>/"
         class="<?= ($activeCat === $menuSlug) ? 'active' : '' ?>"
         <?= ($activeCat === $menuSlug) ? 'aria-current="page"' : '' ?>>
        <?= $item['label'] ?>
      </a>
    </li>
  <?php endforeach; ?>
  <li>
    <a href="/calculators/"
       class="<?= $isCalculators ? 'active' : '' ?>"
       <?= $isCalculators ? 'aria-current="page"' : '' ?>>
      Калькуляторы
    </a>
  </li>
  <li>
    <a href="/slovar/"
       class="<?= $isGlossary ? 'active' : '' ?>"
       <?= $isGlossary ? 'aria-current="page"' : '' ?>>
      Словарь
    </a>
  </li>
</ul>
