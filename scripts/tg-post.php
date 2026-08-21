<?php
/**
 * scripts/tg-post.php — авто-постинг статей в Telegram-канал.
 *
 * Берёт статьи из реестра includes/all-articles-meta.php и публикует их в канал
 * по очереди (обложка + заголовок + описание + ссылка). Порядок перемешивается
 * один раз на цикл и хранится в состоянии, поэтому повторов внутри цикла нет:
 * все статьи выходят по разу, потом очередь пересобирается заново.
 *
 * НАСТРОЙКА (делается один раз на сервере):
 *   1. Создать бота у @BotFather, получить токен.
 *   2. Создать канал, добавить бота АДМИНОМ с правом публикации.
 *   3. Создать файл scripts/tg.env (не в git):
 *        TG_BOT_TOKEN=123456:AA...ТОКЕН
 *        TG_CHANNEL=@your_channel        (или числовой -100XXXXXXXXXX)
 *
 * ЗАПУСК:
 *   php scripts/tg-post.php            # опубликовать 1 статью
 *   php scripts/tg-post.php 2          # опубликовать 2 статьи подряд
 *   php scripts/tg-post.php --dry      # показать, что бы отправилось, без отправки
 *   php scripts/tg-post.php 2 --dry
 *
 * CRON (две статьи в день, разнести по времени — лучше для охвата):
 *   0 10 * * *  cd /путь/к/сайту && php scripts/tg-post.php >> scripts/tg.log 2>&1
 *   0 18 * * *  cd /путь/к/сайту && php scripts/tg-post.php >> scripts/tg.log 2>&1
 * Либо один запуск на две статьи:
 *   0 10 * * *  cd /путь/к/сайту && php scripts/tg-post.php 2 >> scripts/tg.log 2>&1
 */

require_once __DIR__ . '/../includes/all-articles-meta.php';

const TG_BASE      = 'https://prodom-expert.ru';
const TG_STATE     = __DIR__ . '/tg-state.json';
const TG_ENV       = __DIR__ . '/tg.env';
const TG_UTM       = '?utm_source=telegram&utm_medium=channel';
const TG_OG_DEFAULT = '/assets/img/og-default.jpg';

$args   = array_slice($argv, 1);
$dry    = in_array('--dry', $args, true);
$count  = 1;
foreach ($args as $a) { if (ctype_digit($a)) { $count = max(1, min(10, (int) $a)); } }

