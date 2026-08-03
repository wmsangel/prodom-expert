#!/usr/bin/env python3
"""Публикация партии статей от 19 июля 2026.

Тексты статей пишутся отдельно в articles/<cat>/<slug>.html.
Этот скрипт: рисует обложки и таблицы-инфографику, затем регистрирует статьи
во всех реестрах сайта (all-articles-meta.php, article.php, rss.php, search.php,
sitemap.xml). Идемпотентен: уже зарегистрированные slug'и пропускаются.
"""
from __future__ import annotations

import json
import os
import re
import sys
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
ARTICLES = ROOT / "articles"
COVERS = ROOT / "assets" / "img" / "articles"
INLINE = ROOT / "assets" / "img" / "inline"
TABLES = Path(
    "/private/tmp/claude-501/-Users-igorzagorodnyi-Sites-ad-domexpert/"
    "ba780b13-b80d-4620-a6d2-381ba732cda3/scratchpad/tables"
)

DATE = "19 июля 2026"
ISO = "2026-07-19"
RSS_DAY = "Sun, 19 Jul 2026"

CAT_COLORS = {
    "remont": ("#2C2C2C", "#C45C4A", "Ремонт"),
    "okna": ("#1F2A37", "#3B82A0", "Окна и двери"),
    "santehnika": ("#1E2F2C", "#2F8F7B", "Сантехника"),
    "elektrika": ("#2A2418", "#C9A227", "Электрика"),
    "interer": ("#2B2420", "#A67C52", "Интерьер"),
    "sovety": ("#222831", "#5B7C99", "Советы"),
}

AUTHORS = {
    "remont": "Редакция ДомЭксперт",
    "okna": "Редакция ДомЭксперт",
    "santehnika": "Иван Петров",
    "elektrika": "Алексей Смирнов",
    "interer": "Мария Соколова",
    "sovety": "Редакция ДомЭксперт",
}

