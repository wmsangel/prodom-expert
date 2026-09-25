#!/usr/bin/env bash
#
# deploy.sh — заливка prodom-expert.ru на прод по FTP (curl), как это делает medi-чат.
#
# Креды лежат в scripts/deploy.env (gitignored):
#   FTP_HOST, FTP_USER, FTP_PASS, REMOTE_ROOT=/prodom-expert.ru/public_html
#
# Режимы:
#   scripts/deploy.sh                 → файлы последнего коммита (git show HEAD)
#   scripts/deploy.sh --working       → незакоммиченные изменения (git status)
#   scripts/deploy.sh --since <ref>   → всё, что изменилось начиная с <ref>..HEAD
#   scripts/deploy.sh path/a path/b   → ровно эти файлы (пути от корня репозитория)
#   scripts/deploy.sh --all           → весь сайт (первичная заливка; долго)
#
# Флаги: --dry-run (только показать список), -y (не спрашивать подтверждение).
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

ENV_FILE="$ROOT/scripts/deploy.env"
[ -f "$ENV_FILE" ] || { echo "✗ нет $ENV_FILE (FTP_HOST/USER/PASS/REMOTE_ROOT)"; exit 1; }
set -a; . "$ENV_FILE"; set +a
: "${FTP_HOST:?}"; : "${FTP_USER:?}"; : "${FTP_PASS:?}"; : "${REMOTE_ROOT:?}"

# Никогда не заливаем: секреты, служебные каталоги, локальные скрипты деплоя, логи.
EXCLUDE_RE='^(\.git/|\.idea/|scripts/|node_modules/|\.DS_Store|.*/\.DS_Store|.*\.env$|.*/tg\.env$|config\.php$|.*\.log$|photos-inbox/|router\.php$)'

DRY=0; YES=0; MODE=""; SINCE=""; declare -a FILES=()
while [ $# -gt 0 ]; do
  case "$1" in
    --dry-run) DRY=1 ;;
    -y|--yes)  YES=1 ;;
    --working) MODE="working" ;;
    --all)     MODE="all" ;;
    --since)   MODE="since"; shift; SINCE="${1:?--since требует <ref>}" ;;
    --*)       echo "неизвестный флаг: $1"; exit 1 ;;
    *)         FILES+=("$1") ;;
  esac
  shift
done

# Собираем список файлов относительно корня репозитория.
declare -a LIST=()
if [ "${#FILES[@]}" -gt 0 ]; then
  LIST=("${FILES[@]}")
elif [ "$MODE" = "working" ]; then
  while IFS= read -r f; do [ -n "$f" ] && LIST+=("$f"); done < <(git status --porcelain | awk '{print $2}')
elif [ "$MODE" = "since" ]; then
  while IFS= read -r f; do [ -n "$f" ] && LIST+=("$f"); done < <(git diff --name-only --diff-filter=ACMR "$SINCE"..HEAD)
elif [ "$MODE" = "all" ]; then
  while IFS= read -r f; do [ -n "$f" ] && LIST+=("$f"); done < <(git ls-files)
else
  # по умолчанию — файлы последнего коммита
  while IFS= read -r f; do [ -n "$f" ] && LIST+=("$f"); done < <(git show --pretty="" --name-only --diff-filter=ACMR HEAD)
fi

# Фильтруем исключения и несуществующие (удалённые) файлы.
declare -a UP=()
for f in "${LIST[@]}"; do
  [[ "$f" =~ $EXCLUDE_RE ]] && continue
  [ -f "$f" ] || { echo "  ⤫ пропуск (нет файла): $f"; continue; }
  UP+=("$f")
done

if [ "${#UP[@]}" -eq 0 ]; then echo "Нечего заливать."; exit 0; fi

echo "Хост:  $FTP_HOST"
echo "Путь:  $REMOTE_ROOT"
echo "Файлов к заливке: ${#UP[@]}"
printf '  • %s\n' "${UP[@]}"

if [ "$DRY" -eq 1 ]; then echo "(dry-run — ничего не отправлено)"; exit 0; fi

if [ "$YES" -ne 1 ]; then
  printf "Залить на ПРОД? [y/N] "
  read -r ans; [[ "$ans" =~ ^[Yy]$ ]] || { echo "Отменено."; exit 0; }
fi

ok=0; fail=0
for f in "${UP[@]}"; do
  code=$(curl -s --show-error --max-time 60 --ftp-pasv --ftp-create-dirs \
              -u "$FTP_USER:$FTP_PASS" -T "$f" "ftp://$FTP_HOST$REMOTE_ROOT/$f" \
              -w '%{http_code}' -o /dev/null 2>/dev/null) || code="ERR"
  if [ "$code" = "226" ] || [ "$code" = "213" ] || [ "$code" = "250" ]; then
    echo "  ✓ $f"; ok=$((ok+1))
  else
    echo "  ✗ $f  (код $code)"; fail=$((fail+1))
  fi
done
echo "Готово: ✓ $ok, ✗ $fail"
[ "$fail" -eq 0 ]
