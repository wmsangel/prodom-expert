#!/bin/bash
#
# tg-daily.sh — ежедневный автопостинг ДомЭксперт в Telegram.
# Запускается launchd (см. com.prodom.tg-daily.plist) дважды в день:
#   утром (до 14:00) публикуется СТАТЬЯ (tg-post.php),
#   вечером — ОРИГИНАЛЬНЫЙ пост/опрос (tg-content.php).
# Итого 2 разных поста в сутки, лента не приедается.
#
# ВАЖНО: пути абсолютные — launchd не читает профиль оболочки и про MAMP/PATH
# ничего не знает. Если поменяется версия php в MAMP — поправить PHP ниже.
#
set -u
DIR="/Users/igorzagorodnyi/Sites/ad.domexpert"
PHP="/Applications/MAMP/bin/php/php8.2.0/bin/php"

cd "$DIR" || exit 1
STAMP="$(date '+%Y-%m-%d %H:%M')"
HOUR="$(date +%H)"

# 10 -> 09..13 = утро (статья); иначе вечер (оригинальный контент)
if [ "$HOUR" -lt 14 ]; then
  echo "[$STAMP] tg-post (статья)"
  "$PHP" scripts/tg-post.php 1
else
  echo "[$STAMP] tg-content (оригинальный пост)"
  "$PHP" scripts/tg-content.php 1
fi