# slug, cat, title, desc, readTime, short (для обложки)
ARTS = [
    dict(slug="poklejka-oboev-tehnologiya", cat="remont",
         title="Поклейка обоев своими руками: подготовка стен, стыки и углы без ошибок",
         desc="Расчёт рулонов, подготовка основания, разметка первой полосы, стыковка встык, углы и обои за батареей.",
         readTime="19 мин", short="Поклейка обоев"),
    dict(slug="keramogranit-na-pol-ukladka", cat="remont",
         title="Керамогранит на пол: выбор, раскладка и укладка на тёплый пол",
         desc="Классы PEI и R, форматы и ректификат, клей и гребёнка, швы, затирка и деформационные швы над тёплым полом.",
         readTime="20 мин", short="Керамогранит на пол"),
    dict(slug="plesen-v-kvartire-prichiny-i-borba", cat="remont",
         title="Плесень в квартире: причины, устранение и профилактика без рецидива",
         desc="Точка росы, мостики холода, проверка вентиляции и чем реально обрабатывать поражённые поверхности.",
         readTime="19 мин", short="Плесень в квартире"),

    dict(slug="regulirovka-plastikovykh-okon", cat="okna",
         title="Регулировка пластиковых окон: прижим, провисание створки и режим зима/лето",
         desc="Где дует и почему цепляет створка: три плоскости регулировки, эксцентрики, уплотнитель и смазка фурнитуры.",
         readTime="18 мин", short="Регулировка окон"),
    dict(slug="otkosy-okon-shtukaturka-gkl-sendvich", cat="okna",
         title="Откосы на окна: штукатурка, гипсокартон или сэндвич — сравнение и монтаж",
         desc="Три технологии, угол рассвета, утепление откоса, пароизоляция шва и почему на откосах появляется плесень.",
         readTime="19 мин", short="Откосы на окна"),
    dict(slug="razdvizhnye-dveri-kupe-penal", cat="okna",
         title="Раздвижные двери: купе, пенал и гармошка — что выбрать и как ставить",
         desc="Сравнение систем по месту, звуку и цене, вес полотна, пенал в стене и что заложить на черновом этапе.",
         readTime="18 мин", short="Раздвижные двери"),

    dict(slug="zamena-radiatorov-otopleniya", cat="santehnika",
         title="Замена радиаторов отопления в квартире: согласование, выбор и подключение",
         desc="Зона ответственности УК, давление в системе, расчёт секций, байпас и схемы подключения без потери мощности.",
         readTime="20 мин", short="Замена радиаторов"),
    dict(slug="zaliv-sosedey-chto-delat-akt", cat="santehnika",
         title="Залив квартиры: акт, оценка ущерба и компенсация — пошаговый порядок",
         desc="Что делать в первые минуты, как составить акт, кто виноват при аварии на стояке и как получить возмещение.",
         readTime="19 мин", short="Залив квартиры"),
    dict(slug="vodonagrevatel-boyler-vybor-montazh", cat="santehnika",
         title="Водонагреватель в квартире: накопительный или проточный, объём и монтаж",
         desc="Расчёт литража, сухой ТЭН и анод, группа безопасности, отдельная линия с УЗО и обвязка с байпасом.",
         readTime="19 мин", short="Водонагреватель"),

    dict(slug="raschet-secheniya-kabelya-tablicy", cat="elektrika",
         title="Расчёт сечения кабеля и мощности: таблицы для квартирной проводки",
         desc="Ток по мощности, таблицы сечение — автомат, потери напряжения, типовые группы квартиры и запас на будущее.",
         readTime="20 мин", short="Сечение кабеля"),
    dict(slug="montazh-kondicionera-elektrika-trassa", cat="elektrika",
         title="Монтаж кондиционера: трасса, дренаж и отдельная линия питания",
         desc="Что закладывать при черновых работах, вальцовка и вакуумирование, дренаж самотёком или помпа, линия 16 А.",
         readTime="19 мин", short="Монтаж кондиционера"),
    dict(slug="elektrika-v-vannoy-zony-ip", cat="elektrika",
         title="Электрика в ванной: зоны безопасности, IP, УЗО и уравнивание потенциалов",
         desc="Зоны 0–3 и что в них можно ставить, расшифровка IP, УЗО 10–30 мА, ДСУП и подключение техники в мокрой зоне.",
         readTime="19 мин", short="Электрика в ванной"),

    dict(slug="dizayn-prihozhey-planirovka-hranenie", cat="interer",
         title="Дизайн прихожей: планировка, хранение и отделка, стойкая к грязи",
         desc="Глубина шкафа, зона мокрой обуви, износостойкие материалы, свет с датчиком движения и узкий коридор.",
         readTime="18 мин", short="Дизайн прихожей"),
    dict(slug="dizayn-balkona-kak-komnaty", cat="interer",
         title="Балкон как комната: кабинет, лаунж или мини-сад — планировка и инженерия",
         desc="Что разрешено законом, нагрузка на плиту, порядок работ и сценарии под 2,5, 4 и 6 м² с электрикой и светом.",
         readTime="19 мин", short="Балкон как комната"),
    dict(slug="neoklassika-v-interere-kvartiry", cat="interer",
         title="Неоклассика в интерьере квартиры: молдинги, палитра и чувство меры",
         desc="Пропорции стеновых панелей, сложные оттенки, кессоны при низком потолке, латунь и типичный перебор с золотом.",
         readTime="18 мин", short="Неоклассика"),

    dict(slug="remont-vtorichki-chto-menyat", cat="sovety",
         title="Ремонт вторичного жилья: что менять обязательно, а что можно оставить",
         desc="Аудит по возрасту дома, что меняется всегда, скрытые проблемы старого фонда и почему смету считают после демонтажа.",
         readTime="19 мин", short="Ремонт вторички"),
    dict(slug="kak-profinansirovat-remont-kredit-rassrochka", cat="sovety",
         title="Чем платить за ремонт: накопления, кредит, рассрочка — сравнение переплаты",
         desc="Реальная стоимость каждого инструмента, поэтапное финансирование под график работ и налоговый вычет.",
         readTime="18 мин", short="Чем платить за ремонт"),
    dict(slug="zakon-o-tishine-remontnye-raboty", cat="sovety",
         title="Закон о тишине и ремонт: часы работ, штрафы и как договориться с соседями",
         desc="Типовые окна для шумных работ, региональные различия, фиксация нарушения и как снизить конфликт с соседями.",
         readTime="18 мин", short="Закон о тишине"),
]