/** Чтение TG_BOT_TOKEN и TG_CHANNEL из scripts/tg.env. */
function tg_env(): array {
    $env = ['TG_BOT_TOKEN' => '', 'TG_CHANNEL' => ''];
    if (is_readable(TG_ENV)) {
        foreach (file(TG_ENV, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) { continue; }
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }
    return $env;
}

/** Есть ли обложка для статьи на диске (чтобы не слать битый URL). */
function tg_cover_path(string $slug): ?string {
    $fs = __DIR__ . '/../assets/img/articles/' . $slug . '.png';
    return is_file($fs) ? '/assets/img/articles/' . $slug . '.png' : null;
}

/**
 * Очередь слагов. Порядок: сначала самые трафиковые (scripts/tg-priority.txt,
 * отсортирован по показам GSC), затем остальные статьи вперемешку. Курсор
 * хранится в состоянии; на новый цикл очередь пересобирается (приоритет —
 * снова первым, хвост — заново перемешан).
 */
function tg_next_slugs(int $n): array {
    $meta = domexpert_all_articles_meta();
    $all  = array_keys($meta);
    $state = ['queue' => [], 'pos' => 0];
    if (is_readable(TG_STATE)) {
        $s = json_decode((string) file_get_contents(TG_STATE), true);
        if (is_array($s) && !empty($s['queue'])) { $state = $s; }
    }
    $needRebuild = empty($state['queue'])
        || $state['pos'] >= count($state['queue'])
        || count($state['queue']) !== count($all)
        || count(array_diff($state['queue'], $all)) > 0;
    if ($needRebuild) {
        $priority = [];
        $pfile = __DIR__ . '/tg-priority.txt';
        if (is_readable($pfile)) {
            foreach (file($pfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $slug = trim($line);
                if ($slug !== '' && $slug[0] !== '#' && isset($meta[$slug]) && !in_array($slug, $priority, true)) {
                    $priority[] = $slug;
                }
            }
        }
        $rest = array_values(array_diff($all, $priority));
        shuffle($rest);
        $state['queue'] = array_merge($priority, $rest);
        $state['pos'] = 0;
    }
    $picked = [];
    for ($i = 0; $i < $n && $state['pos'] < count($state['queue']); $i++) {
        $slug = $state['queue'][$state['pos']];
        $state['pos']++;
        if (isset($meta[$slug])) { $picked[] = $slug; }
    }
    return [$picked, $state];
}

/** HTML-подпись к посту. */
function tg_caption(string $slug, array $m): string {
    $title = htmlspecialchars($m['title'], ENT_QUOTES, 'UTF-8');
    $desc  = htmlspecialchars($m['desc'],  ENT_QUOTES, 'UTF-8');
    $cat   = function_exists('domexpert_category_label') ? domexpert_category_label($m['cat']) : $m['cat'];
    $cat   = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
    $read  = htmlspecialchars($m['readTime'] ?? '', ENT_QUOTES, 'UTF-8');
    $url   = TG_BASE . '/article/' . $slug . '/' . TG_UTM;
    return "<b>{$title}</b>\n\n{$desc}\n\n🏷 {$cat}   ⏱ {$read}\n👉 <a href=\"{$url}\">Читать на сайте</a>";
}

/** Вызов Telegram Bot API. */
function tg_call(string $token, string $method, array $params): array {
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

// ── основной ход ──────────────────────────────────────────────────────────
$env  = tg_env();
$meta = domexpert_all_articles_meta();
[$slugs, $state] = tg_next_slugs($count);

if (!$slugs) { fwrite(STDERR, "Нет статей для публикации.\n"); exit(1); }

if (!$dry && ($env['TG_BOT_TOKEN'] === '' || $env['TG_CHANNEL'] === '')) {
    fwrite(STDERR, "Не задан TG_BOT_TOKEN или TG_CHANNEL в scripts/tg.env. Запусти с --dry для проверки.\n");
    exit(1);
}

$ok = 0;
foreach ($slugs as $slug) {
    $m       = $meta[$slug];
    $caption = tg_caption($slug, $m);
    $cover   = tg_cover_path($slug);
    $photo   = TG_BASE . ($cover ?? TG_OG_DEFAULT);

    echo "── {$slug}\n";
    echo "   фото: {$photo}\n";
    echo "   " . str_replace("\n", "\n   ", strip_tags($caption)) . "\n";

    if ($dry) { $ok++; continue; }

    $res = tg_call($env['TG_BOT_TOKEN'], 'sendPhoto', [
        'chat_id'    => $env['TG_CHANNEL'],
        'photo'      => $photo,
        'caption'    => $caption,
        'parse_mode' => 'HTML',
    ]);
    if (!empty($res['ok'])) {
        $ok++;
        echo "   ✓ отправлено\n";
    } else {
        // если фото не приняли (битый URL) — пробуем текстом
        $res2 = tg_call($env['TG_BOT_TOKEN'], 'sendMessage', [
            'chat_id' => $env['TG_CHANNEL'], 'text' => $caption,
            'parse_mode' => 'HTML', 'disable_web_page_preview' => false,
        ]);
        if (!empty($res2['ok'])) { $ok++; echo "   ✓ отправлено текстом\n"; }
        else { fwrite(STDERR, "   ✗ ошибка: " . ($res['description'] ?? '?') . "\n"); }
    }
}

// Сохраняем курсор только если реально публиковали (в dry — не двигаем).
if (!$dry) {
    @file_put_contents(TG_STATE, json_encode($state, JSON_UNESCAPED_UNICODE));
}
echo "Готово: {$ok} из " . count($slugs) . ($dry ? " (dry-run, состояние не менялось)" : "") . "\n";
exit($ok > 0 ? 0 : 1);
