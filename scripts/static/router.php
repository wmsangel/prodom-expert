<?php
/**
 * router.php — для `php -S 127.0.0.1:PORT -t . scripts/static/router.php`.
 * Эмулирует .htaccess-переписывание чистых URL в PHP-страницы, чтобы собрать
 * статическую копию сайта локальным обходом (см. build.sh).
 * Только для сборки статики; на прод не идёт.
 *
 * Канонический домен билда можно задать env STATIC_CANONICAL (по умолч. .com) —
 * тогда canonical/OG/JSON-LD/внутренние абсолютные URL рендерятся под новый домен.
 */
define('SITE_CANONICAL', getenv('STATIC_CANONICAL') ?: 'https://prodom-expert.com');

$root = $_SERVER['DOCUMENT_ROOT'] ?: getcwd();
$uri  = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Реальные файлы (css/js/img/…) — отдаём как есть.
$path = $root . $uri;
if ($uri !== '/' && is_file($path)) {
  return false;
}

$run = function (string $php) use ($root) { require $root . '/' . $php; };

if ($uri === '/' )                                                      { $run('index.php'); return true; }
if (preg_match('#^/article/([a-z0-9-]+)/?$#', $uri, $m))                { $_GET['slug'] = $m[1]; $run('article.php');  return true; }
if (preg_match('#^/calc/([a-z0-9-]+)/?$#', $uri, $m))                   { $_GET['slug'] = $m[1]; $run('calc.php');     return true; }
if (preg_match('#^/embed/calc/([a-z0-9-]+)/?$#', $uri, $m))             { $_GET['slug'] = $m[1]; $run('embed.php');    return true; }
if (preg_match('#^/slovar/([a-z0-9-]+)/?$#', $uri, $m))                 { $_GET['slug'] = $m[1]; $run('termin.php');   return true; }
if ($uri === '/slovar' || $uri === '/slovar/')                         { $run('slovar.php');      return true; }
if (preg_match('#^/category/([a-z0-9-]+)/page/([0-9]+)/?$#', $uri, $m)) { $_GET['cat']=$m[1]; $_GET['page']=$m[2]; $run('category.php'); return true; }
if (preg_match('#^/category/([a-z0-9-]+)/?$#', $uri, $m))               { $_GET['cat']=$m[1]; $run('category.php'); return true; }
if ($uri === '/calculators' || $uri === '/calculators/')               { $run('calculators.php'); return true; }
if ($uri === '/articles' || $uri === '/articles/')                     { $run('articles.php');    return true; }
if ($uri === '/vidzhety' || $uri === '/vidzhety/')                     { $run('vidzhety.php');    return true; }

// Прямые *.php (about.php, privacy.php, …).
$php = ltrim($uri, '/');
if (preg_match('#^[a-z0-9_-]+\.php$#', $php) && is_file($root . '/' . $php)) { $run($php); return true; }

http_response_code(404);
$run('404.php');
return true;
