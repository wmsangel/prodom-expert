<?php
/**
 * Обложка статьи: файлы в /assets/img/articles/{slug}.(jpg|jpeg|webp|png)
 * Возвращает относительный путь с ведущим «/» или null.
 */
function article_cover_web_path(string $slug): ?string
{
  $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
  if ($slug === '') {
    return null;
  }
  $dir = __DIR__ . '/../assets/img/articles/';
  foreach (['.jpg', '.jpeg', '.webp', '.png'] as $ext) {
    if (is_file($dir . $slug . $ext)) {
      return '/assets/img/articles/' . $slug . $ext;
    }
  }
  return null;
}

/** Абсолютный путь к файлу обложки или null */
function article_cover_fs_path(string $slug): ?string
{
  $web = article_cover_web_path($slug);
  if ($web === null) {
    return null;
  }
  return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($web, '/'));
}

/**
 * <picture> с WebP-источником и <img>-фолбэком.
 * Хостинг на nginx раздаёт статику сам и .htaccess-негоциацию WebP игнорирует,
 * поэтому подмену jpg→webp делаем на стороне браузера через <picture>. Если рядом
 * с картинкой нет .webp — возвращаем обычный <img> (мягкая деградация).
 *
 * @param string $webPath веб-путь к jpg/png (с ведущим «/»)
 * @param string $alt     alt-текст
 * @param array  $attrs   доп. атрибуты <img>: class, loading, decoding, fetchpriority, width, height, itemprop
 */
function du_cover_picture(string $webPath, string $alt, array $attrs = []): string
{
  $esc = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  $a = '';
  foreach ($attrs as $k => $v) {
    if ($v === null || $v === '') { continue; }
    $a .= ' ' . $esc($k) . '="' . $esc($v) . '"';
  }
  $img = '<img src="' . $esc($webPath) . '" alt="' . $esc($alt) . '"' . $a . '>';

  $webPathRel = ltrim($webPath, '/');
  $webpWeb = preg_replace('/\.(jpe?g|png)$/i', '.webp', $webPath);
  $webpFs  = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, preg_replace('/\.(jpe?g|png)$/i', '.webp', $webPathRel));
  if ($webpWeb === $webPath || !is_file($webpFs)) {
    return $img; // нет webp — отдаём обычный img
  }
  return '<picture><source srcset="' . $esc($webpWeb) . '" type="image/webp">' . $img . '</picture>';
}
