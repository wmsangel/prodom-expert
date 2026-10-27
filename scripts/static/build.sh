#!/usr/bin/env bash
# build.sh — статическая сборка сайта для Cloudflare Pages (prodom-expert.com).
# Обходит локальный PHP-рендер (router.php) по всем URL из sitemap.xml + embeds,
# копирует статику, меняет домен .ru→.com в sitemap/robots, кладёт _headers/_redirects.
# Запуск:  bash scripts/static/build.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
OUT="$ROOT/scripts/static/dist"
PORT=8099
NEW="prodom-expert.com"
OLD="prodom-expert.ru"
BASE="http://127.0.0.1:$PORT"

cd "$ROOT"
rm -rf "$OUT"; mkdir -p "$OUT"

# 1) Локальный сервер с canonical=.com
STATIC_CANONICAL="https://$NEW" php -S 127.0.0.1:$PORT -t . scripts/static/router.php >/tmp/static-build.log 2>&1 &
SRV=$!
trap 'kill $SRV 2>/dev/null || true' EXIT
sleep 1.5

fetch() { # $1 = path, $2 = outfile
  local code; code=$(curl -s -o "$2" -w "%{http_code}" "$BASE$1")
  [ "$code" = "200" ] || echo "  ⚠ $1 → $code"
}

# 2) Список путей: из sitemap.xml (352) + embed-страницы калькуляторов.
PATHS=$(grep -oE '<loc>[^<]+</loc>' sitemap.xml | sed -E 's#</?loc>##g; s#https://'"$OLD"'##')
CALCS=$(php -r 'require "includes/all-calculators-meta.php"; foreach(array_keys(domexpert_all_calculators_meta()) as $s) echo "/embed/calc/$s/\n";')

n=0
while IFS= read -r p; do
  [ -z "$p" ] && continue
  case "$p" in
    */) out="$OUT$p"index.html ;;         # чистый URL → index.html
    *.php) out="$OUT$p" ;;                  # .php-страница → файл как есть
    *) out="$OUT$p/index.html" ;;
  esac
  mkdir -p "$(dirname "$out")"
  fetch "$p" "$out"
  n=$((n+1))
done <<< "$PATHS
$CALCS"

# 3) Статика: assets, favicon, ads.txt
cp -R assets "$OUT/assets"
cp favicon.ico ads.txt "$OUT/" 2>/dev/null || true

# 4) sitemap/robots/rss — с доменом .com
for f in sitemap.xml sitemap-feeds.xml sitemap_index.xml; do
  [ -f "$f" ] && sed "s#$OLD#$NEW#g" "$f" > "$OUT/$f"
done
sed "s#$OLD#$NEW#g" robots.txt > "$OUT/robots.txt"

# 5) Cloudflare Pages: .php отдаём как HTML; rss.php — как XML
cat > "$OUT/_headers" <<'HED'
/*.php
  Content-Type: text/html; charset=utf-8
/rss.php
  Content-Type: application/rss+xml; charset=utf-8
HED

# 6) Отчёт
kill $SRV 2>/dev/null || true; trap - EXIT
html=$(find "$OUT" -name '*.html' | wc -l | tr -d ' ')
php=$(find "$OUT" -name '*.php' | wc -l | tr -d ' ')
echo "Готово: $html .html + $php .php страниц, обойдено $n маршрутов → $OUT"
