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
SITE_CANONICAL="https://$NEW" php -S 127.0.0.1:$PORT -t . scripts/static/router.php >/tmp/static-build.log 2>&1 &
SRV=$!
trap 'kill $SRV 2>/dev/null || true' EXIT
sleep 1.5

fetch() { # $1 = path, $2 = outfile
  local code; code=$(curl -s -o "$2" -w "%{http_code}" "$BASE$1")
  [ "$code" = "200" ] || echo "  ⚠ $1 → $code"
}

# 2) Список путей: из sitemap.xml (352) + embed-страницы калькуляторов + /search.php.
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
$CALCS
/search.php"

# 3) Статика: assets, favicon, ads.txt
cp -R assets "$OUT/assets"
cp favicon.ico ads.txt "$OUT/" 2>/dev/null || true

# 3a) Cloudflare Pages Functions (форма → Telegram) НЕ кладём в dist: wrangler
# ищет ./functions рядом с папкой ассетов. Деплой — из scripts/static/:
#   cd scripts/static && npx wrangler pages deploy dist --project-name=prodom-expert
# JS формы на статике шлёт на /api/feedback (Function), а не на PHP.
sed -i '' 's#/feedback\.php#/api/feedback#g' "$OUT/assets/js/main.js" 2>/dev/null || \
  sed -i 's#/feedback\.php#/api/feedback#g' "$OUT/assets/js/main.js"

# 3b) Поиск: статический индекс Pagefind + его UI на странице /search.php.
if npx -y pagefind --site "$OUT" >/tmp/pagefind.log 2>&1 && [ -f "$OUT/search.php" ]; then
  python3 - "$OUT/search.php" <<'PY'
import sys
f = sys.argv[1]
snip = '''<link href="/pagefind/pagefind-ui.css" rel="stylesheet">
<script src="/pagefind/pagefind-ui.js"></script>
<div id="pagefind-search" class="container" style="margin:24px auto;max-width:880px"></div>
<script>addEventListener("DOMContentLoaded",function(){var q=(new URLSearchParams(location.search)).get("q")||"";new PagefindUI({element:"#pagefind-search",showImages:false,showSubResults:true,translations:{placeholder:"Поиск по сайту ДомЭксперт",zero_results:"Ничего не найдено: «[SEARCH_TERM]»"}});if(q){var i=document.querySelector("#pagefind-search input");if(i){i.value=q;i.dispatchEvent(new Event("input"));}}});</script>'''
s = open(f, encoding='utf-8').read()
s = s.replace('</body>', snip + '\n</body>', 1)
open(f, 'w', encoding='utf-8').write(s)
PY
  echo "  ✓ Pagefind проиндексирован, UI внедрён в /search.php"
else
  echo "  ⚠ Pagefind/search не отработал (см. /tmp/pagefind.log) — поиск добавить отдельно"
fi

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
