<?php
/**
 * scripts/tg-content.php — публикация ОРИГИНАЛЬНЫХ постов в Telegram-канал
 * (лайфхаки, разборы, мифы, чек-листы, опросы) — в дополнение к репостам статей
 * из tg-post.php.
 *
 * Контент лежит в scripts/tg-content.jsonl — по одному JSON-объекту на строку:
 *   {"type":"text","text":"<b>Заголовок</b>\n\nтекст…"}
 *   {"type":"poll","question":"...?","options":["A","B","C"]}
 * Курсор (что уже опубликовано) — в scripts/tg-content-state.json.
 * Очередь идёт по кругу: дошли до конца — начинаем сначала (бэклог переиспользуется).
 *
 * Запуск:
 *   php scripts/tg-content.php --dry     # показать следующий пост, без отправки
 *   php scripts/tg-content.php           # опубликовать 1 следующий пост
 *   php scripts/tg-content.php 2         # опубликовать 2 подряд
 *
 * Токен/канал — из scripts/tg.env (TG_BOT_TOKEN, TG_CHANNEL), как у tg-post.php.
 */

const C_ENV   = __DIR__ . '/tg.env';
const C_FILE  = __DIR__ . '/tg-content.jsonl';
const C_STATE = __DIR__ . '/tg-content-state.json';

$args  = array_slice($argv, 1);
$dry   = in_array('--dry', $args, true);
$count = 1;
foreach ($args as $a) { if (ctype_digit((string) $a)) { $count = max(1, (int) $a); } }

function c_env(): array {
    $env = ['TG_BOT_TOKEN' => '', 'TG_CHANNEL' => ''];
    if (is_readable(C_ENV)) {
        foreach (file(C_ENV, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) { continue; }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            if (array_key_exists($k, $env)) { $env[$k] = trim($v); }
        }
    }
    return $env;
}

function c_call(string $token, string $method, array $params): array {
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25,
        ]);
        $body = (string) curl_exec($ch);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => [
            'method' => 'POST', 'timeout' => 25, 'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($params),
        ]]);
        $body = (string) @file_get_contents($url, false, $ctx);
    }
    $j = json_decode($body, true);
    return is_array($j) ? $j : ['ok' => false, 'description' => 'bad response'];
}

// Загрузка контента
if (!is_readable(C_FILE)) { fwrite(STDERR, "Нет файла контента " . C_FILE . "\n"); exit(1); }
$items = [];
foreach (file(C_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') { continue; }
    $obj = json_decode($line, true);
    if (is_array($obj) && !empty($obj['type'])) { $items[] = $obj; }
}
if (!$items) { fwrite(STDERR, "Контент пуст.\n"); exit(1); }

// Курсор
$pos = 0;
if (is_readable(C_STATE)) {
    $s = json_decode((string) file_get_contents(C_STATE), true);
    if (is_array($s) && isset($s['pos'])) { $pos = (int) $s['pos']; }
}

$env = c_env();
if (!$dry && ($env['TG_BOT_TOKEN'] === '' || $env['TG_CHANNEL'] === '')) {
    fwrite(STDERR, "Не задан TG_BOT_TOKEN/TG_CHANNEL в scripts/tg.env.\n");
    exit(1);
}

$ok = 0;
for ($i = 0; $i < $count; $i++) {
    $item = $items[$pos % count($items)];
    $pos++;
    $type = $item['type'];

    echo "── [{$type}] " . mb_substr(strip_tags($item['text'] ?? $item['question'] ?? ''), 0, 70) . "…\n";
    if ($dry) { $ok++; continue; }

    if ($type === 'poll') {
        $res = c_call($env['TG_BOT_TOKEN'], 'sendPoll', [
            'chat_id'     => $env['TG_CHANNEL'],
            'question'    => (string) $item['question'],
            'options'     => json_encode(array_values($item['options']), JSON_UNESCAPED_UNICODE),
            'is_anonymous'=> true,
        ]);
    } else {
        $res = c_call($env['TG_BOT_TOKEN'], 'sendMessage', [
            'chat_id'    => $env['TG_CHANNEL'],
            'text'       => (string) $item['text'],
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => $item['preview'] ?? false ? 'false' : 'true',
        ]);
    }
    if (!empty($res['ok'])) { $ok++; echo "   ✓ отправлено\n"; }
    else { fwrite(STDERR, "   ✗ ошибка: " . ($res['description'] ?? '?') . "\n"); }
}

if (!$dry) {
    @file_put_contents(C_STATE, json_encode(['pos' => $pos], JSON_UNESCAPED_UNICODE));
}
echo "Готово: {$ok} из {$count}" . ($dry ? " (dry-run)" : "") . "\n";
exit($ok > 0 ? 0 : 1);
