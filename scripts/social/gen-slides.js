#!/usr/bin/env node
/*
 * gen-slides.js — генератор слайдов для соцсетей ДомЭксперт.
 * SVG → PNG через sharp (как OG-картинки). Фирменный стиль: графит + охра-акцент,
 * шрифт IBM Plex Sans (сетевой; стоит в fontconfig). RU-only.
 *
 * Форматы: 1080×1080 (лента) и 1080×1920 (сторис). Фото статей — реальные обложки
 * assets/img/articles/<slug>.(webp|jpg): sharp resize cover + composite overlay-SVG.
 *
 * Запуск:  node scripts/social/gen-slides.js            — весь контент-план
 *          node scripts/social/gen-slides.js p1 p3       — только посты p1, p3
 *
 * Выход:   scripts/social/out/<postId>-<n>.png
 */
'use strict';
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const ROOT = path.resolve(__dirname, '../..');
const OUT = path.join(__dirname, 'out');
const COVERS = path.join(ROOT, 'assets/img/articles');
const PLAN = require('./content-plan.js');

// ── Палитра (графит сети + охра-акцент из HUB.md) ──
const C = {
  bg:      '#14161b',  // графит тёмный
  surface: '#1b1e25',
  ink:     '#eef0f4',  // текст на тёмном
  muted:   '#9aa0ad',
  line:    '#2b303a',
  accent:  '#7a6630',  // охра (HUB.md) — для фонов-акцентов
  accentL: '#d0b36a',  // светлая охра — для текста/акцентов на тёмном
  ok:      '#6bbd8e',
  bad:     '#e08383',
  onAccent:'#faf7ee',
};
const FONT = 'IBM Plex Sans';

// ── Утилиты ──
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// Перенос строки по ширине (оценка ширины глифа IBM Plex Sans ≈ 0.54·fontSize для кириллицы).
function wrap(text, fontSize, maxWidth, weightFactor = 0.54) {
  const perChar = fontSize * weightFactor;
  const maxChars = Math.max(6, Math.floor(maxWidth / perChar));
  const words = String(text).split(/\s+/);
  const lines = [];
  let cur = '';
  for (const w of words) {
    const t = cur ? cur + ' ' + w : w;
    if (t.length <= maxChars) { cur = t; }
    else { if (cur) lines.push(cur); cur = w; }
  }
  if (cur) lines.push(cur);
  return lines;
}

function tspans(lines, x, startY, lineH) {
  return lines.map((l, i) => `<tspan x="${x}" y="${startY + i * lineH}">${esc(l)}</tspan>`).join('');
}

// SVG-иконки (векторные, не эмодзи)
function iconCheck(x, y, s, color) {
  return `<path d="M ${x} ${y + s * 0.55} l ${s * 0.3} ${s * 0.32} l ${s * 0.55} ${-s * 0.72}" fill="none" stroke="${color}" stroke-width="${s * 0.14}" stroke-linecap="round" stroke-linejoin="round"/>`;
}
function iconCross(x, y, s, color) {
  const w = s * 0.14;
  return `<path d="M ${x + s * 0.12} ${y + s * 0.12} l ${s * 0.66} ${s * 0.66} M ${x + s * 0.78} ${y + s * 0.12} l ${-s * 0.66} ${s * 0.66}" stroke="${color}" stroke-width="${w}" stroke-linecap="round"/>`;
}

// Шапка бренда (логотип-слово + домен)
function brand(x, y, light = true) {
  const col = light ? C.ink : C.onAccent;
  return `
    <text x="${x}" y="${y}" font-family="${FONT}" font-weight="700" font-size="34" fill="${col}">ДомЭксперт</text>
    <text x="${x}" y="${y + 30}" font-family="${FONT}" font-weight="500" font-size="22" fill="${light ? C.accentL : C.onAccent}" opacity="0.85">prodom-expert.ru</text>`;
}

// Чип рубрики
function chip(x, y, label, bg = C.accent, fg = C.onAccent) {
  const w = 40 + label.length * 15;
  return `
    <rect x="${x}" y="${y}" rx="22" width="${w}" height="44" fill="${bg}"/>
    <text x="${x + w / 2}" y="${y + 29}" text-anchor="middle" font-family="${FONT}" font-weight="600" font-size="22" letter-spacing="1" fill="${fg}">${esc(label.toUpperCase())}</text>`;
}

// ── Шаблоны (возвращают {svg, photo?}) ──
// size: {W,H}. Все координаты — в пикселях целевого размера.

