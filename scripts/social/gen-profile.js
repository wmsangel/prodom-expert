#!/usr/bin/env node
/*
 * gen-profile.js — аватар и обложки «Актуального» для Instagram ДомЭксперт.
 * SVG → PNG через sharp. Стиль: графит + охра, IBM Plex Sans (как gen-slides.js).
 *
 * Выход: scripts/social/profile/avatar.png (1080×1080) и hl-<id>.png (1080×1920).
 * Запуск: node scripts/social/gen-profile.js
 */
'use strict';
const fs = require('fs');
const path = require('path');
const sharp = require('sharp');
const { HIGHLIGHTS } = require('./profile.js');

const OUT = path.join(__dirname, 'profile');
fs.mkdirSync(OUT, { recursive: true });

// Палитра (как gen-slides.js).
const C = { bg: '#14161b', ink: '#eef0f4', accent: '#7a6630', accentL: '#d0b36a', line: '#2b303a' };
const FONT = 'IBM Plex Sans';
const render = (svg, out) => sharp(Buffer.from(svg)).png().toFile(out);

// ── Иконки (центр cx,cy; размер s; цвет col; обводка lw) ──
function icon(name, cx, cy, s, col, lw) {
  const H = s / 2;
  switch (name) {
    case 'house': {
      const w = s * 0.46, roofY = cy - s * 0.42, eave = cy - s * 0.06, baseY = cy + s * 0.46;
      return `
        <path d="M ${cx - w - s * 0.08} ${eave} L ${cx} ${roofY} L ${cx + w + s * 0.08} ${eave}" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linejoin="round" stroke-linecap="round"/>
        <path d="M ${cx - w} ${eave} L ${cx - w} ${baseY} L ${cx + w} ${baseY} L ${cx + w} ${eave}" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linejoin="round"/>
        <path d="M ${cx - w * 0.42} ${cy + s * 0.14} l ${w * 0.3} ${w * 0.3} l ${w * 0.6} ${-w * 0.66}" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linecap="round" stroke-linejoin="round"/>`;
    }
    case 'bolt':
      return `<path d="M ${cx + s * 0.12} ${cy - H} L ${cx - s * 0.28} ${cy + s * 0.08} L ${cx - s * 0.02} ${cy + s * 0.08} L ${cx - s * 0.12} ${cy + H} L ${cx + s * 0.3} ${cy - s * 0.12} L ${cx + s * 0.03} ${cy - s * 0.12} Z" fill="${col}"/>`;
    case 'drop':
      return `<path d="M ${cx} ${cy - H} C ${cx + s * 0.5} ${cy + s * 0.05}, ${cx + s * 0.34} ${cy + H}, ${cx} ${cy + H} C ${cx - s * 0.34} ${cy + H}, ${cx - s * 0.5} ${cy + s * 0.05}, ${cx} ${cy - H} Z" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linejoin="round"/>`;
    case 'roller': {
      const rx = cx - s * 0.34, ry = cy - s * 0.42, rw = s * 0.68, rh = s * 0.3;
      return `
        <rect x="${rx}" y="${ry}" width="${rw}" height="${rh}" rx="${s * 0.06}" fill="none" stroke="${col}" stroke-width="${lw}"/>
        <path d="M ${cx + s * 0.1} ${ry + rh} L ${cx + s * 0.1} ${cy} L ${cx - s * 0.12} ${cy} L ${cx - s * 0.12} ${cy + H}" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linecap="round" stroke-linejoin="round"/>`;
    }
    case 'calc': {
      const bx = cx - s * 0.36, by = cy - H, bw = s * 0.72, bh = s;
      const dots = [];
      for (let r = 0; r < 2; r++) for (let c = 0; c < 3; c++)
        dots.push(`<circle cx="${bx + bw * (0.24 + c * 0.26)}" cy="${by + bh * (0.56 + r * 0.22)}" r="${s * 0.045}" fill="${col}"/>`);
      return `
        <rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="${s * 0.08}" fill="none" stroke="${col}" stroke-width="${lw}"/>
        <rect x="${bx + bw * 0.16}" y="${by + bh * 0.12}" width="${bw * 0.68}" height="${bh * 0.2}" rx="${s * 0.03}" fill="${col}"/>
        ${dots.join('')}`;
    }
    case 'clipboard': {
      const bx = cx - s * 0.32, by = cy - s * 0.44, bw = s * 0.64, bh = s * 0.9;
      return `
        <rect x="${bx}" y="${by}" width="${bw}" height="${bh}" rx="${s * 0.07}" fill="none" stroke="${col}" stroke-width="${lw}"/>
        <rect x="${cx - s * 0.13}" y="${by - s * 0.07}" width="${s * 0.26}" height="${s * 0.14}" rx="${s * 0.03}" fill="${col}"/>
        <path d="M ${cx - s * 0.18} ${cy + s * 0.04} l ${s * 0.14} ${s * 0.14} l ${s * 0.26} ${-s * 0.3}" fill="none" stroke="${col}" stroke-width="${lw}" stroke-linecap="round" stroke-linejoin="round"/>`;
    }
    case 'bulb':
      return `
        <circle cx="${cx}" cy="${cy - s * 0.1}" r="${s * 0.34}" fill="none" stroke="${col}" stroke-width="${lw}"/>
        <path d="M ${cx - s * 0.14} ${cy + s * 0.26} L ${cx + s * 0.14} ${cy + s * 0.26} M ${cx - s * 0.1} ${cy + s * 0.4} L ${cx + s * 0.1} ${cy + s * 0.4}" stroke="${col}" stroke-width="${lw}" stroke-linecap="round"/>
        <path d="M ${cx} ${cy - s * 0.1} l ${-s * 0.1} ${s * 0.16} M ${cx} ${cy + s * 0.06} l ${s * 0.1} ${-s * 0.14}" stroke="${col}" stroke-width="${lw * 0.8}" stroke-linecap="round"/>`;
    default:
      return '';
  }
}

