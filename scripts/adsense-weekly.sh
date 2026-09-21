#!/bin/bash
#
# adsense-weekly.sh — недельная проверка готовности к AdSense (запускает launchd).
# Гоняет adsense-readiness.sh, пишет лог, и ТОЛЬКО когда статус 🟢 (пора подавать)
# шлёт уведомление macOS. Опционально — сообщение в Telegram владельцу, если в
# scripts/tg.env задан TG_OWNER_CHAT_ID (личный chat_id, узнаётся у @userinfobot).
#
# Пути абсолютные — launchd не читает профиль оболочки. sqlite3 в /usr/bin.
set -u
DIR="/Users/igorzagorodnyi/Sites/ad.domexpert"
export PATH="/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin"
cd "$DIR" || exit 1

LOG="$DIR/scripts/adsense-readiness.log"
STAMP="$(date '+%Y-%m-%d %H:%M')"

OUT="$(bash scripts/adsense-readiness.sh 2>&1)"; CODE=$?
{ echo "===== $STAMP (exit $CODE) ====="; echo "$OUT"; echo; } >> "$LOG"

# 0 = 🟢 «пора подавать» → уведомляем. Жёлтый/красный — тихо, только в лог.
if [ "$CODE" -eq 0 ]; then
  MSG="AdSense: трафик дорос до порога — пора подавать заявку!"
  osascript -e "display notification \"$MSG\" with title \"ДомЭксперт · AdSense\" sound name \"Glass\"" 2>/dev/null

  # Опционально: Telegram владельцу (личный чат, НЕ канал). Включается добавлением
  # строки TG_OWNER_CHAT_ID=... в scripts/tg.env — иначе шаг пропускается.
  if [ -f scripts/tg.env ]; then
    set -a; . scripts/tg.env 2>/dev/null; set +a
    if [ -n "${TG_OWNER_CHAT_ID:-}" ] && [ -n "${TG_BOT_TOKEN:-}" ]; then
      curl -s -m 10 "https://api.telegram.org/bot${TG_BOT_TOKEN}/sendMessage" \
        --data-urlencode "chat_id=${TG_OWNER_CHAT_ID}" \
        --data-urlencode "text=🟢 ${MSG}" >/dev/null 2>&1
    fi
  fi
fi
exit 0