function tplCover(s, size) {
  const { W, H } = size;
  const pad = 80;
  const titleSize = W === H ? 66 : 74;
  const lines = wrap(s.title, titleSize, W - pad * 2, 0.5);
  const lineH = titleSize * 1.12;
  const titleTop = H - pad - (lines.length - 1) * lineH - 10;
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <defs>
      <linearGradient id="grad" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#000" stop-opacity="0.55"/>
        <stop offset="0.45" stop-color="#000" stop-opacity="0.12"/>
        <stop offset="0.72" stop-color="#000" stop-opacity="0.45"/>
        <stop offset="1" stop-color="#000" stop-opacity="0.86"/>
      </linearGradient>
    </defs>
    <rect width="${W}" height="${H}" fill="url(#grad)"/>
    ${brand(pad, pad + 24, true)}
    ${chip(pad, titleTop - 74, s.rubric)}
    <text font-family="${FONT}" font-weight="700" font-size="${titleSize}" fill="#fff" letter-spacing="-0.5">
      ${tspans(lines, pad, titleTop, lineH)}
    </text>
  </svg>`;
  return { svg, photo: s.photo };
}

function tplText(s, size, idx, total) {
  const { W, H } = size;
  const pad = 90;
  const kSize = 60, kH = kSize * 1.18;
  const heads = wrap(s.head || '', kSize, W - pad * 2, 0.52);
  const bSize = 40, bH = bSize * 1.4;
  const body = wrap(s.body || '', bSize, W - pad * 2, 0.54);
  const headTop = W === H ? 300 : 520;
  const bodyTop = headTop + heads.length * kH + 54;
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <rect width="${W}" height="${H}" fill="${C.bg}"/>
    <rect x="0" y="0" width="${W}" height="8" fill="${C.accent}"/>
    ${brand(pad, pad + 20, true)}
    ${total ? `<text x="${W - pad}" y="${pad + 44}" text-anchor="end" font-family="${FONT}" font-weight="600" font-size="30" fill="${C.accentL}">${idx}/${total}</text>` : ''}
    <text font-family="${FONT}" font-weight="700" font-size="${kSize}" fill="${C.ink}" letter-spacing="-0.5">${tspans(heads, pad, headTop, kH)}</text>
    <text font-family="${FONT}" font-weight="400" font-size="${bSize}" fill="${C.muted}">${tspans(body, pad, bodyTop, bH)}</text>
  </svg>`;
  return { svg };
}

function tplList(s, size, idx, total) {
  const { W, H } = size;
  const pad = 90;
  const hSize = 54;
  const heads = wrap(s.head || '', hSize, W - pad * 2, 0.52);
  const hH = hSize * 1.14;
  let y = (W === H ? 250 : 430) + heads.length * hH + 40;
  const rowSize = 38, icon = 46;
  const rows = (s.items || []).map((it) => {
    const txt = typeof it === 'string' ? it : it.t;
    const good = typeof it === 'string' ? true : it.ok !== false;
    const lines = wrap(txt, rowSize, W - pad * 2 - icon - 28, 0.54);
    const ic = good ? iconCheck(pad, y, icon, C.ok) : iconCross(pad, y, icon, C.bad);
    const t = `<text font-family="${FONT}" font-weight="500" font-size="${rowSize}" fill="${C.ink}">${tspans(lines, pad + icon + 28, y + 36, rowSize * 1.3)}</text>`;
    const block = ic + t;
    y += Math.max(icon + 26, lines.length * rowSize * 1.3 + 22);
    return block;
  }).join('');
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <rect width="${W}" height="${H}" fill="${C.bg}"/>
    <rect x="0" y="0" width="${W}" height="8" fill="${C.accent}"/>
    ${brand(pad, pad + 20, true)}
    ${total ? `<text x="${W - pad}" y="${pad + 44}" text-anchor="end" font-family="${FONT}" font-weight="600" font-size="30" fill="${C.accentL}">${idx}/${total}</text>` : ''}
    <text font-family="${FONT}" font-weight="700" font-size="${hSize}" fill="${C.ink}" letter-spacing="-0.5">${tspans(heads, pad, (W === H ? 250 : 430), hH)}</text>
    ${rows}
  </svg>`;
  return { svg };
}

function tplCta(s, size) {
  const { W, H } = size;
  const pad = 90;
  const hSize = 60;
  const heads = wrap(s.head || '', hSize, W - pad * 2, 0.5);
  const hH = hSize * 1.14;
  const top = (W === H ? 360 : 620);
  const bSize = 38;
  const body = wrap(s.body || '', bSize, W - pad * 2, 0.54);
  const bodyTop = top + heads.length * hH + 40;
  const plateY = bodyTop + body.length * bSize * 1.4 + 50;
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <rect width="${W}" height="${H}" fill="${C.accent}"/>
    ${brand(pad, pad + 20, false)}
    <text font-family="${FONT}" font-weight="700" font-size="${hSize}" fill="${C.onAccent}" letter-spacing="-0.5">${tspans(heads, pad, top, hH)}</text>
    <text font-family="${FONT}" font-weight="400" font-size="${bSize}" fill="${C.onAccent}" opacity="0.9">${tspans(body, pad, bodyTop, bSize * 1.4)}</text>
    <rect x="${pad}" y="${plateY}" rx="16" width="${W - pad * 2}" height="92" fill="${C.onAccent}"/>
    <text x="${pad + 36}" y="${plateY + 58}" font-family="${FONT}" font-weight="600" font-size="36" fill="${C.accent}">${esc(s.cta || 'Ссылка в профиле →')}</text>
  </svg>`;
  return { svg };
}

