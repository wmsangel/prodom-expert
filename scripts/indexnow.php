<?php
/**
 * scripts/indexnow.php — уведомление IndexNow (Bing, Яндекс и др.) об изменённых
 * страницах, чтобы их переобошли за часы, а не за недели.
 *
 * Ключ лежит в корне сайта: /bdc0699bb43e072d7f5351a49c7f95f3.txt
 * Один POST на api.indexnow.org рассылается всем участникам протокола.
 *
 * ВАЖНО: пингуются ЖИВЫЕ адреса prodom-expert.ru, поэтому запускать ПОСЛЕ
 * заливки изменений на сервер, а не до.
 *
 * Использование:
 *   php scripts/indexnow.php --changed              # URL из последнего git-коммита
 *   php scripts/indexnow.php --sitemap              # все URL из sitemap
 *   php scripts/indexnow.php /article/foo/ /calc/bar/   # конкретные адреса
 *   php scripts/indexnow.php https://prodom-expert.ru/  # полный URL
 *   (без аргументов — главная страница)
 */

const IN_HOST     = 'prodom-expert.ru';
const IN_KEY      = 'bdc0699bb43e072d7f5351a49c7f95f3';
const IN_KEY_URL  = 'https://prodom-expert.ru/bdc0699bb43e072d7f5351a49c7f95f3.txt';
const IN_ENDPOINT = 'https://api.indexnow.org/indexnow';
const IN_BASE     = 'https://prodom-expert.ru';

$root = dirname(__DIR__);

/** Абсолютный URL из пути или полного адреса. */
function in_url(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    if (preg_match('#^https?://#i', $s)) return $s;
    return IN_BASE . '/' . ltrim($s, '/');
}

/** Слаг-файл статьи/калькулятора → публичный URL. */
function in_file_to_url(string $path): ?string {
    if (preg_match('#^articles/[^/]+/([a-z0-9-]+)\.html$#', $path, $m)) {
        return IN_BASE . '/article/' . $m[1] . '/';
    }
    if (preg_match('#^calc-forms/([a-z0-9-]+)\.html$#', $path, $m)) {
        return IN_BASE . '/calc/' . $m[1] . '/';
    }
    return null;
}

$args = array_slice($argv, 1);
$urls = [];

if (in_array('--changed', $args, true)) {
    // URL из файлов, изменённых в последнем коммите
    $out = [];
    exec('cd ' . escapeshellarg($root) . ' && git diff --name-only HEAD~1 HEAD 2>/dev/null', $out);
    foreach ($out as $f) {
        $u = in_file_to_url(trim($f));
        if ($u) $urls[] = $u;
    }
    if (!$urls) { $urls[] = IN_BASE . '/'; } // хотя бы главную
} elseif (in_array('--sitemap', $args, true)) {
    foreach (['sitemap.xml', 'sitemap-feeds.xml'] as $sm) {
        $file = $root . '/' . $sm;
        if (is_readable($file) && preg_match_all('#<loc>([^<]+)</loc>#', (string)file_get_contents($file), $m)) {
            foreach ($m[1] as $loc) $urls[] = trim($loc);
        }
    }
} else {
    foreach ($args as $a) {
        if (strpos($a, '--') === 0) continue;
        $u = in_url($a);
        if ($u) $urls[] = $u;
    }
    if (!$urls) { $urls[] = IN_BASE . '/'; }
}

$urls = array_values(array_unique($urls));
if (!$urls) { fwrite(STDERR, "Нет URL для отправки.\n"); exit(1); }

$payload = json_encode([
    'host'        => IN_HOST,
    'key'         => IN_KEY,
    'keyLocation' => IN_KEY_URL,
    'urlList'     => $urls,
], JSON_UNESCAPED_SLASHES);

echo "IndexNow → " . count($urls) . " URL:\n";
foreach ($urls as $u) echo "  $u\n";

if (in_array('--dry', $args, true)) {
    echo "\n[--dry] Отправка пропущена. Тело запроса:\n" . $payload . "\n";
    exit(0);
}

$status = 0; $body = '';
if (function_exists('curl_init')) {
    $ch = curl_init(IN_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $body   = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) { fwrite(STDERR, 'curl: ' . curl_error($ch) . "\n"); }
    curl_close($ch);
} else {
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json; charset=utf-8\r\n",
        'content' => $payload,
        'timeout' => 20,
        'ignore_errors' => true,
    ]]);
    $body = (string) @file_get_contents(IN_ENDPOINT, false, $ctx);
    if (isset($http_response_header[0]) && preg_match('#\s(\d{3})\s#', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
}

echo "\nОтвет: HTTP $status\n";
if ($body !== '') echo $body . "\n";
// 200/202 — принято. IndexNow часто отвечает пустым телом при успехе.
exit(($status >= 200 && $status < 300) ? 0 : 1);