# ─── картинки ────────────────────────────────────────────────────────

def font(size: int, bold: bool = False):
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold
        else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/System/Library/Fonts/Helvetica.ttc",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size)
            except Exception:
                continue
    return ImageFont.load_default()


def _shade(hex_color: str, delta: int) -> str:
    h = hex_color.lstrip("#")
    rgb = [max(0, min(255, int(h[i:i + 2], 16) + delta)) for i in (0, 2, 4)]
    return "#{:02x}{:02x}{:02x}".format(*rgb)


def make_cover(slug: str, cat: str, title: str) -> None:
    left, accent, cat_label = CAT_COLORS[cat]
    img = Image.new("RGB", (1200, 630), left)
    d = ImageDraw.Draw(img)
    d.polygon([(680, 0), (1200, 0), (1200, 630), (520, 630)], fill=accent)
    d.polygon([(780, 0), (1200, 0), (1200, 630), (900, 630)], fill=_shade(accent, -18))
    d.rounded_rectangle([48, 48, 84, 84], radius=6, fill=accent)
    d.text((100, 52), "ДомЭксперт", font=font(28, True), fill="#FFFFFF")
    pill_w = 28 + len(cat_label) * 14
    d.rounded_rectangle([48, 110, 48 + pill_w, 146], radius=14, fill=accent)
    d.text((64, 116), cat_label, font=font(18, True), fill="#FFFFFF")
    y = 220
    for line in textwrap.wrap(title, width=22)[:4]:
        d.text((48, y), line, font=font(48, True), fill="#FFFFFF")
        y += 58
    d.text((48, 560), "prodom-expert.ru", font=font(20), fill="#DDDDDD")
    img.save(COVERS / f"{slug}.png", "PNG", optimize=True)


def make_table_image(slug: str, cat: str, spec: dict) -> None:
    _, accent, _ = CAT_COLORS[cat]
    headers = spec["headers"]
    rows = spec["rows"]
    col_w = 300
    row_h = 46
    pad = 24
    w = pad * 2 + col_w * len(headers)
    h = pad * 2 + row_h * (1 + len(rows)) + 34
    img = Image.new("RGB", (w, h), "#FAFAF8")
    d = ImageDraw.Draw(img)
    d.rectangle([0, 0, w, row_h + pad], fill=accent)
    for i, head in enumerate(headers):
        d.text((pad + i * col_w + 8, pad + 10), head[:34], font=font(17, True), fill="#FFFFFF")
    for r, row in enumerate(rows):
        y = pad + row_h * (r + 1)
        if r % 2 == 0:
            d.rectangle([0, y, w, y + row_h], fill="#F0EEE8")
        for c, cell in enumerate(row):
            d.text((pad + c * col_w + 8, y + 13), str(cell)[:36], font=font(16), fill="#222222")
    caption = spec.get("caption", "")
    if caption:
        d.text((pad, h - 28), caption[:80], font=font(15), fill="#777777")
    img.save(INLINE / f"{slug}-table.png", "PNG", optimize=True)


# ─── регистрация в реестрах ──────────────────────────────────────────