function tplMythFact(s, size) {
  const { W, H } = size;
  const pad = 90;
  const mSize = 46, mH = mSize * 1.2;
  const myth = wrap(s.myth, mSize, W - pad * 2, 0.54);
  const fSize = 50, fH = fSize * 1.16;
  const fact = wrap(s.fact, fSize, W - pad * 2, 0.52);
  const top = W === H ? 250 : 430;
  const mTop = top + 60;
  const factTop = mTop + myth.length * mH + 110;
  const fBodyTop = factTop + fact.length * fH + 30;
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <rect width="${W}" height="${H}" fill="${C.bg}"/>
    ${brand(pad, pad + 20, true)}
    ${chip(pad, top - 20, 'Миф', C.bad, '#fff')}
    <text font-family="${FONT}" font-weight="600" font-size="${mSize}" fill="${C.muted}"><tspan>${tspans(myth, pad, mTop + 40, mH)}</tspan></text>
    ${chip(pad, factTop - 70, 'Факт', C.ok, '#06210f')}
    <text font-family="${FONT}" font-weight="700" font-size="${fSize}" fill="${C.ink}" letter-spacing="-0.5">${tspans(fact, pad, factTop, fH)}</text>
    <text x="${pad}" y="${Math.min(H - pad, fBodyTop + 40)}" font-family="${FONT}" font-weight="500" font-size="32" fill="${C.accentL}">${esc(s.cta || 'Разбор — в профиле →')}</text>
  </svg>`;
  return { svg };
}

function tplStory(s, size) {
  const { W, H } = size; // ожидается 1080×1920
  const pad = 90;
  const qSize = 64;
  const q = wrap(s.question, qSize, W - pad * 2, 0.5);
  const qH = qSize * 1.16;
  const svg = `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" xmlns="http://www.w3.org/2000/svg">
    <rect width="${W}" height="${H}" fill="${C.bg}"/>
    <rect x="0" y="0" width="${W}" height="8" fill="${C.accent}"/>
    ${brand(pad, 200, true)}
    <text font-family="${FONT}" font-weight="700" font-size="${qSize}" fill="${C.ink}" letter-spacing="-0.5">${tspans(q, pad, 560, qH)}</text>
    <text x="${W / 2}" y="${H - 150}" text-anchor="middle" font-family="${FONT}" font-weight="500" font-size="30" fill="${C.muted}">${esc(s.hint || 'Добавь стикер «Опрос» нативно в Stories')}</text>
  </svg>`;
  return { svg };
}

const TEMPLATES = { cover: tplCover, text: tplText, list: tplList, cta: tplCta, mythfact: tplMythFact, story: tplStory };

async function resolvePhoto(slug) {
  for (const ext of ['webp', 'jpg', 'jpeg', 'png']) {
    const p = path.join(COVERS, `${slug}.${ext}`);
    if (fs.existsSync(p)) return p;
  }
  return null;
}

async function renderSlide(spec, size, outFile) {
  const tpl = TEMPLATES[spec.type];
  if (!tpl) throw new Error('нет шаблона: ' + spec.type);
  let photo = null;
  if (spec.type === 'cover' && spec.cover) photo = await resolvePhoto(spec.cover);
  const built = tpl({ ...spec, photo }, size, spec._idx, spec._total);
  const svgBuf = Buffer.from(built.svg);
  if (built.photo) {
    const base = await sharp(built.photo).resize(size.W, size.H, { fit: 'cover', position: 'attention' }).toBuffer();
    await sharp(base).composite([{ input: svgBuf, top: 0, left: 0 }]).png().toFile(outFile);
  } else {
    await sharp(svgBuf, { density: 72 }).resize(size.W, size.H).png().toFile(outFile);
  }
}

async function main() {
  if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });
  const only = new Set(process.argv.slice(2));
  const posts = PLAN.posts.filter((p) => !only.size || only.has(p.id));
  let n = 0;
  for (const post of posts) {
    const story = post.format === 'story';
    const size = story ? { W: 1080, H: 1920 } : { W: 1080, H: 1080 };
    const slides = post.slides.map((s, i) => ({ ...s, _idx: i + 1, _total: post.slides.length > 1 ? post.slides.length : 0 }));
    for (let i = 0; i < slides.length; i++) {
      const out = path.join(OUT, `${post.id}-${i + 1}.png`);
      await renderSlide(slides[i], size, out);
      n++;
      process.stdout.write(`  ✓ ${post.id}-${i + 1}.png  (${post.rubric})\n`);
    }
  }
  console.log(`Готово: ${n} слайдов в ${path.relative(ROOT, OUT)}/`);
}

main().catch((e) => { console.error('ОШИБКА:', e.message); process.exit(1); });
