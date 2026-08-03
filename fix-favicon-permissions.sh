#!/bin/bash
# Запустить один раз из Terminal:
# bash ~/Sites/ad.domexpert/fix-favicon-permissions.sh

SITE="$HOME/Sites/ad.domexpert"

echo "Исправляем права на favicon-файлы..."
chmod 644 "$SITE/favicon.ico"
chmod 644 "$SITE/assets/img/favicon.svg"
chmod 644 "$SITE/assets/img/favicon-16.png"
chmod 644 "$SITE/assets/img/favicon-32.png"
chmod 644 "$SITE/assets/img/favicon-120.png"

echo "Результат:"
ls -la "$SITE/favicon.ico" "$SITE/assets/img/favicon"*.png "$SITE/assets/img/favicon.svg"

echo ""
echo "Готово! Права установлены 644 — веб-сервер сможет отдать файлы."
echo "После деплоя отправьте страницу /favicon.ico на переобход в Яндекс.Вебмастере."