// ── Аватар 1080×1080 ──
function avatarSVG() {
  const W = 1080, cx = 540;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${W}" viewBox="0 0 ${W} ${W}">
    <rect width="${W}" height="${W}" fill="${C.bg}"/>
    <circle cx="${cx}" cy="430" r="300" fill="none" stroke="${C.line}" stroke-width="3"/>
    ${icon('house', cx, 430, 360, C.accentL, 26)}
    <text x="${cx}" y="820" text-anchor="middle" font-family="${FONT}" font-size="128" font-weight="800" fill="${C.ink}" letter-spacing="-2">ДомЭксперт</text>
    <rect x="${cx - 150}" y="880" width="300" height="10" rx="5" fill="${C.accent}"/>
    <text x="${cx}" y="968" text-anchor="middle" font-family="${FONT}" font-size="46" font-weight="600" fill="${C.accentL}" letter-spacing="6">РЕМОНТ · СТРОЙКА</text>
  </svg>`;
}

// ── Обложка «Актуального» 1080×1920 (иконка в центре под круговой кроп IG) ──
function highlightSVG(iconName) {
  const W = 1080, H = 1920, cx = 540, cy = 960;
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
    <rect width="${W}" height="${H}" fill="${C.bg}"/>
    <circle cx="${cx}" cy="${cy}" r="300" fill="none" stroke="${C.accent}" stroke-width="6"/>
    ${icon(iconName, cx, cy, 300, C.accentL, 30)}
  </svg>`;
}

(async () => {
  await render(avatarSVG(), path.join(OUT, 'avatar.png'));
  process.stdout.write('  ✓ avatar.png (1080×1080)\n');
  for (const h of HIGHLIGHTS) {
    await render(highlightSVG(h.icon), path.join(OUT, `hl-${h.id}.png`));
    process.stdout.write(`  ✓ hl-${h.id}.png (${h.name})\n`);
  }
  process.stdout.write(`Готово: профиль в ${path.relative(process.cwd(), OUT)}/\n`);
})();
