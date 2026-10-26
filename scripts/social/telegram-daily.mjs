/**
 * Дневная рассылка готового поста в Telegram (ДомЭксперт).
 *
 * Шлёт владельцу в Telegram ГОТОВЫЙ пост на сегодня, чтобы просто скачать и
 * выложить в Instagram: сначала само медиа (картинка / карусель / видео) с
 * короткой подписью-заголовком, затем ОТДЕЛЬНЫМ сообщением — полную подпись
 * с хэштегами (копипаст) и, для сторис, инструкцию по стикеру.
 * Реклама не маркируется (органика).
 *
 * Бот, скорее всего, один на всю сеть проектов — поэтому в заголовке каждого
 * сообщения стоит имя проекта (ДомЭксперт).
 *
 * Источник плана — posts.js (пост на сегодня по дню недели). Слайды берутся из
 * out/<id>-<n>.png (сгенерить: node gen-slides.js). Видео — опциональное поле
 * `video` у поста (путь относительно scripts/social/).
 *
 * Конфиг — scripts/social/.telegram.json (НЕ коммитить; см. .telegram.json.example):
 *   { "bot_token": "123456:ABC...", "chat_id": "123456789" }
 * или env TG_BOT_TOKEN / TG_CHAT_ID.
 *
 * Запуск:  node telegram-daily.mjs            # пост на сегодня
 *          node telegram-daily.mjs p1         # конкретный пост по id (тест)
 * Автозапуск раз в день — launchd (см. README.md).
 */
import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { join, dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { POSTS } from './posts.js';
import PROFILE from './profile.js';

const PROJECT = 'ДомЭксперт';
const SITE = 'prodom-expert.ru';
const HUB_URL = 'https://claude.ai/artifact/8TbDJekuzJwP87TC1vQuhw';
const ROOT = dirname(fileURLToPath(import.meta.url));
const OUT = join(ROOT, 'out');
const PROFILE_DIR = join(ROOT, 'profile');
const he = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const DOW = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];

function config() {
  let c = {};
  try { c = JSON.parse(readFileSync(join(ROOT, '.telegram.json'), 'utf8')); } catch {}
  const bot = process.env.TG_BOT_TOKEN || c.bot_token;
  const chat = process.env.TG_CHAT_ID || c.chat_id;
  if (!bot || !chat) {
    console.error('Нет bot_token/chat_id. Заполни scripts/social/.telegram.json (см. .telegram.json.example) или TG_BOT_TOKEN/TG_CHAT_ID.');
    process.exit(1);
  }
  return { bot, chat };
}

// Слайды поста: out/<id>-<n>.png, по возрастанию номера.
function slidesFor(id) {
  if (!existsSync(OUT)) return [];
  const re = new RegExp(`^${id}-(\\d+)\\.png$`);
  return readdirSync(OUT)
    .map((f) => { const m = f.match(re); return m ? { f, n: +m[1] } : null; })
    .filter(Boolean)
    .sort((a, b) => a.n - b.n)
    .map((x) => join(OUT, x.f));
}

const fmtName = (f) => ({ carousel: 'карусель', story: 'сторис', video: 'видео' }[f] || 'пост');
const header = (post) => `🏠 ${PROJECT} · ${post.day} · ${post.rubric} · ${fmtName(post.format)}`;

function captionBlock(post) {
  const parts = [];
  parts.push(header(post));
  parts.push(`🔗 Ведёт на: ${SITE}${post.page}`);
  if (post.sticker) parts.push(`🎯 Стикер (добавить в IG): ${post.sticker}`);
  parts.push('— — — ПОДПИСЬ (скопировать) — — —');
  parts.push([post.caption, post.hashtags].filter(Boolean).join('\n\n'));
  parts.push(`Хаб со всеми слайдами: ${HUB_URL}`);
  return parts.filter(Boolean).join('\n\n');
}

async function tg(bot, method, form) {
  const res = await fetch(`https://api.telegram.org/bot${bot}/${method}`, { method: 'POST', body: form });
  const data = await res.json();
  if (!data.ok) throw new Error(`${method}: ${data.description}`);
  return data;
}

const fileBlob = (path) => new Blob([readFileSync(path)]);

async function sendPhoto(bot, chat, path, caption) {
  const form = new FormData();
  form.append('chat_id', chat);
  form.append('photo', fileBlob(path), basename(path));
  if (caption) form.append('caption', caption);
  await tg(bot, 'sendPhoto', form);
}

async function sendVideo(bot, chat, path, caption) {
  const form = new FormData();
  form.append('chat_id', chat);
  form.append('video', fileBlob(path), basename(path));
  if (caption) form.append('caption', caption);
  await tg(bot, 'sendVideo', form);
}

// Карусель → альбом (до 10 фото). Подпись к альбому короткая (лимит 1024) —
// полный текст уходит отдельным сообщением.
async function sendAlbum(bot, chat, paths, caption) {
  const chunk = paths.slice(0, 10);
  const form = new FormData();
  form.append('chat_id', chat);
  const media = chunk.map((p, i) => ({ type: 'photo', media: `attach://p${i}`, ...(i === 0 && caption ? { caption } : {}) }));
  form.append('media', JSON.stringify(media));
  chunk.forEach((p, i) => form.append(`p${i}`, fileBlob(p), basename(p)));
  await tg(bot, 'sendMediaGroup', form);
}

async function sendText(bot, chat, text) {
  const form = new FormData();
  form.append('chat_id', chat);
  form.append('text', text);
  form.append('disable_web_page_preview', 'true');
  await tg(bot, 'sendMessage', form);
}

