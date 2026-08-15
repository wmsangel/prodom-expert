<?php
/**
 * includes/article-photos.php
 * Блок фотографий в статье.
 *
 * Фото берутся из assets/img/photos/<slug>/photos.json. Файл создаёт либо
 * scripts/prepare_photos.py (свои снимки из photos-inbox/), либо
 * scripts/fetch_free_photos.py (снимки со свободной лицензией с Викисклада).
 * Ничего править в разметке статей не нужно: появилась папка со снимками —
 * блок появился, нет папки — блока нет.
 *
 * Каждое фото может нести блок credit с данными о правах:
 *   credit: {author, license, license_url, page_url, source, modified}
 * Если credit есть — под снимком печатается строка атрибуции, а в конце блока
 * собирается список «Источники изображений». Это требование лицензий CC BY и
 * CC BY-SA: имя автора, название лицензии со ссылкой, ссылка на оригинал и
 * отметка об изменениях. Без credit снимок считается собственным.
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

if (!function_exists('domexpert_photo_credit')) {
  /** Нормализованный блок прав фотографии; null — снимок собственный. */
  function domexpert_photo_credit(array $photo): ?array
  {
    if (empty($photo['credit']) || !is_array($photo['credit'])) {
      return null;
    }
    $c   = $photo['credit'];
    $get = static fn(string $k): string => isset($c[$k]) ? trim((string) $c[$k]) : '';

    $author = $get('author');
    if ($author === '') {
      $author = 'автор не указан';
    }
    return [
      'author'      => $author,
      'license'     => $get('license'),
      'license_url' => $get('license_url'),
      'page_url'    => $get('page_url'),
      'source'      => $get('source') !== '' ? $get('source') : 'Wikimedia Commons',
      'modified'    => !empty($c['modified']),
    ];
  }
}

if (!function_exists('domexpert_photo_credit_html')) {
  /** Строка атрибуции: автор · лицензия · источник · отметка об изменениях. */
  function domexpert_photo_credit_html(array $credit): string
  {
    $esc   = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $parts = [];

    $author  = $esc($credit['author']);
    $parts[] = $credit['page_url'] !== ''
      ? '<a href="' . $esc($credit['page_url']) . '" rel="nofollow noopener" target="_blank">' . $author . '</a>'
      : $author;

    if ($credit['license'] !== '') {
      $license = $esc($credit['license']);
      $parts[] = $credit['license_url'] !== ''
        ? '<a href="' . $esc($credit['license_url']) . '" rel="nofollow noopener license" target="_blank">' . $license . '</a>'
        : $license;
    }

    $parts[] = $esc($credit['source']);

    $line = 'Фото: ' . implode(' · ', $parts);
    if ($credit['modified']) {
      $line .= ' · изображение масштабировано';
    }
    return $line;
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
  // Чего в блоке больше — своих снимков или заимствованных: от этого зависит
  // подпись под заголовком, чтобы она не приписывала редакции чужие фото.
  $creditedCount = 0;
  foreach ($articlePhotos as $photo) {
    if (domexpert_photo_credit($photo) !== null) {
      $creditedCount++;
    }
  }
  $ownCount = count($articlePhotos) - $creditedCount;

  if ($creditedCount === 0) {
    $photosNote = 'Снимки сделаны редакцией на реальных объектах.';
  } elseif ($ownCount === 0) {
    $photosNote = 'Иллюстрации из открытых источников со свободной лицензией — авторы и условия указаны под снимками.';
  } else {
    $photosNote = 'Часть снимков сделана редакцией, часть взята из открытых источников со свободной лицензией — авторы указаны под фото.';
  }
?>
<section class="article-photos" aria-label="Фотографии к статье">
  <h2 class="section-title">Как это выглядит на практике</h2>
  <p class="article-photos-note"><?= htmlspecialchars($photosNote, ENT_QUOTES, 'UTF-8') ?></p>

  <div class="article-photos-grid">
    <?php foreach ($articlePhotos as $i => $photo): ?>
      <?php
        $caption = isset($photo['caption']) ? (string) $photo['caption'] : '';
        $alt     = $caption !== ''
          ? $caption
          : $photoTitle . ' — фото ' . ($i + 1);
        $credit  = domexpert_photo_credit($photo);
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
        <?php if ($caption !== '' || $credit !== null): ?>
        <figcaption>
          <?php if ($caption !== ''): ?><?= htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
          <?php if ($credit !== null): ?>
          <span class="article-photo-credit"><?= domexpert_photo_credit_html($credit) ?></span>
          <?php endif; ?>
        </figcaption>
        <?php endif; ?>
      </figure>
    <?php endforeach; ?>
  </div>

  <?php if ($creditedCount > 0): ?>
  <details class="article-photos-sources">
    <summary>Источники изображений</summary>
    <ul>
      <?php foreach ($articlePhotos as $photo): ?>
        <?php $credit = domexpert_photo_credit($photo); ?>
        <?php if ($credit === null) { continue; } ?>
        <li>
          <?php if (!empty($photo['title'])): ?>«<?= htmlspecialchars((string) $photo['title'], ENT_QUOTES, 'UTF-8') ?>» — <?php endif; ?>
          <?= domexpert_photo_credit_html($credit) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </details>
  <?php endif; ?>
</section>
<?php endif; ?>