def php_q(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def insert_after(path: Path, anchor: str, block: str) -> bool:
    """Вставляет block сразу после строки-якоря. Возвращает False, если якорь не найден."""
    text = path.read_text(encoding="utf-8")
    idx = text.find(anchor)
    if idx == -1:
        return False
    cut = text.index("\n", idx) + 1
    path.write_text(text[:cut] + block + text[cut:], encoding="utf-8")
    return True


def registered(path: Path, slug: str) -> bool:
    return f"'{slug}'" in path.read_text(encoding="utf-8")


def main() -> int:
    missing = [a for a in ARTS if not (ARTICLES / a["cat"] / f"{a['slug']}.html").is_file()]
    if missing:
        print("НЕТ ФАЙЛОВ СТАТЕЙ:", ", ".join(a["slug"] for a in missing))
        return 1

    meta_php = ROOT / "includes" / "all-articles-meta.php"
    article_php = ROOT / "article.php"
    rss_php = ROOT / "rss.php"
    search_php = ROOT / "search.php"
    sitemap = ROOT / "sitemap.xml"

    meta_lines, data_lines, rss_lines, search_lines, sitemap_lines = [], [], [], [], []

    for i, a in enumerate(ARTS):
        slug, cat = a["slug"], a["cat"]
        cat_label = CAT_COLORS[cat][2]

        make_cover(slug, cat, a["short"])
        spec_path = TABLES / f"{slug}.json"
        if spec_path.is_file():
            make_table_image(slug, cat, json.loads(spec_path.read_text(encoding="utf-8")))
        else:
            print("  ! нет данных таблицы для", slug)

        t, dsc = php_q(a["title"]), php_q(a["desc"])

        if not registered(meta_php, slug):
            meta_lines.append(
                f"  '{slug}' => ['cat' => '{cat}', 'title' => '{t}', 'desc' => '{dsc}', "
                f"'date' => '{DATE}', 'readTime' => '{a['readTime']}'],\n"
            )
        if not registered(article_php, slug):
            data_lines.append(
                f"  '{slug}' => [\n"
                f"    'title'    => '{t}',\n"
                f"    'catSlug'  => '{cat}',\n"
                f"    'catLabel' => '{cat_label}',\n"
                f"    'date'     => '{DATE}',\n"
                f"    'author'   => '{AUTHORS[cat]}',\n"
                f"    'readTime' => '{a['readTime']}',\n"
                f"    'desc'     => '{dsc}',\n"
                f"  ],\n"
            )
        if not registered(rss_php, slug):
            hh, mm = 9 + (i * 15) // 60, (i * 15) % 60
            rss_lines.append(
                f"  ['slug' => '{slug}', 'title' => '{t}', 'desc' => '{php_q(a['desc'][:88])}', "
                f"'date' => '{RSS_DAY} {hh:02d}:{mm:02d}:00 +0300', 'cat' => '{cat_label}'],\n"
            )
        if not registered(search_php, slug):
            search_lines.append(
                f"  ['slug' => '{slug}', 'cat' => '{cat}', 'catLabel' => '{cat_label}', "
                f"'title' => '{t}', 'desc' => '{dsc}'],\n"
            )
        if f"/article/{slug}/" not in sitemap.read_text(encoding="utf-8"):
            sitemap_lines.append(
                f"  <url><loc>https://prodom-expert.ru/article/{slug}/</loc>"
                f"<changefreq>monthly</changefreq><priority>0.8</priority>"
                f"<lastmod>{ISO}</lastmod></url>\n"
            )

    targets = [
        (meta_php, "  return [", meta_lines, "all-articles-meta.php"),
        (article_php, "$articlesData = [", data_lines, "article.php"),
        (rss_php, "$articles = [", rss_lines, "rss.php"),
        (search_php, "$allArticles = [", search_lines, "search.php"),
    ]
    for path, anchor, lines, label in targets:
        if not lines:
            print(f"  = {label}: всё уже зарегистрировано")
            continue
        if not insert_after(path, anchor, "".join(lines)):
            print(f"  ! ЯКОРЬ НЕ НАЙДЕН в {label}: {anchor!r}")
            return 1
        print(f"  + {label}: {len(lines)} записей")

    # sitemap: <urlset ...> многострочный, поэтому вставляем перед закрывающим тегом
    if sitemap_lines:
        xml = sitemap.read_text(encoding="utf-8")
        sitemap.write_text(
            xml.replace("</urlset>", "".join(sitemap_lines) + "</urlset>"), encoding="utf-8"
        )
        print(f"  + sitemap.xml: {len(sitemap_lines)} записей")
    else:
        print("  = sitemap.xml: всё уже зарегистрировано")

    print(f"\nГотово: {len(ARTS)} статей, дата {DATE}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
