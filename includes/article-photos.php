<?php
/**
 * includes/article-photos.php
 * Блок собственных фотографий в статье.
 *
 * Фото берутся из assets/img/photos/<slug>/photos.json, который создаёт
 * scripts/prepare_photos.py. Ничего править в разметке статей не нужно:
 * появилась папка со снимками — блок появился, нет папки — блока нет.
 *
 * Ожидает переменную $photoArticleSlug (или $_GET['slug'] как запасной вариант).
 */

if (!function_exists('domexpert_article_photos')) {
  /** Список фотографий статьи; пустой массив, если их нет. */
  function domexpert_article_photos(string $slug): array
  {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
    if ($slug === '') {
      return [];
    }
    $file = dirname(__DIR__) . '/assets/img/photos/' . $slug . '/photos.json';
    if (!is_readable($file)) {
      return [];
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data) || empty($data['photos']) || !is_array($data['photos'])) {
      return [];
    }
    return $data['photos'];
  }
}

$photoSlug = '';
if (isset($photoArticleSlug) && $photoArticleSlug !== '') {
  $photoSlug = (string) $photoArticleSlug;
} elseif (isset($_GET['slug'])) {
  $photoSlug = (string) $_GET['slug'];
}

$articlePhotos = domexpert_article_photos($photoSlug);
$photoTitle    = isset($meta['title']) ? (string) $meta['title'] : '';

if ($articlePhotos):
?>
<section class="article-photos" aria-label="Фотографии к статье">
  <h2 class="section-title">Как это выглядит на практике</h2>
  <p class="article-photos-note">Снимки сделаны редакцией на реальных объектах.</p>

  <div class="article-photos-grid">
    <?php foreach ($articlePhotos as $i => $photo): ?>
      <?php
        $alt = $photo['caption'] !== ''
          ? $photo['caption']
          : $photoTitle . ' — фото ' . ($i + 1);
      ?>
      <figure class="article-photo">
        <picture>
          <source srcset="<?= htmlspecialchars($photo['webp'], ENT_QUOTES, 'UTF-8') ?>" type="image/webp">
          <img src="<?= htmlspecialchars($photo['jpg'], ENT_QUOTES, 'UTF-8') ?>"
               width="<?= (int) $photo['width'] ?>"
               height="<?= (int) $photo['height'] ?>"
               alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>"
               loading="lazy"
               decoding="async">
        </picture>
        <?php if ($photo['caption'] !== ''): ?>
          <figcaption><?= htmlspecialchars($photo['caption'], ENT_QUOTES, 'UTF-8') ?></figcaption>
        <?php endif; ?>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
