#!/bin/bash
#
# adsense-readiness.sh — готов ли prodom-expert к повторной подаче в AdSense.
# По данным Google Search Console (локальная база seo-stats) считает органический
# трафик за 28 дней и даёт вердикт: рано / близко / пора подавать.
#
# Причина прошлого отказа — не сайт (контент/страницы/ads.txt готовы), а низкий
# трафик из поиска. Скрипт следит именно за этим порогом, чтобы подать в нужный
# момент, а не гадать. Запуск: bash scripts/adsense-readiness.sh
#
set -u
DB="$HOME/.config/seo-stats/stats.db"
PROJECT="prodom-expert.ru"

# --- Пороги готовности (правь при желании) ---
MIN_CLICKS=15        # минимум кликов из поиска за 28 дней
MIN_NEAR_P1=5        # минимум запросов на позиции < 20 (страница 1-2)

q(){ sqlite3 "$DB" "$1" 2>/dev/null; }

if [ ! -f "$DB" ]; then echo "❌ База GSC не найдена: $DB"; exit 1; fi
HAS=$(q "SELECT COUNT(*) FROM gsc_breakdown WHERE project='$PROJECT';")
if [ "${HAS:-0}" = "0" ]; then echo "❌ В базе нет данных по $PROJECT"; exit 1; fi

MAXD=$(q "SELECT MAX(date) FROM gsc_breakdown WHERE project='$PROJECT';")
W28="date > date('$MAXD','-28 day')"
WPREV="date > date('$MAXD','-56 day') AND date <= date('$MAXD','-28 day')"
BASE="FROM gsc_breakdown WHERE project='$PROJECT' AND dimension='query'"

CLICKS=$(q "SELECT CAST(COALESCE(SUM(clicks),0) AS INT) $BASE AND $W28;")
IMPR=$(q "SELECT CAST(COALESCE(SUM(impressions),0) AS INT) $BASE AND $W28;")
POS=$(q "SELECT ROUND(AVG(position),1) $BASE AND $W28;")
CLICKS_PREV=$(q "SELECT CAST(COALESCE(SUM(clicks),0) AS INT) $BASE AND $WPREV;")
NEAR_P1=$(q "SELECT COUNT(*) FROM (SELECT key FROM gsc_breakdown WHERE project='$PROJECT' AND dimension='query' AND $W28 GROUP BY key HAVING AVG(position)<20 AND SUM(impressions)>=3);")
ON_P1=$(q "SELECT COUNT(*) FROM (SELECT key FROM gsc_breakdown WHERE project='$PROJECT' AND dimension='query' AND $W28 GROUP BY key HAVING AVG(position)<10 AND SUM(impressions)>=3);")

CLICKS=${CLICKS:-0}; IMPR=${IMPR:-0}; NEAR_P1=${NEAR_P1:-0}; ON_P1=${ON_P1:-0}; CLICKS_PREV=${CLICKS_PREV:-0}
TREND="→"
[ "$CLICKS" -gt "$CLICKS_PREV" ] && TREND="↑ рост"
[ "$CLICKS" -lt "$CLICKS_PREV" ] && TREND="↓ спад"

echo "════════════════════════════════════════════════"
echo " AdSense-readiness · $PROJECT · данные до $MAXD"
echo "════════════════════════════════════════════════"
echo " Клики из поиска (28 дн):     $CLICKS   (было $CLICKS_PREV, $TREND)"
echo " Показы (28 дн):              $IMPR"
echo " Средняя позиция:             ${POS:-—}"
echo " Запросов на стр. 1-2 (<20):  $NEAR_P1"
echo " Запросов на стр. 1 (<10):    $ON_P1"
echo " Порог: клики ≥ $MIN_CLICKS  и  запросов<20 ≥ $MIN_NEAR_P1"
echo "────────────────────────────────────────────────"

if [ "$CLICKS" -ge "$MIN_CLICKS" ] && [ "$NEAR_P1" -ge "$MIN_NEAR_P1" ]; then
  echo " ✅ ПОРА ПОДАВАТЬ: трафик дорос до порога."
  echo "    Сайт технически готов давно — подавай заявку в AdSense."
elif [ "$CLICKS" -ge 5 ]; then
  echo " 🟡 БЛИЗКО: реальные клики пошли, но ещё ниже порога. Продолжаем"
  echo "    контент и перелинковку, проверяем через неделю-две."
else
  echo " 🔴 РАНО: кликов из поиска почти нет — подача = вероятный отказ"
  echo "    «Бесполезный контент». Работаем над трафиком, подачу не тратим."
  [ "$NEAR_P1" -ge 3 ] && echo "    ➕ Но позиции формируются ($NEAR_P1 запросов < 20) — направление верное."
fi
echo "════════════════════════════════════════════════"

# Топ запросов ближе всего к странице 1 — куда дожимать перелинковкой
echo " Ближе всего к топу (дожать → быстрее клики):"
q "SELECT '   '||key||'  ~поз '||ROUND(AVG(position),1)||', показов '||SUM(impressions)
   FROM gsc_breakdown WHERE project='$PROJECT' AND dimension='query' AND $W28
   GROUP BY key HAVING SUM(impressions)>=5 ORDER BY AVG(position) ASC LIMIT 8;"