// Документ (без сжатия) — чтобы скачать оригинал PNG.
async function sendDocument(bot, chat, path, caption) {
  const form = new FormData();
  form.append('chat_id', chat);
  form.append('document', fileBlob(path), basename(path));
  if (caption) form.append('caption', caption);
  await tg(bot, 'sendDocument', form);
}

// Текст с тап-для-копирования: значение в <code>…</code> (parse_mode HTML).
async function sendHTML(bot, chat, text) {
  const form = new FormData();
  form.append('chat_id', chat);
  form.append('text', text);
  form.append('parse_mode', 'HTML');
  form.append('disable_web_page_preview', 'true');
  await tg(bot, 'sendMessage', form);
}

// Полное оформление профиля — каждое отдельным сообщением (копипаст/скачать).
async function runProfile(bot, chat) {
  const avatar = join(PROFILE_DIR, 'avatar.png');
  await sendText(bot, chat, `🏠 ${PROJECT} · Оформление профиля Instagram\nСкачай файлы и вставь по чек-листу (последнее сообщение). Тексты в серых блоках — тапни, чтобы скопировать.`);
  // 1) Аватар: файл без сжатия + превью.
  if (existsSync(avatar)) {
    await sendDocument(bot, chat, avatar, '1) Аватар 1080×1080 — файл без сжатия');
    await sendPhoto(bot, chat, avatar, '1) Аватар — превью');
  }
  // 2) Имя + @username.
  await sendHTML(bot, chat, `2) Имя профиля (≤30, с ключевым словом для поиска):\n<code>${he(PROFILE.NAME)}</code>\n\nВарианты @username — занять первый свободный:\n` + PROFILE.USERNAMES.map((u) => `<code>${he(u)}</code>`).join('\n'));
  // 3) Bio.
  await sendHTML(bot, chat, `3) Описание (Bio, ≤150):\n<code>${he(PROFILE.BIO)}</code>`);
  // 4) Ссылка с UTM.
  await sendHTML(bot, chat, `4) Ссылка в профиле (с UTM):\n<code>${he(PROFILE.LINK)}</code>`);
  // 5) Тип и категория.
  await sendText(bot, chat, `5) Тип аккаунта: ${PROFILE.ACCOUNT_TYPE}\nКатегория: «${PROFILE.CATEGORY}»\n(Business — выбрать, если нужна кнопка «Эл. адрес» в профиле.)`);
  // 6) Обложки «Актуального»: превью-альбом + файлы + названия.
  const hls = PROFILE.HIGHLIGHTS.map((h) => ({ ...h, path: join(PROFILE_DIR, `hl-${h.id}.png`) })).filter((h) => existsSync(h.path));
  if (hls.length) {
    await sendAlbum(bot, chat, hls.map((h) => h.path), '6) Обложки «Актуального» — превью (без текста, круг обрезает)');
    for (const h of hls) await sendDocument(bot, chat, h.path, `Актуальное: ${h.name}`);
    await sendText(bot, chat, '6) Названия «Актуального» (по порядку):\n' + hls.map((h, i) => `${i + 1}. ${h.name}`).join('\n'));
  }
  // 7) Контакт.
  await sendHTML(bot, chat, `7) Контакт (рабочая почта):\n<code>${he(PROFILE.CONTACT_EMAIL)}</code>`);
  // 8) Закрепить 3 поста.
  const pinLine = PROFILE.PINS.map((p) => { const post = POSTS.find((x) => x.id === p.id); return `• ${post ? `${post.rubric} (${post.day})` : p.id} — ${p.why}`; }).join('\n');
  await sendText(bot, chat, `8) Закрепить 3 поста:\n${pinLine}`);
  // 9) Чек-лист.
  await sendText(bot, chat, '9) Чек-лист «что нажать в Instagram»:\n' + PROFILE.CHECKLIST.map((s, i) => `${i + 1}. ${s}`).join('\n'));
  console.log('Профиль отправлен в Telegram.');
}

// ─── main ───
const { bot, chat } = config();
const arg = process.argv[2];

if (arg === '--profile') {
  try { await runProfile(bot, chat); process.exit(0); }
  catch (e) { console.error('Telegram ошибка:', e.message); process.exit(1); }
}

const today = DOW[new Date().getDay()];
const post = arg ? POSTS.find((p) => p.id === arg) : POSTS.find((p) => p.day === today);

if (!post) {
  console.log(arg ? `Пост «${arg}» не найден.` : `На ${today} поста в плане нет — выходной.`);
  process.exit(0);
}

const head = header(post);
const video = post.video ? join(ROOT, post.video) : null;

try {
  // 1) Медиа с короткой подписью-заголовком.
  if (video && existsSync(video)) {
    await sendVideo(bot, chat, video, head);
  }
  const slides = slidesFor(post.id);
  if (slides.length > 1) await sendAlbum(bot, chat, slides, head);
  else if (slides.length === 1) await sendPhoto(bot, chat, slides[0], head);
  else if (!video) console.warn(`Нет слайдов для ${post.id} — запусти node gen-slides.js`);
  // 2) Полная подпись с тегами — отдельным сообщением (копипаст).
  await sendText(bot, chat, captionBlock(post));
  console.log(`Отправлено в Telegram: ${today} · ${post.rubric} (${post.id})`);
} catch (e) {
  console.error('Telegram ошибка:', e.message);
  process.exit(1);
}
