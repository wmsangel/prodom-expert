# Соцсети ДомЭксперт

Модуль для быстрого постинга: генерим слайды, берём текст из хаба, публикуем вручную в
Instagram (лента + сторис), Pinterest и Telegram. Стиль — графит сети + охра-акцент (HUB.md),
шрифт IBM Plex Sans. RU-only, честный тон.

## Файлы
- `posts.js` — **единый источник** контент-плана (неделя = 7 постов, Пн–Вс): id, день, формат
  (post/carousel/story), рубрика, page/target, caption, хэштеги, sticker (сторис), slides[].
  Используется и генератором слайдов, и дневной рассылкой — второй копии текста нет.
- `gen-slides.js` — генератор слайдов (Node + sharp, SVG→PNG). Шаблоны: cover, text, list(✓/✗),
  cta, mythfact, story. Форматы 1080×1080 (лента) и 1080×1920 (сторис). Фото статей — реальные
  обложки `assets/img/articles/<slug>.(webp|jpg)`.
- `scripts/telegram-daily.mjs` — дневная рассылка готового поста владельцу (см. ниже).
- `.telegram.json.example` — образец конфига бота (сам `.telegram.json` — в .gitignore, секрет).
- `com.prodom.social.telegram-daily.plist` — launchd-агент автозапуска (08:05).
- `out/` — сгенерированные PNG (`<id>-<n>.png`). Регенерируемы, в git не нужны.

## Запуск
```bash
npm install sharp                      # один раз (IBM Plex Sans уже в fontconfig)
node scripts/social/gen-slides.js      # все посты → scripts/social/out/
node scripts/social/gen-slides.js p1 p3  # только выбранные
```

## Дневная рассылка готового поста владельцу (Telegram)
`scripts/telegram-daily.mjs` раз в день присылает владельцу в Telegram-бота ГОТОВЫЙ пост
на сегодня, чтобы просто скачать и выложить в Instagram: сначала само медиа (картинка /
карусель / видео) с короткой подписью-заголовком `🏠 ДомЭксперт · день · рубрика · формат`,
затем ОТДЕЛЬНЫМ сообщением — полную подпись с хэштегами (копипаст) и, для сторис, какой
нативный стикер добавить. Бот, скорее всего, один на всю сеть — поэтому имя проекта в каждом
заголовке. Это НЕ канальный автопостер (см. ниже) — это личная рассылка владельцу.

Node 20, без зависимостей (fetch + FormData + Blob). Источник — `posts.js`, слайды — из `out/`.

Настройка (один раз):
1. Создать бота в [@BotFather](https://t.me/BotFather) → `/newbot` → получить токен.
2. `cp .telegram.json.example .telegram.json` и вставить `bot_token`. Нажать боту `/start`.
3. Узнать `chat_id`: `curl -s "https://api.telegram.org/bot<ТОКЕН>/getUpdates" | grep -o '"chat":{"id":[0-9-]*'` → вписать в `.telegram.json`.
4. Тест: `node scripts/telegram-daily.mjs p1` (конкретный пост) или без аргумента — пост на сегодня.
5. Автозапуск: `cp com.prodom.social.telegram-daily.plist ~/Library/LaunchAgents/ && launchctl load ~/Library/LaunchAgents/com.prodom.social.telegram-daily.plist` (08:05; Mac должен быть включён).

Конфиг: `.telegram.json` `{bot_token, chat_id}` или env `TG_BOT_TOKEN`/`TG_CHAT_ID`.

## Хаб-страница (артефакт)
Публикуется как приватный артефакт: слайды картинками (files → `slides/<id>-<n>.png`),
кнопки «копировать» текст/хэштеги, галочки «опубликовано» (localStorage), подсветка сегодня,
тема, инструкция по сохранению (долгий тап / правый клик — у артефакта нет кнопки «Скачать»).
Ссылка — в HUB.md (строка `Соцсети:`) и в витрине. Текущий URL: https://claude.ai/artifact/8TbDJekuzJwP87TC1vQuhw
Обновить: пере-сгенерить слайды → переопубликовать артефакт на тот же URL с новыми files.

## Telegram (переиспользуем существующий автопостер)
Канал: https://t.me/prodom_expert. Автопостинг УЖЕ работает — launchd `com.prodom.tg-daily`
постит дважды в день: утром статья (`tg-post.php`), вечером оригинальный пост/опрос
(`tg-content.php` из `scripts/tg-content.jsonl`, по кругу). Токен/канал — в `scripts/tg.env`.
Новые вечнозелёные посты из этого плана ДОБАВЛЯЕМ строкой в `scripts/tg-content.jsonl`
(type text/poll) — они сами попадут в ротацию. Отдельный скрипт не нужен.
Проверка следующего поста: `php scripts/tg-content.php --dry`.

## Сети (выбор и обоснование)
- **Instagram** (лента + сторис, вручную из хаба) — визуальный охват, сторис-опросы.
- **Pinterest** — ПРИОРИТЕТ: поисковик, пины живут месяцами, лучший фит под «только органика»;
  карусельные слайды → вертикальные пины на статьи/калькуляторы.
- **Telegram** (сеть) — t.me/prodom_expert, дневное напоминание/анонсы.
- **Дзен** (опц., RU) — поиск + лента Яндекса, монетизируется; разборы заходят.
- Видео (Reels/Shorts/TikTok) — пока не берём.
Честно: IG/TG без встроенного поиска растут медленно; при сильном SEO начинать с Pinterest/Дзен.
