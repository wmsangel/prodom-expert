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
